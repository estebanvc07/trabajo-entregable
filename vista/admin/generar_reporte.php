 <?php

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once '../../conexionBD/db.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Administración</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="reportes.php">Volver a Reportes</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Reporte de Ventas</h2>
        <p>Periodo: <?php echo $fecha_inicio; ?> al <?php echo $fecha_fin; ?></p>

        <?php
        // Obtener ventas del periodo
        $sql = "SELECT v.id, v.fecha, u.nombre as vendedor, v.total,
                COUNT(dv.id) as num_productos
                FROM ventas v 
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                LEFT JOIN detalle_venta dv ON v.id = dv.venta_id
                WHERE DATE(v.fecha) BETWEEN ? AND ?
                GROUP BY v.id
                ORDER BY v.fecha DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        ?>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Resumen</h5>
                <?php
                $total_ventas = $pdo->prepare("SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?");
                $total_ventas->execute([$fecha_inicio, $fecha_fin]);
                $total = $total_ventas->fetch()['total'];
                ?>
                <p class="h3">Total vendido: $<?php echo number_format($total, 2); ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Vendedor</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($venta = $stmt->fetch()): ?>
                    <tr>
                        <td><?php echo $venta['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($venta['vendedor']); ?></td>
                        <td><?php echo $venta['num_productos']; ?></td>
                        <td>$<?php echo number_format($venta['total'], 2); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" 
                                    onclick="verDetalle(<?php echo $venta['id']; ?>)">
                                Ver Detalle
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Detalle Venta -->
    <div class="modal fade" id="detalleVentaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleVentaBody">
                    Cargando...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function verDetalle(ventaId) {
        const modal = new bootstrap.Modal(document.getElementById('detalleVentaModal'));
        modal.show();
        
        fetch(`detalle_venta.php?id=${ventaId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('detalleVentaBody').innerHTML = html;
            });
    }
    </script>
</body>
</html>