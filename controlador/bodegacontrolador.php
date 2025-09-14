<?php

session_start();
require_once '../conexionBD/db.php';

// Verificar si el usuario es de bodega
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'bodega') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Registrar entrada de producto
if (isset($_POST['registrar_entrada'])) {
    try {
        $pdo->beginTransaction();
        
        $producto_id = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
        $precio_unitario = filter_var($_POST['precio_unitario'], FILTER_VALIDATE_FLOAT);
        $observacion = trim($_POST['observacion'] ?? '');

        // Validación mejorada
        if (!$producto_id) {
            throw new Exception("Debe seleccionar un producto válido");
        }

        if (!$cantidad || $cantidad <= 0) {
            throw new Exception("La cantidad debe ser mayor a 0");
        }

        if (!$precio_unitario || $precio_unitario <= 0) {
            throw new Exception("El precio unitario debe ser mayor a 0");
        }

        // Verificar que el producto existe y obtener sus datos
        $stmt = $pdo->prepare("
            SELECT p.*, pr.nombre as proveedor_nombre 
            FROM productos p 
            LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("El producto seleccionado no existe");
        }

        // Actualizar stock
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET stock = stock + ?, 
                fecha_actualizacion = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$cantidad, $producto_id]);

        // Registrar movimiento de stock
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_stock (
                producto_id, tipo, cantidad, observacion, usuario_id, fecha
            ) VALUES (?, 'entrada', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $producto_id, 
            $cantidad, 
            $observacion, 
            $_SESSION['usuario_id']
        ]);

        // Registrar en compras_proveedor
        $stmt = $pdo->prepare("
            INSERT INTO compras_proveedor (
                proveedor_id, producto_id, cantidad, precio_unitario, 
                fecha_compra, numero_factura
            ) VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        
        $numero_factura = 'ENT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $stmt->execute([
            $producto['proveedor_id'],
            $producto_id,
            $cantidad,
            $precio_unitario,  // Asegurarse que este valor está presente
            $numero_factura
        ]);

        $pdo->commit();
        $_SESSION['success'] = "Entrada registrada correctamente. Stock actualizado.";
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../vista/bodega/entradas.php");
    exit;
}

// Obtener movimientos con filtros
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'movimientos') {
    try {
        $query = "SELECT 
                    m.id,
                    DATE_FORMAT(m.fecha, '%d/%m/%Y %H:%i') as fecha,
                    p.nombre as producto,
                    m.tipo,
                    m.cantidad,
                    COALESCE(m.observacion, '') as observacion,
                    u.nombre as usuario
                FROM movimientos_stock m
                JOIN productos p ON m.producto_id = p.id
                JOIN usuarios u ON m.usuario_id = u.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($_GET['producto_id'])) {
            $query .= " AND m.producto_id = ?";
            $params[] = filter_var($_GET['producto_id'], FILTER_VALIDATE_INT);
        }

        if (!empty($_GET['tipo'])) {
            $query .= " AND m.tipo = ?";
            $params[] = in_array($_GET['tipo'], ['entrada', 'salida']) ? $_GET['tipo'] : 'entrada';
        }

        if (!empty($_GET['fecha'])) {
            $query .= " AND DATE(m.fecha) = ?";
            $params[] = $_GET['fecha'];
        }

        $query .= " ORDER BY m.fecha DESC LIMIT 1000";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $movimientos]);

    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al cargar movimientos: ' . $e->getMessage()]);
    }
    exit;
}

// Exportar movimientos a CSV
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'exportar_movimientos') {
    try {
        $query = "SELECT 
                    DATE_FORMAT(m.fecha, '%d/%m/%Y %H:%i') as fecha,
                    p.nombre as producto,
                    m.tipo,
                    m.cantidad,
                    u.nombre as usuario,
                    COALESCE(m.observacion, '-') as observacion
                FROM movimientos_stock m
                JOIN productos p ON m.producto_id = p.id
                JOIN usuarios u ON m.usuario_id = u.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($_GET['producto_id'])) {
            $query .= " AND m.producto_id = ?";
            $params[] = filter_var($_GET['producto_id'], FILTER_VALIDATE_INT);
        }

        if (!empty($_GET['tipo'])) {
            $query .= " AND m.tipo = ?";
            $params[] = in_array($_GET['tipo'], ['entrada', 'salida']) ? $_GET['tipo'] : 'entrada';
        }

        if (!empty($_GET['fecha'])) {
            $query .= " AND DATE(m.fecha) = ?";
            $params[] = $_GET['fecha'];
        }

        $query .= " ORDER BY m.fecha DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=movimientos_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Fecha', 'Producto', 'Tipo', 'Cantidad', 'Usuario', 'Observación']);

        foreach ($movimientos as $mov) {
            $mov['tipo'] = ucfirst($mov['tipo']);
            fputcsv($output, array_values($mov));
        }

        fclose($output);
        exit;

    } catch(Exception $e) {
        $_SESSION['error'] = "Error al exportar: " . $e->getMessage();
        header("Location: ../vista/bodega/movimientos.php");
        exit;
    }
}

// Actualizar stock de producto
if (isset($_POST['actualizar_stock'])) {
    try {
        $pdo->beginTransaction();

        $producto_id = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        $nuevo_stock = filter_var($_POST['nuevo_stock'], FILTER_VALIDATE_INT);
        $observacion = trim($_POST['observacion'] ?? '');

        if (!$producto_id || $nuevo_stock === false || $nuevo_stock < 0) {
            throw new Exception("Datos inválidos para la actualización");
        }

        // Obtener stock actual y nombre del producto
        $stmt = $pdo->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("Producto no encontrado");
        }

        $diferencia = $nuevo_stock - $producto['stock'];
        if ($diferencia !== 0) {
            $tipo = $diferencia > 0 ? 'entrada' : 'salida';
            $cantidad = abs($diferencia);

            // Actualizar stock
            $stmt = $pdo->prepare("UPDATE productos SET stock = ?, fecha_actualizacion = NOW() WHERE id = ?");
            $stmt->execute([$nuevo_stock, $producto_id]);

            // Registrar movimiento
            $stmt = $pdo->prepare("INSERT INTO movimientos_stock (producto_id, tipo, cantidad, observacion, usuario_id, fecha) 
                                 VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$producto_id, $tipo, $cantidad, $observacion, $_SESSION['usuario_id']]);

            $pdo->commit();
            $_SESSION['success'] = "Stock de {$producto['nombre']} actualizado de {$producto['stock']} a {$nuevo_stock}";
        } else {
            $pdo->commit();
            $_SESSION['info'] = "No hubo cambios en el stock";
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../vista/bodega/inventario.php");
    exit;
}

// Obtener productos con stock bajo
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'stock_bajo') {
    try {
        $stmt = $pdo->query("
            SELECT 
                id,
                nombre,
                descripcion,
                stock,
                precio,
                DATE_FORMAT(fecha_actualizacion, '%d/%m/%Y %H:%i') as ultima_actualizacion
            FROM productos 
            WHERE stock <= 10
            ORDER BY stock ASC, nombre ASC
        ");
        
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $productos]);
        
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
    }
    exit;
}

// Cargar producto para editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cargar_producto') {
    try {
        $producto_id = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        
        if (!$producto_id) {
            throw new Exception("ID de producto inválido");
        }

        $stmt = $pdo->prepare("
            SELECT p.*, pr.nombre as proveedor_nombre
            FROM productos p
            LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
            WHERE p.id = ?
        ");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception("Producto no encontrado");
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $producto
        ]);

    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Obtener proveedores para el select
if (isset($_POST['accion']) && $_POST['accion'] === 'obtener_proveedores') {
    try {
        $stmt = $pdo->query("SELECT id, nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre");
        $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html = '<option value="">Seleccione un proveedor</option>';
        foreach ($proveedores as $proveedor) {
            $html .= "<option value='{$proveedor['id']}'>{$proveedor['nombre']}</option>";
        }
        
        echo $html;
    } catch(Exception $e) {
        echo '<option value="">Error al cargar proveedores</option>';
    }
    exit;
}

// Actualizar producto
if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_producto') {
    try {
        $producto_id = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
        $proveedor_id = filter_var($_POST['proveedor_id'], FILTER_VALIDATE_INT);

        if (!$producto_id || empty($nombre) || $precio === false) {
            throw new Exception("Datos de producto inválidos");
        }

        $stmt = $pdo->prepare("
            UPDATE productos 
            SET nombre = ?, 
                descripcion = ?,
                precio = ?,
                proveedor_id = ?,
                fecha_actualizacion = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$nombre, $descripcion, $precio, $proveedor_id, $producto_id]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente'
        ]);

    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Eliminar producto
if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_producto') {
    try {
        $producto_id = filter_var($_POST['producto_id'], FILTER_VALIDATE_INT);
        
        if (!$producto_id) {
            throw new Exception("ID de producto inválido");
        }

        // Verificar si el producto existe
        $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("El producto no existe");
        }

        // Eliminar el producto
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);

    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Obtener productos para inventario
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'obtener_productos') {
    try {
        $stmt = $pdo->query("
            SELECT 
                p.id,
                p.nombre,
                p.descripcion,
                p.stock,
                p.precio,
                pr.nombre as proveedor,
                DATE_FORMAT(p.fecha_actualizacion, '%d/%m/%Y %H:%i') as ultima_actualizacion
            FROM productos p
            LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
            ORDER BY p.nombre ASC
        ");
        
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $productos]);
        
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
    }
    exit;
}

// Crear nuevo producto
if (isset($_POST['accion']) && $_POST['accion'] === 'crear_producto') {
    try {
        $pdo->beginTransaction();

        // Validar datos
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
        $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
        $proveedor_id = filter_var($_POST['proveedor_id'], FILTER_VALIDATE_INT);

        // Validar campos requeridos
        if (empty($nombre) || empty($descripcion) || $stock === false || $precio === false || !$proveedor_id) {
            throw new Exception("Todos los campos son obligatorios y deben ser válidos");
        }

        // Verificar duplicados
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM productos 
            WHERE LOWER(nombre) = LOWER(?) 
            AND LOWER(descripcion) = LOWER(?)
        ");
        $stmt->execute([strtolower($nombre), strtolower($descripcion)]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ya existe un producto con el mismo nombre y descripción");
        }

        // Insertar producto
        $stmt = $pdo->prepare("
            INSERT INTO productos (
                nombre, 
                descripcion, 
                stock, 
                precio, 
                proveedor_id, 
                fecha_actualizacion
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $nombre,
            $descripcion,
            $stock,
            $precio,
            $proveedor_id
        ]);

        $producto_id = $pdo->lastInsertId();

        // Registrar en compras_proveedor si hay stock inicial
        if ($stock > 0) {
            $numero_factura = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO compras_proveedor (
                    proveedor_id,
                    producto_id,
                    cantidad,
                    precio_unitario,
                    fecha_compra,
                    numero_factura
                ) VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            
            $stmt->execute([
                $proveedor_id,
                $producto_id,
                $stock,
                $precio,
                $numero_factura
            ]);

            // Registrar movimiento de stock
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_stock (
                    producto_id,
                    tipo,
                    cantidad,
                    observacion,
                    usuario_id,
                    fecha
                ) VALUES (?, 'entrada', ?, 'Stock inicial del producto', ?, NOW())
            ");
            
            $stmt->execute([
                $producto_id,
                $stock,
                $_SESSION['usuario_id']
            ]);
        }

        $pdo->commit();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Producto creado correctamente y registrado en el historial de compras'
        ]);

    } catch(Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Agregar esta sección para manejar la obtención de detalles
if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_detalles') {
    try {
        $compra_id = filter_var($_GET['compra_id'], FILTER_VALIDATE_INT);
        
        if (!$compra_id) {
            throw new Exception("ID de compra inválido");
        }

        $stmt = $pdo->prepare("
            SELECT 
                cp.*,
                p.nombre as producto_nombre,
                p.descripcion as producto_descripcion,
                pr.nombre as proveedor_nombre
            FROM compras_proveedor cp
            INNER JOIN productos p ON cp.producto_id = p.id
            INNER JOIN proveedores pr ON cp.proveedor_id = pr.id
            WHERE cp.id = ?
        ");
        
        $stmt->execute([$compra_id]);
        $detalles = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$detalles) {
            throw new Exception("Compra no encontrada");
        }

        // Formatear la fecha
        $detalles['fecha_compra'] = date('d/m/Y H:i', strtotime($detalles['fecha_compra']));

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'detalles' => $detalles
        ]);

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!-- Agregar en el head -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Agregar después del select -->
<script>
$(document).ready(function() {
    $('#producto_id').select2({
        placeholder: "Seleccione un producto",
        width: '100%'
    });
});
</script>