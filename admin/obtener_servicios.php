<?php
// obtener_servicios.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../db_config.php';

try {
    // 1. Detectar URL base de Render dinámicamente
    $protocolo = "https"; 
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'clientes-fantasy.onrender.com';
    $baseUrl = $protocolo . "://" . $host . "/";

    // 2. Consultar catálogo de servicios disponibles
    $categoria = $_GET['categoria'] ?? null;
    
    if ($categoria) {
        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE disponible_servicio = 1 AND categoria = ? ORDER BY id_servicio DESC");
        $stmt->execute([$categoria]);
    } else {
        $stmt = $pdo->query("SELECT * FROM servicios WHERE disponible_servicio = 1 ORDER BY id_servicio DESC");
    }

    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Preparar consulta para obtener la galería multimedia de cada servicio
    $stmtGaleria = $pdo->prepare("SELECT id_galeria, ruta_archivo, tipo_archivo FROM servicio_galeria WHERE id_servicio = ? ORDER BY id_galeria ASC LIMIT 5");

    // 4. Recorrer y adosar el arreglo 'galeria' a cada servicio
    foreach ($servicios as &$servicio) {
        $idServicio = $servicio['id_servicio'];
        $stmtGaleria->execute([$idServicio]);
        $archivos = $stmtGaleria->fetchAll(PDO::FETCH_ASSOC);

        $galeria = array_map(function($item) use ($baseUrl) {
            $rutaLimpia = ltrim($item['ruta_archivo'], '/');
            
            $urlCompleta = filter_var($item['ruta_archivo'], FILTER_VALIDATE_URL) 
                ? $item['ruta_archivo'] 
                : $baseUrl . $rutaLimpia;

            return [
                'id_galeria'   => (int)$item['id_galeria'],
                'url'          => $urlCompleta,
                'tipo_archivo' => $item['tipo_archivo'] // 'imagen' o 'video'
            ];
        }, $archivos);

        // Guardamos el arreglo para la app móvil
        $servicio['galeria'] = $galeria;
    }

    echo json_encode([
        'exito' => true,
        'servicios' => $servicios
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al cargar catálogo: ' . $e->getMessage()
    ]);
}
?>
