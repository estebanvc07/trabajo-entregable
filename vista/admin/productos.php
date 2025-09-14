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
    <title>Gestión de Productos - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Administración</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="usuarios.php">Usuarios</a>
                <a class="nav-link active" href="productos.php">Productos</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Productos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoProductoModal">
                Nuevo Producto
            </button>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Stock</th>
                    <th>Precio</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
                while ($producto = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>{$producto['id']}</td>";
                    echo "<td>{$producto['nombre']}</td>";
                    echo "<td>{$producto['descripcion']}</td>";
                    echo "<td>{$producto['stock']}</td>";
                    echo "<td>$" . number_format($producto['precio'], 0, ',', '.') . "</td>";
                    echo "<td>{$producto['fecha_creacion']}</td>";
                    echo "<td>
                            <button class='btn btn-sm btn-warning' 
                                    onclick='editarProducto({$producto['id']}, 
                                    \"{$producto['nombre']}\", 
                                    \"{$producto['descripcion']}\",
                                    {$producto['stock']},
                                    {$producto['precio']})'>
                                Editar
                            </button>
                            <button class='btn btn-sm btn-danger' 
                                    onclick='eliminarProducto({$producto['id']})'>
                                Eliminar
                            </button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Modal Nuevo Producto -->
        <div class="modal fade" id="nuevoProductoModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../../controlador/productoControlador.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>ID Producto</label>
                                <input type="number" name="id" class="form-control" required>
                                <small class="text-muted">Ingrese el ID único para el producto</small>
                            </div>
                            <div class="mb-3">
                                <label>Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Stock</label>
                                <input type="number" name="stock" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Precio</label>
                                <input type="number" name="precio" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" name="crear" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Editar Producto -->
        <div class="modal fade" id="editarProductoModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../../controlador/productoControlador.php" method="POST">
                        <input type="hidden" name="id" id="editId">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Nombre</label>
                                <input type="text" name="nombre" id="editNombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Descripción</label>
                                <textarea name="descripcion" id="editDescripcion" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Stock</label>
                                <input type="number" name="stock" id="editStock" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Precio</label>
                                <input type="number" name="precio" id="editPrecio" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" name="actualizar" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        function editarProducto(id, nombre, descripcion, stock, precio) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editDescripcion').value = descripcion;
            document.getElementById('editStock').value = stock;
            document.getElementById('editPrecio').value = precio;
            
            new bootstrap.Modal(document.getElementById('editarProductoModal')).show();
        }

        function eliminarProducto(id) {
            if(confirm('¿Está seguro de eliminar este producto?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../controlador/productoControlador.php';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id';
                inputId.value = id;

                const inputEliminar = document.createElement('input');
                inputEliminar.type = 'hidden';
                inputEliminar.name = 'eliminar';

                form.appendChild(inputId);
                form.appendChild(inputEliminar);
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>
    </div>
</body>
</html>
