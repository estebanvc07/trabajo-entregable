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
    <title>Factura #<?= $factura['numero_factura'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { font-size: 12pt; }
        }
        .factura-header { border-bottom: 2px solid #dee2e6; }
        .factura-footer { border-top: 2px solid #dee2e6; }
    </style>
</head>
<body class="bg-white">
    <div class="container my-5">
        <div class="text-center mb-4 factura-header pb-3">
            <h2>IB FERRETERIA</h2>
            <p class="mb-0">NIT: 123456789-0</p>
            <p class="mb-0">Dirección: Calle Principal #123</p>
            <p>Teléfono: (123) 456-7890</p>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <h4>FACTURA DE VENTA</h4>
                <p class="mb-0"><strong>N°:</strong> <?= $factura['numero_factura'] ?></p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])) ?></p>
            </div>
            <div class="col-6">
                <h5>Cliente</h5>
                <p class="mb-0"><strong>Nombre:</strong> <?= htmlspecialchars($factura['cliente_nombre']) ?></p>
                <p class="mb-0"><strong>Documento:</strong> <?= htmlspecialchars($factura['cliente_documento']) ?></p>
                <p class="mb-0"><strong>Dirección:</strong> <?= htmlspecialchars($factura['cliente_direccion']) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($factura['cliente_telefono']) ?></p>
            </div>
        </div>

        <table class="table table-bordered">
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
            <tfoot>
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

        <div class="text-center mt-4 factura-footer pt-3">
            <p class="mb-1">¡Gracias por su compra!</p>
            <small class="text-muted">Esta factura es un documento válido para efectos tributarios</small>
        </div>

        <div class="mt-4 text-center no-print">
            <button onclick="window.print()" class="btn btn-primary">Imprimir Factura</button>
            <a href="facturas.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</body>
</html>