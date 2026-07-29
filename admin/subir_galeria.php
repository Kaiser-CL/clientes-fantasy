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
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

        for ($i = 0; $i < count($archivos['name']); $i++) {
            
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                
                $tmpName = $archivos['tmp_name'][$i];
                $extension = strtolower(pathinfo($archivos['name'][$i], PATHINFO_EXTENSION));
                
                // Verificar extensión de imagen
                if (!in_array($extension, $extensionesPermitidas)) {
                    continue; 
                }

                // Generar nombre único para no sobrescribir nada
                $nombreArchivo = uniqid("img_") . "." . $extension;
                $rutaFisicaFinal = $directorioDestino . $nombreArchivo;

                // Mover archivo temporal al directorio final
                if (move_uploaded_file($tmpName, $rutaFisicaFinal)) {
                    
                    // Ruta relativa que se almacenará en la Base de Datos
                    $rutaRelativaBD = "Images/" . $tipo . "/" . $idEntidad . "/" . $nombreArchivo;

                    // Insertar según el tipo de entidad
                    if ($tipo === 'paquetes') {
                        $sql = "INSERT INTO galeria (id_paquete, ruta_imagen) VALUES (?, ?)";
                    } else {
                        $sql = "INSERT INTO galeria (id_extra, ruta_imagen) VALUES (?, ?)";
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$idEntidad, $rutaRelativaBD]);
                    
                    $totalSubidos++;
                }
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => "Se subieron {$totalSubidos} imágenes correctamente.",
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
