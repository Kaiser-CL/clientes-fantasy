<?php

header("Content-Type: application/json");

require_once "../config/database.php";

$db = new Database();
$conn = $db->conectar();

$id_solicitud = $_GET['id_solicitud'];

$sql = "
SELECT *
FROM solicitudes_servicio
WHERE id_solicitud = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id_solicitud]);

$solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$solicitud){
    echo json_encode([
        "success"=>false,
        "message"=>"Solicitud no encontrada"
    ]);
    exit;
}

$conn->beginTransaction();

try{

    $sql = "
    UPDATE solicitudes_servicio
    SET estado='aprobada'
    WHERE id_solicitud=?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id_solicitud]);

    $sqlServicio = "
    SELECT precio
    FROM servicios
    WHERE id_servicio=?
    ";

    $stmtServicio = $conn->prepare($sqlServicio);
    $stmtServicio->execute([$solicitud['id_servicio']]);

    $servicio = $stmtServicio->fetch(PDO::FETCH_ASSOC);

    $subtotal = $servicio['precio'];

    $sqlEventoServicio = "
    INSERT INTO evento_servicio
    (
        id_evento,
        id_servicio,
        cantidad,
        subtotal
    )
    VALUES
    (
        ?, ?, 1, ?
    )
    ";

    $stmtEventoServicio = $conn->prepare($sqlEventoServicio);

    $stmtEventoServicio->execute([
        $solicitud['id_evento'],
        $solicitud['id_servicio'],
        $subtotal
    ]);

    $conn->commit();

    echo json_encode([
        "success"=>true
    ]);

}catch(Exception $e){

    $conn->rollBack();

    echo json_encode([
        "success"=>false,
        "error"=>$e->getMessage()
    ]);
}