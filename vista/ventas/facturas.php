<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['ventas', 'admin'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../../conexionBD/db.php';

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$filtro_cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Inicializar array de parámetros
$params = [];

// Consulta para contar total de registros
$sql_count = "SELECT COUNT(*) FROM facturas f
              JOIN ventas v ON f.venta_id = v.id
              JOIN usuarios u ON v.usuario_id = u.id
              WHERE 1=1";

if ($filtro_fecha) {
    $sql_count .= " AND DATE(f.fecha_emision) = ?";
    $params[] = $filtro_fecha;
}
if ($filtro_cliente) {
    $sql_count .= " AND (f.cliente_nombre LIKE ? OR f.cliente_documento LIKE ?)";
    $params[] = "%{$filtro_cliente}%";
    $params[] = "%{$filtro_cliente}%";
}
if ($filtro_estado) {
    $sql_count .= " AND f.estado = ?";
    $params[] = $filtro_estado;
}

$stmt = $pdo->prepare($sql_count);
$stmt->execute($params);
$total_registros = $stmt->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Consulta principal de facturas
$sql = "SELECT f.*, v.fecha as fecha_venta, u.nombre as vendedor_nombre
        FROM facturas f
        JOIN ventas v ON f.venta_id = v.id
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE 1=1";

$params = [];

if ($filtro_fecha) {
    $sql .= " AND DATE(f.fecha_emision) = ?";
    $params[] = $filtro_fecha;
}
if ($filtro_cliente) {
    $sql .= " AND (f.cliente_nombre LIKE ? OR f.cliente_documento LIKE ?)";
    $params[] = "%{$filtro_cliente}%";
    $params[] = "%{$filtro_cliente}%";
}
if ($filtro_estado) {
    $sql .= " AND f.estado = ?";
    $params[] = $filtro_estado;
}

// Agregar orden y paginación (evitando ? para LIMIT y OFFSET)
$sql .= " ORDER BY f.fecha_emision DESC LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas - IB Ferreteria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-receipt me-2"></i>Facturas</h2>
            <small class="text-muted">
                Accediendo como: <?= ucfirst($_SESSION['rol']) ?>
            </small>
        </div>
        <a href="<?= $_SESSION['rol'] === 'admin' ? '../admin/dashboard.php' : 'dashboard.php' ?>" 
           class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver 
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= $filtro_fecha ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente/Documento</label>
                    <input type="text" name="cliente" class="form-control" value="<?= htmlspecialchars($filtro_cliente) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="emitida" <?= $filtro_estado === 'emitida' ? 'selected' : '' ?>>Emitida</option>
                        <option value="anulada" <?= $filtro_estado === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="facturas.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Facturas -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>N° Factura</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Vendedor</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($facturas as $factura): ?>
                        <tr>
                            <td><?= $factura['numero_factura'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])) ?></td>
                            <td><?= htmlspecialchars($factura['cliente_nombre']) ?></td>
                            <td><?= htmlspecialchars($factura['cliente_documento']) ?></td>
                            <td><?= htmlspecialchars($factura['vendedor_nombre']) ?></td>
                            <td class="text-end">$<?= number_format($factura['total'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge bg-<?= $factura['estado'] === 'emitida' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($factura['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="ver_factura.php?id=<?= $factura['venta_id'] ?>" class="btn btn-info" title="Ver Detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="imprimir_factura.php?id=<?= $factura['venta_id'] ?>" class="btn btn-secondary" title="Imprimir">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <?php if ($factura['estado'] === 'emitida'): ?>
                                        <button onclick="anularFactura(<?= $factura['id'] ?>)" class="btn btn-danger" title="Anular">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($facturas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No se encontraron facturas</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&fecha=<?= $filtro_fecha ?>&cliente=<?= urlencode($filtro_cliente) ?>&estado=<?= $filtro_estado ?>">
                                Anterior
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= $pagina == $i ? 'active' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $i ?>&fecha=<?= $filtro_fecha ?>&cliente=<?= urlencode($filtro_cliente) ?>&estado=<?= $filtro_estado ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&fecha=<?= $filtro_fecha ?>&cliente=<?= urlencode($filtro_cliente) ?>&estado=<?= $filtro_estado ?>">
                                Siguiente
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function anularFactura(id) {
        if (confirm('¿Está seguro de que desea anular esta factura?')) {
            fetch('../../controlador/facturaControlador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    accion: 'anular',
                    factura_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Error al anular la factura');
                }
            })
            .catch(() => {
                alert('Error al procesar la solicitud');
            });
        }
    }
</script>
</body>
</html>
