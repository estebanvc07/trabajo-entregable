<?php
// Nueva venta - IB Ferreteria
session_start();
require_once '../conexionBD/db.php';

// Verificar si el usuario es vendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'ventas') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Buscar productos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'buscar') {
    $busqueda = '%' . $_GET['q'] . '%';
    
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE nombre LIKE ? AND stock > 0");
        $stmt->execute([$busqueda]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($productos);
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Procesar la venta y crear factura
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    if ($datos['accion'] === 'finalizar') {
        try {
            $pdo->beginTransaction();

            // Crear la venta
            $stmt = $pdo->prepare("
                INSERT INTO ventas (usuario_id, fecha, total) 
                VALUES (?, NOW(), ?)
            ");
            $stmt->execute([$_SESSION['usuario_id'], $datos['total']]);
            $venta_id = $pdo->lastInsertId();

            // Insertar detalles y actualizar stock
            $stmt = $pdo->prepare("
                INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt_stock = $pdo->prepare("
                UPDATE productos SET stock = stock - ? WHERE id = ?
            ");

            foreach ($datos['productos'] as $producto) {
                $subtotal = $producto['cantidad'] * $producto['precio'];
                $stmt->execute([
                    $venta_id,
                    $producto['id'],
                    $producto['cantidad'],
                    $producto['precio'],
                    $subtotal
                ]);

                $stmt_stock->execute([$producto['cantidad'], $producto['id']]);
            }

            // Crear factura
            $numero_factura = date('Ymd') . str_pad($venta_id, 4, '0', STR_PAD_LEFT);
            $subtotal = $datos['total'] / 1.19; // 19% IVA
            $iva = $datos['total'] - $subtotal;

            $stmt = $pdo->prepare("
                INSERT INTO facturas (
                    venta_id, 
                    numero_factura,
                    subtotal,
                    iva,
                    total,
                    cliente_nombre,
                    cliente_documento,
                    cliente_direccion,
                    cliente_telefono
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $venta_id,
                $numero_factura,
                $subtotal,
                $iva,
                $datos['total'],
                $datos['cliente']['cliente_nombre'],
                $datos['cliente']['cliente_documento'],
                $datos['cliente']['cliente_direccion'],
                $datos['cliente']['cliente_telefono']
            ]);

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'venta_id' => $venta_id
            ]);

        } catch(Exception $e) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Crear venta con factura
if (isset($_POST['crear_venta'])) {
    try {
        $pdo->beginTransaction();

        // Crear la venta
        $stmt = $pdo->prepare("
            INSERT INTO ventas (usuario_id, fecha, total) 
            VALUES (?, NOW(), ?)
        ");
        $stmt->execute([$_SESSION['usuario_id'], $_POST['total']]);
        $venta_id = $pdo->lastInsertId();

        // Insertar detalles de la venta
        $stmt = $pdo->prepare("
            INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($_POST['productos'] as $producto) {
            $subtotal = $producto['cantidad'] * $producto['precio'];
            $stmt->execute([
                $venta_id,
                $producto['id'],
                $producto['cantidad'],
                $producto['precio'],
                $subtotal
            ]);

            // Actualizar stock
            $stmt2 = $pdo->prepare("
                UPDATE productos 
                SET stock = stock - ? 
                WHERE id = ?
            ");
            $stmt2->execute([$producto['cantidad'], $producto['id']]);
        }

        // Crear factura
        $numero_factura = date('Ymd') . str_pad($venta_id, 4, '0', STR_PAD_LEFT);
        $subtotal = $_POST['total'] / 1.19; // Asumiendo IVA del 19%
        $iva = $_POST['total'] - $subtotal;

        $stmt = $pdo->prepare("
            INSERT INTO facturas (
                venta_id, 
                numero_factura, 
                subtotal, 
                iva, 
                total, 
                cliente_nombre,
                cliente_documento,
                cliente_direccion,
                cliente_telefono
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $venta_id,
            $numero_factura,
            $subtotal,
            $iva,
            $_POST['total'],
            $_POST['cliente_nombre'],
            $_POST['cliente_documento'],
            $_POST['cliente_direccion'],
            $_POST['cliente_telefono']
        ]);

        $pdo->commit();
        $_SESSION['success'] = "Venta realizada y factura generada correctamente";
        
        // Redireccionar a la página de impresión de factura
        header("Location: ../vista/ventas/imprimir_factura.php?id=" . $venta_id);
        exit;

    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error al procesar la venta: " . $e->getMessage();
        header("Location: ../vista/ventas/nueva_venta.php");
        exit;
    }
}

// Validar stock antes de crear la venta
if (isset($_POST['crear_venta'])) {
    try {
        $pdo->beginTransaction();
        
        // Verificar stock disponible
        $error_stock = false;
        $productos = $_POST['productos'];
        
        foreach ($productos as $producto) {
            $stmt = $pdo->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->execute([$producto['id']]);
            $stock_actual = $stmt->fetchColumn();
            
            if ($stock_actual < $producto['cantidad']) {
                $error_stock = true;
                $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
                $stmt->execute([$producto['id']]);
                $nombre_producto = $stmt->fetchColumn();
                
                $_SESSION['error'] = "Stock insuficiente para el producto: " . $nombre_producto . 
                                   " (Disponible: " . $stock_actual . 
                                   ", Solicitado: " . $producto['cantidad'] . ")";
                break;
            }
        }

        if ($error_stock) {
            $pdo->rollBack();
            header("Location: ../vista/ventas/nueva_venta.php");
            exit;
        }

        // Si hay suficiente stock, continuar con la venta
        // Crear la venta
        $stmt = $pdo->prepare("
            INSERT INTO ventas (usuario_id, fecha, total) 
            VALUES (?, NOW(), ?)
        ");
        $stmt->execute([$_SESSION['usuario_id'], $_POST['total']]);
        $venta_id = $pdo->lastInsertId();

        // Insertar detalles de la venta
        $stmt = $pdo->prepare("
            INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($_POST['productos'] as $producto) {
            $subtotal = $producto['cantidad'] * $producto['precio'];
            $stmt->execute([
                $venta_id,
                $producto['id'],
                $producto['cantidad'],
                $producto['precio'],
                $subtotal
            ]);

            // Actualizar stock
            $stmt2 = $pdo->prepare("
                UPDATE productos 
                SET stock = stock - ? 
                WHERE id = ?
            ");
            $stmt2->execute([$producto['cantidad'], $producto['id']]);
        }

        // Crear factura
        $numero_factura = date('Ymd') . str_pad($venta_id, 4, '0', STR_PAD_LEFT);
        $subtotal = $_POST['total'] / 1.19; // Asumiendo IVA del 19%
        $iva = $_POST['total'] - $subtotal;

        $stmt = $pdo->prepare("
            INSERT INTO facturas (
                venta_id, 
                numero_factura, 
                subtotal, 
                iva, 
                total, 
                cliente_nombre,
                cliente_documento,
                cliente_direccion,
                cliente_telefono
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $venta_id,
            $numero_factura,
            $subtotal,
            $iva,
            $_POST['total'],
            $_POST['cliente_nombre'],
            $_POST['cliente_documento'],
            $_POST['cliente_direccion'],
            $_POST['cliente_telefono']
        ]);

        $pdo->commit();
        $_SESSION['success'] = "Venta realizada y factura generada correctamente";
        
        // Redireccionar a la página de impresión de factura
        header("Location: ../vista/ventas/imprimir_factura.php?id=" . $venta_id);
        exit;

    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error al procesar la venta: " . $e->getMessage();
        header("Location: ../vista/ventas/nueva_venta.php");
        exit;
    }
}

// Ver detalle de venta
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'detalle') {
    $venta_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.nombre, d.cantidad, d.precio_unitario, d.subtotal
            FROM detalle_venta d
            JOIN productos p ON d.producto_id = p.id
            WHERE d.venta_id = ?
        ");
        $stmt->execute([$venta_id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($detalles);
    } catch(PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>