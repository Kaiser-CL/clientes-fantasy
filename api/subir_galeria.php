<?php
// api/subir_galeria.php

require_once dirname(__DIR__) . '/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // --- ACCIÓN: ELIMINAR FOTO ---
    if ($accion === 'eliminar_foto') {
        $idGaleria = intval($_POST['id_galeria'] ?? 0);

        if ($idGaleria <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de galería no válido.']);
            exit;
        }

        try {
            // 1. Obtener la ruta del archivo para borrarlo del disco
            $stmt = $pdo->prepare("SELECT ruta_archivo FROM servicio_galeria WHERE id_galeria = ?");
            $stmt->execute([$idGaleria]);
            $foto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($foto) {
                $rutaFisica = dirname(__DIR__) . '/' . $foto['ruta_archivo'];
                if (file_exists($rutaFisica)) {
                    unlink($rutaFisica); // Elimina el archivo físico del servidor
                }

                // 2. Eliminar el registro de la Base de Datos
                $stmtDelete = $pdo->prepare("DELETE FROM servicio_galeria WHERE id_galeria = ?");
                $stmtDelete->execute([$idGaleria]);

                echo json_encode(['status' => 'success', 'message' => 'Archivo eliminado correctamente.']);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'El archivo no existe en la base de datos.']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            exit;
        }
    }

    // --- ACCIÓN: SUBIR FOTOS ---
    $tipo = $_POST['tipo'] ?? ''; 
    $idEntidad = intval($_POST['id_entidad'] ?? $_POST['id'] ?? 0); 

    if (!in_array($tipo, ['paquetes', 'extras']) || $idEntidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo o ID de entidad no válido.']);
        exit;
    }

    $directorioDestino = dirname(__DIR__) . "/Images/" . $tipo . "/" . $idEntidad . "/";

    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    $archivosInput = $_FILES['archivos'] ?? $_FILES['imagenes'] ?? null;

    if ($archivosInput && !empty($archivosInput['name'][0])) {
        $totalSubidos = 0;
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'];
        $totalArchivos = is_array($archivosInput['name']) ? count($archivosInput['name']) : 1;

        for ($i = 0; $i < $totalArchivos; $i++) {
            $error = is_array($archivosInput['error']) ? $archivosInput['error'][$i] : $archivosInput['error'];
            
            if ($error === UPLOAD_ERR_OK) {
                $tmpName = is_array($archivosInput['tmp_name']) ? $archivosInput['tmp_name'][$i] : $archivosInput['tmp_name'];
                $nombreOriginal = is_array($archivosInput['name']) ? $archivosInput['name'][$i] : $archivosInput['name'];
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                
                if (!in_array($extension, $extensionesPermitidas)) continue; 

                $tipoArchivo = in_array($extension, ['mp4', 'mov']) ? 'video' : 'imagen';
                $nombreArchivo = uniqid("img_") . "." . $extension;
                $rutaFisicaFinal = $directorioDestino . $nombreArchivo;

                if (move_uploaded_file($tmpName, $rutaFisicaFinal)) {
                    $rutaRelativaBD = "Images/" . $tipo . "/" . $idEntidad . "/" . $nombreArchivo;
                    $sql = "INSERT INTO servicio_galeria (id_servicio, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$idEntidad, $rutaRelativaBD, $tipoArchivo]);
                    $totalSubidos++;
                }
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => "Se subieron {$totalSubidos} archivos correctamente.",
            'id_entidad' => $idEntidad,
            'tipo' => $tipo
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se seleccionó ninguna imagen o archivo.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
    exit;
}
?>
