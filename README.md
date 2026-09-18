# Canal Ético — LOGI TRADING ENTERPRISES INC.

Formulario público para que clientes y proveedores reporten quejas, sugerencias o
irregularidades (anónimo o identificado), más un panel administrativo para gestionarlos.

## Requisitos del servidor

- PHP 8.1 o superior, con las extensiones `pdo_pgsql` y `pgsql` habilitadas.
- Base de datos PostgreSQL (Supabase), acceso vía **Session Pooler** (no conexión directa).
- Cuenta de [Resend](https://resend.com) para el envío de notificaciones por correo.
- Apache con `mod_rewrite` y soporte de `.htaccess` (ya incluidos en el proyecto).

## Instalación

1. Copia `.env.example` como `.env` y completa tus credenciales reales de Supabase y Resend.
2. Copia `config/admins.example.php` como `config/admins.php` y define ahí los usuarios y
   contraseñas de los administradores (ver sección "Administradores" más abajo).
3. Sube todo el proyecto manteniendo la estructura de carpetas tal cual — no muevas ni
   renombres `bootstrap/`, `providers/`, `config/`, `storage/` ni `public/`.
4. Verifica que `storage/adjuntos/` tenga permisos de escritura (755 o 775).
5. El punto de entrada público es la carpeta `public/` — el formulario se sirve desde ahí
   (`public/index.html`) y el panel administrativo desde `public/login.php`.

## Cómo usar el formulario (usuarios finales)

El formulario vive en `public/index.html` y normalmente se integra dentro de la página
`/contactanos/` del sitio en WordPress, pegando el contenido de
`widget_elementor_contactanos.html` en un widget de tipo **HTML** de Elementor (no
"Editor de texto"). No se usa `public/index.html` como página aparte en producción.

Pasos que sigue quien reporta:

1. Elige una categoría: **Queja o Reclamo**, **Sugerencia de Mejora** o **Reporte de
   Irregularidades**.
2. Indica la fecha en que ocurrió (no puede ser una fecha futura).
3. Escribe la descripción (obligatoria, máximo 2500 caracteres).
4. Decide si quiere reportar de forma **anónima** o **identificada**. Si es identificada,
   los datos de contacto (nombre, empresa, correo, teléfono) son opcionales incluso así.
5. Puede adjuntar hasta 10 archivos (PDF, PNG o JPG), 10 MB en total entre todos.
6. Al enviar, recibe en pantalla un número de referencia con formato `CASO-2026-NNNNNN` —
   es lo único que el sistema le muestra; no hay forma de "consultar estado" por su cuenta
   todavía (ver sección "Mejoras pendientes").

## Cómo usar el panel administrativo

**Acceso:** `public/login.php` (ej. `https://logienter.com/canal-etico/public/login.php`).
Cada administrador entra con su propio usuario y contraseña (ver `config/admins.php`).

### Casos recibidos (`admin.php`)

- Arriba se muestran donas con el porcentaje de casos por categoría.
- La tabla lista todos los casos, más recientes primero. Se puede filtrar por categoría y
  por estado con los selectores de arriba.
- Cada fila es clicable: al hacer clic se expande y muestra la descripción completa, los
  datos de contacto (si no es anónimo) y la fecha de recepción.
- El estado de cada caso se cambia directamente desde el desplegable de la fila — se
  guarda automáticamente al seleccionar, sin botón aparte. Estados disponibles: `recibido`
  → `en revisión` → `en investigación` → `resuelto` → `cerrado`.
- El botón **Eliminar** borra el caso de forma permanente (pide confirmación antes). Borra
  también los registros de sus adjuntos en la base de datos y los archivos físicos
  correspondientes en `storage/adjuntos/`.

### Bitácora (`bitacora.php`)

Registro de auditoría de todo lo que hacen los administradores: inicios de sesión (exitosos
y fallidos), cierres de sesión, cambios de estado y eliminaciones de casos. Se llena sola,
no requiere ninguna acción manual.

### Adjuntos

Dentro del detalle de cada caso, la fila "Adjuntos" lista el nombre original de cada
archivo como enlace — al hacer clic se abre en una pestaña nueva (o se descarga, según el
tipo de archivo y el navegador). Solo funciona con sesión de administrador iniciada.

## Administradores — cómo agregar, cambiar o quitar usuarios

Los administradores se definen en `config/admins.php` (este archivo **no** va a GitHub,
está en `.gitignore` por seguridad). Las contraseñas van **hasheadas** con
`password_hash()`, nunca en texto plano:

```php
<?php
return [
    ['nombre' => 'danielsanchez', 'password' => '$2b$10$wsSWDMzFC8XMQ/DWPF8m4.iorm3hsv376m5RDpCeLMYlEQZGbcVsK'],
    ['nombre' => 'nombre_usuario_2', 'password' => '$2y$10$...'],
    ['nombre' => 'nombre_usuario_3', 'password' => '$2y$10$...'],
    ['nombre' => 'nombre_usuario_4', 'password' => '$2y$10$...'],
    ['nombre' => 'nombre_usuario_5', 'password' => '$2y$10$...'],
];
```

Para generar el hash de cada contraseña nueva, corre en terminal (donde tengas PHP):

```
php -r "echo password_hash('la_contraseña_elegida', PASSWORD_DEFAULT), PHP_EOL;"
```

Copia el resultado completo (empieza con `$2y$` o `$2b$`) y pégalo como `'password'` de esa
persona — nunca la contraseña en texto plano.

- Para **cambiar una contraseña**: genera un hash nuevo y reemplázalo.
- Para **agregar un administrador**: agrega una línea nueva con el mismo formato.
- Para **quitar un administrador**: borra su línea completa (el arreglo puede tener menos
  o más de 5 personas, no hay un límite fijo en el código).
- Los cambios aplican de inmediato, no hace falta reiniciar nada — solo hay que volver a
  subir el archivo al servidor.

> **Nota de seguridad:** aunque las contraseñas ya van hasheadas, `config/admins.php` sigue
> sin poder ser accesible desde el navegador — el `.htaccess` incluido en el proyecto lo
> bloquea. No lo quites ni lo excluyas al subir los archivos.

## Variables de entorno (`.env`)

| Variable | Para qué sirve |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Conexión a Supabase (Session Pooler) |
| `RESEND_API_KEY` | API key de Resend para enviar notificaciones |
| `EMAIL_FROM` | Remitente de las notificaciones (dominio verificado) |
| `EMAIL_TO` | A quién llega la notificación de cada caso nuevo |

El `.env` real **nunca** debe subirse a un repositorio público ni compartirse por un canal
abierto — contiene las credenciales de la base de datos.

## Seguridad — qué protege el `.htaccess`

El proyecto incluye archivos `.htaccess` que bloquean el acceso directo por navegador a:

- `.env` y cualquier archivo `.sql` en la raíz del proyecto
- Toda la carpeta `bootstrap/`
- Toda la carpeta `providers/`
- Toda la carpeta `config/` (donde están las contraseñas de administradores)
- Toda la carpeta `storage/` (donde están los adjuntos subidos)

Solo la carpeta `public/` debe quedar accesible desde el navegador. Antes de dar por
terminado el despliegue, probar que estas URLs devuelvan error 403 (no que muestren
contenido):

```
https://logienter.com/canal-etico/.env
https://logienter.com/canal-etico/config/admins.php
https://logienter.com/canal-etico/bootstrap/app.php
```

## Mejoras pendientes (no bloquean la entrega, pero valen para una siguiente fase)

- Límite de intentos fallidos de inicio de sesión.
- Protección CSRF en las acciones del panel (cambiar estado, eliminar).
- Que quien reporta pueda consultar el estado de su caso con el número de referencia.
