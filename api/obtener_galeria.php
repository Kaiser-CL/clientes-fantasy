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
    $sql = "SELECT id_galeria, id_servicio, titulo_concepto, descripcion_concepto, tipo_archivo, url_archivo, fecha_subida 
            FROM galeria_conceptos 
            WHERE id_servicio = ? 
            ORDER BY id_galeria DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEntidad]);
    $conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Detección de Host e IP en Render / HTTPS
    $protocolo = "https"; 
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'clientes-fantasy.onrender.com';
    $baseUrl = $protocolo . "://" . $host . "/";

    // Decodificar la lista JSON y formatear la respuesta
    $dataFormateada = array_map(function($item) use ($baseUrl) {
        $listaImagenes = json_decode($item['url_archivo'], true);

        // Si no era JSON (registros antiguos), lo envolvemos como array de 1 elemento
        if (!is_array($listaImagenes)) {
            $listaImagenes = [$item['url_archivo']];
        }

        // Construir las URLs completas en HTTPS para Flutter o la web
        $urlsCompletas = array_map(function($ruta) use ($baseUrl) {
            $rutaLimpia = ltrim($ruta, '/');
            return filter_var($ruta, FILTER_VALIDATE_URL) ? $ruta : $baseUrl . $rutaLimpia;
        }, $listaImagenes);

        return [
            'id_galeria' => $item['id_galeria'],
            'id_servicio' => $item['id_servicio'],
            'titulo_concepto' => $item['titulo_concepto'],
            'descripcion_concepto' => $item['descripcion_concepto'],
            'tipo_archivo' => $item['tipo_archivo'],
            'imagenes' => $listaImagenes,      // Lista de rutas relativas
            'urls_completas' => $urlsCompletas, // Lista con URLs completas
            'fecha_subida' => $item['fecha_subida']
        ];
    }, $conceptos);

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
