<?php
// api/obtener_galeria.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__) . '/db_config.php';

$tipo = $_GET['tipo'] ?? ''; // 'paquetes' o 'extras'
$idEntidad = intval($_GET['id'] ?? 0);

if (!in_array($tipo, ['paquetes', 'extras']) || $idEntidad <= 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Parámetros inválidos. Se requiere tipo (paquetes|extras) e id mayor a 0.'
    ]);
    exit;
}

try {
    $sql = "SELECT id_galeria, ruta_archivo, tipo_archivo, fecha_registro 
            FROM servicio_galeria 
            WHERE id_servicio = ? 
            ORDER BY id_galeria DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEntidad]);
    $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CORRECCIÓN PARA RENDER: Forzar HTTPS y detectar el Host correctamente detrás del Reverse Proxy
    $protocolo = "https"; 
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'clientes-fantasy.onrender.com';
    $baseUrl = $protocolo . "://" . $host . "/";

    // Formatear la lista asegurando la URL en HTTPS
    $dataFormateada = array_map(function($item) use ($baseUrl) {
        $rutaLimpia = ltrim($item['ruta_archivo'], '/');
        
        // Si la ruta guardada ya es una URL externa completa (ej. Cloudinary/ImgBB), se respeta
        if (filter_var($item['ruta_archivo'], FILTER_VALIDATE_URL)) {
            $urlCompleta = $item['ruta_archivo'];
        } else {
            $urlCompleta = $baseUrl . $rutaLimpia;
        }

        return [
            'id_galeria' => $item['id_galeria'],
            'ruta_archivo' => $rutaLimpia,
            'url_completa' => $urlCompleta,
            'tipo_archivo' => $item['tipo_archivo'],
            'fecha_registro' => $item['fecha_registro']
        ];
    }, $archivos);

    echo json_encode([
        'status' => 'success',
        'tipo' => $tipo,
        'id_entidad' => $idEntidad,
        'total' => count($dataFormateada),
        'data' => $dataFormateada
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar la base de datos: ' . $e->getMessage()
    ]);
}
?>
