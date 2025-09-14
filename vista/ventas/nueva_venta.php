<?php

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'ventas') {
    header("Location: ../login.php");
    exit;
}
require_once '../../conexionBD/db.php';

if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

// Add this for debugging
echo '<div class="d-none">';
echo '<h3>Debug Info:</h3>';
print_r($_POST);
echo '</div>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .stock-badge {
            font-size: 0.9rem;
            padding: 0.5em 1em;
        }
        .mensaje-sistema {
            position: fixed;
            top: 200px;
            right: 150px;
            z-index: 1050;
            min-width: 300px;
        }
        #resultadosBusqueda {
            position: absolute;
            width: 100%;
            z-index: 1000;
            background: white;
            border: 1px solid rgba(0,0,0,.125);
            border-radius: 0.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        #resultadosBusqueda .list-group-item {
            border-left: none;
            border-right: none;
            border-radius: 0;
        }
        
        #resultadosBusqueda .list-group-item:first-child {
            border-top: none;
        }
        
        #resultadosBusqueda .list-group-item:last-child {
            border-bottom: none;
        }
        
        #resultadosBusqueda .list-group-item:hover {
            background-color: #f8f9fa;
        }
        
        .select2-container--default .select2-results__option {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .select2-results__option--highlighted {
            background-color: #f8f9fa !important;
            color: #333 !important;
        }
        #detalles_producto {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">IB Ferreteria - Ventas</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Inicio</a>
                <a class="nav-link active" href="nueva_venta.php">Nueva Venta</a>
                <a class="nav-link" href="historial.php">Historial</a>
                <a class="nav-link" href="../logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Nueva Venta</h5>
                    </div>
                    <div class="card-body">
                        <form id="formVenta" onsubmit="return false;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Producto</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="buscarProducto" 
                                           placeholder="Buscar por nombre o descripción..." 
                                           autocomplete="off">
                                    <input type="hidden" id="productoSeleccionado">
                                    <div id="resultadosBusqueda" class="list-group mt-2" 
                                         style="display: none; max-height: 300px; overflow-y: auto;"></div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" class="form-control" id="cantidad" min="1" onchange="verificarStock()">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-primary w-100" onclick="agregarProducto()">
                                        <i class="bi bi-plus-circle me-2"></i>Agregar
                                    </button>
                                </div>
                            </div>
                            <div id="mensajeError" class="alert alert-danger d-none"></div>
                        </form>

                        <div class="mt-4">
                            <table class="table" id="tablaProductos">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio Unit.</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td colspan="2"><strong id="totalVenta">$0</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                            <button onclick="finalizarVenta()" class="btn btn-success float-end">
                                <i class="bi bi-check-circle me-2"></i>Finalizar Venta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información de Stock</h5>
                    </div>
                    <div class="card-body">
                        <div id="infoStock">
                            <p class="text-muted">Seleccione un producto para ver su información</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agregar después de la tabla de productos -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Datos del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <form id="formCliente">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre Cliente *</label>
                                        <input type="text" class="form-control" name="cliente_nombre" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Documento/NIT *</label>
                                        <input type="text" class="form-control" name="cliente_documento" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" class="form-control" name="cliente_direccion">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input type="text" class="form-control" name="cliente_telefono">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Resumen de Venta</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Subtotal</label>
                            <h4 id="subtotal">$0</h4>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">IVA (19%)</label>
                            <h4 id="iva">$0</h4>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total</label>
                            <h3 id="total" class="text-primary">$0</h3>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="finalizarVenta()">
                            <i class="bi bi-check-circle"></i> Finalizar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="mensajeSistema" class="mensaje-sistema"></div>

    <div class="modal fade" id="modalFacturacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Datos de Facturación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formFacturacion">
                        <div class="mb-3">
                            <label class="form-label">Nombre Cliente</label>
                            <input type="text" class="form-control" name="cliente_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Documento/NIT</label>
                            <input type="text" class="form-control" name="cliente_documento" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="cliente_direccion">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="cliente_telefono">
                        </div>
                        <div class="alert alert-info">
                            <strong>Total a Pagar:</strong> 
                            <span id="modalTotal" class="fs-4"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="procesarVenta()">
                        Procesar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    let productosEnVenta = [];
    let total = 0;

    function verificarStock(stock = null, precio = null) {
        const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
        const infoStock = document.getElementById('infoStock');
        const buscarInput = document.getElementById('buscarProducto');
        const productoId = document.getElementById('productoSeleccionado').value;

        if (!stock) {
            fetch(`../../controlador/productoControlador.php?accion=verificar_stock&id=${productoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        stock = data.stock;
                        mostrarInfoStock(stock, precio, cantidad);
                    } else {
                        mostrarMensaje('Error al verificar stock: ' + data.error);
                    }
                })
                .catch(error => {
                    mostrarMensaje('Error al verificar stock');
                });
        } else {
            mostrarInfoStock(stock, precio, cantidad);
        }
    }

    function mostrarInfoStock(stock, precio, cantidad) {
        const infoStock = document.getElementById('infoStock');
        
        if (typeof stock !== 'number') {
            console.error('Stock inválido:', stock);
            return false;
        }

        let stockClass = stock <= 10 ? 'danger' : (stock <= 20 ? 'warning' : 'success');
        let stockStatus = stock <= 10 ? 'CRÍTICO' : (stock <= 20 ? 'BAJO' : 'NORMAL');

        infoStock.innerHTML = `
            <div class="text-center mb-4">
                <span class="badge bg-${stockClass} stock-badge mb-2">${stockStatus}</span>
                <h3 class="text-${stockClass}">${stock} unidades</h3>
                <hr>
                <h6 class="mb-2">Precio Unitario:</h6>
                <h4>$${precio ? precio.toLocaleString('es-CO') : '0'}</h4>
            </div>
        `;

        if (cantidad > stock) {
            mostrarMensaje(`¡Error! Stock insuficiente. Stock disponible: ${stock} unidades`);
            return false;
        }

        return true;
    }

    function agregarProducto() {
        const idProducto = document.getElementById('productoSeleccionado').value;
        const nombre = document.getElementById('buscarProducto').value;
        const cantidad = parseInt(document.getElementById('cantidad').value);
        const precio = parseFloat(document.getElementById('buscarProducto').dataset.precio);

        if (!idProducto || !cantidad) {
            mostrarMensaje('Por favor complete todos los campos');
            return;
        }

        // Verificar stock en tiempo real
        fetch(`../../controlador/productoControlador.php?accion=verificar_stock&id=${idProducto}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    mostrarMensaje('Error al verificar stock: ' + data.error);
                    return;
                }

                const stockActual = data.stock;
                
                if (cantidad > stockActual) {
                    mostrarMensaje(`Stock insuficiente. Disponible: ${stockActual} unidades`);
                    return;
                }

                // Verificar si ya existe el producto en la venta
                let productoExistente = productosEnVenta.find(p => p.id === idProducto);
                const cantidadTotal = productoExistente ? 
                    productoExistente.cantidad + cantidad : 
                    cantidad;

                if (cantidadTotal > stockActual) {
                    mostrarMensaje(`La cantidad total excede el stock disponible (${stockActual} unidades)`);
                    return;
                }

                if (productoExistente) {
                    productoExistente.cantidad = cantidadTotal;
                    productoExistente.subtotal = productoExistente.precio * cantidadTotal;
                } else {
                    productosEnVenta.push({
                        id: idProducto,
                        nombre: nombre,
                        precio: precio,
                        cantidad: cantidad,
                        subtotal: precio * cantidad
                    });
                }

                actualizarTablaProductos();
                limpiarFormularioProducto();
            })
            .catch(error => {
                mostrarMensaje('Error al verificar el stock');
            });
    }

    function limpiarFormularioProducto() {
        document.getElementById('productoSeleccionado').value = '';
        document.getElementById('buscarProducto').value = '';
        document.getElementById('cantidad').value = '';
        document.getElementById('infoStock').innerHTML = '<p class="text-muted">Seleccione un producto para ver su información</p>';
    }

    function calcularTotales() {
        const subtotal = productosEnVenta.reduce((sum, p) => sum + (p.precio * p.cantidad), 0);
        const iva = subtotal * 0.19;
        const total = subtotal + iva;

        document.getElementById('subtotal').textContent = `$${subtotal.toLocaleString('es-CO')}`;
        document.getElementById('iva').textContent = `$${iva.toLocaleString('es-CO')}`;
        document.getElementById('total').textContent = `$${total.toLocaleString('es-CO')}`;
    }

    function actualizarTablaProductos() {
        const tbody = document.querySelector('#tablaProductos tbody');
        tbody.innerHTML = '';
        total = 0;

        productosEnVenta.forEach((producto, index) => {
            total += producto.subtotal;
            tbody.innerHTML += `
                <tr>
                    <td>${producto.nombre}</td>
                    <td>$${producto.precio.toLocaleString('es-CO')}</td>
                    <td>${producto.cantidad}</td>
                    <td>$${producto.subtotal.toLocaleString('es-CO')}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="eliminarProducto(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        document.getElementById('totalVenta').textContent = `$${total.toLocaleString('es-CO')}`;
        calcularTotales();
    }

    function eliminarProducto(index) {
        productosEnVenta.splice(index, 1);
        actualizarTablaProductos();
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

    function finalizarVenta() {
        if (productosEnVenta.length === 0) {
            mostrarMensaje('Agregue productos a la venta', 'warning');
            return;
        }

        const formCliente = document.getElementById('formCliente');
        if (!formCliente.checkValidity()) {
            mostrarMensaje('Complete los datos del cliente', 'warning');
            formCliente.reportValidity();
            return;
        }

        const formData = new FormData(formCliente);
        const datosCliente = Object.fromEntries(formData.entries());
        const subtotal = productosEnVenta.reduce((sum, p) => sum + (p.precio * p.cantidad), 0);
        const iva = subtotal * 0.19;
        const total = subtotal + iva;

        fetch('../../controlador/ventaControlador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                accion: 'finalizar',
                productos: productosEnVenta,
                cliente: datosCliente,
                subtotal: subtotal,
                iva: iva,
                total: total
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `imprimir_factura.php?id=${data.venta_id}`;
            } else {
                mostrarMensaje(data.error || 'Error al procesar la venta', 'danger');
            }
        })
        .catch(error => {
            mostrarMensaje('Error al procesar la venta', 'danger');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const buscarInput = document.getElementById('buscarProducto');
        const resultadosDiv = document.getElementById('resultadosBusqueda');
        const productoSeleccionadoInput = document.getElementById('productoSeleccionado');
        let timeoutId;

        buscarInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(timeoutId);

            if (query.length === 0) {
                resultadosDiv.style.display = 'none';
                resultadosDiv.innerHTML = '';
                return;
            }

            timeoutId = setTimeout(() => {
                fetch(`../../controlador/productoControlador.php?accion=buscar&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultadosDiv.innerHTML = '';
                        if (!Array.isArray(data) || data.length === 0) {
                            resultadosDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron productos</div>';
                            resultadosDiv.style.display = 'block';
                            return;
                        }

                        // Reemplazar la sección donde se crean los resultados
                        data.forEach(producto => {
                            const item = document.createElement('a');
                            item.href = "#";
                            item.className = 'list-group-item list-group-item-action';
                            
                            // Formatear la descripción
                            const descripcion = producto.descripcion ? 
                                producto.descripcion.trim() : 'Sin descripción';
                            
                            item.innerHTML = `
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${producto.nombre}</h6>
                                    <small class="text-success">Stock: ${producto.stock}</small>
                                </div>
                                <p class="mb-1 text-muted small">${descripcion}</p>
                                <small class="text-primary">Precio: $${Number(producto.precio).toLocaleString('es-CO')}</small>
                            `;

                            item.addEventListener('click', function (e) {
                                e.preventDefault();
                                const nombreCompleto = `${producto.nombre} - ${descripcion}`;
                                buscarInput.value = nombreCompleto;
                                productoSeleccionadoInput.value = producto.id;
                                buscarInput.dataset.stock = producto.stock;
                                buscarInput.dataset.precio = producto.precio;
                                buscarInput.dataset.descripcion = descripcion;
                                verificarStock(producto.stock, producto.precio);
                                resultadosDiv.style.display = 'none';
                                buscarInput.blur();
                            });
                            
                            resultadosDiv.appendChild(item);
                        });
                        resultadosDiv.style.display = 'block';
                    })
                    .catch(() => {
                        resultadosDiv.innerHTML = '<div class="list-group-item text-danger">Error al buscar productos</div>';
                        resultadosDiv.style.display = 'block';
                    });
            }, 300);
        });

        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!resultadosDiv.contains(e.target) && e.target !== buscarInput) {
                resultadosDiv.style.display = 'none';
            }
        });
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
