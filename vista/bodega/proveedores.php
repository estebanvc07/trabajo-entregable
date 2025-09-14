<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin', 'bodega'])) {
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
    <title>Proveedores - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .mensaje-sistema {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Bodega</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link" href="inventario.php">Inventario</a>
                <a class="nav-link active" href="proveedores.php">Proveedores</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container mt-4">
        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Proveedores</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProveedor">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Proveedor
            </button>
        </div>

        <!-- Mensajes del sistema -->
        <div id="mensajeSistema" class="mensaje-sistema"></div>

        <!-- Tabla de proveedores -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>NIT</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Contacto</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM proveedores ORDER BY nombre");
                            while ($proveedor = $stmt->fetch()): 
                                $estadoClass = $proveedor['estado'] === 'activo' ? 'success' : 'danger';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($proveedor['nombre']) ?></td>
                                <td><?= htmlspecialchars($proveedor['nit']) ?></td>
                                <td><?= htmlspecialchars($proveedor['telefono']) ?></td>
                                <td><?= htmlspecialchars($proveedor['email']) ?></td>
                                <td><?= htmlspecialchars($proveedor['contacto_nombre']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $estadoClass ?>">
                                        <?= ucfirst($proveedor['estado']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarProveedor(<?= $proveedor['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Proveedor -->
    <div class="modal fade" id="modalProveedor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formProveedor" action="../../controlador/proveedorControlador.php" method="POST" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="crear">
                        <input type="hidden" name="id" id="proveedorId">
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">NIT</label>
                            <input type="text" class="form-control" name="nit" required autocomplete="on">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" name="telefono">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="direccion">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre de Contacto</label>
                            <input type="text" class="form-control" name="contacto_nombre">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editarProveedor(id) {
        fetch(`../../controlador/proveedorControlador.php?accion=obtener&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const proveedor = data.proveedor;
                    document.getElementById('modalTitle').textContent = 'Editar Proveedor';
                    document.getElementById('proveedorId').value = proveedor.id;
                    document.querySelector('[name="nombre"]').value = proveedor.nombre;
                    document.querySelector('[name="nit"]').value = proveedor.nit;
                    document.querySelector('[name="direccion"]').value = proveedor.direccion || '';
                    document.querySelector('[name="telefono"]').value = proveedor.telefono || '';
                    document.querySelector('[name="email"]').value = proveedor.email || '';
                    document.querySelector('[name="contacto_nombre"]').value = proveedor.contacto_nombre || '';
                    document.querySelector('[name="estado"]').value = proveedor.estado;
                    document.querySelector('[name="accion"]').value = 'actualizar';
                    
                    new bootstrap.Modal(document.getElementById('modalProveedor')).show();
                }
            })
            .catch(() => mostrarMensaje('Error al cargar los datos del proveedor'));
    }

    function mostrarMensaje(mensaje, tipo = 'danger') {
        const mensajeDiv = document.getElementById('mensajeSistema');
        const alert = document.createElement('div');
        alert.className = `alert alert-${tipo} alert-dismissible fade show`;
        alert.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        mensajeDiv.appendChild(alert);

        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }

    // Mostrar mensajes del sistema
    <?php if (isset($_SESSION['success'])): ?>
        mostrarMensaje('<?= $_SESSION['success'] ?>', 'success');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        mostrarMensaje('<?= $_SESSION['error'] ?>', 'danger');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    </script>
</body>
</html>