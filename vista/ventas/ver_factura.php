<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['ventas', 'admin'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../../conexionBD/db.php';

$venta_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$venta_id) {
    die("ID de venta inválido");
}

// Obtener datos de la factura
$stmt = $pdo->prepare("
    SELECT f.*, v.fecha as fecha_venta, u.nombre as vendedor_nombre
    FROM facturas f
    JOIN ventas v ON f.venta_id = v.id
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE f.venta_id = ?
");
$stmt->execute([$venta_id]);
$factura = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener detalles de la venta
$stmt = $pdo->prepare("
    SELECT dv.*, p.nombre as producto_nombre
    FROM detalle_venta dv
    JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = ?
");
$stmt->execute([$venta_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Factura - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Factura #<?= $factura['numero_factura'] ?></h5>
                <div>
                    <a href="facturas.php" class="btn btn-light btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                    <a href="imprimir_factura.php?id=<?= $venta_id ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-printer"></i> Imprimir
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Datos del Cliente</h6>
                        <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($factura['cliente_nombre']) ?></p>
                        <p class="mb-1"><strong>Documento:</strong> <?= htmlspecialchars($factura['cliente_documento']) ?></p>
                        <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($factura['cliente_direccion']) ?></p>
                        <p><strong>Teléfono:</strong> <?= htmlspecialchars($factura['cliente_telefono']) ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted">Detalles de la Factura</h6>
                        <p class="mb-1"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])) ?></p>
                        <p class="mb-1"><strong>Estado:</strong> 
                            <span class="badge bg-<?= $factura['estado'] === 'emitida' ? 'success' : 'danger' ?>">
                                <?= ucfirst($factura['estado']) ?>
                            </span>
                        </p>
                        <p><strong>Vendedor:</strong> <?= htmlspecialchars($factura['vendedor_nombre']) ?></p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?= htmlspecialchars($detalle['producto_nombre']) ?></td>
                                <td class="text-center"><?= $detalle['cantidad'] ?></td>
                                <td class="text-end">$<?= number_format($detalle['precio_unitario'], 0, ',', '.') ?></td>
                                <td class="text-end">$<?= number_format($detalle['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end">$<?= number_format($factura['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>IVA (19%):</strong></td>
                                <td class="text-end">$<?= number_format($factura['iva'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>$<?= number_format($factura['total'], 0, ',', '.') ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>