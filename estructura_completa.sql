/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `categorias_servicio` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_categoria` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_categoria`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `nombre_categoria` (`nombre_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=60100;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `descuentos` (
  `id_descuento` int NOT NULL AUTO_INCREMENT,
  `nombre_descuento` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_descuento` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `porcentaje_descuento` decimal(5,2) NOT NULL,
  `fecha_inicio_descuento` date NOT NULL,
  `fecha_fin_descuento` date NOT NULL,
  `activo_descuento` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_descuento`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `dispositivos` (
  `id_dispositivo` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token_fcm_dispositivo` text COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro_dispositivo` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dispositivo`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_dispositivo_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `evento_producto` (
  `id_evento_producto` int NOT NULL AUTO_INCREMENT,
  `id_evento` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad_producto_evento` int NOT NULL DEFAULT '1',
  `subtotal_producto_evento` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_evento_producto`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_evento_producto_evento` (`id_evento`),
  KEY `fk_evento_producto_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `evento_servicio` (
  `id_evento_servicio` int NOT NULL AUTO_INCREMENT,
  `id_evento` int NOT NULL,
  `id_servicio` int NOT NULL,
  `cantidad_servicio_evento` int NOT NULL DEFAULT '1',
  `subtotal_servicio_evento` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_evento_servicio`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_evento_servicio_evento` (`id_evento`),
  KEY `fk_evento_servicio_servicio` (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `eventos` (
  `id_evento` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_sucursal` int DEFAULT NULL,
  `nombre_evento` varchar(150) NOT NULL,
  `clasificacion_evento` varchar(20) DEFAULT 'infantil',
  `fecha_evento` date NOT NULL,
  `hora_evento` time NOT NULL,
  `ubicacion` text DEFAULT NULL,
  `numero_invitados` int DEFAULT '0',
  `costo_total` decimal(10,2) DEFAULT '0.00',
  `saldo_pendiente` decimal(10,2) DEFAULT '0.00',
  `fecha_limite_pago` date DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'pendiente',
  PRIMARY KEY (`id_evento`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_1` (`id_cliente`),
  KEY `fk_2` (`id_sucursal`),
  CONSTRAINT `fk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_2` FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales` (`id_sucursal`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin AUTO_INCREMENT=30005;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `historial_eventos` (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `id_evento` int NOT NULL,
  `accion_historial` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_historial` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_historial` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_historial_evento` (`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `notificaciones` (
  `id_notificacion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `titulo_notificacion` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `mensaje_notificacion` text COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_envio_notificacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `leida_notificacion` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_notificacion`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_notificacion_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `pagos` (
  `id_pago` int NOT NULL AUTO_INCREMENT,
  `id_evento` int NOT NULL,
  `monto_pago` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  `referencia_pago` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_pago` enum('completado','pendiente','rechazado') COLLATE utf8mb4_general_ci DEFAULT 'completado',
  PRIMARY KEY (`id_pago`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_pago_evento` (`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `nombre_producto` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_producto` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `precio_producto` decimal(10,2) NOT NULL,
  `stock_producto` int DEFAULT '0',
  `imagen_producto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `disponible_producto` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_producto`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `resenas` (
  `id_resena` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_servicio` int NOT NULL,
  `calificacion_resena` int NOT NULL,
  `comentario_resena` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_resena` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_resena`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_resena_usuario` (`id_usuario`),
  KEY `fk_resena_servicio` (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_rol`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=60002;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `servicio_descuento` (
  `id_servicio_descuento` int NOT NULL AUTO_INCREMENT,
  `id_servicio` int NOT NULL,
  `id_descuento` int NOT NULL,
  PRIMARY KEY (`id_servicio_descuento`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_servicio_descuento_servicio` (`id_servicio`),
  KEY `fk_servicio_descuento_descuento` (`id_descuento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `servicios` (
  `id_servicio` int NOT NULL AUTO_INCREMENT,
  `nombre_servicio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion_servicio` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  `precio_servicio` decimal(10,2) NOT NULL,
  `es_por_persona` tinyint(1) DEFAULT '0',
  `foto_servicio` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `imagen_servicio` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `disponible_servicio` tinyint(1) DEFAULT '1',
  `categoria` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'infantil',
  `id_categoria` int DEFAULT NULL,
  `tipo_cobro` enum('fijo','por_persona') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'fijo',
  `clasificacion_evento` enum('infantil','social','ambos') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ambos',
  `tipo_registro` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'servicio',
  `ubicacion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'jardin',
  PRIMARY KEY (`id_servicio`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_servicio_categoria` (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=90001;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `solicitudes_servicio` (
  `id_solicitud` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_evento` int NOT NULL,
  `id_servicio` int NOT NULL,
  `fecha_solicitud_servicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado_solicitud` enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_general_ci DEFAULT 'pendiente',
  `comentario_solicitud` text COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_solicitud_usuario` (`id_usuario`),
  KEY `fk_solicitud_evento` (`id_evento`),
  KEY `fk_solicitud_servicio` (`id_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `sucursales` (
  `id_sucursal` int NOT NULL AUTO_INCREMENT,
  `nombre_sucursal` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'activo',
  PRIMARY KEY (`id_sucursal`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `tipos_evento` (
  `id_tipo_evento` int NOT NULL AUTO_INCREMENT,
  `nombre_tipo_evento` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_tipo_evento`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `nombre_tipo_evento` (`nombre_tipo_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40014 SET FOREIGN_KEY_CHECKS=0*/;
/*!40101 SET NAMES binary*/;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `apellidos_usuario` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `correo_usuario` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono_usuario` char(10) COLLATE utf8mb4_general_ci NOT NULL,
  `contrasena_usuario` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rol_usuario` enum('admin','cliente') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cliente',
  `fecha_registro_usuario` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado_usuario` tinyint(1) DEFAULT '1',
  `id_rol` int NOT NULL,
  `id_empleado_registro` int DEFAULT NULL,
  `id` int DEFAULT NULL,
  PRIMARY KEY (`id_usuario`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `correo_usuario` (`correo_usuario`),
  KEY `fk_usuario_rol` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci AUTO_INCREMENT=120001;
