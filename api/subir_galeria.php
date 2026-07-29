<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_config.php';

$accion = $_POST['accion'] ?? '';

// --- PROCESAR ELIMINACIÓN DE IMAGEN ---
if ($accion === 'eliminar_foto') {
    // Acepta id_galeria o id por si viene desde distintas llamadas
    $id_galeria = filter_input(INPUT_POST, 'id_galeria', FILTER_VALIDATE_INT) 
               ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id_galeria) {
        echo json_encode(['status' => 'error', 'message' => 'ID de galería no proporcionado o inválido.']);
        exit;
    }

    try {
        // 1. Obtener la ruta para borrar el archivo físico del servidor
        $stmtSel = $pdo->prepare("SELECT ruta_archivo FROM servicio_galeria WHERE id_galeria = ?");
        $stmtSel->execute([$id_galeria]);
        $foto = $stmtSel->fetch(PDO::FETCH_ASSOC);

        if (!$foto) {
            echo json_encode(['status' => 'error', 'message' => 'El registro no existe en la base de datos.']);
            exit;
        }

        // 2. Intentar borrar archivo físico
        $ruta_fisica = __DIR__ . '/../' . ltrim($foto['ruta_archivo'], '/.');
        if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
            @unlink($ruta_fisica);
        }

        // 3. Eliminar el registro de TiDB
        $stmtDel = $pdo->prepare("DELETE FROM servicio_galeria WHERE id_galeria = ?");
        $stmtDel->execute([$id_galeria]);

        echo json_encode(['status' => 'success', 'message' => 'Imagen eliminada correctamente.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar en BD: ' . $e->getMessage()]);
        exit;
    }
}

// --- PROCESAR SUBIDA DE ARCHIVOS (TU CÓDIGO EXISTENTE DE SUBIDA) ---
// ...
