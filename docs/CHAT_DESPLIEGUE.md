# Chat en tiempo real — instalación y comprobación

## Fase 2

Reverb 1.11.1 instalado por Composer. `ChatService` centraliza envío idempotente, recibos, presencia visible y escritura; autoriza cada operación con `ConversationPolicy`. Canales privados de conversación e inbox personal en `routes/channels.php`. `MessageSent`, `MessageRead` (entrega/lectura) y `UserTyping` se emiten después del commit; el texto y las rutas de archivos no viajan en los eventos. Laravel Notifications persiste la campana fuera del hilo activo, salvo que esté silenciado. La presencia visible tiene un TTL de 35 segundos, por lo que al cerrar abruptamente una pestaña puede existir ese pequeño periodo sin campana; el mensaje y el contador de no leídos no se pierden.

El servidor limita envíos/escritura. Los recibos validan conversación, destinatario y mensaje; un evento del navegador no determina la hora de lectura. La autorización de un WebSocket no sustituye la comprobación de permisos al consultar mensajes o descargar imágenes.

Imágenes: hasta tres por mensaje, máximo 5 MB cada una y dimensiones máximas 3000×3000. PHP GD es obligatorio para adjuntar: se recodifica a PNG, sin EXIF/GPS ni SVG. Almacenamiento local privado, descarga autenticada, sin URL pública. `plaza:prune-chat-files` elimina únicamente imágenes sin registro y de más de 24 horas bajo `chat/`; corre diariamente y dispone de `--dry-run`. Así se limpian también archivos de historiales purgados por cascade.

### Requisitos

- PHP 8.2+, extensión GD y requisitos de Composer.
- Cola `database`/Redis atendida por worker (no `sync` en producción).
- Caché compartida por los procesos de PHP, por ejemplo database/Redis.
- Reverb como servicio separado, Nginx con TLS y proxy WebSocket.
- Scheduler ejecutando `php artisan schedule:run` cada minuto.

### Dependencias

Con el lock del repositorio, ejecutar `composer install --no-dev --prefer-dist --optimize-autoloader`, no un update general. No ejecutar `install:broadcasting` encima: los archivos y autorización ya están integrados y ese asistente podría sobrescribirlos.

Las dependencias del navegador de fase 3 se instalan con `npm ci` y se compilan con `npm run build`. Puede compilarse en Windows y transferir assets primero y manifest al final, como en los despliegues anteriores.

### Entorno (nunca subir valores secretos a Git)

```dotenv
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
REVERB_APP_ID=plaza-local
REVERB_APP_KEY=REEMPLAZAR_CON_CLAVE_ALEATORIA
REVERB_APP_SECRET=REEMPLAZAR_CON_SECRETO_ALEATORIO
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_ALLOWED_ORIGINS=tu-dominio.example
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=tu-dominio.example
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Generar key y secret diferentes con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. El secret no se pone en variables VITE. Los valores VITE quedan incrustados al compilar; si compilas localmente, deben corresponder al host público del servidor, no a 127.0.0.1 del visitante. No usar comodín de orígenes en producción. La conexión backend usa loopback; el navegador usa TLS público.

Después del respaldo y durante el despliegue: `php artisan migrate --force`, `php artisan optimize`, reiniciar el worker y Reverb. No ejecutar `migrate:fresh`. Mantener la aplicación en mantenimiento si alguno de estos pasos falla.

### Servicio y proxy

El servicio systemd debe ejecutar `php artisan reverb:start --host=127.0.0.1 --port=8080` desde `/home/ejidos/plaza-local`, con el usuario de la aplicación, `Restart=always` y PHP 8.2. No instalar una ruta de PHP inventada: verificar `command -v php` en el servidor. No abrir 8080 a Internet si Nginx hace el proxy.

En el bloque HTTPS existente de Nginx, integrar (no reemplazar el resto del sitio):

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 75s;
}
```

Validar con `sudo nginx -t` antes de recargar. `/broadcasting/auth` permanece en Laravel; no enviarlo a Reverb. Los endpoints backend `/apps/` son accesibles por loopback, no necesitan exposición pública con esta configuración.

### Pruebas requeridas tras despliegue

Dos navegadores con usuarios diferentes: iniciar desde publicación, enviar/recibir sin recargar, escribir, pasar de enviado a entregado/leído, adjuntar imagen, buscar hilo y cargar mensajes anteriores. Con un tercero ajeno: denegar canal y descarga. Desconectar/reconectar, cerrar pestaña y comprobar campana; reenviar el mismo token no duplica el mensaje. Probar expiración/disputa sin permitir nuevos envíos.

Verificar en red `101 Switching Protocols`, autenticación de canal `200` para participantes y `403` para terceros, estado de worker/Reverb y trabajos fallidos. Un test con broadcaster simulado no demuestra conectividad real del servidor.

Referencias oficiales: https://laravel.com/docs/12.x/reverb y https://livewire.laravel.com/docs/4.x/events.
