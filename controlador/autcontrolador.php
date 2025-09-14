<?php
session_start();
require_once '../conexionBD/db.php';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && $password === $usuario['password']) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            
            switch($usuario['rol']) {
                case 'admin':
                    header("Location: ../vista/admin/dashboard.php");
                    break;
                case 'ventas':
                    header("Location: ../vista/ventas/dashboard.php");
                    break;
                case 'bodega':
                    header("Location: ../vista/bodega/dashboard.php");
                    break;
            }
            exit;
        } else {
            $_SESSION['error'] = "Credenciales incorrectas";
            header("Location: ../vista/login.php");
            exit;
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error en el sistema";
        header("Location: ../vista/login.php");
        exit;
    }
}
?>