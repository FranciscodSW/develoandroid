<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$hostname = 'localhost';
$database = 'gourmeet';
$username = 'root';
$password = '';

// Conexión
$conexion = new mysqli($hostname, $username, $password, $database);

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión BD', 'recetas' => []]);
    exit;
}

// Obtener ID de la receta
$rec_id = isset($_GET['REC_ID']) ? intval($_GET['REC_ID']) : 0;

if ($rec_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'REC_ID inválido', 'recetas' => []]);
    exit;
}

// Consulta para obtener detalles de la receta con sus ingredientes
$sql = "SELECT 
    r.REC_ID,
    r.REC_NOMBRE,
    r.REC_DESCRIPCION,
    r.REC_PORCIONES,
    r.REC_TIEMPO_PREPARACION,
    r.REC_FECHACREACION,
    r.Dificultad,
    r.Calorias,
    r.REC_ENLACEYOUTUBE,
    r.REC_DATOGOUMEET,
    r.FotoReceta,

    r.REC_RC_ID,
    rc.RC_DESCRIPCION AS REC_CATEGORIA,

    ri.RI_ID,
    ri.RI_DESC_CANTIDAD,

    i.Ing_DESCRIPCION AS Nombre_Ingrediente,
    i.Calorias AS Calorias_Ingrediente,
    i.Precio_Estimado,

    cal.promedio,
    cal.votos,

    c.COM_ID,
    c.COM_COMENTARIO,
    c.COM__FECHA,

    cli.CLI_NOMBRE AS NombreCliente,
    cli.FotoUsuario AS FotoCliente,

    resp.COM_ID AS RESP_ID,
    resp.COM_COMENTARIO AS RESPUESTA,
    resp.COM__FECHA AS RESP_FECHA,

    cli_resp.CLI_NOMBRE AS NombreRespuesta,
    cli_resp.FotoUsuario AS FotoRespuesta

FROM recetas r

INNER JOIN recetas_categoria rc 
    ON r.REC_RC_ID = rc.RC_ID

INNER JOIN recetas_ingredientes ri 
    ON r.REC_ID = ri.RI_REC_ID

INNER JOIN ingredientes i 
    ON ri.RI_ING_ID = i.Ing_ID

LEFT JOIN (
    SELECT 
        CAL_REC_ID,
        ROUND(AVG(CAL_CALIFICACION), 1) AS promedio,
        COUNT(*) AS votos
    FROM calificaciones
    WHERE CAL_ESTATUS = 1
    GROUP BY CAL_REC_ID
) cal ON r.REC_ID = cal.CAL_REC_ID

LEFT JOIN comentarios c 
    ON r.REC_ID = c.COM_REC_ID 
    AND c.COM_ESTATUS = 1
    AND c.COM_RESPUESTA_ID = 0

LEFT JOIN clientes cli 
    ON c.COM_CLI_ID = cli.CLI_ID
    AND cli.CLI_ESTATUS = 1
    
LEFT JOIN comentarios resp 
    ON c.COM_ID = resp.COM_RESPUESTA_ID
    AND resp.COM_ESTATUS = 1

LEFT JOIN clientes cli_resp
    ON resp.COM_CLI_ID = cli_resp.CLI_ID
    AND cli_resp.CLI_ESTATUS = 1

WHERE r.REC_ID = ?
  AND r.REC_ESTATUS = 1
  AND ri.RI_ESTATUS = 1

ORDER BY ri.RI_ID, c.COM__FECHA;";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $rec_id);
$stmt->execute();
$resultado = $stmt->get_result();

$receta = null;
$ingredientes = [];
$comentarios = [];

while ($fila = $resultado->fetch_assoc()) {

    // ✅ RECETA (solo una vez)
    if ($receta === null) {
        $receta = [
            'REC_ID' => $fila['REC_ID'],
            'REC_NOMBRE' => $fila['REC_NOMBRE'],
            'REC_DESCRIPCION' => $fila['REC_DESCRIPCION'],
            'REC_PORCIONES' => $fila['REC_PORCIONES'],
            'REC_TIEMPO_PREPARACION' => $fila['REC_TIEMPO_PREPARACION'],
            'REC_FECHACREACION' => $fila['REC_FECHACREACION'],
            'Dificultad' => $fila['Dificultad'],
            'Calorias' => $fila['Calorias'],
            'REC_ENLACEYOUTUBE' => $fila['REC_ENLACEYOUTUBE'],
            'REC_DATOGOUMEET' => $fila['REC_DATOGOUMEET'],
            'FotoReceta' => $fila['FotoReceta'],
            'promedio' => $fila['promedio'] ?? 0,
            'votos' => $fila['votos'] ?? 0,
            'tipo' => $fila['REC_CATEGORIA'],
            'REC_RC_ID' => $fila['REC_RC_ID']
        ];
    }

    // ✅ INGREDIENTES (evitar duplicados)
    if ($fila['RI_ID'] && !isset($ingredientes[$fila['RI_ID']])) {
        $ingredientes[$fila['RI_ID']] = [
            'RI_ID' => $fila['RI_ID'],
            'RI_DESC_CANTIDAD' => $fila['RI_DESC_CANTIDAD'],
            'Nombre_Ingrediente' => $fila['Nombre_Ingrediente'],
            'Calorias_Ingrediente' => $fila['Calorias_Ingrediente'],
            'Precio_Estimado' => $fila['Precio_Estimado']
        ];
    }

    // ✅ COMENTARIOS
    if ($fila['COM_ID']) {

        // Crear comentario si no existe
        if (!isset($comentarios[$fila['COM_ID']])) {
            $comentarios[$fila['COM_ID']] = [
                'COM_ID' => $fila['COM_ID'],
                'COM_COMENTARIO' => $fila['COM_COMENTARIO'],
                'COM_FECHA' => $fila['COM__FECHA'],
                'CLI_NOMBRE' => $fila['NombreCliente'],
                'FotoUsuario' => $fila['FotoCliente'],
                'respuestas' => []
            ];
        }

        // ✅ RESPUESTAS
        if ($fila['RESP_ID']) {

            $resp_id = $fila['RESP_ID'];

            if (!isset($comentarios[$fila['COM_ID']]['respuestas'][$resp_id])) {

                $comentarios[$fila['COM_ID']]['respuestas'][$resp_id] = [
                    'RESP_ID' => $fila['RESP_ID'],
                    'RESPUESTA' => $fila['RESPUESTA'],
                    'RESP_FECHA' => $fila['RESP_FECHA'],
                    'CLI_NOMBRE' => $fila['NombreRespuesta'],
                    'FotoUsuario' => $fila['FotoRespuesta']
                ];
            }
        }
    }
}

if ($receta === null) {
    echo json_encode(['success' => false, 'error' => 'Receta no encontrada']);
    exit;
}

// Convertir mapas a arrays limpios
$receta['ingredientes'] = array_values($ingredientes);
foreach ($comentarios as &$comentario) {

    if (!empty($comentario['respuestas'])) {
        $comentario['respuestas'] = array_values($comentario['respuestas']);
    } else {
        $comentario['respuestas'] = [];
    }
}
echo json_encode([
    'success' => true,
    'receta' => $receta,
    'comentarios' => array_values($comentarios)
], JSON_UNESCAPED_UNICODE);

$conexion->close();
