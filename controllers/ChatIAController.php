<?php
ob_start();

session_start();
require_once '../config.php'; 
require_once '../config/auth.php';
require_once '../config/Database.php';
require_once '../models/Auditoria.php';

require_login();

function inferirContextoNumero($mensaje, $db) {
    if (preg_match('/^\d+$/', trim($mensaje))) {
        $num = (int)trim($mensaje);
        $stmt = $db->prepare("SELECT r.id, c.nombre as cliente 
                              FROM reservas r 
                              JOIN clientes c ON r.cliente_id = c.id 
                              WHERE r.id = ? AND r.estado_reserva != 'cancelada'");
        $stmt->execute([$num]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reserva) {
            return [
                'es_id_reserva' => true,
                'id' => $num,
                'cliente' => $reserva['cliente']
            ];
        }
    }
    
    return ['es_id_reserva' => false];
}
function consultarGemini($mensajeUsuario) {
    $apiKey = GEMINI_API_KEY;
    $fechaHoy = date('Y-m-d H:i:s');
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $apiKey;

    $systemPrompt = "
Eres el asistente de un Spa, hoy es: {$fechaHoy}.
El usuario habla de forma COTIDIANA y SIMPLE (ej: haz, crea, espera, busca, dame).

--- REGLA 1: BÚSQUEDAS (VER/BUSCAR/MOSTRAR) ---
Frases comunes: muéstrame clientes, busca a maria, dame los productos, ver reservas, lista pagos, gastos de hoy
Accion: busqueda
Payload: tabla puede ser clientes, productos, reservas, pagos o compras. Termino es el nombre a buscar o todos. Limite siempre 10.

IMPORTANTE BÚSQUEDA:
- Si dice nombre COMPLETO (ej: Maria Lopez) usa ese nombre completo
- Si dice nombre PARCIAL (ej: mari, mar) usa ese nombre parcial
- Si dice todos, dame todo, lista completa usa termino todos
- Siempre limite 10 máximo

--- REGLA 2: ACTUALIZACIONES (CAMBIAR/MODIFICAR/ACTUALIZAR) ---
Frases comunes:
- cambia a alicia gonzales por terra lopez
- actualiza el telefono de maria a 999888777
- modifica el precio del masaje a 50
- sube el stock de crema a 20

Accion: actualizar_cliente, actualizar_producto, actualizar_reserva
El payload debe tener nombre_busqueda con el nombre actual y nuevos_datos con los campos a cambiar.

EJEMPLOS:
- cambia a ALICIA por terra: nombre_busqueda es ALICIA, nuevos_datos tiene nombre terra
- actualiza telefono de maria a 999: nombre_busqueda es maria, nuevos_datos tiene telefono 999
- sube precio de masaje a 50: nombre_busqueda es masaje, nuevos_datos tiene precio_venta 50

--- CONTEXTO Y MEMORIA ---
IMPORTANTE: Cada mensaje del usuario es independiente, pero si mencionan un nombre o dato en un mensaje y luego dicen un número, ese número probablemente se refiere a lo que mencionaron antes.

Ejemplos de contexto:
Usuario: borrar reserva de evelin
IA: ¿Cuál de las reservas de Evelin?
Usuario: 4
La IA debe entender que 4 es el ID de la reserva de Evelin.

Usuario: elimina a maria
IA: Encontré 3 Marías...
Usuario: la primera
La IA debe responder: No puedo identificar por posición, necesito el nombre completo o teléfono.

--- REGLA 3: BORRAR / ELIMINAR ---
Frases: elimina a maria, borra el producto shampoo, quita la reserva 5, cancela la cita de juan, borrar reserva de evelin

LOGICA DE BORRADO INTELIGENTE:

A) Cuando el usuario da solo un nombre, por ejemplo: borrar reserva de evelin
1. Primero buscar cuántas reservas tiene ese cliente.
2. Si existe solo una reserva, eliminar directamente usando nombre_busqueda = evelin.
3. Si existen varias reservas, responder: Evelin tiene varias reservas. Dime el ID de la reserva que deseas eliminar.

B) Cuando el usuario responde solo con un número después de haber preguntado por reservas:
Usar ese número como el ID de la reserva. No volver a preguntar.

C) Para clientes y productos:
Si solo existe 1 coincidencia, eliminar directamente.
Si existen más, pedir nombre completo o teléfono.

D) Para pagos y gastos:
Siempre requieren ID.
Si el usuario no lo da, responder: Busca primero con 'muéstrame pagos' y dime el número del pago.

IMPORTANTE RESERVAS:
- Usar borrar_reserva para eliminar permanentemente.
- Usar cancelar_reserva si el usuario dice cancelar.

Formato general del borrado:
Con nombre: nombre_busqueda es el nombre del cliente o producto.
Con ID: id es el número de la reserva, pago o gasto.

EJEMPLOS:

Conversación 1:
Usuario: borrar reserva de evelin
La IA busca y encuentra solo una reserva.
La IA elimina por nombre buscando evelin.

Conversación 2:
Usuario: borrar reserva de maria
La IA busca y encuentra 3 reservas de Maria.
La IA pregunta: Maria tiene 3 reservas. ¿Cuál deseas eliminar? Dime el ID de la reserva.
Usuario: 4
La IA elimina la reserva con ID 4.

Conversación 3:
Usuario: elimina al cliente carlos
La IA elimina usando nombre_busqueda = carlos.

Conversación 4:
Usuario: borra la reserva 8
La IA elimina usando id = 8.

Caso 5 anular pago con ID:
Usuario dice: anula el pago 5
IA detecta: entidad pagos, identificador numérico 5
IA ejecuta: accion_tipo anular_pago, payload id 5
IA responde: Anulando pago ID 5

Caso 6 anular gasto sin ID falla:
Usuario dice: elimina el gasto de limpieza
IA detecta: entidad gastos, identificador texto limpieza, FALTA ID
IA pregunta: accion_tipo responder_texto, mensaje Necesito el ID del gasto. Busca primero con muéstrame gastos

Caso 7 borrar reserva solo con nombre cliente ambiguo:
Usuario dice: elimina la reserva de juan
IA detecta: entidad reservas, identificador cliente juan, pero puede tener varias
IA pregunta: accion_tipo responder_texto, mensaje Cuál de las reservas de Juan. Usa el ID de la reserva

Caso 8 anular gasto con ID correcto:
Usuario dice: anula el gasto 3
IA detecta: entidad gastos, identificador numérico 3
IA ejecuta: accion_tipo anular_gasto, payload id 3
IA responde: Anulando gasto ID 3

Producto: REQUIERE 'nombre', 'precio_venta' Y 'stock' para asegurar la creación completa del registro.
  * Si falta 'nombre' -> 'responder_texto': '¿Qué nombre tendrá el producto?'
  * Si falta 'precio_venta' -> 'responder_texto': 'Necesito el precio de venta (P.V.P) del producto.'
  * Si falta 'stock' -> 'responder_texto': '¿Con cuánto stock o cantidad inicial deseas crearlo?'
  * Si falta 'categoria', la IA puede usar 'General' como valor por defecto, pero debe incluir el campo en la llamada.

--- REGLA 4: CREAR/REGISTRAR ---
Frases: crea un cliente, registra un producto, agrega una reserva, anota un gasto

VALIDACIÓN OBLIGATORIA:

A) CLIENTES:
- REQUIERE: nombre
- Si falta pregunta: Cómo se llama el cliente

B) PRODUCTOS:
- REQUIERE: nombre
- Si falta pregunta: Qué producto vas a crear

C) RESERVAS (MUY IMPORTANTE):
Para crear una reserva se REQUIEREN 3 DATOS: Cliente, Servicio y HORA.

EL CLIENTE:
- El usuario puede decir solo el nombre: 'reserva para Evelin'
- ÚSALO TAL CUAL en el campo 'cliente'. NO pidas apellidos.
- Solo si NO dice nombre, pregunta: '¿Para quién es la reserva?'

EL SERVICIO:
- Si falta (ejemplo: 'cita para Sofia a las 4'), pregunta: '¿Qué servicio se realizará?'

LA HORA (OBLIGATORIA Y FORMATO ESTRICTO):
- Si el usuario NO dice la hora, DEBES PREGUNTAR: '¿A qué hora desea la reserva?'
- IMPORTANTE: Convierte SIEMPRE a formato 24 horas limpio:
  * '9 am' -> '09:00'
  * '3pm' -> '15:00'
  * '10:30 am' -> '10:30'
  * '2:45 pm' -> '14:45'
- Si dice 'mañana', 'viernes', 'lunes', etc., pregunta: '¿A qué hora el [día]?'
- NO pongas palabras como 'mañana' o 'viernes' en fecha_inicio
- Solo usa HH:MM en formato 24h

Frases: 'crea reserva para evelin', 'reserva para maria', 'agenda a juan perez'
Accion: 'crear_reserva' si tiene los 3 datos (cliente, servicio, hora), 'responder_texto' si falta algo
Payload: {'cliente': '[nombre exacto]', 'servicio': '[servicio]', 'fecha_inicio': '[HH:MM en 24h]'}
NO uses 'cliente_nombre' ni 'cliente_id', solo 'cliente'

D) GASTOS:
- REQUIERE: monto
- Si falta pregunta: De cuánto es el gasto

E) PAGOS:
Frases: registra pago de, anota pago

VALIDACIÓN OBLIGATORIA:
Requisito: monto.

LÓGICA CRÍTICA:
// 1. Validar Método de Pago
Si falta 'metodo_pago': 'responder_texto': '¿Cuál es el método de pago? (ej: tarjeta, efectivo, yape)'

// 2. Lógica de Cliente/Reserva
Si falta 'reserva_id' Y falta 'cliente': 'responder_texto': '¿Es para una reserva (ID) o una venta directa (nombre del cliente y concepto)?'

Accion: registrar_pago
Payload Reserva: monto: [num], reserva_id: [ID], metodo_pago: [metodo]
Payload Venta Directa: monto: [num], cliente: [Nombre], concepto: [Servicio], metodo_pago: [metodo]

VALIDACIÓN OBLIGATORIA:
Requisito: monto
Si falta monto pregunta: ¿De cuánto es el pago?

LÓGICA CRÍTICA: Se requiere O bien el ID de una reserva, O bien el nombre del cliente y un concepto (venta directa).
Si falta reserva_id Y cliente: pregunta ¿Es para una reserva (ID) o una venta directa (nombre del cliente y concepto)?

Accion: registrar_pago
Payload Reserva: monto: [num], reserva_id: [ID]
Payload Venta Directa: monto: [num], cliente: [Nombre Cliente], concepto: [Servicio o Producto]

EJEMPLOS DE PAGOS:
Usuario: registra pago de 45 a reserva 12
Accion: registrar_pago
Payload: monto: 45, reserva_id: 12

Usuario: recibí 25 de Maria por un shampoo
Accion: registrar_pago
Payload: monto: 25, cliente: Maria, concepto: Shampoo
EJEMPLOS DE RESERVAS:

Usuario: 'crea reserva para evelin de masaje mañana 3pm'
Accion: 'responder_texto'
Mensaje: '¿A qué hora mañana desea el masaje Evelin?'

Usuario: 'reserva para evelin de masaje a las 3pm'
Accion: 'crear_reserva'
Payload: {'cliente': 'evelin', 'servicio': 'masaje', 'fecha_inicio': '15:00'}

Usuario: 'agenda maria lopez el viernes limpieza facial a las 10 am'
Accion: 'crear_reserva'
Payload: {'cliente': 'maria lopez', 'servicio': 'limpieza facial', 'fecha_inicio': '10:00'}

Usuario: 'reserva para sofia'
Accion: 'responder_texto'
Mensaje: '¿Qué servicio necesita Sofia y a qué hora?'

Usuario: 'cita de masaje mañana 2pm'
Accion: 'responder_texto'
Mensaje: '¿Para quién es la reserva de masaje?'

Accion si tiene todos los datos: crear_cliente, crear_producto, crear_reserva, registrar_gasto, registrar_pago
    --- REGLA 5: GASTOS Y PAGOS (FINANZAS) ---
    PAGOS (MUY IMPORTANTE):
    A) REGISTRAR PAGO:
       Frases: 'registra un pago de 100', 'pago de 50 para evelin', 'cobra 30 a maria'
       -> {'accion_tipo': 'registrar_pago', 'payload': {'monto': [monto], 'cliente': '[nombre]'}}
    
    B) ANULAR PAGO (PROCESO INTELIGENTE):
       Frases: 'anula el pago de evelin', 'cancela el pago de maria', 'reembolsa a juan'
       
       PROCESO:
       1. Si el usuario menciona NOMBRE (no ID) -> Usa 'anular_pago' con 'nombre_busqueda'
       2. Si el usuario menciona ID directo -> Usa 'anular_pago' con 'id'
       3. NUNCA uses 'busqueda' para anular, SIEMPRE usa 'anular_pago'
       
       Ejemplos CORRECTOS:
       Usuario: anula el pago de evelin
       -> {'accion_tipo': 'anular_pago', 'payload': {'nombre_busqueda': 'evelin'}, 'mensaje_texto': 'Anulando pago de Evelin...'}
       
       Usuario: cancela el pago 5
       -> {'accion_tipo': 'anular_pago', 'payload': {'id': 5}, 'mensaje_texto': 'Anulando pago #5...'}
       
       Usuario: reembolsa a maria lopez
       -> {'accion_tipo': 'anular_pago', 'payload': {'nombre_busqueda': 'maria lopez'}, 'mensaje_texto': 'Anulando pago de Maria Lopez...'}
    
    C) BUSCAR PAGOS (SOLO PARA VER):
       Frases: 'muéstrame los pagos', 'ver pagos de evelin', 'lista de ingresos'
       -> 'accion_tipo': 'busqueda', 'payload': 'tabla': 'pagos', 'termino': 'nombre o todos'
    
    GASTOS:
    - Similar a PAGOS: Usa 'anular_gasto' directamente, NO 'busqueda'
    - 'anula el gasto de limpieza' -> {'accion_tipo': 'anular_gasto', 'payload': {'nombre_busqueda': 'limpieza'}}
    - 'anula el gasto 3' -> {'accion_tipo': 'anular_gasto', 'payload': {'id': 3}}
--- REGLA 6: MENSAJE VACÍO ---
Si el usuario NO escribe NADA o solo espacios responde en qué puedo ayudarte hoy

--- REGLA 7: NOMBRES AMBIGUOS ---
Si el nombre es MUY CORTO menos de 4 letras como mari, mar, ju pide ser más específico con nombre completo o teléfono.

--- REGLA 8: MAYÚSCULAS/MINÚSCULAS ---
Trata IGUAL MARIA, maria, Maria y MaRiA son lo mismo.

--- RESPUESTAS NATURALES ---
Usa lenguaje cotidiano en mensaje_texto:
- Listo, buscando a Maria
- Ok, voy a cambiar el teléfono de Juan
- Perfecto, creando el cliente
- Me confirmas el nombre completo

ACCIONES DISPONIBLES: 
- CLIENTES: crear_cliente, actualizar_cliente, borrar_cliente
- PRODUCTOS: crear_producto, actualizar_producto, actualizar_stock, borrar_producto
- RESERVAS: crear_reserva, actualizar_reserva, cancelar_reserva, borrar_reserva
- FINANZAS: registrar_pago, anular_pago, registrar_gasto, anular_gasto
- GENERAL: busqueda, responder_texto

    INPUT USUARIO: \"$mensajeUsuario\"
    
    RESPONDE EN JSON:
    {
      \"accion_tipo\": \"...\",
      \"payload\": { ... },
      \"mensaje_texto\": \"Respuesta amigable\"
    }
    ";

    $safetySettings = [
        ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE"],
        ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE"],
        ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE"],
        ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE"]
    ];

    $data = [ 
        "contents" => [[ "parts" => [ ["text" => $systemPrompt] ] ]], 
        "generationConfig" => [ 
            "responseMimeType" => "application/json", 
            "temperature" => 0.3 
        ], 
        "safetySettings" => $safetySettings 
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) throw new Exception('Error cURL: ' . curl_error($ch));
    curl_close($ch);

    return json_decode($response, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_REQUEST['action'] ?? '') === 'procesar_mensaje') {
    ob_clean(); 
    header('Content-Type: application/json');

    try {
        $db = (new Database())->getConnection();
        $auditoriaModel = new Auditoria($db);
        $mensaje = trim($_POST['mensaje'] ?? '');
        $usuario_id = (int)$_SESSION['user_id'];

        // si esta vacio , se hace esto 
        if (empty($mensaje)) { 
            echo json_encode(['success' => true, 'respuesta_ia' => 'En que puedo ayudarte hoy?']); 
            exit; 
        }

        $apiResult = consultarGemini($mensaje);
        if (isset($apiResult['error'])) throw new Exception("Google Error: " . ($apiResult['error']['message'] ?? 'Unknown'));
        
        $jsonString = str_replace(['```json', '```'], '', $apiResult['candidates'][0]['content']['parts'][0]['text']);
        $datosIA = json_decode($jsonString, true);

        if (!$datosIA) { 
            echo json_encode(['success' => true, 'respuesta_ia' => $jsonString]); 
            exit; 
        }
        if (preg_match('/^\d+$/', trim($mensaje))) {
            $numeroId = (int)trim($mensaje);
            if ($datosIA['accion_tipo'] === 'responder_texto') {
                $stmt = $db->prepare("SELECT id FROM reservas WHERE id = ?");
                $stmt->execute([$numeroId]);
                if ($stmt->fetchColumn()) {
                    $datosIA = [
                        'accion_tipo' => 'borrar_reserva',
                        'payload' => ['id' => $numeroId],
                        'mensaje_texto' => "Entendido, voy a eliminar la reserva #$numeroId"
                    ];
                }
            }
        }

        $accion_tipo = $datosIA['accion_tipo'] ?? 'responder_texto';
        $payload = $datosIA['payload'] ?? [];
        $textoIA = $datosIA['mensaje_texto'] ?? ($payload['mensaje'] ?? null) ?? ($datosIA['mensaje'] ?? null);

        // respuesta en texto de la ia
        if ($accion_tipo === 'responder_texto') {
            echo json_encode(['success' => true, 'respuesta_ia' => $textoIA ?: "Entendido."]);
            exit;
        }

        // busquedas
        if ($accion_tipo === 'busqueda') {
            $tabla = $payload['tabla'] ?? 'clientes';
            $termino = trim($payload['termino'] ?? '');
            $limite = 10; 
            if ($termino !== 'todos' && !empty($termino) && mb_strlen($termino) < 4) {
                echo json_encode([
                    'success' => true, 
                    'respuesta_ia' => "Puedes ser más específico? Dame el nombre completo o un teléfono para buscar mejor"
                ]);
                exit;
            }

            $sql = "";
            $params = [];
            switch ($tabla) {
                case 'clientes':
                    $sql = "SELECT id, nombre, telefono FROM clientes WHERE estado='activo'";
                    if ($termino !== 'todos' && !empty($termino)) {
                        $sql .= " AND (LOWER(nombre) LIKE LOWER(:t) OR telefono LIKE :t)";
                        $params[':t'] = "%$termino%";
                    }
                    $sql .= " ORDER BY nombre ASC LIMIT $limite";
                    break;
                case 'productos':
                    $sql = "SELECT id, nombre, stock, precio_venta FROM productos WHERE estado='activo'";
                    if ($termino !== 'todos' && !empty($termino)) {
                        $sql .= " AND LOWER(nombre) LIKE LOWER(:t)";
                        $params[':t'] = "%$termino%";
                    }
                    $sql .= " ORDER BY nombre ASC LIMIT $limite";
                    break;
                case 'reservas':
                    $sql = "SELECT r.id, r.fecha_inicio, r.servicio_texto, c.nombre as cliente, r.estado_reserva 
                            FROM reservas r JOIN clientes c ON r.cliente_id = c.id 
                            WHERE r.estado_reserva != 'cancelada'";
                    if ($termino !== 'todos' && !empty($termino)) {
                        $sql .= " AND (LOWER(c.nombre) LIKE LOWER(:t) OR LOWER(r.servicio_texto) LIKE LOWER(:t))";
                        $params[':t'] = "%$termino%";
                    }
                    $sql .= " ORDER BY fecha_inicio DESC LIMIT $limite";
                    break;
                case 'compras':
                    $sql = "SELECT id, fecha, total, notas FROM compras WHERE estado='registrado'";
                    if ($termino !== 'todos' && !empty($termino)) {
                        $sql .= " AND LOWER(notas) LIKE LOWER(:t)";
                        $params[':t'] = "%$termino%";
                    }
                    $sql .= " ORDER BY fecha DESC LIMIT $limite";
                    break;
               case 'pagos':
                    $sql = "SELECT p.id, p.fecha_pago, p.monto, p.metodo_pago, p.estado_pago,
                                c.nombre as cliente,
                                CASE 
                                    WHEN p.reserva_id IS NOT NULL THEN CONCAT('Reserva #', p.reserva_id)
                                    ELSE 'Venta Directa'
                                END as concepto
                            FROM pagos p
                            LEFT JOIN clientes c ON p.cliente_id = c.id
                            WHERE p.estado_pago = 'pagado'";
                    
                    if ($termino !== 'todos' && !empty($termino)) {
                        $sql .= " AND (LOWER(c.nombre) LIKE LOWER(?) OR CAST(p.id AS CHAR) LIKE ?)";
                        $params = ["%$termino%", "%$termino%"];
                    }
                    
                    $sql .= " ORDER BY p.fecha_pago DESC LIMIT $limite";
                    break;
                default:
                    echo json_encode(['success' => true, 'respuesta_ia' => "No puedo buscar en '$tabla'."]);
                    exit;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($resultados)) {
                $msgNoResultados = "No encontré registros en <strong>$tabla</strong>";
                if (!empty($termino) && $termino !== 'todos') {
                    $msgNoResultados .= " con '<em>$termino</em>'";
                }
                $msgNoResultados .= ". 🔍";
                echo json_encode(['success' => true, 'respuesta_ia' => $msgNoResultados]);
            } else {
                $countResultados = count($resultados);
                
                $html = "📋 <strong>Encontré $countResultados resultado(s) en $tabla:</strong><br><ul class='text-start mb-0' style='max-height: 300px; overflow-y: auto;'>";
                
                foreach ($resultados as $row) {
                    $linea = "";
                    foreach ($row as $key => $val) {
                        if ($key !== 'id') {
                            $linea .= "<strong>" . ucfirst($key) . ":</strong> " . htmlspecialchars($val) . " | ";
                        }
                    }
                    $linea = rtrim($linea, " | ");
                    $html .= "<li>$linea</li>";
                }
                $html .= "</ul>";
                if ($countResultados >= $limite) {
                    $html .= "<br><small class='text-muted'>Mostrando los primeros $limite resultados. Para ver más, ve a la vista de <strong>$tabla</strong>.</small>";
                }
                if ($countResultados > 1 && !empty($termino) && $termino !== 'todos') {
                    $html .= "<br><em class='text-warning'> Hay varios resultados. Si quieres actualizar/borrar, sé más específico con apellido o teléfono.</em>";
                }
                
                echo json_encode(['success' => true, 'respuesta_ia' => $html]);
            }
            exit;
        }
        $id_log = $auditoriaModel->solicitarAccionIA($accion_tipo, $payload, $usuario_id);
        
        if ($id_log) {
            $msgFinal = $textoIA ?: "Solicitud de <strong>$accion_tipo</strong> generada.";

            if (isset($payload['nombre_busqueda'])) {
                $msgFinal .= "<br><em>🔍Buscando: " . htmlspecialchars($payload['nombre_busqueda']) . "</em>";
            }
            if (isset($payload['nuevos_datos'])) {
                $cambios = [];
                foreach ($payload['nuevos_datos'] as $k => $v) {
                    $cambios[] = "<strong>$k:</strong> $v";
                }
                $msgFinal .= "<br><em>Cambios: " . implode(', ', $cambios) . "</em>";
            }
            if ($accion_tipo === 'registrar_gasto' && !empty($payload['monto'])) {
                $msgFinal .= " <strong>(Monto: S/ {$payload['monto']})</strong>";
            }
            if ($accion_tipo === 'registrar_pago' && !empty($payload['monto'])) {
                $msgFinal .= " <strong>(Monto: S/ {$payload['monto']})</strong>";
            }

            $html = "🤖<strong>IA:</strong> $msgFinal <br>⏳ <em>Esperando tu aprobación...</em>";
            echo json_encode(['success' => true, 'respuesta_ia' => $html]);
        } else {
            throw new Exception("Error al guardar la solicitud.");
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'respuesta_ia' => "⚠️" . $e->getMessage()]);
    }
    exit;
}
?>