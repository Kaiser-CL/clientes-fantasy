<?php
// api/subir_galeria.php

require_once dirname(__DIR__) . '/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ACEPTAR AMBOS NOMBRES ('id_entidad' O 'id')
    $tipo = $_POST['tipo'] ?? ''; 
    $idEntidad = intval($_POST['id_entidad'] ?? $_POST['id'] ?? 0); 

    // Validar parámetros
    if (!in_array($tipo, ['paquetes', 'extras']) || $idEntidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo o ID de entidad no válido.']);
        exit;
    }

    // Ruta física en el servidor: /Images/{tipo}/{id}/
    $directorioDestino = dirname(__DIR__) . "/Images/" . $tipo . "/" . $idEntidad . "/";

    // Crear la subcarpeta automáticamente si no existe
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    // 2. DETECTAR 'archivos' (QUE ENVÍA EL JS) O 'imagenes'
    $archivosInput = null;
    if (isset($_FILES['archivos'])) {
        $archivosInput = $_FILES['archivos'];
    } elseif (isset($_FILES['imagenes'])) {
        $archivosInput = $_FILES['imagenes'];
    }

    // Validar que se hayan enviado archivos
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
                
                // Verificar extensión
                if (!in_array($extension, $extensionesPermitidas)) {
                    continue; 
                }

                // Definir tipo de archivo (video o imagen)
                $tipoArchivo = in_array($extension, ['mp4', 'mov']) ? 'video' : 'imagen';

                // Generar nombre único
                $nombreArchivo = uniqid("img_") . "." . $extension;
                $rutaFisicaFinal = $directorioDestino . $nombreArchivo;

                // Mover archivo temporal al directorio final
                if (move_uploaded_file($tmpName, $rutaFisicaFinal)) {
                    
                    // Ruta relativa que se almacenará en la BD
                    $rutaRelativaBD = "Images/" . $tipo . "/" . $idEntidad . "/" . $nombreArchivo;

                    // Insertar en la tabla 'servicio_galeria'
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
