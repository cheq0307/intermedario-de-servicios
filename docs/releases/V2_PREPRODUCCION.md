# Release `v2.0.0-rc.5` — preproducción

Este procedimiento actualiza `/home/ejidos/plaza-local` sin reemplazar la base ni sus secretos. Los comandos remotos se ejecutan como `ejidos` salvo los reinicios que ya requieran `sudo`.

## 1. Preflight de solo lectura

En Windows, confirmar que la rama y el build están listos:

```powershell
git status --short
git rev-parse HEAD
git rev-parse v1.0.0
git rev-parse v2.0.0-rc.5
Get-Content .\public\build\manifest.json
```

En el servidor, antes de mutar:

```bash
cd /home/ejidos/plaza-local
pwd -P
git status --short
git branch --show-current
git rev-parse HEAD
git fetch origin feature/feed-social-comercial --tags
df -h .
command -v php
php -v
php -m | grep -i '^gd$'
grep -E '^(APP_ENV|APP_URL|DB_CONNECTION|DB_DATABASE|BROADCAST_CONNECTION|QUEUE_CONNECTION|PLAZA_DEMO_ACCESS_ENABLED)=' .env
systemctl --no-pager --full status plaza-local-worker plaza-local-reverb 2>/dev/null || true
```

Detener el despliegue si hay cambios remotos no reconocidos, falta espacio, PHP no es 8.2+, la base no está identificada o el `git pull --ff-only` no podrá avanzar.

## 2. Respaldo antes de mutar

Crear una carpeta fuera del checkout con permisos privados:

```bash
install -d -m 700 /home/ejidos/backups/plaza-local
release_stamp="$(date +%Y%m%d-%H%M%S)"
```

Si `DB_CONNECTION=sqlite`, resolver el valor real de `DB_DATABASE`, detener escrituras con mantenimiento y copiar preservando metadatos:

```bash
php artisan down --render="errors::503"
sqlite_path="$(php artisan tinker --execute='echo config("database.connections.sqlite.database");' 2>/dev/null)"
test -n "$sqlite_path" && test -f "$sqlite_path"
cp -a -- "$sqlite_path" "/home/ejidos/backups/plaza-local/database-${release_stamp}.sqlite"
sha256sum "/home/ejidos/backups/plaza-local/database-${release_stamp}.sqlite"
```

Si `DB_CONNECTION=mysql` o `mariadb`, usar las credenciales ya configuradas sin imprimirlas:

```bash
php artisan down --render="errors::503"
php artisan db:show
mysqldump --single-transaction --routines --triggers --databases "$(php artisan tinker --execute='echo config("database.connections.mysql.database");' 2>/dev/null)" > "/home/ejidos/backups/plaza-local/database-${release_stamp}.sql"
test -s "/home/ejidos/backups/plaza-local/database-${release_stamp}.sql"
sha256sum "/home/ejidos/backups/plaza-local/database-${release_stamp}.sql"
```

No continuar si el archivo no existe, está vacío o no produce checksum.

## 3. Actualizar código y base

```bash
git pull --ff-only origin feature/feed-social-comercial
git rev-parse HEAD
git describe --tags --exact-match HEAD
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan storage:link
```

No ejecutar `migrate:fresh`, `db:wipe` ni copiar `.env`/SQLite desde Windows.

Agregar o actualizar solamente estas dos líneas del `.env` remoto:

```dotenv
PLAZA_RELEASE_VERSION=2.0.0-rc.5
PLAZA_RELEASE_CHANNEL=preproduction
PLAZA_DEMO_ACCESS_ENABLED=false
```

Mantener deshabilitados los pagos. Ninguna variable del servidor debe convertir `marketplace.payments_enabled` a verdadero; esa puerta permanece cerrada en código.

## 4. Poblar V2 DEMO de forma idempotente

```bash
php artisan plaza:demo-marketplace --force
php artisan plaza:demo-marketplace --force
```

La segunda ejecución debe terminar sin duplicar los perfiles ni las claves públicas fijas del escenario. No usar `tests/Browser/fixture.php`: ese archivo borra exclusivamente su SQLite aislado y no pertenece al despliegue.

## 5. Compilar y transferir assets desde Windows

En PowerShell, dentro del repositorio oficial:

```powershell
& "C:\Users\pccue\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe" ".\node_modules\vite\bin\vite.js" build
scp -r ".\public\build" ejidos@192.168.1.91:/home/ejidos/plaza-local/public/
```

En el servidor, hacer legibles directorios y archivos sin dar permisos de escritura global:

```bash
cd /home/ejidos/plaza-local
find public/build -type d -exec chmod 755 {} +
find public/build -type f -exec chmod 644 {} +
test -r public/build/manifest.json
find public/build/assets -maxdepth 1 -type f -print
php artisan optimize:clear
php artisan optimize
sudo systemctl restart plaza-local-worker plaza-local-reverb
sudo systemctl --no-pager --full status plaza-local-worker plaza-local-reverb
php artisan up
```

Si el nombre real de los servicios difiere, detectarlo en el preflight; no inventar ni crear unidades nuevas durante este release.

## 6. Verificación posterior

```bash
git rev-parse HEAD
git describe --tags --exact-match HEAD
php artisan migrate:status
php artisan about --only=environment
curl -I -H 'Host: shelby-paginal-enrique.ngrok-free.dev' http://127.0.0.1/
curl -I -H 'Host: shelby-paginal-enrique.ngrok-free.dev' http://127.0.0.1/demo/acceso
curl -I -H 'Host: shelby-paginal-enrique.ngrok-free.dev' http://127.0.0.1/build/manifest.json
curl -i -X POST -H 'Host: shelby-paginal-enrique.ngrok-free.dev' http://127.0.0.1/webhooks/stripe
```

Además, comprobar en navegador acceso DEMO, etiqueta V2, imágenes, administración y dos usuarios enviando/recibiendo un mensaje sin recargar. La ruta de Stripe debe responder `503`; un `101 Switching Protocols` en `/app/` confirma el transporte Reverb, no un webhook de pago.

## 7. Rollback de emergencia

`v1.0.0` protege el código anterior, no transforma automáticamente datos nuevos. Antes de volver a esa etiqueta, mantener la aplicación abajo y restaurar el respaldo compatible creado antes del despliegue. No ejecutar un checkout de V1 sobre una base migrada a V2 sin revisar primero las migraciones aplicadas.

Registrar commit, etiqueta, respaldo, resultado de migraciones y hora de reactivación. Si falla una comprobación crítica, dejar mantenimiento activo y restaurar código + base como una unidad.
