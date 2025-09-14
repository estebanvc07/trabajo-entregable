<?php

session_start();
require_once '../conexionBD/db.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../vista/login.php");
    exit;
}

// Crear nuevo usuario
if (isset($_POST['crear'])) {
    // Validar que se proporcionó un ID
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        $_SESSION['error'] = "Debe proporcionar un ID de usuario";
        header("Location: ../vista/admin/usuarios.php");
        exit;
    }

    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        $_SESSION['error'] = "El ID debe ser un número entero positivo";
        header("Location: ../vista/admin/usuarios.php");
        exit;
    }

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $rol = $_POST['rol'];

    try {
        $pdo->beginTransaction();

        // Verificar si el ID ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->fetch()) {
            throw new PDOException("El ID {$id} ya está en uso");
        }

        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new PDOException("El email ya está registrado");
        }

        // Insertar nuevo usuario con ID específico
        $stmt = $pdo->prepare("INSERT INTO usuarios (id, nombre, email, password, rol, fecha_registro) VALUES (?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$id, $nombre, $email, $password, $rol]);

        $pdo->commit();
        $_SESSION['success'] = "Usuario creado correctamente con ID: {$id}";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../vista/admin/usuarios.php");
    exit;
}

// Eliminar usuario
if (isset($_POST['eliminar'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    
    if (!$id) {
        $_SESSION['error'] = "ID de usuario inválido";
        header("Location: ../vista/admin/usuarios.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Verificar que no sea el último administrador
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            throw new PDOException("Usuario no encontrado");
        }

        if ($usuario['rol'] === 'admin') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
            if ($stmt->fetchColumn() <= 1) {
                throw new PDOException("No se puede eliminar el último administrador");
            }
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Usuario eliminado correctamente";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../vista/admin/usuarios.php");
    exit;
}

// Actualizar usuario
if (isset($_POST['actualizar'])) {
    $id_actual = filter_var($_POST['id_actual'], FILTER_VALIDATE_INT);
    $nuevo_id = filter_var($_POST['nuevo_id'], FILTER_VALIDATE_INT);
    
    if (!$id_actual) {
        $_SESSION['error'] = "ID de usuario actual inválido";
        header("Location: ../vista/admin/usuarios.php");
        exit;
    }

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];

    try {
        $pdo->beginTransaction();

        // Verificar que el usuario existe
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id_actual]);
        $usuario_actual = $stmt->fetch();

        if (!$usuario_actual) {
            throw new PDOException("Usuario no encontrado");
        }

        // Verificar que no sea el último administrador si se cambia el rol
        if ($usuario_actual['rol'] === 'admin' && $rol !== 'admin') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'");
            if ($stmt->fetchColumn() <= 1) {
                throw new PDOException("No se puede cambiar el rol del último administrador");
            }
        }

        // Verificar email duplicado
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id_actual]);
        if ($stmt->fetch()) {
            throw new PDOException("El email ya está registrado para otro usuario");
        }

        // Si se quiere cambiar el ID
        if ($nuevo_id && $nuevo_id !== $id_actual) {
            // Verificar si el nuevo ID ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$nuevo_id]);
            if ($stmt->fetch()) {
                throw new PDOException("El ID {$nuevo_id} ya está en uso");
            }

            // Crear nuevo registro con el nuevo ID
            $sql = "INSERT INTO usuarios (id, nombre, email, password, rol, fecha_registro) 
                   SELECT ?, ?, ?, password, ?, fecha_registro 
                   FROM usuarios WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nuevo_id, $nombre, $email, $rol, $id_actual]);

            // Eliminar registro antiguo
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_actual]);
        } else {
            // Actualización normal sin cambio de ID
            $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?";
            $params = [$nombre, $email, $rol];
            
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = trim($_POST['password']);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id_actual;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        $pdo->commit();
        $_SESSION['success'] = "Usuario actualizado correctamente";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../vista/admin/usuarios.php");
    exit;
}
?>