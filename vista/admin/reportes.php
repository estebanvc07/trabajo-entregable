 <?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
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
    <title>Reportes - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Administración</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="usuarios.php">Usuarios</a>
                <a class="nav-link active" href="reportes.php">Reportes</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Reportes del Sistema</h2>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ventas por Periodo</h5>
                        <form action="generar_reporte.php" method="GET">
                            <div class="mb-3">
                                <label>Fecha Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Fecha Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Generar Reporte</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Productos Más Vendidos</h5>
                        <?php
                        $sql = "SELECT p.nombre, SUM(dv.cantidad) as total_vendido
                               FROM productos p
                               JOIN detalle_venta dv ON p.id = dv.producto_id
                               GROUP BY p.id
                               ORDER BY total_vendido DESC
                               LIMIT 5";
                        $stmt = $pdo->query($sql);
                        ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad Vendida</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $stmt->fetch()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                    <td><?php echo $row['total_vendido']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>