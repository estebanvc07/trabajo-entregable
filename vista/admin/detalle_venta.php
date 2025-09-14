 <?php

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso no autorizado");
}

require_once '../../conexionBD/db.php';

if (!isset($_GET['id'])) {
    die("ID de venta no proporcionado");
}

$venta_id = $_GET['id'];

try {
    // Obtener información de la venta
    $stmt = $pdo->prepare("
        SELECT v.*, u.nombre as vendedor 
        FROM ventas v 
        LEFT JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.id = ?
    ");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch();

    if (!$venta) {
        die("Venta no encontrada");
    }

    // Obtener detalles de los productos
    $stmt = $pdo->prepare("
        SELECT dv.*, p.nombre as producto_nombre 
        FROM detalle_venta dv
        JOIN productos p ON dv.producto_id = p.id
        WHERE dv.venta_id = ?
    ");
    $stmt->execute([$venta_id]);
    $detalles = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <strong>Fecha:</strong> 
            <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?>
        </div>
        <div class="col-md-6">
            <strong>Vendedor:</strong> 
            <?php echo htmlspecialchars($venta['vendedor']); ?>
        </div>
    </div>

    <table class="table table-sm">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($detalles as $detalle): ?>
            <tr>
                <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                <td><?php echo $detalle['cantidad']; ?></td>
                <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                <td><strong>$<?php echo number_format($venta['total'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php
} catch(PDOException $e) {
    die("Error al obtener los detalles: " . $e->getMessage());
}
?>