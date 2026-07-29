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

    // Detectar el protocolo y dominio base de la petición actual
    $protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocolo . "://" . $host . "/";

    // Formatear la lista adjuntando la URL completa para el frontend y la App
    $dataFormateada = array_map(function($item) use ($baseUrl) {
        $rutaLimpia = ltrim($item['ruta_archivo'], '/');
        return [
            'id_galeria' => $item['id_galeria'],
            'ruta_archivo' => $rutaLimpia,
            'url_completa' => $baseUrl . $rutaLimpia,
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
