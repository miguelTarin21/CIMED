let preguntas = [];
let preguntaActual = 0;
let respuestasSeleccionadas = [];
let mostrarExplicacion = false;
let bloqueado = false;

// ═══════════════════════════════════════════════════════
//  Signos vitales globales
// ═══════════════════════════════════════════════════════
let ecgVitals = { FC: 75, FR: 16, TA: '120/80', Temperatura: 37.0, SAT: 98, ritmo: 'sinusal' };

// ═══════════════════════════════════════════════════════
//  WaveformChannel — motor de canal genérico
// ═══════════════════════════════════════════════════════
class WaveformChannel {
    constructor(canvas, type, color) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.type = type;
        this.color = color;
        this.width = 0;
        this.height = canvas.height;
        this.data = null;
        this.writePos = 0;
        this.phase = Math.random(); // Desincronizar canales
        this.speed = 2;
        this._raf = null;
        this._eegVal = 0;
        this._fvT    = Math.random(); // Fase propia de FV (independiente del FC)
        this._fvAmpT = 0;             // Modulador coarse→fine (avanza muy lento)
    }

    resize(w) {
        this.width = w;
        this.canvas.width = w;
        this.data = new Float32Array(w).fill(this.height / 2);
        this.writePos = 0;
    }

    // ── Formas de onda ──────────────────────────────
    _ecgSample(t) {
        const rhythm = ecgVitals.ritmo || 'sinusal';
        
        if (rhythm === 'asistole') {
            // Asistole: Línea casi plana con un poco de ruido de fondo
            return 0.5 + (Math.random() - 0.5) * 0.015;
        }
        
        if (rhythm === 'fv') {
            // Fibrilación ventricular — oscilaciones basadas en tiempo real:
            //  • Usa Date.now() para garantizar variación visible en cada frame,
            //    completamente independiente de la FC, phase o cualquier acumulador.
            //  • Ciclo coarse→fine cosmético (~20s) modulado por seno lento.
            const nowSec = Date.now() / 1000;
            const env    = 0.70 + 0.30 * Math.abs(Math.sin(nowSec * 0.05 * Math.PI));
            return 0.5 + (Math.sin(nowSec *  7.3) * 0.30
                        + Math.sin(nowSec * 13.9) * 0.22
                        + Math.sin(nowSec * 21.5) * 0.14
                        + Math.sin(nowSec * 37.1) * 0.08
                        + (Math.random() - 0.5) * 0.12) * env;
        }

        const g = (c, a, s) => a * Math.exp(-Math.pow((t - c) / s, 2));

        if (rhythm === 'st_elevacion') {
            // Elevación ST: segmento ST elevado ampliamente + onda T alta y concordante
            // La línea de base no regresa a cero entre S y T → sube visible
            return g(0.18, 0.20, 0.042)   // Onda P
                + g(0.42, -0.08, 0.012)   // Q pequeña
                + g(0.47, 1.00, 0.013)    // R
                + g(0.52, -0.12, 0.011)   // S poco profunda (el ST no baja)
                + g(0.63, 0.55, 0.075)    // Elevación ST amplia y alta
                + g(0.78, 0.50, 0.055);   // Onda T alta y ancha (concordante)
        }

        if (rhythm === 'st_depresion') {
            // ST Depression (NSTEMI / isquemia subendocárdica):
            // QRS de morfología NORMAL — la depresión ocurre desde el punto J,
            // el segmento ST desciende horizontalmente y la onda T se invierte.
            // La onda S es igual al sinusal (S profunda es de BRI/HVI, no de isquemia).
            return g(0.18, 0.20, 0.042)   // Onda P normal
                + g(0.42, -0.10, 0.013)   // Q normal
                + g(0.47, 1.00, 0.013)    // R normal
                + g(0.52, -0.28, 0.013)   // S igual al sinusal (NO exagerada)
                + g(0.61, -0.28, 0.055)   // Depresión ST horizontal desde J-point
                + g(0.75, -0.18, 0.048);  // Onda T invertida (isquemia subendocárdica)
        }

        if (rhythm === 'taquicardia') {
            // Taquicardia Ventricular (TV) monomorfa:
            //  • Sin onda P visible (disociación AV)
            //  • QRS ancho y bizarro: rampa lenta de subida, pico tardío, descenso prolongado
            //  • Onda T discordante (polaridad opuesta al QRS) — sello clínico de TV
            return g(0.28, 0.30, 0.060)    // Inicio lento del QRS ancho
                + g(0.42, 0.95, 0.050)     // Pico del QRS (ancho, tardío)
                + g(0.56, -0.50, 0.060)    // Descenso ancho post-QRS
                + g(0.73, -0.30, 0.060);   // Onda T discordante (invertida)
        }

        // Ritmo Sinusal (normal, no taquicardia — FC controlada por slider)
        return g(0.20, 0.22, 0.045)
            + g(0.43, -0.10, 0.013)
            + g(0.48, 1.00, 0.013)
            + g(0.53, -0.28, 0.013)
            + g(0.70, 0.32, 0.055);
    }

    _spo2Sample(t) {
        const rhythm = ecgVitals.ritmo || 'sinusal';
        const fc = ecgVitals.FC || 0;
        if (rhythm === 'asistole' || rhythm === 'fv' || fc === 0) {
            return 0.0 + (Math.random() - 0.5) * 0.01;
        }
        const main = Math.pow(Math.sin(Math.PI * t), 2) * 0.85;
        const notch = (t > 0.56 && t < 0.66)
            ? -0.09 * Math.sin(Math.PI * (t - 0.56) / 0.10) : 0;
        return main + notch;
    }

    _bpSample(t) {
        const rhythm = ecgVitals.ritmo || 'sinusal';
        const fc = ecgVitals.FC || 0;
        if (rhythm === 'asistole' || rhythm === 'fv' || fc === 0) {
            return 0.04 + (Math.random() - 0.5) * 0.01;
        }
        if (t < 0.12) return t / 0.12;
        if (t < 0.20) return 1 - (t - 0.12) / 0.08 * 0.22;
        if (t < 0.26) return 0.78 + Math.sin(Math.PI * (t - 0.20) / 0.06) * 0.13;
        return Math.max(0.04, 0.91 - (t - 0.26) / 0.74 * 0.87);
    }

    _eegSample() {
        this._eegVal += (Math.random() - 0.5) * 0.28;
        this._eegVal *= 0.91;
        return this._eegVal * 0.5 + 0.5;
    }

    _co2Sample(t) {
        if (t < 0.07) return 0;
        if (t < 0.17) return (t - 0.07) / 0.10;
        if (t < 0.58) return 1.0 - (t - 0.17) / 0.41 * 0.07; // plateau suave
        if (t < 0.66) return Math.max(0, 0.93 - (t - 0.58) / 0.08 * 0.93);
        return 0;
    }

    sample(t) {
        switch (this.type) {
            case 'ecg': return this._ecgSample(t);
            case 'spo2': return this._spo2Sample(t);
            case 'bp': return this._bpSample(t);
            case 'eeg': return this._eegSample();
            case 'co2': return this._co2Sample(t);
            default: return 0.5;
        }
    }

    pixelsPerCycle() {
        const fc = Math.max(ecgVitals.FC, 1);
        const fr = Math.max(ecgVitals.FR, 1);
        // ~120 px/s a 60fps con speed=2
        switch (this.type) {
            case 'ecg':
            case 'spo2':
            case 'bp': return (120 * 60) / fc;
            case 'eeg': return 38;
            case 'co2': return (120 * 60) / fr;
        }
    }

    update() {
        this.phase += this.speed / this.pixelsPerCycle();
        if (this.phase >= 1) this.phase -= 1;

        // FV: avanzar fase propia a velocidad fija (independiente del FC)
        this._fvT += 2 / 85;
        if (this._fvT >= 1) this._fvT -= 1;

        // FV coarse→fine: ciclo lento de ~18s (60fps × 18s = 1080 frames → paso ~0.00093)
        // Va de amplitud máxima (coarse) a mínima (fine) y vuelve a subir, como en paro real
        this._fvAmpT += 0.00093;
        if (this._fvAmpT >= 1) this._fvAmpT -= 1;

        const val = this.sample(this.phase);
        const cx  = this.height / 2;
        const amp = this.height * 0.36;
        const isFV = (this.type === 'ecg' && (ecgVitals.ritmo || 'sinusal') === 'fv');

        if (this.type === 'eeg') {
            this.data[this.writePos] = cx - (val - 0.5) * amp * 1.6;
        } else if (this.type === 'co2') {
            // Capno: crece hacia arriba (invertido)
            this.data[this.writePos] = (this.height - 8) - val * (this.height - 16);
        } else if (isFV) {
            // FV: amplitud extra para que sea visualmente inconfundible
            this.data[this.writePos] = cx - (val - 0.5) * amp * 2.4;
        } else {
            this.data[this.writePos] = (cx + amp * 0.28) - val * amp;
        }
        this.writePos = (this.writePos + 1) % this.width;
    }

    draw() {
        const ctx = this.ctx;

        // Fondo
        ctx.fillStyle = '#050a06';
        ctx.fillRect(0, 0, this.width, this.height);

        // Rejilla
        ctx.strokeStyle = 'rgba(255,255,255,0.04)';
        ctx.lineWidth = 0.5;
        for (let x = 0; x < this.width; x += 40) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, this.height); ctx.stroke();
        }
        for (let y = 0; y < this.height; y += this.height / 4) {
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(this.width, y); ctx.stroke();
        }
        // Línea central
        ctx.strokeStyle = 'rgba(255,255,255,0.07)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(0, this.height / 2); ctx.lineTo(this.width, this.height / 2);
        ctx.stroke();

        // Waveform
        ctx.strokeStyle = this.color;
        ctx.lineWidth = 1.8;
        ctx.shadowBlur = 10;
        ctx.shadowColor = this.color;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.beginPath();
        for (let i = 0; i < this.width; i++) {
            const idx = (this.writePos + i) % this.width;
            if (i === 0) ctx.moveTo(i, this.data[idx]);
            else ctx.lineTo(i, this.data[idx]);
        }
        ctx.stroke();
        ctx.shadowBlur = 0;

        // Barrido (sweep)
        const xW = (this.writePos === 0 ? this.width - 1 : this.writePos - 1);
        const grad = ctx.createLinearGradient(xW - 24, 0, xW + 4, 0);
        grad.addColorStop(0, 'rgba(5,10,6,0)');
        grad.addColorStop(1, 'rgba(5,10,6,0.97)');
        ctx.fillStyle = grad;
        ctx.fillRect(xW - 24, 0, 28, this.height);
    }

    tick() {
        this.update();
        this.draw();
        this._raf = requestAnimationFrame(() => this.tick());
    }

    start() { this._raf = requestAnimationFrame(() => this.tick()); }
    stop() { if (this._raf) cancelAnimationFrame(this._raf); }
}

// ═══════════════════════════════════════════════════════
//  Clasificación de signos vitales
// ═══════════════════════════════════════════════════════
function getVitalClass(type, value) {
    if (type === 'fc') {
        if (value < 40 || value > 150) return 'critical';
        if (value < 60 || value > 100) return 'warning';
        return 'normal';
    }
    if (type === 'fr') {
        if (value < 8 || value > 30) return 'critical';
        if (value < 12 || value > 20) return 'warning';
        return 'normal';
    }
    if (type === 'sat') {
        if (value < 90) return 'critical';
        if (value < 95) return 'warning';
        return 'normal';
    }
    if (type === 'temp') {
        if (value < 35 || value > 39) return 'critical';
        if (value < 36.1 || value > 37.2) return 'warning';
        return 'normal';
    }
    if (type === 'ta') {
        if (value >= 180) return 'critical';
        if (value >= 130) return 'warning';
        return 'normal';
    }
    return 'normal';
}

function setVitalCard(cardId, displayId, value, displayText, type) {
    const card = document.getElementById(cardId);
    const disp = document.getElementById(displayId);
    if (!card || !disp) return;
    const cls = getVitalClass(type, value);
    // Preserva clases base, solo cambia estado
    const base = card.dataset.baseClass || card.className.split(' ')[0];
    card.dataset.baseClass = base;
    card.className = base + (cls !== 'normal' ? ' mv-' + cls : '');
    disp.textContent = displayText;
}

// ═══════════════════════════════════════════════════════
//  Actualizar UI del monitor con signos vitales
// ═══════════════════════════════════════════════════════
function updateVitalsUI(signos) {
    if (!signos) return;
    const fc = parseInt(signos.FC) || 0;
    const fr = parseInt(signos.FR) || 0;
    const sat = parseInt(signos.SAT) || 0;
    const temp = parseFloat(signos.Temperatura) || 0;
    const ta = signos.TA || '--/--';

    // Derivados simulados
    const bis = temp > 0 ? Math.min(99, Math.max(40, Math.round(91 + (37.0 - temp) * 6))) : '--';
    const etco2 = fr > 0 ? Math.min(55, Math.max(20, Math.round(38 + (16 - fr) * 0.6))) : '--';

    const ritmo = signos.ritmo || 'sinusal';
    const ritmoAnterior = ecgVitals.ritmo;

    // Actualizar objeto global para los canales
    ecgVitals = { FC: fc, FR: fr, TA: ta, Temperatura: temp, SAT: sat, ritmo: ritmo };

    // Textos estáticos del caso
    const _set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    _set('fr-paciente', fr);
    _set('fc-paciente', fc);
    _set('ta-paciente', ta);
    _set('temp-paciente', temp ? temp.toFixed(1) : '--');
    _set('sat-paciente', sat);

    // Tarjetas del monitor
    setVitalCard('card-fc', 'vi-fc', fc, fc, 'fc');
    setVitalCard('card-sat', 'vi-sat', sat, sat + '%', 'sat');
    setVitalCard('card-temp', 'vi-bis', temp, bis, 'temp');
    const taSis = parseInt(ta.split('/')[0]) || 0;
    setVitalCard('card-ta', 'vi-ta', taSis, ta, 'ta');
    // FR va dentro del bloque CO2
    const frDisp = document.getElementById('vi-fr');
    if (frDisp) {
        frDisp.textContent = fr;
        const frCls = getVitalClass('fr', fr);
        const frCard = document.getElementById('card-fr');
        if (frCard) frCard.className = 'mv-block mv-block-co2' + (frCls !== 'normal' ? ' mv-' + frCls : '');
    }
    _set('vi-etco2', etco2);
    _set('vi-temp', temp ? temp.toFixed(1) : '--');

    // Barra de alertas
    const alertEl = document.getElementById('monitor-alert');
    if (alertEl) {
        const alerts = [];
        if (fc > 100) alerts.push('FC ALTA');
        if (fc < 60 && fc > 0) alerts.push('FC BAJA');
        if (sat < 95 && sat > 0) alerts.push('SpO2 BAJA');
        if (taSis >= 130) alerts.push('TA ALTA');
        if (temp > 37.5) alerts.push('TEMP ALTA');
        if (temp < 36.0 && temp > 0) alerts.push('HIPOTERMIA');
        if (fr > 20) alerts.push('FR ALTA');
        if (fr < 12 && fr > 0) alerts.push('FR BAJA');
        if (alerts.length) {
            alertEl.innerHTML = '⚠ ALERTA: ' + alerts.join(' &nbsp;|&nbsp; ');
            alertEl.style.display = 'block';
        } else {
            alertEl.style.display = 'none';
        }
    }

    // Status bar
    const overallCls = (fc < 40 || fc > 150 || sat < 90) ? 'critical'
        : (fc < 60 || fc > 100 || sat < 95) ? 'warning' : '';
    const bpmEl = document.getElementById('ecg-bpm-display');
    const ledEl = document.getElementById('ecg-led');
    const statEl = document.getElementById('ecg-status-text');
    if (bpmEl) bpmEl.textContent = fc ? `${fc} BPM` : '-- BPM';
    if (ledEl) ledEl.className = 'ecg-led' + (overallCls ? ' ' + overallCls : '');
    if (statEl) statEl.textContent = overallCls === 'critical' ? '⚠ CRÍTICO'
        : overallCls === 'warning' ? '⚠ Advertencia'
            : fc ? 'NORMAL' : 'Cargando…';
    const updEl = document.getElementById('ecg-last-update');
    if (updEl) updEl.textContent = 'Última act: ' +
        new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ═══════════════════════════════════════════════════════
//  Polling: refrescar signos vitales
// ═══════════════════════════════════════════════════════
function pollVitals() {
    fetch(`./BE/get-case-info.php?id=${caseId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.signos_paciente) updateVitalsUI(data.signos_paciente);
        })
        .catch(() => { });
}

// ═══════════════════════════════════════════════════════
//  Tabs
// ═══════════════════════════════════════════════════════
function initTabs() {
    const tabs = document.querySelectorAll('.sim-tab');
    const panels = document.querySelectorAll('.sim-panel');
    let ecgStarted = false;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            tab.classList.add('active');
            const panelId = 'panel-' + tab.dataset.tab;
            const panel = document.getElementById(panelId);
            if (panel) panel.classList.add('active');

            // Redimensionar + arrancar canales ECG al activar la peñstaña electro
            if (tab.dataset.tab === 'electro') {
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {          // 2 frames: panel ya es visible
                        (window._channels || []).forEach(ch => {
                            const rect = ch.canvas.parentElement.getBoundingClientRect();
                            const w = Math.max(rect.width - 2, 50);
                            ch.resize(w);
                            if (!ecgStarted) ch.start();   // start() sólo la 1ª vez
                        });
                        ecgStarted = true;
                    });
                });
            }
        });
    });
}

// ═══════════════════════════════════════════════════════
//  Inicialización
// ═══════════════════════════════════════════════════════
document.addEventListener("DOMContentLoaded", function () {

    initTabs();

    fetch(`./BE/get-case-info.php?id=${caseId}`)
        .then(r => r.json())
        .then(data => {
            const _set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

            _set('nombre-paciente', data.paciente.nombre);
            _set('nombre-caso', data.caso.nombre);
            _set('descripcion-caso', data.caso.descripcion);
            _set('sexo-paciente', data.paciente.sexo);
            _set('edad-paciente', data.paciente.edad);
            _set('peso-paciente', data.paciente.peso);
            _set('altura-paciente', data.paciente.altura);
            _set('motivo-consulta', data.caso.motivoConsulta);

            const enf = document.getElementById('enfermedades-paciente');
            if (enf) enf.textContent = data.paciente.enfermedades;

            const fechaContainer = document.getElementById('fecha-actualizacion-container');
            const fechaElement = document.getElementById('fecha-actualizacion');
            if (data.caso.fecha_actualizacion) {
                if (fechaElement) fechaElement.textContent = data.caso.fecha_actualizacion;
                if (fechaContainer) fechaContainer.style.display = 'flex';
            } else {
                if (fechaContainer) fechaContainer.style.display = 'none';
            }

            // ══ Videos → lista + preview inline ══
            const videosList = document.getElementById('videos-list');
            const videos = data.videos || [];
            if (videosList) {
                if (videos.length === 0) {
                    videosList.innerHTML = '<p class="text-muted">No hay videos disponibles para este caso.</p>';
                } else {
                    // Lista de nombres
                    const listEl = document.createElement('ul');
                    listEl.className = 'sim-item-list';
                    videos.forEach((v, i) => {
                        const li = document.createElement('li');
                        li.className = 'sim-item-list-row';
                        li.innerHTML = `<i class="bi bi-play-circle-fill me-2" style="color:#e44;"></i>
                                        <span>${v.nombre || 'Video ' + (i + 1)}</span>`;
                        li.addEventListener('click', () => {
                            const isActive = li.classList.contains('active');
                            listEl.querySelectorAll('.sim-item-list-row').forEach(r => r.classList.remove('active'));
                            if (isActive) {
                                // Segundo clic → ocultar
                                previewEl.innerHTML = '';
                                previewEl.style.display = 'none';
                            } else {
                                li.classList.add('active');
                                previewEl.innerHTML = `
                                    <video class="sim-preview-video" controls autoplay>
                                        <source src="${v.ruta}" type="video/mp4">
                                        Tu navegador no soporta video HTML5.
                                    </video>`;
                                previewEl.style.display = 'block';
                            }
                        });
                        listEl.appendChild(li);
                    });

                    // Área de preview
                    const previewEl = document.createElement('div');
                    previewEl.className = 'sim-preview-area';
                    previewEl.style.display = 'none';

                    videosList.innerHTML = '';
                    videosList.appendChild(listEl);
                    videosList.appendChild(previewEl);
                }
            }

            if (data.signos_paciente) updateVitalsUI(data.signos_paciente);

            // ══ Estudios → lista + preview inline ══
            const estudiosContainer = document.getElementById('estudios-container');
            if (estudiosContainer) {
                estudiosContainer.innerHTML = '';       // limpiar grid anterior
                estudiosContainer.className = '';       // quitar clases row de Bootstrap

                if (!data.archivos || data.archivos.length === 0) {
                    estudiosContainer.innerHTML = '<p class="text-muted">No hay estudios disponibles.</p>';
                } else {
                    const listEl = document.createElement('ul');
                    listEl.className = 'sim-item-list';

                    const previewEl = document.createElement('div');
                    previewEl.className = 'sim-preview-area';
                    previewEl.style.display = 'none';

                    data.archivos.forEach((archivo, index) => {
                        const li = document.createElement('li');
                        li.className = 'sim-item-list-row';
                        li.innerHTML = `<i class="bi bi-file-earmark-text me-2" style="color:#2f50ff;"></i>
                                        <span>${archivo.nombre || 'Archivo ' + (index + 1)}</span>`;
                        li.addEventListener('click', () => {
                            const isActive = li.classList.contains('active');
                            listEl.querySelectorAll('.sim-item-list-row').forEach(r => r.classList.remove('active'));
                            if (isActive) {
                                // Segundo clic → ocultar
                                previewEl.innerHTML = '';
                                previewEl.style.display = 'none';
                            } else {
                                li.classList.add('active');
                                previewEl.innerHTML = `<iframe src="${archivo.ruta}" class="sim-preview-iframe"></iframe>`;
                                previewEl.style.display = 'block';
                            }
                        });
                        listEl.appendChild(li);
                    });

                    estudiosContainer.appendChild(listEl);
                    estudiosContainer.appendChild(previewEl);
                }
            }

            preguntas = data.preguntas;
            respuestasSeleccionadas = Array(preguntas.length).fill(null);

            // ── Canales del monitor: crear pero NO arrancar aún (panel oculto) ──
            const channelDefs = [
                { id: 'canvas-ecg', type: 'ecg', color: '#00ff9f' },
                { id: 'canvas-spo2', type: 'spo2', color: '#4db8ff' },
                { id: 'canvas-bp', type: 'bp', color: '#ff6060' },
                { id: 'canvas-eeg', type: 'eeg', color: '#00e5cc' },
                { id: 'canvas-co2', type: 'co2', color: '#ffee00' },
            ];
            window._channels = [];
            channelDefs.forEach(cfg => {
                const canvas = document.getElementById(cfg.id);
                if (!canvas) return;
                const ch = new WaveformChannel(canvas, cfg.type, cfg.color);
                // No resize ni start todavía; se harán cuando el usuario abra la pestaña Electro
                window._channels.push(ch);
            });
        })
        .catch(err => console.error('Error al obtener datos:', err));

    setInterval(pollVitals, 3000);

    window.addEventListener('resize', () => {
        const electroPanel = document.getElementById('panel-electro');
        if (!electroPanel || !electroPanel.classList.contains('active')) return;
        (window._channels || []).forEach(ch => {
            const rect = ch.canvas.parentElement.getBoundingClientRect();
            const w = Math.max(rect.width - 2, 50);
            ch.resize(w);
        });
    });
});

// ═══════════════════════════════════════════════════════
//  Quiz / Preguntas
// ═══════════════════════════════════════════════════════
function iniciarPreguntas() {
    document.getElementById('seccionBienvenida').style.display = 'none';
    document.getElementById('seccionPrincipal').style.display = 'block';
    mostrarPregunta();
}

function mostrarPregunta() {
    if (preguntaActual >= preguntas.length) { mostrarFinalizacion(); return; }
    const pregunta = preguntas[preguntaActual];
    document.getElementById('preguntaTexto').textContent = pregunta.texto_pregunta;
    document.getElementById('opcionesContainer').innerHTML = pregunta.incisos.map(op => {
        const sel = respuestasSeleccionadas[preguntaActual] === op.texto ? 'selected' : '';
        const disabled = bloqueado ? 'disabled' : '';
        return `<button class="btn btn-outline-secondary w-100 mb-2 ${sel} ${disabled}"
                    onclick="seleccionarRespuesta('${op.texto}')" ${disabled}>
                    ${op.texto}
                </button>`;
    }).join('');

    const expEl = document.getElementById('explicacionIncorrecta');
    if (mostrarExplicacion) {
        expEl.textContent = pregunta.feedback;
    } else {
        expEl.textContent = '';
    }
}

function seleccionarRespuesta(opcionSeleccionada) {
    if (!bloqueado) {
        respuestasSeleccionadas[preguntaActual] = opcionSeleccionada;
        mostrarExplicacion = false;
        mostrarPregunta();
    }
}

function cambiarPregunta() {
    const pregunta = preguntas[preguntaActual];
    let respuesta = respuestasSeleccionadas[preguntaActual];
    if (!respuesta) {
        const incorrecta = pregunta.incisos.find(o => o.correcto === 0);
        respuesta = incorrecta ? incorrecta.texto : 'Respuesta no válida';
        respuestasSeleccionadas[preguntaActual] = respuesta;
    }
    const opcion = pregunta.incisos.find(o => o.texto === respuesta);
    if (!mostrarExplicacion && opcion && opcion.correcto === 0) {
        mostrarExplicacion = true;
        bloqueado = true;
        mostrarPregunta();
    } else {
        preguntaActual++;
        mostrarExplicacion = false;
        bloqueado = false;
        mostrarPregunta();
    }
}

// ═══════════════════════════════════════════════════════
//  Beep del monitor — sincronizado con FC + alertas
// ═══════════════════════════════════════════════════════
(function initBeep() {
    let audioCtx = null;
    let beepInterval = null;
    let muted = false;
    let lastStatus = 'normal';
    let flatlineOsc = null;
    let flatlineGain = null;

    function getAudioCtx() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        return audioCtx;
    }

    // status: 'normal' | 'warning' | 'critical'
    function getStatus() {
        const fc = ecgVitals.FC || 0;
        const sat = ecgVitals.SAT || 0;
        const ta = parseInt((ecgVitals.TA || '0').split('/')[0]) || 0;
        const fr = ecgVitals.FR || 0;

        if (fc < 40 || fc > 150 || sat < 90 || ta >= 180 || fr < 8 || fr > 30)
            return 'critical';
        if (fc < 60 || fc > 100 || sat < 95 || ta >= 130 || fr < 12 || fr > 20)
            return 'warning';
        return 'normal';
    }

    // Frecuencia variable del pitido según saturación de oxígeno (SpO₂ acústico)
    function getPitchFreq() {
        const sat = ecgVitals.SAT || 98;
        // Mapea SAT (70 a 100) a un pitch audible entre 400Hz y 850Hz
        return Math.max(380, Math.min(900, 400 + (sat - 70) * 15));
    }

    // Parámetros de sonido según estado
    function getBeepConfig(status) {
        const baseFreq = getPitchFreq();
        switch (status) {
            case 'critical':
                return {
                    freq1: baseFreq * 1.25,   // tono principal (más agudo)
                    freq2: baseFreq * 0.75,   // tono secundario (alarma dual)
                    volume: 0.28,
                    duration: 0.12,
                    dual: true    // doble pitido
                };
            case 'warning':
                return {
                    freq1: baseFreq * 1.15,
                    freq2: null,
                    volume: 0.20,
                    duration: 0.10,
                    dual: false
                };
            default: // normal
                return {
                    freq1: baseFreq,
                    freq2: null,
                    volume: 0.12,
                    duration: 0.08,
                    dual: false
                };
        }
    }

    function startFlatline() {
        if (muted) return;
        if (flatlineOsc) return;
        try {
            const ctx = getAudioCtx();
            if (ctx.state === 'suspended') ctx.resume();
            flatlineOsc = ctx.createOscillator();
            flatlineGain = ctx.createGain();
            flatlineOsc.connect(flatlineGain);
            flatlineGain.connect(ctx.destination);
            flatlineOsc.type = 'sine';
            flatlineOsc.frequency.setValueAtTime(450, ctx.currentTime); // 450Hz es la estándar de flatline
            flatlineGain.gain.setValueAtTime(0.001, ctx.currentTime);
            flatlineGain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.05);
            flatlineOsc.start();
        } catch (e) {}
    }

    function stopFlatline() {
        if (flatlineOsc) {
            try {
                const ctx = getAudioCtx();
                flatlineGain.gain.setValueAtTime(flatlineGain.gain.value, ctx.currentTime);
                flatlineGain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
                const osc = flatlineOsc;
                setTimeout(() => { try { osc.stop(); } catch(e) {} }, 60);
            } catch(e) {}
            flatlineOsc = null;
            flatlineGain = null;
        }
    }

    function playBeep() {
        const rhythm = ecgVitals.ritmo || 'sinusal';
        const fc = ecgVitals.FC || 0;
        if (rhythm === 'asistole' || rhythm === 'fv' || fc === 0) {
            stopBeeps();
            startFlatline();
            return;
        }

        stopFlatline();

        if (muted) return;
        try {
            const ctx = getAudioCtx();
            if (ctx.state === 'suspended') ctx.resume();
            const status = getStatus();
            const cfg = getBeepConfig(status);

            function tone(freq, delayMs) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                const t0 = ctx.currentTime + delayMs / 1000;
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, t0);

                gain.gain.setValueAtTime(0.001, t0);
                gain.gain.exponentialRampToValueAtTime(cfg.volume, t0 + 0.005);
                gain.gain.exponentialRampToValueAtTime(0.001, t0 + cfg.duration);

                osc.start(t0);
                osc.stop(t0 + cfg.duration + 0.01);
            }

            tone(cfg.freq1, 0);
            if (cfg.dual) tone(cfg.freq2, 140); // segundo tono 140ms después

        } catch (e) { }
    }

    function getMsPerBeat() {
        const fc = Math.max(ecgVitals.FC || 0, 1);
        const status = getStatus();
        if (status === 'critical') return Math.min(Math.round(60000 / fc), 400);
        return Math.round(60000 / fc);
    }

    function stopBeeps() {
        if (beepInterval) {
            clearInterval(beepInterval);
            beepInterval = null;
        }
    }

    function scheduleBeeps() {
        const rhythm = ecgVitals.ritmo || 'sinusal';
        const fc = ecgVitals.FC || 0;
        if (rhythm === 'asistole' || rhythm === 'fv' || fc === 0) {
            stopBeeps();
            startFlatline();
            return;
        }

        stopFlatline();
        if (beepInterval) clearInterval(beepInterval);
        beepInterval = setInterval(playBeep, getMsPerBeat());
    }

    // Monitorear cambios cada segundo
    setInterval(() => {
        const rhythm = ecgVitals.ritmo || 'sinusal';
        const fc = ecgVitals.FC || 0;
        
        if (rhythm === 'asistole' || rhythm === 'fv' || fc === 0) {
            stopBeeps();
            if (!muted) {
                startFlatline();
            } else {
                stopFlatline();
            }
        } else {
            stopFlatline();
            const status = getStatus();
            if (status !== lastStatus) {
                lastStatus = status;
                scheduleBeeps();
                flashStatusChange(status);
            } else if (!beepInterval) {
                scheduleBeeps();
            }
        }
    }, 1000);

    // Efecto visual al cambiar de estado
    function flashStatusChange(status) {
        const monitor = document.querySelector('.monitor-pro');
        if (!monitor) return;
        const colors = {
            critical: '0 0 30px rgba(255,50,50,0.6)',
            warning: '0 0 20px rgba(255,200,0,0.4)',
            normal: '0 0 40px rgba(0,255,159,0.08)'
        };
        monitor.style.transition = 'box-shadow .3s';
        monitor.style.boxShadow = colors[status];
        // Flash rápido en crítico
        if (status === 'critical') {
            let count = 0;
            const flash = setInterval(() => {
                monitor.style.boxShadow = count % 2 === 0
                    ? '0 0 50px rgba(255,0,0,0.7)'
                    : colors.critical;
                if (++count >= 6) clearInterval(flash);
            }, 150);
        }
    }

    // Botón mute en el statusbar
    document.addEventListener('DOMContentLoaded', () => {
        const statusbar = document.querySelector('.mon-statusbar');
        if (statusbar) {
            const btn = document.createElement('button');
            btn.id = 'btn-mute';
            btn.innerHTML = '🔊';
            btn.title = 'Silenciar/Activar beep';
            btn.style.cssText = `
                background:none;border:1px solid #333;color:#aaa;
                font-size:.75rem;padding:2px 10px;border-radius:4px;
                cursor:pointer;margin-left:10px;font-family:monospace;
                transition: color .2s, border-color .2s;
            `;
            btn.onclick = () => {
                muted = !muted;
                btn.innerHTML = muted ? '🔇' : '🔊';
                btn.style.color = muted ? '#444' : '#aaa';
                btn.style.borderColor = muted ? '#222' : '#333';
                if (!muted && audioCtx?.state === 'suspended') audioCtx.resume();

                const rhythm = ecgVitals.ritmo || 'sinusal';
                const fc = ecgVitals.FC || 0;
                if (rhythm === 'asistole' || rhythm === 'fv' || fc === 0) {
                    if (muted) stopFlatline();
                    else startFlatline();
                }
            };
            statusbar.appendChild(btn);
        }

        // Arrancar al primer clic (política autoplay del navegador)
        document.addEventListener('click', function startAudio() {
            scheduleBeeps();
            document.removeEventListener('click', startAudio);
        }, { once: true });
    });
})();

function mostrarFinalizacion() {
    document.body.innerHTML = `
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;
                    height:100vh;text-align:center;background:#050a06;">
            <div style="font-size:3rem;color:#00ff9f;margin-bottom:16px;">✓</div>
            <h2 style="color:#fff;font-family:monospace">Simulación terminada</h2>
            <button onclick="window.location.href='iniciar-simulacion.php'"
                style="margin-top:24px;background:#00ff9f;color:#000;border:none;
                       padding:12px 28px;border-radius:6px;font-size:1rem;
                       font-weight:700;cursor:pointer;letter-spacing:1px;">
                VOLVER
            </button>
        </div>`;
}