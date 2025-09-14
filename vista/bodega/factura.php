<?php

session_start();
require_once '../../conexionBD/db.php';

// Verificar permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'bodega') {
    header('Location: ../login.php');
    exit;
}

// Verificar si se recibió un ID de compra
if (!isset($_GET['compra_id'])) {
    header('Location: compras.php');
    exit;
}

// Obtener datos de la compra
try {
    $stmt = $pdo->prepare("
        SELECT 
            cp.*,
            p.nombre as producto_nombre,
            p.descripcion as producto_descripcion,
            pr.nombre as proveedor_nombre,
            pr.nit as proveedor_nit,
            pr.direccion as proveedor_direccion,
            pr.telefono as proveedor_telefono
        FROM compras_proveedor cp
        INNER JOIN productos p ON cp.producto_id = p.id
        INNER JOIN proveedores pr ON cp.proveedor_id = pr.id
        WHERE cp.id = ?
    ");
    
    $stmt->execute([$_GET['compra_id']]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        $_SESSION['error'] = "Compra no encontrada";
        header('Location: compras.php');
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error al cargar los datos de la compra";
    header('Location: compras.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #<?php echo $compra['numero_factura']; ?> - IB Ferretería</title>
    <style>
                
            @media print {
                .no-print { display: none !important; }
                body { margin: 0; padding: 20px; }
                .container { box-shadow: none !important; }
            }
        
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                margin: 0;
                background-color: #f5f5f5;
                color: #333;
            }
        
            .container {
                max-width: 800px;
                margin: 40px auto;
                padding: 40px;
                background: white;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 10px;
            }
        
            .header {
                text-align: center;
                margin-bottom: 40px;
                position: relative;
                padding-bottom: 20px;
            }
        
            .header:after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 25%;
                right: 25%;
                height: 3px;
                background: linear-gradient(to right, transparent, #198754, transparent);
            }
        
            .header h1 {
                color: #198754;
                margin: 0 0 10px 0;
                font-size: 2.5em;
            }
        
            .header h2 {
                color: #666;
                margin: 0;
                font-weight: normal;
            }
        
            .info-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
        
            .info-section h3 {
                color: #198754;
                margin-top: 0;
                border-bottom: 2px solid #e9ecef;
                padding-bottom: 10px;
            }
        
            .info-section p {
                margin: 8px 0;
            }
        
            .table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin: 30px 0;
            }
        
            .table th, .table td {
                border: 1px solid #e9ecef;
                padding: 15px;
            }
        
            .table th {
                background: #198754;
                color: white;
                font-weight: 500;
                text-transform: uppercase;
                font-size: 0.9em;
                letter-spacing: 1px;
            }
        
            .table tr:nth-child(even) {
                background-color: #f8f9fa;
            }
        
            .table td {
                border-top: none;
            }
        
            .total-row {
                font-weight: bold;
                background-color: #e9ecef !important;
            }
        
            .total-row td {
                border-top: 2px solid #198754;
            }
        
            .btn-print {
                background: linear-gradient(145deg, #198754, #157347);
                color: white;
                padding: 12px 25px;
                border: none;
                border-radius: 25px;
                cursor: pointer;
                margin: 20px 0;
                font-weight: bold;
                transition: transform 0.2s, box-shadow 0.2s;
                box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            }
        
            .btn-print:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 12px rgba(0,0,0,0.15);
            }
        
            .footer {
                margin-top: 40px;
                text-align: center;
                color: #666;
                padding-top: 20px;
                border-top: 1px dashed #e9ecef;
            }
        
            .footer p {
                margin: 5px 0;
                font-size: 0.9em;
            }
        
    </style>
</head>
<body>
    <div class="container">
        <button onclick="window.print()" class="btn-print no-print">Imprimir Factura</button>
        
        <div class="header">
            <h1>IB FERRETERÍA</h1>
            <h2>FACTURA DE COMPRA</h2>
        </div>

        <div class="info-section">
            <p><strong>Factura N°:</strong> <?php echo htmlspecialchars($compra['numero_factura']); ?></p>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($compra['fecha_compra'])); ?></p>
        </div>

        <div class="info-section">
            <h3>DATOS DEL PROVEEDOR</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($compra['proveedor_nombre']); ?></p>
            <p><strong>NIT:</strong> <?php echo htmlspecialchars($compra['proveedor_nit']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($compra['proveedor_direccion'] ?? 'No registrada'); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($compra['proveedor_telefono'] ?? 'No registrado'); ?></p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($compra['producto_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($compra['producto_descripcion']); ?></td>
                    <td><?php echo $compra['cantidad']; ?></td>
                    <td>$ <?php echo number_format($compra['precio_unitario'], 0, ',', '.'); ?></td>
                    <td>$ <?php echo number_format($compra['cantidad'] * $compra['precio_unitario'], 0, ',', '.'); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;"><strong>TOTAL:</strong></td>
                    <td>$ <?php echo number_format($compra['cantidad'] * $compra['precio_unitario'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Este documento es una constancia de compra válida.</p>
            <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <script>
        // Imprimir automáticamente si se pasa el parámetro print=true
        if (new URLSearchParams(window.location.search).get('print') === 'true') {
            window.print();
        }
    </script>
</body>
</html>