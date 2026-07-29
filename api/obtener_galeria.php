<?php
// api/obtener_galeria.php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

// Ajusta la ruta a tu conexión de BD según tu estructura
require_once '../config/db.php'; 

$tipo = $_GET['tipo'] ?? ''; // 'paquetes' o 'extras'
$idEntidad = intval($_GET['id'] ?? 0);

if (!in_array($tipo, ['paquetes', 'extras']) || $idEntidad <= 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Parámetros inválidos. Se requiere tipo (paquetes|extras) e id mayor a 0.'
    ]);
    exit;
}

// Determinar la columna de la tabla galeria a filtrar
$campoFiltro = ($tipo === 'paquetes') ? 'id_paquete' : 'id_extra';

try {
    $sql = "SELECT id, ruta_imagen, fecha_creacion FROM galeria WHERE {$campoFiltro} = ? ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idEntidad]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'tipo' => $tipo,
        'id_entidad' => $idEntidad,
        'total' => count($imagenes),
        'data' => $imagenes
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar la base de datos: ' . $e->getMessage()
    ]);
}
?>
