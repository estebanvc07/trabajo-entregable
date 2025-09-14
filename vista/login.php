<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: panel.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo time(); ?>">
     <link rel="stylesheet" href="../../assets/css/estilos.css">

</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="../assets/img/logo_ibferreteria.png" alt="logo" class="mb-3" style="width: 250px; height: auto;">

                            <!--<i class="bi bi-tools fs-1 text-primary"></i>
                            <h3>IB Ferreteria</h3>-->
                            <p class="text-muted">Ingrese sus credenciales</p>
                        </div>

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php 
                                    echo htmlspecialchars($_SESSION['error']);
                                    unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="../controlador/autcontrolador.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-envelope me-2"></i>Email
                                </label>
                                <input type="email" name="email" class="form-control" 
                                       required placeholder="Ingrese su email">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-lock me-2"></i>Contraseña
                                </label>
                                <input type="password" name="password" class="form-control" 
                                       required placeholder="Ingrese su contraseña">
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Iniciar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
