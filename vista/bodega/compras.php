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
    <title>Compras a Proveedores - IB Ferreteria</title>
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
                <a class="nav-link" href="proveedores.php">Proveedores</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Historial de Compras a Proveedores</h2>
            </div>
        </div>

        <div class="card">
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
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT 
                                        cp.id,
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
                                ");
                                
                                while ($compra = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($compra['fecha_compra'])) . "</td>";
                                    echo "<td>{$compra['proveedor_nombre']}</td>";
                                    echo "<td>{$compra['producto_nombre']}</td>";
                                    echo "<td class='text-center'>{$compra['cantidad']}</td>";
                                    echo "<td class='text-end'>$" . number_format($compra['precio_unitario'], 0, ',', '.') . "</td>";
                                    echo "<td class='text-end'>$" . number_format($compra['precio_unitario'] * $compra['cantidad'], 0, ',', '.') . "</td>";
                                    echo "<td>{$compra['numero_factura']}</td>";
                                    echo "<td>
                                            <div class='btn-group'>
                                                <button class='btn btn-sm btn-primary me-1' onclick='verDetalles({$compra['id']})'>
                                                    <i class='bi bi-eye'></i>
                                                </button>
                                                <a href='factura.php?compra_id={$compra['id']}' 
                                                   class='btn btn-sm btn-success' 
                                                   target='_blank'
                                                   title='Ver/Imprimir Factura'>
                                                    <i class='bi bi-printer'></i>
                                                </a>
                                            </div>
                                        </td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='8' class='text-center text-danger'>Error al cargar las compras</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar detalles de la compra -->
    <div class="modal fade" id="detallesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detallesCompra"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function verDetalles(id) {
        fetch(`../../controlador/bodegaControlador.php?accion=obtener_detalles&compra_id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const detalles = `
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <th>Fecha:</th>
                                    <td>${data.detalles.fecha_compra}</td>
                                </tr>
                                <tr>
                                    <th>Proveedor:</th>
                                    <td>${data.detalles.proveedor_nombre}</td>
                                </tr>
                                <tr>
                                    <th>Producto:</th>
                                    <td>${data.detalles.producto_nombre}</td>
                                </tr>
                                <tr>
                                    <th>Descripción:</th>
                                    <td>${data.detalles.producto_descripcion || 'No disponible'}</td>
                                </tr>
                                <tr>
                                    <th>Cantidad:</th>
                                    <td>${data.detalles.cantidad}</td>
                                </tr>
                                <tr>
                                    <th>Precio Unitario:</th>
                                    <td>$${new Intl.NumberFormat('es-CO').format(data.detalles.precio_unitario)}</td>
                                </tr>
                                <tr>
                                    <th>Total:</th>
                                    <td>$${new Intl.NumberFormat('es-CO').format(data.detalles.precio_unitario * data.detalles.cantidad)}</td>
                                </tr>
                                <tr>
                                    <th>N° Factura:</th>
                                    <td>${data.detalles.numero_factura}</td>
                                </tr>
                            </table>
                        </div>`;
                    
                    document.getElementById('detallesCompra').innerHTML = detalles;
                    new bootstrap.Modal(document.getElementById('detallesModal')).show();
                } else {
                    mostrarError('Error al cargar los detalles: ' + data.error);
                }
            })
            .catch(error => mostrarError('Error de conexión al servidor'));
    }

    function mostrarError(mensaje) {
        alert(mensaje); // Puedes reemplazar esto con una mejor visualización del error
    }

    // Mostrar mensajes de éxito/error después de descargar factura
    <?php if (isset($_SESSION['error'])): ?>
        alert('<?php echo $_SESSION['error']; ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        alert('<?php echo $_SESSION['success']; ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    </script>
</body>
</html>