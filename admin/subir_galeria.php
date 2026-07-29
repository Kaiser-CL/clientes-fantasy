<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'auth_check.php';
require_once __DIR__ . '/../db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    try {
        $accion = $_POST['accion'] ?? '';
        
        // A) ELIMINAR UN ARCHIVO DE LA GALERÍA
        if ($accion === 'eliminar_foto') {
            $id_galeria = intval($_POST['id_galeria'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT * FROM servicio_galeria WHERE id_galeria = ?");
            $stmt->execute([$id_galeria]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $file_path = __DIR__ . '/../' . $item['ruta_archivo'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
                $stmt_del = $pdo->prepare("DELETE FROM servicio_galeria WHERE id_galeria = ?");
                $stmt_del->execute([$id_galeria]);
                echo json_encode(['exito' => true, 'mensaje' => 'Archivo eliminado correctamente.']);
            } else {
                echo json_encode(['exito' => false, 'error' => 'Registro no encontrado.']);
            }
            exit;
        }

        // B) SUBIR ARCHIVOS (USANDO TU CARPETA Images/)
        $id_servicio = intval($_POST['id_servicio'] ?? 0);
        if ($id_servicio <= 0) {
            throw new Exception("ID de servicio no válido.");
        }

        $stmt_s = $pdo->prepare("SELECT nombre_servicio, tipo_registro, ubicacion FROM servicios WHERE id_servicio = ?");
        $stmt_s->execute([$id_servicio]);
        $serv = $stmt_s->fetch(PDO::FETCH_ASSOC);

        if (!$serv) {
            throw new Exception("El servicio especificado no existe.");
        }

        $tipo = (strtolower($serv['tipo_registro']) === 'paquete') ? 'paquetes' : 'extras';
        
        // Limpieza de nombre para directorio
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $serv['nombre_servicio'])));
        $ubi = strtolower(trim($serv['ubicacion'] ?? 'jardin'));
        $nombre_carpeta = "serv_{$id_servicio}_{$slug}_{$ubi}";

        // Apunta directamente a tu carpeta Images/
        $directory_path = __DIR__ . "/../Images/{$tipo}/{$nombre_carpeta}/";
        if (!is_dir($directory_path)) {
            mkdir($directory_path, 0777, true);
        }

        if (!empty($_FILES['archivos_multimedia']['name'][0])) {
            $archivos = $_FILES['archivos_multimedia'];
            $total_archivos = count($archivos['name']);
            $subidos = 0;

            for ($i = 0; $i < $total_archivos; $i++) {
                if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $archivos['tmp_name'][$i];
                    $original_name = basename($archivos['name'][$i]);
                    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                    $ext_permitidas_img = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext_permitidas_vid = ['mp4', 'mov', 'webm'];

                    $tipo_media = 'imagen';
                    if (in_array($ext, $ext_permitidas_vid)) {
                        $tipo_media = 'video';
                    } elseif (!in_array($ext, $ext_permitidas_img)) {
                        continue;
                    }

                    $nuevo_nombre = time() . '_' . uniqid() . '.' . $ext;
                    $target_file = $directory_path . $nuevo_nombre;
                    $db_relative_path = "Images/{$tipo}/{$nombre_carpeta}/" . $nuevo_nombre;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $stmt_ins = $pdo->prepare("INSERT INTO servicio_galeria (id_servicio, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)");
                        $stmt_ins->execute([$id_servicio, $db_relative_path, $tipo_media]);
                        $subidos++;
                    }
                }
            }

            echo json_encode(['exito' => true, 'mensaje' => "Se subieron {$subidos} archivos a la galería."]);
            exit;
        }

        throw new Exception("No se adjuntaron archivos válidos.");

    } catch (Exception $e) {
        echo json_encode(['exito' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
