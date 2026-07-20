<?php
// obtener_servicios.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    // Si viene un parámetro de categoría se filtra, si no, se entregan todos los disponibles
    $categoria = $_GET['categoria'] ?? null;
    
    if ($categoria) {
        $stmt = $pdo->prepare("SELECT * FROM servicios WHERE disponible_servicio = 1 AND categoria = ?");
        $stmt->execute([$categoria]);
    } else {
        $stmt = $pdo->query("SELECT * FROM servicios WHERE disponible_servicio = 1");
    }

    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'exito' => true,
        'servicios' => $servicios
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al cargar catálogo: ' . $e->getMessage()
    ]);
}
?>