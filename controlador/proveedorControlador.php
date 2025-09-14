<?php

session_start();
require_once '../conexionBD/db.php';

// Verificar autorización
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin', 'bodega'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener proveedor específico
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'obtener') {
    try {
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('ID inválido');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
        $stmt->execute([$id]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$proveedor) {
            throw new Exception('Proveedor no encontrado');
        }
        
        echo json_encode(['success' => true, 'proveedor' => $proveedor]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Crear proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    try {
        $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, nit, direccion, telefono, email, contacto_nombre) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['nit'],
            $_POST['direccion'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['contacto_nombre']
        ]);
        $_SESSION['success'] = "Proveedor creado correctamente";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error al crear el proveedor: " . $e->getMessage();
    }
    header("Location: ../vista/bodega/proveedores.php");
    exit;
}

// Actualizar proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    try {
        $stmt = $pdo->prepare("UPDATE proveedores SET 
                              nombre = ?, 
                              nit = ?,
                              direccion = ?,
                              telefono = ?,
                              email = ?,
                              contacto_nombre = ?,
                              estado = ?
                              WHERE id = ?");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['nit'],
            $_POST['direccion'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['contacto_nombre'],
            $_POST['estado'],
            $_POST['id']
        ]);
        $_SESSION['success'] = "Proveedor actualizado correctamente";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error al actualizar el proveedor: " . $e->getMessage();
    }
    header("Location: ../vista/bodega/proveedores.php");
    exit;
}
?>