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
    <title>Historial de Ventas - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Ventas</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="nueva_venta.php">Nueva Venta</a>
                <a class="nav-link active" href="historial.php">Historial</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Historial de Ventas</h2>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Fecha Desde</label>
                        <input type="date" name="fecha_desde" class="form-control" 
                               value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control"
                               value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Ventas -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Venta</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $where = "WHERE usuario_id = ?";
                            $params = [$_SESSION['usuario_id']];

                            if (!empty($_GET['fecha_desde'])) {
                                $where .= " AND DATE(fecha) >= ?";
                                $params[] = $_GET['fecha_desde'];
                            }
                            if (!empty($_GET['fecha_hasta'])) {
                                $where .= " AND DATE(fecha) <= ?";
                                $params[] = $_GET['fecha_hasta'];
                            }

                            $sql = "SELECT * FROM ventas $where ORDER BY fecha DESC";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);

                            while ($venta = $stmt->fetch()) {
                                echo "<tr>";
                                echo "<td>{$venta['id']}</td>";
                                echo "<td>" . date('d/m/Y H:i', strtotime($venta['fecha'])) . "</td>";
                                echo "<td>$" . number_format($venta['total'], 0, ',', '.') . "</td>";
                                echo "<td>
                                        <button class='btn btn-sm btn-info' 
                                                onclick='verDetalle({$venta['id']})'>
                                            Ver Detalle
                                        </button>
                                    </td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Venta -->
    <div class="modal fade" id="detalleVentaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalleVentaBody">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function verDetalle(ventaId) {
        fetch(`../../controlador/ventaControlador.php?accion=detalle&id=${ventaId}`)
            .then(response => response.json())
            .then(data => {
                let html = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.nombre}</td>
                            <td>${item.cantidad}</td>
                            <td>$${item.precio_unitario}</td>
                            <td>$${item.subtotal}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
                document.getElementById('detalleVentaBody').innerHTML = html;
                new bootstrap.Modal(document.getElementById('detalleVentaModal')).show();
            });
    }
    </script>
</body>
</html>