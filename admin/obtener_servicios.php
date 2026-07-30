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

    // 2. Parámetros de filtro
    $categoria = $_GET['categoria'] ?? null;
    $id_sucursal = $_GET['id_sucursal'] ?? $_GET['sucursal'] ?? null;
    $ubicacion = $_GET['ubicacion'] ?? null; // Por si desde el front envías el texto ("Salón Carmelo", "Jardín")

    // 3. Construcción dinámica de la consulta
    $where = ["disponible_servicio = 1"];
    $params = [];

    // Filtro por categoría (si se requiere)
    if ($categoria) {
        $where[] = "categoria = ?";
        $params[] = $categoria;
    }

    // Filtro por Sucursal / Salón
    // Trae los del salón específico O los generales (Ambos / NULL / 0)
    if ($id_sucursal) {
        $where[] = "(id_sucursal = ? OR id_sucursal IS NULL OR id_sucursal = 0)";
        $params[] = $id_sucursal;
    } elseif ($ubicacion) {
        $where[] = "(ubicacion = ? OR ubicacion = 'Ambos' OR ubicacion IS NULL OR ubicacion = '')";
        $params[] = $ubicacion;
    }

    $sqlWhere = implode(" AND ", $where);
    $sql = "SELECT * FROM servicios WHERE {$sqlWhere} ORDER BY id_servicio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Preparar consulta para obtener la galería multimedia de cada servicio
    $stmtGaleria = $pdo->prepare("SELECT id_galeria, ruta_archivo, tipo_archivo FROM servicio_galeria WHERE id_servicio = ? ORDER BY id_galeria ASC LIMIT 5");

    // 5. Recorrer y adosar el arreglo 'galeria' a cada servicio
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

        // Guardamos el arreglo para la app móvil y panel
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
