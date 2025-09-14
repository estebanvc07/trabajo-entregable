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
    <title>Entradas de Inventario - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Bodega</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="inventario.php">Inventario</a>
                <a class="nav-link active" href="entradas.php">Entradas</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Registrar Entrada</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../controlador/bodegaControlador.php" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="producto_id" class="form-label">Producto</label>
                                <select class="form-select" name="producto_id" id="producto_id" required>
                                    <option value="">Seleccione un producto</option>
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT 
                                            p.id,
                                            p.nombre,
                                            p.descripcion,
                                            p.precio,
                                            pr.nombre as proveedor_nombre
                                        FROM productos p
                                        LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
                                        ORDER BY p.nombre ASC
                                    ");
                                    $stmt->execute();
                                    
                                    while ($producto = $stmt->fetch()) {
                                        echo '<option value="' . $producto['id'] . '" 
                                                     data-precio="' . $producto['precio'] . '"
                                                     data-descripcion="' . htmlspecialchars($producto['descripcion']) . '">'
                                            . htmlspecialchars($producto['nombre'])
                                            . ' - ' . htmlspecialchars($producto['descripcion'])
                                            . ' (Proveedor: ' . htmlspecialchars($producto['proveedor_nombre']) . ')'
                                            . '</option>';
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">Por favor seleccione un producto</div>
                            </div>

                            <div class="mb-3">
                                <label for="cantidad" class="form-label">Cantidad</label>
                                <input type="number" class="form-control" name="cantidad" id="cantidad" required min="1">
                                <div class="invalid-feedback">La cantidad debe ser mayor a 0</div>
                            </div>

                            <div class="mb-3">
                                <label for="precio_unitario" class="form-label">Precio Unitario</label>
                                <input type="number" class="form-control" name="precio_unitario" id="precio_unitario" required min="0" step="1">
                                <div class="invalid-feedback">El precio debe ser mayor a 0</div>
                            </div>

                            <div class="mb-3">
                                <label for="observacion" class="form-label">Observación</label>
                                <textarea class="form-control" name="observacion" id="observacion" rows="3"></textarea>
                            </div>

                            <button type="submit" name="registrar_entrada" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Registrar Entrada
                            </button>
                        </form>

                        <script>
                            document.getElementById('producto_id').addEventListener('change', function() {
                                const selectedOption = this.options[this.selectedIndex];
                                const precio = selectedOption.getAttribute('data-precio');
                                const descripcion = selectedOption.getAttribute('data-descripcion');
                                
                                // Actualizar precio unitario automáticamente
                                document.getElementById('precio_unitario').value = precio || '';
                                
                                // Mostrar descripción si existe el elemento
                                if (document.getElementById('descripcion_producto')) {
                                    document.getElementById('descripcion_producto').textContent = descripcion || 'Sin descripción';
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Últimas Entradas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT 
                                            DATE_FORMAT(m.fecha, '%d/%m/%Y %H:%i') as fecha,
                                            p.nombre as producto,
                                            m.cantidad,
                                            u.nombre as usuario
                                        FROM movimientos_stock m
                                        JOIN productos p ON m.producto_id = p.id
                                        JOIN usuarios u ON m.usuario_id = u.id
                                        WHERE m.tipo = 'entrada'
                                        ORDER BY m.fecha DESC
                                        LIMIT 10
                                    ");
                                    while ($mov = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>{$mov['fecha']}</td>";
                                        echo "<td>{$mov['producto']}</td>";
                                        echo "<td>{$mov['cantidad']}</td>";
                                        echo "<td>{$mov['usuario']}</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opcional: Agregar un div para mostrar la descripción -->
        <div class="mb-3">
            <label class="form-label">Descripción del producto:</label>
            <div id="descripcion_producto" class="form-text border p-2 bg-light">
                Seleccione un producto para ver su descripción
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>