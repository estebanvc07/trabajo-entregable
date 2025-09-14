<?php

session_start();
require_once '../conexionBD/db.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['ventas', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    if ($datos['accion'] === 'anular') {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ?");
            $stmt->execute([$datos['factura_id']]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch(Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}