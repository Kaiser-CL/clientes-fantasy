<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . '/../db_config.php';
ob_clean();

// Función auxiliar para mantener el campo JSON en la tabla servicios al día
function sincronizarGaleriaJson($pdo, $id_servicio) {
    try {
        $stmt = $pdo->prepare("SELECT ruta_archivo FROM servicio_galeria WHERE id_servicio = ? ORDER BY id_galeria ASC");
        $stmt->execute([$id_servicio]);
        $rutas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Convertir el arreglo PHP a una cadena JSON ["uploads/...", "uploads/..."]
        $json_rutas = json_encode($rutas, JSON_UNESCAPED_SLASHES);

        // Actualizar la tabla principal de servicios
        $stmtUpd = $pdo->prepare("UPDATE servicios SET galeria_urls = ? WHERE id_servicio = ?");
        $stmtUpd->execute([$json_rutas, $id_servicio]);
    } catch (Exception $e) {
        // Error silencioso en sincronización para no romper la respuesta principal
    }
}

$accion = $_POST['accion'] ?? '';

// ==========================================
// 1. ELIMINAR FOTO / VIDEO
// ==========================================
if ($accion === 'eliminar_foto') {
    $id_galeria = filter_input(INPUT_POST, 'id_galeria', FILTER_VALIDATE_INT) 
               ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id_galeria) {
        echo json_encode(['status' => 'error', 'message' => 'ID de galería no proporcionado.']);
        exit;
    }

    try {
        $stmtSel = $pdo->prepare("SELECT id_galeria, id_servicio, ruta_archivo FROM servicio_galeria WHERE id_galeria = ?");
        $stmtSel->execute([$id_galeria]);
        $foto = $stmtSel->fetch(PDO::FETCH_ASSOC);

        if (!$foto) {
            echo json_encode(['status' => 'error', 'message' => 'El registro no existe en la base de datos.']);
            exit;
        }

        $id_servicio = $foto['id_servicio'];

        // Eliminar registro de la tabla individual
        $stmtDel = $pdo->prepare("DELETE FROM servicio_galeria WHERE id_galeria = ?");
        $stmtDel->execute([$id_galeria]);

        // Borrar archivo físico si existe
        if (!empty($foto['ruta_archivo'])) {
            $ruta_limpia = ltrim($foto['ruta_archivo'], '/.');
            $ruta_fisica = __DIR__ . '/../' . $ruta_limpia;
            if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
                @unlink($ruta_fisica);
            }
        }

        // Sincronizar el arreglo JSON en la tabla servicios
        sincronizarGaleriaJson($pdo, $id_servicio);

        echo json_encode(['status' => 'success', 'message' => 'Elemento eliminado y arreglo actualizado.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
        exit;
    }
}

// ==========================================
// 2. SUBIR ARCHIVOS MULTIMEDIA
// ==========================================
$id_servicio = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id_servicio) {
    echo json_encode(['status' => 'error', 'message' => 'ID de servicio faltante para la subida.']);
    exit;
}

if (!isset($_FILES['archivos']) || empty($_FILES['archivos']['name'][0])) {
    echo json_encode(['status' => 'error', 'message' => 'No se seleccionaron archivos para subir.']);
    exit;
}

$directorio_destino = __DIR__ . '/../uploads/galeria/';
if (!file_exists($directorio_destino)) {
    mkdir($directorio_destino, 0777, true);
}

$subidos = 0;

foreach ($_FILES['archivos']['tmp_name'] as $index => $tmp_name) {
    if ($_FILES['archivos']['error'][$index] !== UPLOAD_ERR_OK) {
        continue;
    }

    $nombre_original = $_FILES['archivos']['name'][$index];
    $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    
    $nuevo_nombre = 'galeria_' . $id_servicio . '_' . time() . '_' . uniqid() . '.' . $ext;
    $ruta_absoluta = $directorio_destino . $nuevo_nombre;
    $ruta_relativa = 'uploads/galeria/' . $nuevo_nombre;

    if (move_uploaded_file($tmp_name, $ruta_absoluta)) {
        $tipo_archivo = in_array($ext, ['mp4', 'mov', 'webm']) ? 'video' : 'imagen';

        try {
            $stmt = $pdo->prepare("INSERT INTO servicio_galeria (id_servicio, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)");
            $stmt->execute([$id_servicio, $ruta_relativa, $tipo_archivo]);
            $subidos++;
        } catch (PDOException $e) {
            // Manejar error de inserción
        }
    }
}

if ($subidos > 0) {
    // Sincronizar el arreglo JSON en la tabla servicios con las nuevas URLs agregadas
    sincronizarGaleriaJson($pdo, $id_servicio);

    echo json_encode([
        'status' => 'success',
        'message' => "Se subieron {$subidos} archivo(s) y se actualizó el arreglo de imágenes."
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo subir ningún archivo.'
    ]);
}
exit;
