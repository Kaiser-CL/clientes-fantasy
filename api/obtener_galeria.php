<?php
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . '/../db_config.php';

$id_servicio = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$tipo_param = trim($_GET['tipo'] ?? '');

if (!$id_servicio) {
    echo json_encode(['status' => 'error', 'message' => 'ID de servicio no válido.']);
    exit;
}

try {
    // Normalizar la búsqueda para aceptar tanto 'paquetes'/'extras' como 'paquete'/'servicio_extra'
    $tipo_normalizado = (strpos($tipo_param, 'paquete') !== false) ? 'paquete' : 'servicio_extra';

    // Se consultan todas las imágenes ligadas a este id_servicio en la tabla servicio_galeria
    $stmt = $pdo->prepare("SELECT id_galeria, id_servicio, ruta_archivo, tipo_archivo 
                           FROM servicio_galeria 
                           WHERE id_servicio = ? 
                           ORDER BY id_galeria ASC");
    $stmt->execute([$id_servicio]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];
    foreach ($registros as $row) {
        $ruta_raw = trim($row['ruta_archivo']);
        
        // Limpieza estricta de la ruta para que siempre sea válida desde el cliente admin
        if (preg_match('/^https?:\/\//i', $ruta_raw)) {
            $url_final = $ruta_raw;
        } else {
            // Remueve guiones diagonales iniciales o prefijos '../' extras guardados en BD
            $ruta_limpia = ltrim($ruta_raw, '/.');
            $ruta_limpia = ltrim($ruta_limpia, '/');
            $url_final = "../" . $ruta_limpia;
        }

        $resultado[] = [
            'id_galeria' => (int)$row['id_galeria'],
            'id_servicio' => (int)$row['id_servicio'],
            'ruta_archivo' => $row['ruta_archivo'],
            'url_completa' => $url_final,
            'tipo_archivo' => $row['tipo_archivo'] ?? 'imagen'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $resultado
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
