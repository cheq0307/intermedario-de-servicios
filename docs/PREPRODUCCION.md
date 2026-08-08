# Estado y salida a preproducción de Plaza Local

## Avance estimado

Estas métricas miden cosas distintas y no deben mezclarse:

- Flujo de contratación de servicios con pagos simulados: **95%**.
- MVP completo, incluyendo productos, búsqueda, notificaciones y administración: **85%**.
- Preparación para preproducción controlada: **85%**.
- Preparación para producción con dinero real: **40%**.

## Flujo funcional disponible

- Una identidad puede operar como cliente, proveedor, ambas o solamente personal administrativo.
- Verificación de correo y recuperación de contraseña a nivel de aplicación.
- Perfiles comerciales y reputación pública.
- Feed local de productos, servicios y solicitudes.
- Propuestas privadas con precio y plazo.
- Conversaciones privadas.
- Contratación y seguimiento: espera de pago, pago, inicio, entrega y confirmación.
- Pagos simulados con comisión congelada, liberación al completar y devolución administrativa.
- Cancelación previa al inicio con motivo.
- Disputas después de iniciar, con expediente y resolución administrativa.
- Reseñas bilaterales únicamente después de una orden completada.
- Cálculo y congelamiento de comisión por orden.

## Estado de la infraestructura de staging

- Completado: SMTP de Gmail y entrega básica verificada.
- Completado: base de datos y credenciales exclusivas de staging.
- Completado: entorno staging sin depuración y con clave propia.
- Completado: HTTPS temporal mediante ngrok y cookies seguras.
- Completado: worker de colas persistente mediante systemd.
- Completado: scheduler ejecutado por cron cada minuto.
- Completado: backups automáticos de base y archivos con validación de integridad.
- Completado: cuenta superadministradora y cuenta administradora delegada.
- Pendiente: restaurar un backup completo en una instancia separada.
- Pendiente: alertas externas y revisión de rotación/retención de logs.
- Pendiente: ejecutar y firmar la matriz de aceptación con las cuentas piloto.

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
5. Simular el pago; comprobar que antes no se pueda iniciar, y después iniciar, entregar y confirmar.
6. Publicar reseñas desde ambas cuentas.
7. Abrir otra orden, iniciar y abrir una disputa.
8. Responder como ambas partes y resolver como administrador.
9. Verificar que un tercero no pueda acceder a orden, chat o disputa.
10. Restaurar un backup en una instancia separada.

11. Activar cliente y proveedor en una sola cuenta y cambiar de contexto.
12. Probar una cuenta exclusivamente administrativa sin formulario comercial.
13. Resolver disputas con liberación y devolución de pagos simulados.
Los pagos reales deben permanecer desactivados durante esta etapa.
