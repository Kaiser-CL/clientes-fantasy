<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/../db_config.php';

$id_servicio = intval($_GET['id_servicio'] ?? 0);

if ($id_servicio <= 0) {
    echo json_encode([]);
    exit;
}

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT id_galeria, ruta_archivo, tipo_archivo FROM servicio_galeria WHERE id_servicio = ? ORDER BY id_galeria DESC");
        $stmt->execute([$id_servicio]);
        $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($archivos);
        exit;
    } catch (PDOException $e) {
        echo json_encode([]);
        exit;
    }
}
