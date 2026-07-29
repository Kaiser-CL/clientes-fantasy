<?php
// admin/subir_galeria.php

// Si usas sesiones para proteger el admin, puedes incluir tu auth_check.php aquí
// require_once 'includes/auth_check.php';

// Ajusta la ruta a tu archivo de conexión si está en config/ db.php o similar
require_once dirname(__DIR__) . '/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir tipo ('paquetes' o 'extras') e ID de la entidad
    $tipo = $_POST['tipo'] ?? ''; 
    $idEntidad = intval($_POST['id_entidad'] ?? 0); 

    // Validar parámetros para evitar inyecciones o carpetas inválidas
    if (!in_array($tipo, ['paquetes', 'extras']) || $idEntidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tipo o ID de entidad no válido.']);
        exit;
    }

    // Ruta física en el servidor: /Images/{tipo}/{id}/
    $directorioDestino = __DIR__ . "/../Images/" . $tipo . "/" . $idEntidad . "/";

    // Crear la subcarpeta del ID automáticamente si no existe aún
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }

    // Validar que se hayan enviado archivos
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        
        $totalSubidos = 0;
        $archivos = $_FILES['imagenes'];
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov'];

        for ($i = 0; $i < count($archivos['name']); $i++) {
            
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                
                $tmpName = $archivos['tmp_name'][$i];
                $extension = strtolower(pathinfo($archivos['name'][$i], PATHINFO_EXTENSION));
                
                // Verificar extensión
                if (!in_array($extension, $extensionesPermitidas)) {
                    continue; 
                }

                // Definir el tipo de archivo (video o imagen) para tu columna ENUM
                $tipoArchivo = in_array($extension, ['mp4', 'mov']) ? 'video' : 'imagen';

                // Generar nombre único para no sobrescribir nada
                $nombreArchivo = uniqid("img_") . "." . $extension;
                $rutaFisicaFinal = $directorioDestino . $nombreArchivo;

                // Mover archivo temporal al directorio final
                if (move_uploaded_file($tmpName, $rutaFisicaFinal)) {
                    
                    // Ruta relativa que se almacenará en la Base de Datos
                    $rutaRelativaBD = "Images/" . $tipo . "/" . $idEntidad . "/" . $nombreArchivo;

                    // CORRECCIÓN AQUÍ: Se usa la tabla 'servicio_galeria' y sus columnas correctas
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
        echo json_encode(['status' => 'error', 'message' => 'No se seleccionó ninguna imagen.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
    exit;
}
?>
