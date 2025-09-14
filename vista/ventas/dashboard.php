<?php

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'ventas') {
    header("Location: ../login.php");
    exit;
}
require_once '../../conexionBD/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Ventas - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">IB Ferreteria - Ventas</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="nueva_venta.php">Nueva Venta</a>
                <a class="nav-link" href="inventario.php">Inventario</a>
                <a class="nav-link" href="historial.php">Historial</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
        
        <div class="row mt-4">
            <!-- Tarjetas de acceso rápido -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-cart-plus me-2"></i>Nueva Venta
                        </h5>
                        <p class="card-text">Registrar una nueva venta</p>
                        <a href="nueva_venta.php" class="btn btn-primary">Crear Venta</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-box-seam me-2"></i>Inventario
                        </h5>
                        <p class="card-text">Consultar productos disponibles</p>
                        <a href="inventario.php" class="btn btn-success">Ver Inventario</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-clock-history me-2"></i>Historial
                        </h5>
                        <p class="card-text">Ver historial de ventas</p>
                        <a href="historial.php" class="btn btn-secondary">Ver Historial</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt me-2"></i>Últimas Facturas
                        </h5>
                        <a href="facturas.php" class="btn btn-primary btn-sm">
                            Ver Todas las Facturas
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>N° Factura</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Documento</th>
                                        <th class="text-end">Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT f.*, v.fecha as fecha_venta 
                                            FROM facturas f
                                            JOIN ventas v ON f.venta_id = v.id
                                            ORDER BY f.fecha_emision DESC
                                            LIMIT 5
                                        ");
                                        
                                        while ($factura = $stmt->fetch()) {
                                            $estado_badge = $factura['estado'] === 'emitida' ? 'success' : 'danger';
                                            echo "<tr>";
                                            echo "<td>{$factura['numero_factura']}</td>";
                                            echo "<td>" . date('d/m/Y H:i', strtotime($factura['fecha_emision'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($factura['cliente_nombre']) . "</td>";
                                            echo "<td>" . htmlspecialchars($factura['cliente_documento']) . "</td>";
                                            echo "<td class='text-end'>$" . number_format($factura['total'], 0, ',', '.') . "</td>";
                                            echo "<td><span class='badge bg-{$estado_badge}'>" . 
                                                 ucfirst($factura['estado']) . "</span></td>";
                                            echo "<td>
                                                    <div class='btn-group btn-group-sm'>
                                                        <a href='ver_factura.php?id={$factura['venta_id']}' 
                                                           class='btn btn-info' title='Ver Detalles'>
                                                            <i class='bi bi-eye'></i>
                                                        </a>
                                                        <a href='imprimir_factura.php?id={$factura['venta_id']}' 
                                                           class='btn btn-secondary' title='Imprimir'>
                                                            <i class='bi bi-printer'></i>
                                                        </a>
                                                    </div>
                                                </td>";
                                            echo "</tr>";
                                        }

                                        if ($stmt->rowCount() === 0) {
                                            echo "<tr><td colspan='7' class='text-center text-muted'>
                                                    No hay facturas registradas
                                                  </td></tr>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='7' class='text-center text-danger'>
                                                Error al cargar las facturas: " . htmlspecialchars($e->getMessage()) . "
                                              </td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>