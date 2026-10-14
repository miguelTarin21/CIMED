<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>CIMED</title>
	<link rel="icon" type="image/png" href="images/CimarronMedicoIndexIcon.png">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet"/>
	<link href="css/index-style.css" rel="stylesheet"/>
</head>

<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<div class="container-fluid">
			<a class="navbar-brand" href="./">
				<span class="brand-name">
					<span class="brand-ci">CI</span><span class="brand-med">MED</span>
				</span>
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
				aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse justify-content-center" id="navbarNav">
				<ul class="navbar-nav">
					<li class="nav-item">
						<a class="nav-link" href="./">Inicio</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="sobre.html">Sobre el sistema</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="creditos.html">Créditos</a>
					</li>
				</ul>
			</div>
			<a href="#" class="btn btn-primary btn-admin d-flex align-items-center" onclick="verifySession()">
				<i class="bi bi-person-circle me-2"></i> Administrador
			</a>
		</div>
	</nav>

	<!-- Sección de encabezado principal -->
	<div class="container main-container">
		<div class="row align-items-center">
			<div class="col-md-7">
				<h1 class="main-heading">Simulador Médico</h1>
				<p class="main-text">
					Este simulador médico fue desarrollado por la Facultad de Ciencias de la Ingeniería y Tecnología,
					para el área de Medicina, con el propósito de mejorar las capacidades de los
					estudiantes
					en la evaluación de casos clínicos.
				</p>
				<a class="btn btn-primary btn-simulacion">
					<i class="bi bi-play-circle-fill me-2"></i> Iniciar Simulación
				</a>
			</div>
			<div class="col-md-5">
				<img src="images/CimarronMedicoIndexLogo.png" alt="Simulador Médico" class="custom-image">
			</div>
		</div>
	</div>

	<!-- Footer -->
	<footer class="footer py-3 bg-light">
		<div class="container text-center">
			<span class="text-muted">Cuarta Generación de Ingeniería en Software © 2024</span>
		</div>
	</footer>

	<!-- Scripts -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
	<script src="js/login.js"></script>
	<script src="js/input-animation.js"></script>

</body>

</html>