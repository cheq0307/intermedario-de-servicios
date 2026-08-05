# Estado y salida a preproducción de Plaza Local

## Avance estimado

Estas métricas miden cosas distintas y no deben mezclarse:

- Flujo de contratación de servicios sin pagos reales: **90%**.
- MVP completo, incluyendo productos, búsqueda, notificaciones y administración: **65%**.
- Preparación para preproducción controlada: **55%**.
- Preparación para producción con dinero real: **35%**.

## Flujo funcional disponible

- Registro diferenciado de clientes y proveedores.
- Verificación de correo y recuperación de contraseña a nivel de aplicación.
- Perfiles comerciales y reputación pública.
- Feed local de productos, servicios y solicitudes.
- Propuestas privadas con precio y plazo.
- Conversaciones privadas.
- Contratación y seguimiento: aceptada, en progreso, entregada y completada.
- Cancelación previa al inicio con motivo.
- Disputas después de iniciar, con expediente y resolución administrativa.
- Reseñas bilaterales únicamente después de una orden completada.
- Cálculo y congelamiento de comisión por orden.

## Pendientes obligatorios antes de staging

- Configurar SMTP real y probar entrega, rebotes y spam.
- Crear una base de datos y credenciales exclusivas de staging.
- Configurar `APP_ENV=staging`, `APP_DEBUG=false` y una `APP_KEY` propia.
- HTTPS, dominio temporal y cookies seguras.
- Cola persistente para correos y tareas; proceso worker supervisado.
- Programar `php artisan schedule:run` mediante cron.
- Backups automáticos de base de datos y prueba de restauración.
- Logs rotativos y alertas de errores.
- Crear la cuenta administradora mediante consola.
- Ejecutar pruebas de aceptación con cuentas y operaciones ficticias.

## Bloqueadores para producción con dinero real

- Seleccionar proveedor de pagos con producto oficial para marketplace en México.
- KYC y alta bancaria de proveedores mediante el proveedor de pagos.
- Webhooks firmados, idempotencia, conciliación y reembolsos.
- Términos y condiciones, aviso de privacidad y política de disputas revisados legalmente.
- Reglas fiscales y facturación revisadas con contador.
- Protección de archivos privados para evidencia.
- Rate limiting, monitoreo, análisis de vulnerabilidades y pruebas de recuperación.
- Procedimiento de soporte y respuesta a incidentes.

## Despliegue inicial de staging

```bash
git pull origin feature/feed-social-comercial
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan plaza:grant-admin correo@dominio.com
```

La aplicación debe apuntar a una base de datos vacía de staging. No debe reutilizar la base de datos de los otros proyectos ni una base de producción.

## Matriz mínima de aceptación

1. Registrar y verificar un cliente.
2. Registrar y verificar dos proveedores.
3. Publicar una solicitud y recibir dos propuestas.
4. Aceptar una propuesta y comprobar que la otra se rechaza.
5. Iniciar, entregar y confirmar un trabajo.
6. Publicar reseñas desde ambas cuentas.
7. Abrir otra orden, iniciar y abrir una disputa.
8. Responder como ambas partes y resolver como administrador.
9. Verificar que un tercero no pueda acceder a orden, chat o disputa.
10. Restaurar un backup en una instancia separada.

Los pagos reales deben permanecer desactivados durante esta etapa.
