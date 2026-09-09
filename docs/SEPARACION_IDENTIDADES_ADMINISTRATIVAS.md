# Identidades administrativas y verificación telefónica

## Implementación

- `users` y guard `web`: cuentas de Plaza Local, acceso por `/login`.
- `admin_users` y guard `admin`: personal administrativo. Todos entran por `/login`; `/administracion/login` redirige al formulario común. El correo y la contraseña determinan la identidad, sin selector de rol. Las cuentas vinculadas conservan contraseñas diferentes.
- La recuperación desde el login común envía enlaces independientes para las identidades existentes, sin revelar qué tipos de cuenta tiene ese correo.
- Cookie administrativa independiente, limitada a `/administracion`. Con sesiones de BD, utiliza `admin_sessions`; la plaza conserva `sessions`.
- Contraseñas y recuperación independientes. El broker `admins` utiliza `admin_password_reset_tokens` y enlaces administrativos de 30 minutos. Un token de la plaza no restablece una contraseña administrativa.
- Solo existe un propietario (rol `superadmin`, índice único `owner_slot`). No hay botón ni endpoint de promoción a superadministrador. Se crea mediante `php artisan plaza:create-superadmin`, con contraseña interactiva, sin credenciales predeterminadas.
- Para entrar al panel, la identidad administrativa debe estar activa y tener correo y teléfono verificados. Los cambios de contraseña invalidan las sesiones administrativas previas.
- “Cuentas” contiene cuentas de la plaza; “Administradores” es un directorio separado, exclusivo del superadministrador. El propietario puede invitar, suspender o reactivar administradores.

## Dos cuentas de una persona

1. **Administrador primero:** después de verificar correo y teléfono, entra a “Mi cuenta de Plaza Local” y crea su cuenta comercial con una contraseña distinta. Se copian exactamente los dos datos de contacto verificados; no se habilitan roles comerciales en la identidad administrativa.
2. **Cliente primero:** el superadministrador crea una invitación usando exactamente el correo y teléfono del cliente. La invitación autoriza el vínculo, caduca en 48 horas y se utiliza una sola vez. El destinatario define una contraseña administrativa distinta y verifica sus contactos antes de acceder al panel.

El vínculo queda en `account_identity_links` con la identidad que autorizó y la fecha. Ningún administrador puede moderar su propia cuenta vinculada ni aprobar su propio expediente comercial. No se fusionan mensajes, notificaciones, contraseñas ni sesiones entre ambas cuentas.

Los índices únicos de cada tabla y un bloqueo transaccional común (`identity_registration_locks`) serializan las altas y cambios de contacto. No se permite reutilizar parcialmente un correo o teléfono de otra identidad. Los contactos vinculados no se cambian desde la edición normal del perfil: se requiere un procedimiento administrativo de identidad; no hay una edición simultánea automática implementada.

## SMS real: preparación y límite actual

La integración utiliza **Twilio Verify**, sin instalar un SDK adicional. No envía SMS de prueba fingiendo éxito ni acepta un código fijo. Configurar únicamente en el `.env` del servidor:

```dotenv
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_VERIFY_SERVICE_SID=
```

Se requiere una cuenta del proveedor, un servicio Verify habilitado para enviar a México y sus credenciales. No se ha contratado un proveedor ni comprobado entrega real: las pruebas automatizadas simulan las respuestas HTTP del proveedor. Sin configuración, la interfaz indica que el SMS no está habilitado y no verifica ningún teléfono.

- Números mexicanos de 10 dígitos, enviados al proveedor como `+52` más el número.
- Código de 4 dígitos (configurar también esta longitud en Twilio Verify); desafío asociado a la identidad y sesión, con caducidad local de 10 minutos.
- Hasta cinco intentos de validación por desafío y límites de envío por teléfono, identidad e IP.
- Solo una respuesta `approved` del proveedor marca `phone_verified_at`.
- Cambiar un teléfono no vinculado elimina su verificación previa.
- Esta es una comprobación de propiedad del teléfono, **no un segundo factor obligatorio en cada inicio de sesión**. MFA administrativo por inicio de sesión es una ampliación distinta, no debe darse por implementado.

Referencias del adaptador: [iniciar verificación](https://www.twilio.com/docs/verify/api/verification), [comprobar código](https://www.twilio.com/docs/verify/api/verification-check).

## Migrar conservando datos

Respaldar la BD y ejecutar las migraciones en mantenimiento. La separación:

- Copia las identidades administrativas antiguas conservando el hash de contraseña y la verificación de correo. No inventa una verificación telefónica.
- Añade referencias `admin_user_id` para auditoría, moderación, expedientes, soporte y disputas; migra las notificaciones a `AdminUser`.
- Conserva las filas antiguas de `users` como historial desactivado, marca `migrated_to_admin_at` y libera su correo/teléfono. No pueden iniciar sesión ni aparecer en el directorio de clientes o como perfiles públicos.
- Cierra las sesiones antiguas de esos administradores.
- Rechaza una instalación con varios superadministradores antes de cambiar tablas.

Se ensayó en SQLite aislado, incluyendo conservación de auditoría y notificaciones. **No equivale a un ensayo en el MySQL/MariaDB del servidor.** Antes de migrar datos que importen, ensayar sobre una copia de esa misma versión del motor. En MySQL las operaciones DDL no son transaccionales: usar respaldo y ventana de mantenimiento. La reversión de esta separación requiere restaurar el respaldo completo; no se implementa un `migrate:rollback` destructivo.

## Reiniciar exclusivamente la BD de prueba

**Primero desplegar este código y configurar correo y SMS.** Sin SMS no se podrá completar el acceso administrativo nuevo. Confirmar que `DB_DATABASE` del `.env` apunta exclusivamente a la base de prueba de Plaza Local. No usar si la conexión comparte tablas con otro sistema.

En el servidor, dentro de `/home/ejidos/plaza-local`:

```bash
php artisan down
php artisan migrate:fresh --seed --force
php artisan plaza:create-superadmin
php artisan optimize:clear
php artisan optimize
php artisan up
```

Ejecutar cada paso solo si el anterior terminó correctamente. `migrate:fresh` elimina **todas las tablas de la conexión configurada**, incluidos usuarios, operaciones, conversaciones, sesiones e historial. El seeder ya no crea usuarios ni contraseñas de ejemplo; conserva los catálogos iniciales creados por las migraciones. No elimina archivos subidos, el `.env` ni el código. No se ha ejecutado este reinicio sobre la BD del usuario.

## Despliegue habitual

Mantener el flujo existente: compilar en la PC, subir código mediante Git y copiar `public/build/assets` y `manifest.json` al servidor `ejidos@192.168.1.91`. No se necesita instalar npm en el servidor.

Subir primero los assets y luego el manifest de esa misma compilación. Los directorios públicos deben poder recorrerse por el servidor web (normalmente 755) y los archivos leerse (644). No usar permisos 777 ni modificar el `.env` al ajustar archivos públicos.

La integración no depende de que el servidor sea potente. Para una nube con varias instancias, usar BD y almacenamiento compartidos, caché compartida para los límites de intentos y una configuración consistente de sesiones, secretos y HTTPS. El runtime validado es Laravel bajo peticiones normales/PHP-FPM; no se ha validado Octane.
