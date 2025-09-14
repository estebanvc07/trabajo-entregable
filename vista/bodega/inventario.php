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
    <title>Inventario - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Bodega</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link active" href="inventario.php">Inventario</a>
                <a class="nav-link" href="entradas.php">Entradas</a>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Inventario</h2>
            <div class="d-flex gap-2">
                <input type="text" id="buscarProducto" class="form-control" placeholder="Buscar producto...">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#nuevoProductoModal">
                    <i class="bi bi-plus-lg"></i> Nuevo Producto
                </button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="tablaInventario">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th>Stock</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
                            while ($producto = $stmt->fetch()) {
                                $stockClass = $producto['stock'] <= 5 ? 'text-danger fw-bold' : 
                                           ($producto['stock'] <= 10 ? 'text-warning fw-bold' : '');
                                
                                echo "<tr>";
                                echo "<td>{$producto['id']}</td>";
                                echo "<td>{$producto['nombre']}</td>";
                                echo "<td>{$producto['descripcion']}</td>";
                                echo "<td class='{$stockClass}'>{$producto['stock']}</td>";
                                echo "<td>$" . number_format($producto['precio'], 0, ',', '.') . "</td>";
                                echo "<td>
                                        <button class='btn btn-sm btn-primary' onclick='editarProducto({$producto['id']})'>
                                            <i class='bi bi-pencil'></i>
                                        </button>
                                        <button class='btn btn-sm btn-danger' onclick='eliminarProducto({$producto['id']})'>
                                            <i class='bi bi-trash'></i>
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
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock Inicial</label>
                            <input type="number" name="stock" class="form-control" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="1" name="precio" class="form-control" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select" name="proveedor_id" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre");
                                while ($proveedor = $stmt->fetch()) {
                                    echo "<option value='{$proveedor['id']}'>{$proveedor['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" name="crear" class="btn btn-success">
                            <i class="bi bi-save"></i> Guardar
                        </button>
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
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" id="edit_descripcion" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" id="edit_stock" class="form-control" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <input type="number" step="1" name="precio" id="edit_precio" class="form-control" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select" name="proveedor_id" id="edit_proveedor_id" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre");
                                while ($proveedor = $stmt->fetch()) {
                                    echo "<option value='{$proveedor['id']}'>{$proveedor['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" name="actualizar" class="btn btn-primary">
                            <i class="bi bi-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Confirmar eliminación -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmarEliminarLabel">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        ¿Está seguro de eliminar este producto?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="btnConfirmarEliminar" type="button" class="btn btn-danger">Eliminar</button>
      </div>
    </div>
  </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('buscarProducto').addEventListener('keyup', function() {
        let texto = this.value.toLowerCase();
        let tabla = document.getElementById('tablaInventario');
        let filas = tabla.getElementsByTagName('tr');

        for (let i = 1; i < filas.length; i++) {
            let visible = false;
            let celdas = filas[i].getElementsByTagName('td');
            for (let j = 0; j < celdas.length; j++) {
                if (celdas[j].textContent.toLowerCase().indexOf(texto) > -1) {
                    visible = true;
                    break;
                }
            }
            filas[i].style.display = visible ? '' : 'none';
        }
    });

    function editarProducto(id) {
        fetch(`../../controlador/productoControlador.php?accion=obtener&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_id').value = data.producto.id;
                    document.getElementById('edit_nombre').value = data.producto.nombre;
                    document.getElementById('edit_descripcion').value = data.producto.descripcion;
                    document.getElementById('edit_stock').value = data.producto.stock;
                    document.getElementById('edit_precio').value = data.producto.precio;
                    document.getElementById('edit_proveedor_id').value = data.producto.proveedor_id;
                    
                    new bootstrap.Modal(document.getElementById('editarProductoModal')).show();
                } else {
                    alert('Error al cargar el producto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el producto');
            });
    }

    let productoIdAEliminar = null;

function eliminarProducto(id) {
    productoIdAEliminar = id;
    let modal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
    modal.show();
}

document.getElementById('btnConfirmarEliminar').addEventListener('click', function() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controlador/productoControlador.php';

    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'id';
    inputId.value = productoIdAEliminar;

    const inputEliminar = document.createElement('input');
    inputEliminar.type = 'hidden';
    inputEliminar.name = 'eliminar';
    inputEliminar.value = '1';

    form.appendChild(inputId);
    form.appendChild(inputEliminar);

    document.body.appendChild(form);
    form.submit();
});

    </script>
</body>
</html>