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
    <title>Gestión de Usuarios - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Administración</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
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
            <h2>Gestión de Usuarios</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
                Nuevo Usuario
            </button>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
                while ($usuario = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>{$usuario['id']}</td>";
                    echo "<td>{$usuario['nombre']}</td>";
                    echo "<td>{$usuario['email']}</td>";
                    echo "<td>{$usuario['rol']}</td>";
                    echo "<td>{$usuario['fecha_registro']}</td>";
                    echo "<td>
                        <button class='btn btn-sm btn-warning' 
                                onclick='editarUsuario({$usuario['id']}, 
                                \"{$usuario['nombre']}\", 
                                \"{$usuario['email']}\", 
                                \"{$usuario['rol']}\")'>
                            Editar
                        </button>
                        <form method='POST' action='../../controlador/usuarioControlador.php' style='display:inline;'>
                            <input type='hidden' name='id' value='{$usuario['id']}'>
                            <button type='submit' name='eliminar' class='btn btn-sm btn-danger' 
                                onclick='return confirm(\"¿Está seguro de eliminar este usuario?\")'>
                                Eliminar
                            </button>
                        </form>
                    </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Modal Nuevo Usuario -->
        <div class="modal fade" id="nuevoUsuarioModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../../controlador/usuarioControlador.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>ID Usuario</label>
                                <input type="number" name="id" class="form-control" required>
                                <small class="text-muted">Ingrese el ID único para el usuario</small>
                            </div>
                            <div class="mb-3">
                                <label>Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Rol</label>
                                <select name="rol" class="form-control" required>
                                    <option value="admin">Administrador</option>
                                    <option value="ventas">Ventas</option>
                                    <option value="bodega">Bodega</option>
                                </select>
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

        <!-- Modal Editar Usuario -->
        <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../../controlador/usuarioControlador.php" method="POST">
                        <input type="hidden" name="id_actual" id="idActual">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>ID Usuario</label>
                                <input type="number" name="nuevo_id" id="editId" class="form-control">
                                <small class="text-muted">Solo administradores pueden modificar el ID</small>
                            </div>
                            <div class="mb-3">
                                <label>Nombre</label>
                                <input type="text" name="nombre" id="editNombre" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" id="editEmail" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Contraseña (dejar en blanco para mantener la actual)</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Rol</label>
                                <select name="rol" id="editRol" class="form-control" required>
                                    <option value="admin">Administrador</option>
                                    <option value="ventas">Ventas</option>
                                    <option value="bodega">Bodega</option>
                                </select>
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

        <script>
        function editarUsuario(id, nombre, email, rol) {
            document.getElementById('idActual').value = id;
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editEmail').value = email;
            document.getElementById('editRol').value = rol;
            
            new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
        }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </div>
</body>
</html>