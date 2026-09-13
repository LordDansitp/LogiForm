-- Bitácora de administradores: quién entró, cuándo, y qué acciones realizó.

CREATE TABLE bitacora_admin (
  id SERIAL PRIMARY KEY,
  nombre_admin VARCHAR(100) NOT NULL,
  accion VARCHAR(50) NOT NULL,       -- ej. 'login', 'login_fallido', 'eliminar_caso', 'cambiar_estado'
  detalle TEXT,                       -- ej. 'Caso CASO-2026-000001 movido a resuelto'
  creado_en TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_bitacora_creado_en ON bitacora_admin(creado_en);

ALTER TABLE bitacora_admin ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Sin acceso público" ON bitacora_admin FOR ALL USING (false);
