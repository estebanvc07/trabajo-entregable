<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../conexionBD/db.php';

// Verificar si el usuario está autorizado
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin', 'bodega', 'ventas'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener producto para edición (AJAX)
if (isset($_GET['accion']) && $_GET['accion'] === 'obtener') {
    try {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) throw new Exception("ID inválido");

        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) throw new Exception("Producto no encontrado");

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'producto' => $producto]);
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Búsqueda de productos
if (isset($_GET['accion']) && $_GET['accion'] === 'buscar') {
    try {
        $query = $_GET['q'] ?? '';
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.nombre,
                p.descripcion,
                p.precio,
                p.stock
            FROM productos p
            WHERE (LOWER(p.nombre) LIKE LOWER(:query) 
                  OR LOWER(p.descripcion) LIKE LOWER(:query))
                AND p.stock > 0
            ORDER BY p.nombre ASC
            LIMIT 10
        ");
        $stmt->execute(['query' => "%{$query}%"]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($productos);
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Crear nuevo producto
if (isset($_POST['crear'])) {
    try {
        $pdo->beginTransaction();

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_INT);
        $proveedor_id = filter_var($_POST['proveedor_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$nombre || !$descripcion || $stock === false || $precio === false || !$proveedor_id) {
            throw new Exception("Todos los campos son requeridos y deben ser válidos");
        }

        if ($stock < 0 || $precio < 0) {
            throw new Exception("El stock y precio no pueden ser negativos");
        }

        // Verificar nombre único
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ya existe un producto con este nombre");
        }

        // Verificar proveedor activo
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM proveedores WHERE id = ? AND estado = 'activo'");
        $stmt->execute([$proveedor_id]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("El proveedor no existe o está inactivo");
        }

        // Insertar producto
        $stmt = $pdo->prepare("
            INSERT INTO productos (nombre, descripcion, stock, precio, proveedor_id, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nombre, $descripcion, $stock, $precio, $proveedor_id]);
        $producto_id = $pdo->lastInsertId();

        // Registrar stock inicial
        if ($stock > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_stock (producto_id, tipo, cantidad, usuario_id, fecha) 
                VALUES (?, 'entrada', ?, ?, NOW())
            ");
            $stmt->execute([$producto_id, $stock, $_SESSION['usuario_id']]);

            $stmt = $pdo->prepare("
                INSERT INTO compras_proveedor (proveedor_id, producto_id, cantidad, precio_unitario) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$proveedor_id, $producto_id, $stock, $precio]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Producto creado correctamente";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: ../vista/bodega/inventario.php");
    exit;
}

// Actualizar producto
if (isset($_POST['actualizar'])) {
    try {
        $pdo->beginTransaction();

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_INT);
        $proveedor_id = filter_var($_POST['proveedor_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || !$nombre || !$descripcion || $stock === false || $precio === false || !$proveedor_id) {
            throw new Exception("Todos los campos son requeridos y deben ser válidos");
        }

        // Obtener stock anterior
        $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $stockAnterior = $stmt->fetchColumn();

        if ($stockAnterior === false) {
            throw new Exception("Producto no encontrado");
        }

        // Verificar nombre único
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ya existe otro producto con este nombre");
        }

        // Actualizar producto
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET nombre = ?, descripcion = ?, stock = ?, precio = ?, proveedor_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nombre, $descripcion, $stock, $precio, $proveedor_id, $id]);

        // Registrar movimiento si cambió el stock
        if ($stock != $stockAnterior) {
            $tipo = $stock > $stockAnterior ? 'entrada' : 'salida';
            $cantidad = abs($stock - $stockAnterior);

            $stmt = $pdo->prepare("
                INSERT INTO movimientos_stock (producto_id, tipo, cantidad, usuario_id, fecha) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$id, $tipo, $cantidad, $_SESSION['usuario_id']]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Producto actualizado correctamente";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: ../vista/bodega/inventario.php");
    exit;
}

// Eliminar producto
if (isset($_POST['eliminar'])) {
    try {
        $pdo->beginTransaction();

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception("ID inválido");
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM detalle_venta WHERE producto_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el producto porque tiene ventas asociadas");
        }

        $stmt = $pdo->prepare("DELETE FROM movimientos_stock WHERE producto_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM compras_proveedor WHERE producto_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        $_SESSION['success'] = "Producto eliminado correctamente";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header("Location: ../vista/bodega/inventario.php");
    exit;
}



// Verificación de stock por AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'verificar_stock') {
    try {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('ID inválido');
        }

        $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        $stock = $stmt->fetchColumn();

        if ($stock === false) {
            throw new Exception('Producto no encontrado');
        }

        echo json_encode([
            'success' => true,
            'stock' => (int)$stock
        ]);
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
