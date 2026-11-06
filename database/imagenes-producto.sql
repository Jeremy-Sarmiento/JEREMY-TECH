-- Jeremy Tech: imágenes de producto guardadas en la base de datos.
--
-- MariaDB 10.2+. Aplica este archivo sobre una base que ya tiene el esquema
-- anterior. Es idempotente.
--
-- Uso: mysql -u root -p jeremy_tech < database/imagenes-producto.sql

CREATE TABLE IF NOT EXISTS producto_imagenes (
  producto_id bigint(20) unsigned NOT NULL,
  mime varchar(60) NOT NULL,
  datos longblob NOT NULL,
  actualizado_en timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (producto_id),
  CONSTRAINT fk_prodimg_producto FOREIGN KEY (producto_id)
    REFERENCES productos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
