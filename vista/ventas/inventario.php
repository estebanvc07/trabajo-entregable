<?php
session_start();
require_once '../../conexionBD/db.php';

// Verificar conexión
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Inventario - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .stock-bajo { background-color: rgba(255, 193, 7, 0.1) !important; }
        .stock-critico { background-color: rgba(220, 53, 69, 0.1) !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shop me-2"></i>IB Ferreteria - Ventas
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="nueva_venta.php">Nueva Venta</a>
                <a class="nav-link active" href="inventario.php">Inventario</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-12">
                <h2><i class="bi bi-search me-2"></i>Consulta de Inventario</h2>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaInventario">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Disponible</th>
                                <th>Precio</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT 
                                        nombre, descripcion, stock, precio
                                    FROM productos 
                                    WHERE stock > 0
                                    ORDER BY nombre ASC
                                ");
                            } catch (PDOException $e) {
                                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                                exit;
                            }

                            if ($stmt->rowCount() === 0) {
                                echo "<tr><td colspan='5' class='text-center'>No hay productos disponibles</td></tr>";
                            }

                            while ($producto = $stmt->fetch()) {
                                $row_class = $producto['stock'] <= 10 ? 'stock-critico' : 
                                           ($producto['stock'] <= 20 ? 'stock-bajo' : '');
                                $estado_class = $producto['stock'] <= 10 ? 'danger' : 
                                             ($producto['stock'] <= 20 ? 'warning' : 'success');
                                $estado_texto = $producto['stock'] <= 10 ? 'Bajo' : 'Disponible';
                                
                                echo "<tr class='{$row_class}'>";
                                echo "<td><strong>{$producto['nombre']}</strong></td>";
                                echo "<td>{$producto['descripcion']}</td>";
                                echo "<td class='text-{$estado_class}'><strong>{$producto['stock']}</strong></td>";
                                echo "<td>$" . number_format($producto['precio'], 0, ',', '.') . "</td>";
                                echo "<td><span class='badge bg-{$estado_class}'>{$estado_texto}</span></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#tablaInventario').DataTable({
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ productos",
                info: "Mostrando _START_ al _END_ de _TOTAL_ productos",
                infoEmpty: "No hay productos disponibles",
                infoFiltered: "(filtrado de _MAX_ productos)",
                zeroRecords: "No se encontraron resultados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            pageLength: 10,
            order: [[0, 'asc']]
        });
    });
    </script>
</body>
</html>