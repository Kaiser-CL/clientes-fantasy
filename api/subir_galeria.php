<?php
// api/subir_galeria.php

require_once dirname(__DIR__) . '/db_config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // --- ACCIÓN: ELIMINAR REGISTRO DE GALERÍA ---
    if ($accion === 'eliminar_foto') {
        $idGaleria = intval($_POST['id_galeria'] ?? 0);

        if ($idGaleria <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de galería no válido.']);
            exit;
        }

        try {
            // 1. Obtener la lista de imágenes para borrar los archivos del servidor
            $stmt = $pdo->prepare("SELECT url_archivo FROM galeria_conceptos WHERE id_galeria = ?");
            $stmt->execute([$idGaleria]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($registro) {
                $listaArchivos = json_decode($registro['url_archivo'], true);
                if (is_array($listaArchivos)) {
                    foreach ($listaArchivos as $archivo) {
                        $rutaFisica = dirname(__DIR__) . '/' . ltrim($archivo, '/');
                        if (file_exists($rutaFisica)) {
                            unlink($rutaFisica);
                        }
                    }
                } else {
                    $rutaFisica = dirname(__DIR__) . '/' . ltrim($registro['url_archivo'], '/');
                    if (file_exists($rutaFisica)) {
                        unlink($rutaFisica);
                    }
                }

                // 2. Eliminar el registro de la Base de Datos
                $stmtDelete = $pdo->prepare("DELETE FROM galeria_conceptos WHERE id_galeria = ?");
                $stmtDelete->execute([$idGaleria]);

                echo json_encode(['status' => 'success', 'message' => 'Registro y archivos eliminados correctamente.']);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'El registro no existe en la base de datos.']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error de BD: ' . $e->getMessage()]);
            exit;
        }
    }

    // --- ACCIÓN: SUBIR FOTOS COMO LISTA EN UN SOLO CAMPO ---
    $tipo = $_POST['tipo'] ?? ''; 
    $idEntidad = intval($_POST['id_entidad'] ?? $_POST['id'] ?? 0); 
    $tituloConcepto = $_POST['titulo_concepto'] ?? 'Galería de fotos';
    $descripcionConcepto = $_POST['descripcion_concepto'] ?? '';
    $tipoArchivo = $_POST['tipo_archivo'] ?? 'imagen';

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
        $rutasGuardadas = [];
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'];
        $totalArchivos = is_array($archivosInput['name']) ? count($archivosInput['name']) : 1;

        for ($i = 0; $i < $totalArchivos; $i++) {
            $error = is_array($archivosInput['error']) ? $archivosInput['error'][$i] : $archivosInput['error'];
            
            if ($error === UPLOAD_ERR_OK) {
                $tmpName = is_array($archivosInput['tmp_name']) ? $archivosInput['tmp_name'][$i] : $archivosInput['tmp_name'];
                $nombreOriginal = is_array($archivosInput['name']) ? $archivosInput['name'][$i] : $archivosInput['name'];
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                
                if (!in_array($extension, $extensionesPermitidas)) continue; 

                $nombreArchivo = uniqid("img_") . "." . $extension;
                $rutaFisicaFinal = $directorioDestino . $nombreArchivo;

                if (move_uploaded_file($tmpName, $rutaFisicaFinal)) {
                    $rutasGuardadas[] = "Images/" . $tipo . "/" . $idEntidad . "/" . $nombreArchivo;
                }
            }
        }

        if (count($rutasGuardadas) > 0) {
            // Convertimos el arreglo de rutas a una cadena en formato JSON
            $imagenesJson = json_encode($rutasGuardadas, JSON_UNESCAPED_SLASHES);

            // Guardamos en la tabla galeria_conceptos
            $sql = "INSERT INTO galeria_conceptos (id_servicio, titulo_concepto, descripcion_concepto, tipo_archivo, url_archivo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idEntidad, $tituloConcepto, $descripcionConcepto, $tipoArchivo, $imagenesJson]);

            echo json_encode([
                'status' => 'success', 
                'message' => 'Se subieron ' . count($rutasGuardadas) . ' archivos correctamente en una sola lista.',
                'id_entidad' => $idEntidad,
                'tipo' => $tipo,
                'imagenes' => $rutasGuardadas
            ], JSON_UNESCAPED_SLASHES);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo subir ningún archivo válido.']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se seleccionó ninguna imagen o archivo.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
    exit;
}
?>
