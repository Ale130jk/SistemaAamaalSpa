<?php

ob_start();
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/auth.php';
    require_once __DIR__ . '/../config/Database.php';
    require_once __DIR__ . '/../config/helpers.php';
    require_once __DIR__ . '/../models/Auditoria.php';
    require_once __DIR__ . '/../models/Cliente.php';
    require_once __DIR__ . '/../models/Producto.php';
    require_once __DIR__ . '/../models/Reserva.php';
    require_once __DIR__ . '/../models/Pago.php';
    if (file_exists(__DIR__ . '/../models/Compra.php')) {
        require_once __DIR__ . '/../models/Compra.php';
    }
    require_login();
    
    $action = $_REQUEST['action'] ?? '';
    $rolUsuario = $_SESSION['rol'] ?? '';
    function convertirFechaHoraInteligente($input) {
    if (empty($input)) {
        return date('Y-m-d H:i:s');
    }
    
    $input = strtolower(trim($input));
    $fechaBase = date('Y-m-d');
    $horaFinal = '09:00:00'; 
    if (strpos($input, 'mañana') !== false || strpos($input, 'manana') !== false) {
        $fechaBase = date('Y-m-d', strtotime('+1 day'));
    } elseif (strpos($input, 'hoy') !== false) {
        $fechaBase = date('Y-m-d');
    } elseif (preg_match('/(lunes|martes|miercoles|jueves|viernes|sabado|domingo)/i', $input, $matches)) {
        $dia = $matches[1];
        $fechaBase = date('Y-m-d', strtotime("next $dia"));
    }

    if (preg_match('/(\d{1,2}):?(\d{2})?\s*(am|pm)?/i', $input, $matches)) {
        $hora = (int)$matches[1];
        $minutos = $matches[2] ?? '00';
        $periodo = strtolower($matches[3] ?? '');

        if ($periodo === 'pm' && $hora < 12) {
            $hora += 12;
        } elseif ($periodo === 'am' && $hora === 12) {
            $hora = 0;
        }
        
        $horaFinal = str_pad($hora, 2, '0', STR_PAD_LEFT) . ':' . $minutos . ':00';
    }
    
    return $fechaBase . ' ' . $horaFinal;
}

    if ($action !== 'verificar_acciones' && $rolUsuario !== 'admin') {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requiere rol admin.']);
        exit;
    }

    $db = (new Database())->getConnection();
    $auditoriaModel = new Auditoria($db);
    
    ob_clean();

    switch ($action) {
        
        case 'verificar_acciones':
            try {
                $data = $auditoriaModel->getAccionesPendientes();
                echo json_encode(['success' => true, 'data' => $data ?? []]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'data' => []]);
            }
            break;

        case 'confirmar_accion':
            $accion_id = (int)$_POST['id'];
            $admin_id = $_SESSION['user_id'];

            $accion = $auditoriaModel->obtenerAccionPorId($accion_id);
            if (!$accion || $accion['estado'] !== 'pendiente') {
                echo json_encode(['success' => false, 'message' => 'Acción ya procesada.']);
                exit;
            }

           $db->beginTransaction();
            try {
                $p = json_decode($accion['payload'], true);
                  error_log("🔍 DEBUG - Acción: " . $accion['accion_tipo']);
                error_log("🔍 DEBUG - Payload: " . json_encode($p, JSON_UNESCAPED_UNICODE));
                error_log("🔍 DEBUG - ID: " . ($p['id'] ?? 'NO TIENE'));
                error_log("🔍 DEBUG - Nombre: " . ($p['nombre_busqueda'] ?? $p['cliente'] ?? $p['nombre'] ?? 'NO TIENE'));
        
        $solicitante = $accion['solicitado_por'];
        $mensajeExito = "Acción ejecutada.";
                $solicitante = $accion['solicitado_por'];
                $mensajeExito = "Acción ejecutada.";
                $accionesBorrado = ['borrar_cliente', 'borrar_producto', 'borrar_reserva', 'anular_pago', 'anular_gasto'];
                $nombreObjetivoTemp = $p['nombre_busqueda'] ?? $p['nombre_actual'] ?? $p['nombre'] ?? $p['cliente'] ?? null;

                if (in_array($accion['accion_tipo'], $accionesBorrado) && empty($p['id']) && empty($nombreObjetivoTemp)) {
                    throw new Exception("Se requiere el ID o el nombre para eliminar/anular. Busca primero el registro.");
                }

                $nombreObjetivo = $p['nombre_busqueda'] ?? $p['nombre_actual'] ?? $p['nombre'] ?? $p['cliente'] ?? null;
                $datosNuevos = $p['nuevos_datos'] ?? $p;

                if (empty($p['id']) && $nombreObjetivo) {
                    
                    error_log("🔍 Búsqueda automática - Tipo: {$accion['accion_tipo']}, Nombre: $nombreObjetivo");
                    
                    if (strpos($accion['accion_tipo'], 'cliente') !== false) {
                        $stmt = $db->prepare("
                            SELECT id, nombre, telefono 
                            FROM clientes 
                            WHERE estado='activo' 
                            AND (LOWER(nombre) LIKE LOWER(?) OR telefono LIKE ?)
                            ORDER BY id DESC
                            LIMIT 5
                        ");
                        $searchTerm = "%" . trim($nombreObjetivo) . "%";
                        $stmt->execute([$searchTerm, $searchTerm]);
                        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($resultados) > 1) {
                            $opciones = [];
                            foreach ($resultados as $r) {
                                $opciones[] = "{$r['nombre']} (Tel: {$r['telefono']}, ID: {$r['id']})";
                            }
                            throw new Exception("Encontré " . count($resultados) . " clientes: " . implode(', ', $opciones) . ". Por favor, sé más específico con apellido o teléfono.");
                        } elseif (count($resultados) === 1) {
                            $p['id'] = $resultados[0]['id'];
                        } else {
                            throw new Exception("No encontré al cliente '$nombreObjetivo'. Verifica el nombre o teléfono.");
                        }
                    }

                  $accionesProductoBusqueda = ['actualizar_producto', 'borrar_producto', 'actualizar_stock'];

                    if (in_array($accion['accion_tipo'], $accionesProductoBusqueda)) {
                        
                        error_log("🔍 Búsqueda automática de producto: $nombreObjetivo"); 

                        $stmt = $db->prepare("
                            SELECT id, nombre, stock, precio_venta 
                            FROM productos 
                            WHERE estado='activo' 
                            AND LOWER(nombre) LIKE LOWER(?)
                            ORDER BY id DESC
                            LIMIT 5
                        ");
                        $searchTerm = "%" . trim($nombreObjetivo) . "%";
                        $stmt->execute([$searchTerm]);
                        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($resultados) > 1) {
                            $opciones = [];
                            foreach ($resultados as $r) {
                                $opciones[] = "{$r['nombre']} (Stock: {$r['stock']}, Precio: S/{$r['precio_venta']}, ID: {$r['id']})";
                            }
                            throw new Exception("Encontré " . count($resultados) . " productos: " . implode(', ', $opciones) . ". Sé más específico.");
                        } elseif (count($resultados) === 1) {
                            $foundId = $resultados[0]['id'];
                            $p['id'] = $foundId;
                            if (empty($p['producto_id'])) $p['producto_id'] = $foundId;
                        } else {
                            throw new Exception("No encontré el producto '$nombreObjetivo'. Verifica el nombre.");
                        }
                    }

                    if (in_array($accion['accion_tipo'], ['actualizar_reserva', 'cancelar_reserva', 'borrar_reserva'])) {
                        error_log("🔍 Buscando reservas de cliente: $nombreObjetivo");
                        
                        try {
                            $stmt = $db->prepare("
                                SELECT r.id, r.fecha_inicio, r.servicio_texto, c.nombre as cliente_nombre
                                FROM reservas r 
                                JOIN clientes c ON r.cliente_id = c.id
                                WHERE r.estado_reserva != 'cancelada'
                                AND LOWER(c.nombre) LIKE LOWER(?)
                                ORDER BY r.fecha_inicio DESC
                                LIMIT 10
                            ");
                            
                            $searchTerm = "%" . trim($nombreObjetivo) . "%";
                            $stmt->execute([$searchTerm]);
                            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            error_log("🔍 Reservas encontradas: " . count($resultados));
                            
                            if (count($resultados) === 1) {
                                $p['id'] = (int)$resultados[0]['id'];
                                error_log("✅ Usando reserva ID: " . $p['id']);
                                
                            } elseif (count($resultados) > 1) {
                                $lista = [];
                                foreach ($resultados as $r) {
                                    $fechaFormato = date('d/m H:i', strtotime($r['fecha_inicio']));
                                    $lista[] = "ID {$r['id']}: {$r['servicio_texto']} el {$fechaFormato}";
                                }
                                throw new Exception(
                                    "Encontré " . count($resultados) . " reservas de {$nombreObjetivo}: " . 
                                    implode(' | ', $lista) . 
                                    ". Por favor, dime el número de la reserva que deseas eliminar."
                                );
                                
                            } else {
                                throw new Exception("No encontré reservas activas de '$nombreObjetivo'. Verifica que el nombre sea correcto.");
                            }
                            
                        } catch (PDOException $e) {
                            error_log("❌ Error en búsqueda de reservas: " . $e->getMessage());
                            throw new Exception("Error buscando reservas: " . $e->getMessage());
                        }
                    }
                    if (strpos($accion['accion_tipo'], 'gasto') !== false && $accion['accion_tipo'] !== 'registrar_gasto') {
                        if (!empty($p['id'])) {
                        } elseif (!empty($nombreObjetivo)) {
                            $stmt = $db->prepare("
                                SELECT id, fecha, total, notas 
                                FROM compras 
                                WHERE estado='registrado' 
                                AND LOWER(notas) LIKE LOWER(?)
                                ORDER BY fecha DESC
                                LIMIT 5
                            ");
                            $searchTerm = "%" . trim($nombreObjetivo) . "%";
                            $stmt->execute([$searchTerm]);
                            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($resultados) > 1) {
                                $opciones = [];
                                foreach ($resultados as $r) {
                                    $opciones[] = "ID {$r['id']}: S/{$r['total']} - {$r['notas']} ({$r['fecha']})";
                                }
                                throw new Exception("Encontré " . count($resultados) . " gastos: " . implode(' | ', $opciones) . ". Indica el ID.");
                            } elseif (count($resultados) === 1) {
                                $p['id'] = $resultados[0]['id'];
                            } else {
                                throw new Exception("No encontré gastos con '$nombreObjetivo'.");
                            }
                        } else {
                            throw new Exception("Para anular un gasto, necesito el ID. Busca con 'muéstrame gastos'.");
                        }
                    }
                }
                if (strpos($accion['accion_tipo'], 'pago') !== false && $accion['accion_tipo'] !== 'registrar_pago') {
                        if (!empty($p['id'])) {
                            error_log("✅ Pago ya tiene ID: " . $p['id']);
                        } elseif (!empty($nombreObjetivo)) {
                            error_log("🔍Buscando pagos con término: $nombreObjetivo");
                            $stmt = $db->prepare("
                                SELECT p.id, p.monto, p.metodo_pago, p.estado_pago, p.fecha_pago, 
                                    c.nombre as cliente_nombre,
                                    CASE 
                                        WHEN p.reserva_id IS NOT NULL THEN CONCAT('Reserva #', p.reserva_id)
                                        ELSE 'Venta Directa'
                                    END as concepto
                                FROM pagos p 
                                LEFT JOIN clientes c ON p.cliente_id = c.id
                                WHERE p.estado_pago = 'pagado'
                                AND (
                                    LOWER(c.nombre) LIKE LOWER(?) 
                                    OR CAST(p.id AS CHAR) LIKE ?
                                )
                                ORDER BY p.fecha_pago DESC
                                LIMIT 10
                            ");
                            
                            $searchTerm = "%" . trim($nombreObjetivo) . "%";
                            $stmt->execute([$searchTerm, $searchTerm]);
                            
                            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            error_log("🔍 Pagos encontrados: " . count($resultados));
                            
                            if (count($resultados) > 1) {
                                $opciones = [];
                                foreach ($resultados as $r) {
                                    $fecha = date('d/m H:i', strtotime($r['fecha_pago']));
                                    $opciones[] = "ID #{$r['id']} | Cliente: {$r['cliente_nombre']} | S/{$r['monto']} | {$r['concepto']} | {$fecha}";
                                }
                                throw new Exception("Encontré " . count($resultados) . " pagos: " . implode(' | ', $opciones) . ". Indica el número del pago que deseas anular.");
                                
                            } elseif (count($resultados) === 1) {
                                $p['id'] = (int)$resultados[0]['id'];
                                error_log("✅Usando pago ID: " . $p['id']);
                                
                            } else {
                                throw new Exception("No encontré pagos activos con '$nombreObjetivo'. Busca con 'muéstrame pagos'.");
                            }
                            
                        } else {
                            throw new Exception("Para anular un pago, necesito el ID o el nombre del cliente. Busca con 'muéstrame pagos'.");
                        }
                    }
                switch ($accion['accion_tipo']) {

                   case 'crear_producto':
                        $nombre = $p['nombre'] ?? null;
                        if (!$nombre) throw new Exception("Falta el nombre para crear el producto.");
                        $sku = $p['sku'] ?? uniqid('PROD-');
                        $precioVenta = (float)($p['precio_venta'] ?? $p['precio'] ?? 0.00); 
                        $stock = (int)($p['stock'] ?? 0);                            
                        $categoria = $p['categoria'] ?? 'General';                   
                        (new Producto($db))->crear(
                            $sku, 
                            $nombre, 
                            $categoria,      
                            $precioVenta,
                            $stock,          
                            $solicitante     
                        );
                    $mensajeExito = "Producto '$nombre' ($categoria) creado exitosamente con stock $stock.";
                    break;

                    case 'actualizar_producto':
                        if (empty($p['id'])) throw new Exception("No se encontró el producto '$nombreObjetivo'.");
                        
                        $stmt = $db->prepare("SELECT * FROM productos WHERE id = ? AND estado='activo'");
                        $stmt->execute([$p['id']]);
                        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$actual) throw new Exception("El producto ya no existe o está inactivo.");
                        $n = !empty($datosNuevos['nombre']) ? $datosNuevos['nombre'] : $actual['nombre'];
                        $pr = isset($datosNuevos['precio_venta']) ? $datosNuevos['precio_venta'] : $actual['precio_venta'];
                        $st = isset($datosNuevos['stock']) ? $datosNuevos['stock'] : $actual['stock'];

                        $sql = "UPDATE productos SET nombre = :n, precio_venta = :p, stock = :s, modificado_por = :u WHERE id = :id";
                        $db->prepare($sql)->execute([
                            ':n' => $n, 
                            ':p' => $pr, 
                            ':s' => $st,
                            ':u' => $admin_id, 
                            ':id' => $p['id']
                        ]);
                        $mensajeExito = "Producto '$n' actualizado correctamente.";
                        break;

                    case 'actualizar_stock':
                        $prodId = $p['producto_id'] ?? ($p['id'] ?? null);
                        if (empty($prodId)) throw new Exception("Producto no encontrado.");
                        
                        $sql = "UPDATE productos SET stock = stock + :s, modificado_por = :u WHERE id = :id";
                        $db->prepare($sql)->execute([
                            ':s' => (int)$p['cantidad'], 
                            ':u' => $admin_id, 
                            ':id' => $prodId
                        ]);
                        $mensajeExito = "Stock ajustado en " . $p['cantidad'] . " unidades.";
                        break;
                    case 'borrar_producto':
                        if (empty($p['id'])) throw new Exception("Producto no encontrado para eliminar.");
                        $stmt = $db->prepare("SELECT nombre FROM productos WHERE id = ?");
                        $stmt->execute([$p['id']]);
                        $prodNombre = $stmt->fetchColumn(); 
                        
                        if (!$prodNombre) throw new Exception("El producto no existe.");
                        
                        (new Producto($db))->eliminarLogico($p['id'], $admin_id);
                        $mensajeExito = "Producto '$prodNombre' eliminado correctamente.";
                        break;
                    case 'crear_cliente':
                        $nombreC = $p['nombre'] ?? null;
                        if (!$nombreC) throw new Exception("Falta el nombre del cliente.");
                        
                        (new Cliente($db))->crear($nombreC, $p['telefono'] ?? '', $solicitante);
                        $mensajeExito = "Cliente '$nombreC' creado exitosamente.";
                        break;
                        
                    case 'actualizar_cliente':
                        if (empty($p['id'])) throw new Exception("No encontré al cliente '$nombreObjetivo'.");
                        $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ? AND estado='activo'");
                        $stmt->execute([$p['id']]);
                        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$actual) throw new Exception("El cliente ya no existe o está inactivo.");

                        $nuevoNombre = !empty($datosNuevos['nombre']) ? $datosNuevos['nombre'] : $actual['nombre'];
                        $nuevoTelefono = !empty($datosNuevos['telefono']) ? $datosNuevos['telefono'] : $actual['telefono'];

                        (new Cliente($db))->actualizar($p['id'], $nuevoNombre, $nuevoTelefono, $admin_id);
                        $mensajeExito = "Cliente actualizado: $nuevoNombre" . ($nuevoTelefono !== $actual['telefono'] ? " (Tel: $nuevoTelefono)" : "") . ".";
                        break;
                        
                   case 'borrar_cliente':
                        if (empty($p['id'])) throw new Exception("Cliente no encontrado para eliminar.");
                        $stmt = $db->prepare("SELECT nombre FROM clientes WHERE id = ?");
                        $stmt->execute([$p['id']]);
                        $cliNombre = $stmt->fetchColumn();
                        
                        if (!$cliNombre) throw new Exception("El cliente no existe.");
                        
                        (new Cliente($db))->eliminarLogico($p['id'], $admin_id);
                        $mensajeExito = "Cliente '$cliNombre' eliminado correctamente.";
                        break;

                  case 'crear_reserva':
                        $nombreBusqueda = trim($p['cliente'] ?? $p['cliente_nombre'] ?? '');
                        $clienteId = $p['cliente_id'] ?? null;

                        if (empty($clienteId) && !empty($nombreBusqueda)) {
                            $stmt = $db->prepare("
                                SELECT id, nombre, telefono 
                                FROM clientes 
                                WHERE estado='activo' 
                                AND (LOWER(nombre) LIKE LOWER(?) OR telefono LIKE ?)
                                LIMIT 5
                            ");
                            
                            $termino = "%" . $nombreBusqueda . "%";
                            $stmt->execute([$termino, $termino]); 
                            
                            $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            $total = count($coincidencias);

                            if ($total === 1) {
                                $clienteId = $coincidencias[0]['id'];
                            } elseif ($total > 1) {
                                foreach ($coincidencias as $c) {
                                    if (strcasecmp($c['nombre'], $nombreBusqueda) === 0) {
                                        $clienteId = $c['id'];
                                        break;
                                    }
                                }
                                if (empty($clienteId)) {
                                    $nombres = implode(', ', array_column($coincidencias, 'nombre'));
                                    throw new Exception("Encontré varios clientes: $nombres. Sé más específico.");
                                }
                            } else {
                                throw new Exception("No encontré al cliente '$nombreBusqueda'.");
                            }
                        }

                        if (empty($clienteId)) {
                            throw new Exception("No se identificó al cliente. ¿Está registrado?");
                        }
                        $reservaModel = new Reserva($db);
                        $fechaRaw = $p['fecha_inicio'] ?? $p['fecha'] ?? null;
                        $fechaFinal = convertirFechaHoraInteligente($fechaRaw); 
                        error_log("🕐 Fecha original IA: " . ($fechaRaw ?? 'NULL'));
                        error_log("🕐 Fecha convertida: " . ($fechaFinal ?? 'NULL'));

                        if ($fechaFinal === null) {
                            throw new Exception("Falta especificar la hora de la reserva.");
                        }
                        
                        $servicioTxt = $p['servicio_texto'] ?? $p['servicio'] ?? 'Consulta General';
                        $precio = (float)($p['precio'] ?? 0);
                        $duracion = (int)($p['duracion'] ?? $p['duracion_min'] ?? 60);
                        $reservaModel->crear(
                            $clienteId, 
                            null, 
                            $servicioTxt, 
                            $fechaFinal, 
                            $duracion, 
                            $precio, 
                            $solicitante
                        );
                        $stmt = $db->prepare("SELECT nombre FROM clientes WHERE id = ?");
                        $stmt->execute([$clienteId]);
                        $nomReal = $stmt->fetchColumn();

                        $mensajeExito = "✅ Reserva creada para <strong>$nomReal</strong> ($servicioTxt) el " . date('d/m/Y H:i', strtotime($fechaFinal));
                        break;
                    
                    case 'actualizar_reserva':
                        if (empty($p['id'])) throw new Exception("No se encontró la reserva.");
                        $stmt = $db->prepare("SELECT * FROM reservas WHERE id = ?");
                        $stmt->execute([$p['id']]);
                        $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$actual) throw new Exception("La reserva no existe.");

                        $clienteId = $p['cliente_id'] ?? $actual['cliente_id'];
                        $servicio = $p['servicio_texto'] ?? $actual['servicio_texto'];
                        $fecha = $p['fecha_inicio'] ?? $actual['fecha_inicio'];
                        $precio = isset($p['precio']) ? (float)$p['precio'] : (float)$actual['precio'];
                        $duracion = isset($p['duracion_min']) ? (int)$p['duracion_min'] : (int)$actual['duracion_minutos'];
                        
                        (new Reserva($db))->actualizar(
                            $p['id'], 
                            $clienteId, 
                            $servicio, 
                            $fecha, 
                            $precio, 
                            $duracion, 
                            $admin_id
                        );
                        $mensajeExito = "Reserva #" . $p['id'] . " actualizada correctamente.";
                        break;
                    
                    case 'cancelar_reserva':
                        if (empty($p['id'])) throw new Exception("No se encontró la reserva para cancelar.");
                        (new Reserva($db))->cancelar($p['id'], $admin_id);
                        $mensajeExito = "Reserva #" . $p['id'] . " cancelada.";
                        break;
                    
                    case 'borrar_reserva':
                            if (empty($p['id'])) {
                                throw new Exception("No se encontró la reserva para eliminar. Se requiere el ID.");
                                }
                                
                                try {
                                    $stmt = $db->prepare("
                                        SELECT r.id, c.nombre as cliente, r.servicio_texto 
                                        FROM reservas r 
                                        JOIN clientes c ON r.cliente_id = c.id 
                                        WHERE r.id = ?
                                    ");
                                    $stmt->execute([$p['id']]);
                                    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if (!$reserva) {
                                        throw new Exception("La reserva #{$p['id']} no existe o ya fue eliminada.");
                                    }
                                    
                                    error_log("🗑️Eliminando reserva: " . json_encode($reserva));

                                    $stmtDelete = $db->prepare("DELETE FROM reservas WHERE id = ?");
                                    $stmtDelete->execute([$p['id']]);
                                    
                                    $mensajeExito = "✅ Reserva eliminada: {$reserva['cliente']} - {$reserva['servicio_texto']}";
                                    error_log($mensajeExito);
                                    
                                } catch (PDOException $e) {
                                    error_log("❌ Error eliminando reserva: " . $e->getMessage());
                                    throw new Exception("Error al eliminar la reserva: " . $e->getMessage());
                                }
                                break;
                   case 'registrar_pago':
                        if (empty($p['monto'])) throw new Exception("Falta el monto del pago.");

                        $reservaId = (int)($p['reserva_id'] ?? 0);
                        $monto = (float)$p['monto'];
                        $concepto = $p['concepto'] ?? $p['servicio'] ?? 'Venta de Producto'; 
                        $cliId = 0; 
                        $mensajeDetalle = "";
                        if ($reservaId > 0) {
                            $res = (new Reserva($db))->obtenerPorId($reservaId);
                            if (!$res) throw new Exception("La reserva #$reservaId no existe.");
                            $cliId = $res['cliente_id'];
                            $concepto = $res['servicio_texto']; 
                            $mensajeDetalle = "para reserva #$reservaId ({$concepto})";
                            $reservaId = (int)$reservaId; 
                        } else { 
                            $nombreCliente = $p['cliente'] ?? $p['cliente_nombre'] ?? null;
                            $cliId = 1; 
                            if (!empty($nombreCliente)) {
                                $stmt = $db->prepare("SELECT id, nombre FROM clientes WHERE LOWER(nombre) LIKE LOWER(?) AND estado='activo' LIMIT 1");
                                $stmt->execute(["%" . trim($nombreCliente) . "%"]);
                                $clienteResult = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($clienteResult) {
                                    $cliId = (int)$clienteResult['id'];
                                    $mensajeDetalle = "por $concepto para {$clienteResult['nombre']}";
                                } else {
                                    $mensajeDetalle = "por $concepto (Cliente No Encontrado)";
                                }
                            } else {
                                $mensajeDetalle = "por $concepto (Venta General)";
                            }
                            
                            $reservaId = null; 
                        }

                        (new Pago($db))->registrar(
                            $reservaId, 
                            $cliId, 
                            $monto, 
                            $p['metodo_pago'] ?? 'efectivo', 
                            'pagado', 
                            $solicitante
                        );
                        $mensajeExito = "✅ Pago de S/ {$monto} registrado $mensajeDetalle.";
                        break;
                    
                    case 'anular_pago':
                        if (empty($p['id'])) throw new Exception("Falta el ID del pago para anular.");
                        $stmt = $db->prepare("SELECT id, monto FROM pagos WHERE id = ? AND estado_pago='pagado'");
                        $stmt->execute([$p['id']]);
                        $pago = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$pago) throw new Exception("El pago #{$p['id']} no existe o ya fue anulado.");
                        
                        $db->prepare("UPDATE pagos SET estado_pago = 'reembolsado' WHERE id = ?")->execute([$p['id']]);
                        $mensajeExito = "Pago #{$p['id']} de S/ {$pago['monto']} anulado correctamente.";
                        break;
                    case 'registrar_gasto':
                        if (empty($p['monto'])) throw new Exception("El monto del gasto es obligatorio.");
                        
                        $monto = (float)$p['monto'];
                        $concepto = $p['concepto'] ?? $p['notas'] ?? 'Gasto vario';
                        
                        $sql = "INSERT INTO compras (fecha, total, notas, estado, creado_por) VALUES (NOW(), ?, ?, 'registrado', ?)";
                        $db->prepare($sql)->execute([$monto, $concepto, $solicitante]);
                        $mensajeExito = "Gasto de S/ $monto registrado: '$concepto'.";
                        break;

                    case 'anular_gasto':
                        if (empty($p['id'])) throw new Exception("Se requiere ID del gasto para anular.");
                        $stmt = $db->prepare("SELECT id, total, notas FROM compras WHERE id = ? AND estado='registrado'");
                        $stmt->execute([$p['id']]);
                        $gasto = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$gasto) throw new Exception("El gasto #{$p['id']} no existe o ya fue anulado.");
                        
                        $db->prepare("UPDATE compras SET estado = 'anulado' WHERE id = ?")->execute([$p['id']]);
                        $mensajeExito = "Gasto #{$p['id']} de S/ {$gasto['total']} anulado: '{$gasto['notas']}'.";
                        break;

                    default:
                        throw new Exception("Acción desconocida: {$accion['accion_tipo']}");
                }
                $auditoriaModel->confirmarAccion($accion_id, $admin_id);
                $db->commit();
                    $requiereRecarga = [
                    'crear_reserva' => 'calendario',
                    'actualizar_reserva' => 'calendario',
                    'cancelar_reserva' => 'calendario',
                    'borrar_reserva' => 'calendario',
                    'crear_cliente' => 'clientes',
                    'actualizar_cliente' => 'clientes',
                    'borrar_cliente' => 'clientes',
                    'crear_producto' => 'inventario',
                    'actualizar_producto' => 'inventario',
                    'actualizar_stock' => 'inventario',
                    'borrar_producto' => 'inventario',
                    'registrar_pago' => 'pagos',
                    'anular_pago' => 'pagos',
                    'registrar_gasto' => 'egresos',
                    'anular_gasto' => 'egresos'
                ];

                $vistaARecargar = $requiereRecarga[$accion['accion_tipo']] ?? null;

                echo json_encode([
                    'success' => true, 
                    'message' => $mensajeExito,
                    'recargar_vista' => $vistaARecargar 
                ]);

            } catch (Exception $e) {
                $db->rollBack();
                $db->prepare("UPDATE acciones_log SET estado = 'rechazado', ejecutado_en = NOW() WHERE id = ?")->execute([$accion_id]);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'rechazar_accion':
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE acciones_log SET estado = 'rechazado', ejecutado_en = NOW() WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => "Acción rechazada correctamente."]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Acción no válida."]);
    }

} catch (Throwable $e) {
    ob_clean();
    http_response_code(200); 
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>