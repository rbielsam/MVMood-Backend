# MVMood — Backend

API REST de **MVMood**, la red social privada del instituto MVM. Está pensada para conectar al alumnado y al profesorado del centro: solo se puede acceder con una cuenta de correo `@institutmvm.cat`.

Desde la aplicación se pueden crear publicaciones con texto e imagen, comentarlas, darles «me gusta», hablar por chat privado y personalizar el perfil con un nickname y una foto.

Este repositorio contiene el backend (Laravel + Sanctum + Pusher). La interfaz web está en [`../MVMoodReact`](../MVMoodReact).

## Funcionalidades

- **Autenticación con tokens**: registro, inicio y cierre de sesión mediante Laravel Sanctum con bearer tokens.
- **Acceso restringido al instituto**: el registro exige un correo del dominio `@institutmvm.cat`.
- **Verificación de correo**: la cuenta debe verificar su email antes de poder iniciar sesión; las cuentas baneadas tampoco pueden entrar.
- **Recuperación de contraseña**: solicitud y restablecimiento por correo, con límite de intentos y revocación de los tokens existentes.
- **Publicaciones**: creación con texto e imagen opcional, listado paginado en el home, edición y borrado por parte del autor.
- **Comentarios y likes**: interacción con las publicaciones, con recuento y estado de «me gusta» por usuario.
- **Chat privado**: conversaciones uno a uno con mensajes en tiempo real mediante Pusher.
- **Perfil**: consulta de datos, cambio de nickname, subida de foto, cambio de contraseña y eliminación de la cuenta.
- **Bloqueos**: endpoints para consultar y bloquear usuarios (la SPA todavía no expone esta función).
- **Administración**: el rol `admin` existe y el backend impide que un administrador cree publicaciones.

## Stack

- PHP 8.3 o superior
- Laravel 13
- Laravel Sanctum (autenticación por tokens)
- Pusher (difusión en tiempo real)
- MySQL en desarrollo o SQLite
- PHPUnit y Laravel Pint

## Requisitos

- PHP 8.3+ con las extensiones habituales de Laravel (`php-xml`, `php-curl` y el driver de la base de datos).
- Composer.
- MySQL o SQLite.
- Node.js y npm solo si se quieren compilar los assets de Laravel con Vite.

## Puesta en marcha

### Instalación rápida (Ubuntu)

El repositorio incluye `setup.sh`, pensado para un entorno Ubuntu recién preparado:

```bash
chmod +x setup.sh
./setup.sh
```

El script instala PHP y Composer, copia `.env.example`, genera la clave, instala las dependencias, prepara la base de datos y arranca el servidor. Antes de continuar, edita `.env` con tus credenciales de base de datos, correo y Pusher.

### Instalación manual (recomendada)

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configura la base de datos y el correo en .env

php artisan migrate
php artisan storage:link
php artisan db:seed --class=AdminSeeder
php artisan serve
```

La API queda disponible por defecto en `http://127.0.0.1:8000`.

> `storage:link` es necesario para que las imágenes de publicaciones y fotos de perfil sean accesibles desde la web.
>
> Para crear el usuario administrador se usa `AdminSeeder`. No uses `DatabaseSeeder`: está desactualizado y hace referencia a un campo que ya no existe.

## Variables de entorno

Copia `.env.example` y revisa al menos estas claves. No guardes secretos en el repositorio.

| Variable | Para qué sirve |
|---|---|
| `APP_URL` | URL pública del backend (por ejemplo, `http://localhost:8000`). |
| `FRONTEND_URL` | URL de la SPA de React; se usa en los enlaces de verificación y restablecimiento. |
| `DB_CONNECTION` | `mysql` o `sqlite`. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión a MySQL. |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Envío de correos de verificación y recuperación. |
| `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` | Credenciales del servicio Pusher. Deben coincidir con las de la SPA. |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Datos del administrador que crea `AdminSeeder`. |

En desarrollo se puede usar `MAIL_MAILER=log` para revisar los correos en los logs sin enviarlos de verdad.

## Comandos habituales

```bash
# Desarrollo: servidor, colas, logs y Vite
composer dev

# Solo el servidor
php artisan serve

# Pruebas
composer test
php artisan test --filter=NombreDelTest

# Formato de código
vendor/bin/pint
vendor/bin/pint --test
```

## API

Todas las rutas devuelven JSON. Salvo las de autenticación pública, requieren la cabecera:

```http
Authorization: Bearer <token>
Accept: application/json
```

### Autenticación

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/register` | Crea una cuenta con correo del instituto. |
| `POST` | `/api/login` | Inicia sesión y devuelve un token. |
| `POST` | `/api/logout` | Cierra la sesión actual. |
| `POST` | `/api/forgot-password` | Solicita un enlace de recuperación (6 intentos/minuto). |
| `POST` | `/api/reset-password` | Restablece la contraseña con el token recibido. |
| `GET` | `/api/user` | Devuelve el usuario autenticado. |
| `GET` | `/api/email/verify/status` | Indica si el correo está verificado. |

### Publicaciones, comentarios y likes

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/home` | Lista paginada de publicaciones visibles. |
| `POST` | `/api/create` | Crea una publicación con texto e imagen opcional. |
| `PUT` | `/api/publicaciones/{uuid}` | Edita una publicación del autor. |
| `DELETE` | `/api/publicaciones/{uuid}` | Elimina una publicación del autor. |
| `GET` | `/api/publicaciones/{uuid}/likes` | Consulta los likes de una publicación. |
| `POST` | `/api/publicaciones/{uuid}/like` | Da o quita el «me gusta». |
| `GET` | `/api/publicaciones/{uuid}/comentarios` | Lista los comentarios. |
| `POST` | `/api/publicaciones/{uuid}/comentarios` | Añade un comentario. |
| `DELETE` | `/api/comentarios/{uuid}` | Elimina un comentario propio. |

### Chat

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/chats` | Lista las conversaciones del usuario. |
| `GET` | `/api/chats/{chatId}/mensajes` | Devuelve los mensajes de un chat. |
| `POST` | `/api/chats/enviar` | Envía un mensaje. |
| `POST` | `/api/broadcasting/auth` | Autoriza los canales privados de Pusher. |

### Perfil y usuarios

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/perfil` | Datos del perfil autenticado. |
| `POST` | `/api/perfil` | Actualiza nickname y foto de perfil. |
| `PUT` | `/api/perfil/password` | Cambia la contraseña y revoca los tokens. |
| `GET` | `/api/users` | Lista los usuarios para iniciar un chat. |
| `DELETE` | `/api/perfil/delete` | Elimina la cuenta y sus datos. |
| `GET` | `/api/usuarios/bloqueados` | Lista los usuarios bloqueados. |
| `POST` | `/api/usuarios/{uuid}/bloquear` | Bloquea a un usuario. |

## Estructura del proyecto

```text
app/
  Http/Controllers/Api/   Controladores de la API
  Models/                 Modelos Eloquent (User, Publicacion, Chat, Mensaje...)
routes/
  api.php                 Rutas de la API
database/
  migrations/             Esquema de la base de datos
  seeders/                AdminSeeder
tests/
  Feature/                Pruebas de autenticación, publicaciones, chat...
```

La lógica de negocio vive en los controladores y modelos; el acceso a datos se hace siempre con Eloquent. El frontend nunca accede directamente a la base de datos.

## Pruebas

```bash
composer test
```

La suite cubre autenticación, recuperación de contraseña, publicaciones y chat. Existe un caso heredado (`Tests\Feature\ExampleTest`) que espera un `200` en `GET /`; esa ruta ya no existe, por lo que ese test falla y no debe interpretarse como una regresión.

## Documentación

- Manual de la aplicación: [`../docs/Documentation.html`](../docs/Documentation.html)
- Constitución y reglas del proyecto: [`../docs/constitution.md`](../docs/constitution.md) y [`../AGENTS.md`](../AGENTS.md)
- Especificaciones por funcionalidad: [`../specs`](../specs)
- Estado y decisiones del proyecto: [`../MEMORY.md`](../MEMORY.md)

## Seguridad

- No subas `.env` ni credenciales al repositorio.
- Mantén `APP_DEBUG=false` en producción.
- Las claves de Pusher deben coincidir con las de la SPA, pero el `PUSHER_APP_SECRET` solo se configura en el backend.
