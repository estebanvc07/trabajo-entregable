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
    <title>Movimientos de Inventario - IB Ferreteria</title>
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
                <a class="nav-link active" href="movimientos.php">Movimientos</a>
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

        <div class="card">
            <div class="card-header bg-light">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-0">Movimientos de Inventario</h2>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2 justify-content-end">
                            <button class="btn btn-success" onclick="exportarMovimientos()">
                                <i class="bi bi-file-excel"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Producto</label>
                        <select class="form-select" id="filtroProducto">
                            <option value="">Todos los productos</option>
                            <?php
                            $stmt = $pdo->query("SELECT id, nombre FROM productos ORDER BY nombre");
                            while ($row = $stmt->fetch()) {
                                echo "<option value='{$row['id']}'>{$row['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" id="filtroTipo">
                            <option value="">Todos</option>
                            <option value="entrada">Entradas</option>
                            <option value="salida">Salidas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="filtroFecha">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary d-block w-100" onclick="filtrarMovimientos()">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function filtrarMovimientos() {
        const producto_id = document.getElementById('filtroProducto').value;
        const tipo = document.getElementById('filtroTipo').value;
        const fecha = document.getElementById('filtroFecha').value;
        
        let url = '../../controlador/bodegaControlador.php?accion=movimientos';
        if (producto_id) url += `&producto_id=${producto_id}`;
        if (tipo) url += `&tipo=${tipo}`;
        if (fecha) url += `&fecha=${fecha}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.querySelector('#tablaMovimientos tbody');
                    tbody.innerHTML = '';
                    
                    data.data.forEach(mov => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${mov.fecha}</td>
                            <td>${mov.producto}</td>
                            <td>
                                <span class="badge ${mov.tipo === 'entrada' ? 'bg-success' : 'bg-danger'}">
                                    ${mov.tipo.toUpperCase()}
                                </span>
                            </td>
                            <td>${mov.cantidad}</td>
                            <td>${mov.usuario}</td>
                            <td>${mov.observacion || '-'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    alert('Error al cargar los movimientos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los movimientos');
            });
    }

    function exportarMovimientos() {
        const producto_id = document.getElementById('filtroProducto').value;
        const tipo = document.getElementById('filtroTipo').value;
        const fecha = document.getElementById('filtroFecha').value;
        
        let url = '../../controlador/bodegaControlador.php?accion=exportar_movimientos';
        if (producto_id) url += `&producto_id=${producto_id}`;
        if (tipo) url += `&tipo=${tipo}`;
        if (fecha) url += `&fecha=${fecha}`;
        
        window.location.href = url;
    }

    // Cargar movimientos al iniciar
    document.addEventListener('DOMContentLoaded', filtrarMovimientos);
    </script>
</body>
</html>