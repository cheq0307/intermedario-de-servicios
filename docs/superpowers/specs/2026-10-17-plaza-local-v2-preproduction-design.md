# Plaza Local V2 en preproducción

## Intención

Convertir el estado local ya validado de Plaza Local en la nueva versión de preproducción. La versión anterior no seguirá operando en paralelo: su recuperación queda garantizada por la etiqueta Git inmutable `v1.0.0`.

## Resultado esperado

- La rama `feature/feed-social-comercial` contiene la versión candidata V2 y queda publicada en el remoto.
- El commit desplegado queda marcado como `v2.0.0-rc.1`.
- La interfaz identifica el entorno como `Versión 2 · Preproducción` sin introducir ruido visual en producción futura.
- Preproducción conserva sus datos existentes, aplica migraciones normales y recibe el escenario DEMO idempotente actual.
- Las imágenes y demás actividad DEMO son visibles desde el sistema desplegado.
- Las pasarelas y webhooks de pago permanecen deshabilitados.
- El tiempo real propio de mensajes, soporte y notificaciones permanece disponible.

## Reglas de seguridad y operación

- No ejecutar `migrate:fresh`, `db:wipe` ni reemplazar la base desplegada.
- Crear un respaldo recuperable antes de migrar o poblar datos.
- Ejecutar `plaza:demo-marketplace --force`; el comando debe conservar su comportamiento idempotente.
- Compilar Vite en Windows y transferir `public/build` al servidor, publicando el manifiesto junto con sus assets.
- No copiar `.env`, credenciales ni bases locales al servidor.
- No habilitar `marketplace.payments_enabled` ni rutas de proveedores de pago.
- Reiniciar o validar Reverb y los workers existentes sin cambiar sus credenciales.

## Identidad visual de la versión

La versión se obtiene de `PLAZA_RELEASE_VERSION` y el canal de `PLAZA_RELEASE_CHANNEL`. En preproducción se mostrará una etiqueta compacta `V2 · Preproducción` en las superficies DEMO; la ausencia de estas variables no debe romper ni etiquetar una instalación normal.

## Verificación

- Pruebas automatizadas y build verde antes del despliegue.
- Rama y etiqueta remotas apuntan al mismo commit.
- Servidor reporta el commit/tag esperado después del `git pull`.
- Migraciones completadas sin borrado.
- Seeder DEMO completado y ejecutable más de una vez.
- Respuesta HTTP correcta para inicio, login, acceso DEMO y administración.
- Imágenes DEMO servidas con CSS/JS correctos.
- Una comprobación de mensajes/notificaciones confirma que el tiempo real no fue confundido con webhooks de pago.

## Fuera de alcance

- Mantener V1 desplegada en otra URL.
- Importar la base local SQLite o credenciales locales.
- Activar pagos, cobros, comisiones o publicidad pagada.
- Declarar este candidato como producción final.
