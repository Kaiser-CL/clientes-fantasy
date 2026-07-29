<?php
// Asegurar que NO haya output de texto antes de los headers
ob_start();
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . '/../db_config.php';

// Limpiar cualquier posible output sobrante (warnings, notices de PHP)
ob_clean();

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
        // Buscar el registro
        $stmtSel = $pdo->prepare("SELECT id_galeria, ruta_archivo FROM servicio_galeria WHERE id_galeria = ?");
        $stmtSel->execute([$id_galeria]);
        $foto = $stmtSel->fetch(PDO::FETCH_ASSOC);

        if (!$foto) {
            echo json_encode(['status' => 'error', 'message' => 'El registro ya no existe en la base de datos.']);
            exit;
        }

        // Eliminar de la BD
        $stmtDel = $pdo->prepare("DELETE FROM servicio_galeria WHERE id_galeria = ?");
        $stmtDel->execute([$id_galeria]);

        // Intentar borrar archivo físico si existe
        if (!empty($foto['ruta_archivo'])) {
            $ruta_limpia = ltrim($foto['ruta_archivo'], '/.');
            $ruta_fisica = __DIR__ . '/../' . $ruta_limpia;
            if (file_exists($ruta_fisica) && is_file($ruta_fisica)) {
                @unlink($ruta_fisica);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Elemento eliminado correctamente.']);
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
$tipo = trim($_POST['tipo'] ?? 'paquetes');

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
$errores = [];

foreach ($_FILES['archivos']['tmp_name'] as $index => $tmp_name) {
    if ($_FILES['archivos']['error'][$index] !== UPLOAD_ERR_OK) {
        continue;
    }

    $nombre_original = $_FILES['archivos']['name'][$index];
    $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    
    // Generar nombre único
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
            $errores[] = "Error BD al guardar " . $nombre_original;
        }
    } else {
        $errores[] = "No se pudo mover el archivo " . $nombre_original;
    }
}

if ($subidos > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => "Se subieron {$subidos} archivo(s) correctamente.",
        'errores' => $errores
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo subir ningún archivo.',
        'errores' => $errores
    ]);
}
exit;
