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
    <title>Control de Inventario - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .stock-bajo { 
            background-color: rgba(255, 193, 7, 0.1) !important; 
        }
        .stock-critico { 
            background-color: rgba(220, 53, 69, 0.1) !important; 
        }
        .status-badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }
        .filtro-stock {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.05) !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-tools me-2"></i>IB Ferreteria - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link active" href="inventario.php">Inventario</a>
                <a class="nav-link" href="usuarios.php">Usuarios</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="bi bi-box-seam me-2"></i>Control de Inventario</h2>
            </div>
            <div class="col-md-6">
                <div class="filtro-stock bg-light text-center">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="filtroStock" id="todos" value="todos" checked>
                        <label class="form-check-label" for="todos">Todos</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="filtroStock" id="bajo" value="bajo">
                        <label class="form-check-label text-warning" for="bajo">Stock Bajo</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="filtroStock" id="critico" value="critico">
                        <label class="form-check-label text-danger" for="critico">Stock Crítico</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaInventario">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Última Actualización</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT 
                                    nombre, descripcion, stock, precio,
                                    DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i') as ultima_actualizacion
                                FROM productos 
                                ORDER BY stock ASC, nombre ASC
                            ");
                            
                            while ($producto = $stmt->fetch()) {
                                $row_class = $producto['stock'] <= 10 ? 'stock-critico' : 
                                           ($producto['stock'] <= 20 ? 'stock-bajo' : '');
                                $estado_class = $producto['stock'] <= 10 ? 'danger' : 
                                             ($producto['stock'] <= 20 ? 'warning' : 'success');
                                $estado_texto = $producto['stock'] <= 10 ? 'Crítico' : 
                                             ($producto['stock'] <= 20 ? 'Bajo' : 'Normal');
                                
                                echo "<tr class='{$row_class}' data-stock='{$producto['stock']}'>";
                                echo "<td><strong>{$producto['nombre']}</strong></td>";
                                echo "<td>{$producto['descripcion']}</td>";
                                echo "<td class='text-{$estado_class}'><strong>{$producto['stock']}</strong></td>";
                                echo "<td>$" . number_format($producto['precio'], 0, ',', '.') . "</td>";
                                echo "<td>{$producto['ultima_actualizacion']}</td>";
                                echo "<td><span class='badge bg-{$estado_class} status-badge'>{$estado_texto}</span></td>";
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
        const tabla = $('#tablaInventario').DataTable({
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ al _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros)",
                zeroRecords: "No se encontraron resultados",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            pageLength: 25,
            order: [[2, 'asc']],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
        });

        // Función personalizada de filtrado
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const row = tabla.row(dataIndex).node();
            const stock = parseInt($(row).data('stock'));
            const filtro = $('input[name="filtroStock"]:checked').val();
            
            if (filtro === 'bajo') {
                return stock > 10 && stock <= 20;
            } else if (filtro === 'critico') {
                return stock <= 10;
            }
            return true; // Mostrar todos para filtro 'todos'
        });

        // Aplicar filtro cuando cambie la selección
        $('input[name="filtroStock"]').change(function() {
            tabla.draw();
        });
    });
    </script>
</body>
</html>