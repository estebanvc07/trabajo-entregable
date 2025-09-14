<?php

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'bodega') {
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
    <title>Panel Bodega - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/estilos.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Bodega</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="inventario.php">Inventario</a>
                <a class="nav-link" href="proveedores.php">Proveedores</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Resumen de Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Productos</h6>
                        <h2 class="mb-0">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM productos");
                            echo $stmt->fetchColumn();
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6 class="card-title">Stock Bajo</h6>
                        <h2 class="mb-0">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock <= 10");
                            echo $stmt->fetchColumn();
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Entradas Hoy</h6>
                        <h2 class="mb-0">
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM movimientos_stock 
                                               WHERE tipo = 'entrada' AND DATE(fecha) = CURDATE()");
                            echo $stmt->fetchColumn();
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Valor Inventario</h6>
                        <h2 class="mb-0">
                            <?php
                            $stmt = $pdo->query("SELECT SUM(stock * precio) FROM productos");
                            echo "$" . number_format($stmt->fetchColumn(), 0, ',', '.');
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
           
            <!-- <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Proveedores Activos</h6>
                        <h2 class="mb-0">
                            <?php
                            //$stmt = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado = 'activo'");
                            //echo $stmt->fetchColumn();
                            ?>
                        </h2>
                    </div>
                </div>
            </div> -->
        </div>

        <!-- Accesos Rápidos -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam fs-1 text-success"></i>
                        <h5 class="card-title mt-3">Inventario</h5>
                        <p class="card-text">Gestionar productos y stock</p>
                        <a href="inventario.php" class="btn btn-success">
                            <i class="bi bi-arrow-right-circle"></i> Gestionar
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-plus-circle fs-1 text-success"></i>
                        <h5 class="card-title mt-3">Entradas</h5>
                        <p class="card-text">Registrar entrada de productos</p>
                        <a href="entradas.php" class="btn btn-success">
                            <i class="bi bi-arrow-right-circle"></i> Registrar
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-clock-history fs-1 text-success"></i>
                        <h5 class="card-title mt-3">Movimientos</h5>
                        <p class="card-text">Ver historial de movimientos</p>
                        <a href="movimientos.php" class="btn btn-success">
                            <i class="bi bi-arrow-right-circle"></i> Ver Historial
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos Bajo Stock -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Productos con Stock Bajo</h5>
                        <a href="inventario.php?filter=stock_bajo" class="btn btn-dark btn-sm">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock</th>
                                        <th>Precio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, nombre, stock, precio 
                                                       FROM productos 
                                                       WHERE stock <= 10 
                                                       ORDER BY stock ASC
                                                       LIMIT 5");
                                    while ($producto = $stmt->fetch()) {
                                        $stockClass = $producto['stock'] <= 5 ? 'text-danger' : 'text-warning';
                                        echo "<tr>";
                                        echo "<td>{$producto['nombre']}</td>";
                                        echo "<td class='{$stockClass} fw-bold'>{$producto['stock']}</td>";
                                        echo "<td>$" . number_format($producto['precio'], 0, ',', '.') . "</td>";
                                        echo "<td>
                                                <a href='entradas.php?id={$producto['id']}' 
                                                   class='btn btn-sm btn-warning'>
                                                    <i class='bi bi-plus-circle'></i> Agregar Stock
                                                </a>
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
            
            <!-- Últimos Movimientos -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Últimos Movimientos</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $stmt = $pdo->query("
                            SELECT 
                                DATE_FORMAT(m.fecha, '%H:%i') as hora,
                                p.nombre,
                                m.tipo,
                                m.cantidad
                            FROM movimientos_stock m
                            JOIN productos p ON m.producto_id = p.id
                            WHERE DATE(m.fecha) = CURDATE()
                            ORDER BY m.fecha DESC
                            LIMIT 5
                        ");
                        while ($mov = $stmt->fetch()) {
                            $badgeClass = $mov['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger';
                            echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
                            echo "<div><small class='text-muted'>{$mov['hora']}</small> {$mov['nombre']}</div>";
                            echo "<span class='badge {$badgeClass}'>{$mov['cantidad']}</span>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas Compras a Proveedores -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Últimas Compras a Proveedores</h5>
                        <a href="compras.php" class="btn btn-light btn-sm">Ver Todas</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Proveedor</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Total</th>
                                        <th>N° Factura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT 
                                                cp.fecha_compra,
                                                cp.cantidad,
                                                cp.precio_unitario,
                                                cp.numero_factura,
                                                pr.nombre as producto_nombre,
                                                pv.nombre as proveedor_nombre
                                            FROM compras_proveedor cp
                                            JOIN productos pr ON cp.producto_id = pr.id
                                            JOIN proveedores pv ON cp.proveedor_id = pv.id
                                            ORDER BY cp.fecha_compra DESC
                                            LIMIT 5
                                        ");
                                        
                                        $hasRows = false;
                                        while ($compra = $stmt->fetch()) {
                                            $hasRows = true;
                                            echo "<tr>";
                                            echo "<td>" . date('d/m/Y H:i', strtotime($compra['fecha_compra'])) . "</td>";
                                            echo "<td>{$compra['proveedor_nombre']}</td>";
                                            echo "<td>{$compra['producto_nombre']}</td>";
                                            echo "<td>{$compra['cantidad']}</td>";
                                            echo "<td>$" . number_format($compra['precio_unitario'], 0, ',', '.') . "</td>";
                                            echo "<td>$" . number_format($compra['precio_unitario'] * $compra['cantidad'], 0, ',', '.') . "</td>";
                                            echo "<td>{$compra['numero_factura']}</td>";
                                            echo "</tr>";
                                        }
                                        
                                        if (!$hasRows) {
                                            echo "<tr><td colspan='7' class='text-center text-muted'>No hay compras registradas</td></tr>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='7' class='text-center text-danger'>Error al cargar las compras</td></tr>";
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