# Verificación y correcciones — 20 de septiembre de 2026

## Alcance y estado

Revisión local sobre `68924d1`, con cambios sin commit. Se conservaron pasarelas, cobros y tarifas. No se modificaron credenciales, datos del servidor ni la base de datos existente. No se realizó despliegue ni se movió dinero.

**No equivale a una certificación de producción.** Los defectos reproducidos se corrigieron; falta comprobar los servicios externos y el despliegue. SSH a `192.168.1.91` agotó el tiempo de conexión el día de esta revisión.

## Correcciones

- Stripe: confirmaciones duplicadas no vuelven a cobrar/reembolsar; fallos tardíos no degradan pagos confirmados. Se validan importe, moneda y entorno. Confirmaciones anteriores a la asociación local se reintentan, en lugar de descartarse.
- Stripe: éxito tardío de una orden cancelada solicita un único reembolso. Notificaciones firmadas concilian el reembolso completo iniciado por la aplicación. Se rechazan claves cuyo entorno no coincide con `STRIPE_LIVE_MODE`.
- La finalización usa el proveedor guardado en cada pago, no el proveedor global que pudiera haber cambiado después.
- Disputas: un administrador no puede resolver la disputa de su propia cuenta de comprador o proveedor vinculada.
- Mercado Pago: las promociones pagadas admiten notificaciones de reembolso/contracargo, sin reiniciarse con aprobaciones repetidas o antiguas. Validación del entorno y protección frente a segundos pagos.
- Vacantes: checkout de Mercado Pago con tarifa congelada, confirmación mediante webhook firmado, retorno que no publica por sí mismo y protección frente a duplicados/reapertura de vacantes cerradas. Nueva migración para referencias y datos de checkout.
- Corregido el formato de cabeceras de idempotencia usado con el SDK de Mercado Pago; prueba con transporte simulado del propio SDK.
- Límites de solicitudes en registro, publicaciones, mensajes, soporte y checkout. Ajuste de orden de bloqueos en vencimiento de reservas.
- Avisos de simulación limitados a pagos simulados. CommonMark actualizado de 2.9.2 a 2.10.1.

## Verificación local

Resultado final: **260 pruebas PHP aprobadas (1417 aserciones)** y **10 pruebas JavaScript aprobadas**. Build Vite aprobado; comprobación de formato de los últimos cambios con Pint aprobada; `git diff --check` sin errores de espacios.

Pruebas PHP con SQLite en memoria y proveedores simulados, sin llamadas de cobro externas. Regresiones incorporadas a `tests/Feature/PaymentReadinessAuditTest.php` y `tests/Feature/VacancyPaymentTest.php`.

Comandos reproducibles:

```sh
php artisan test --compact
node --test tests/js/live-polling.test.js
composer audit --no-interaction
node node_modules/vite/bin/vite.js build --outDir storage/framework/audit-build-20260920
git diff --check
```

Composer audit no reporta vulnerabilidades conocidas después de la actualización. Esto no sustituye una auditoría de dependencias JavaScript, infraestructura o configuración real. El build se generó en una carpeta aislada, sin reemplazar `public/build`.

## Requisitos antes del despliegue

1. Recuperar acceso al servidor y comprobar la revisión realmente instalada, respaldo recuperable, configuración, permisos y estado de servicios.
2. Revisar las nuevas variables sin publicar sus valores secretos: `STRIPE_LIVE_MODE=false` para pruebas; `MARKETPLACE_VACANCY_PAYMENT_DRIVER=mercadopago`; sandbox de Mercado Pago consistente con sus credenciales. Activar modo real requiere una decisión explícita y claves/webhooks del mismo entorno.
3. Aplicar la migración `2026_09_20_000000_add_vacancy_checkout_fields.php` tras respaldo. No ejecutar `migrate:fresh` ni borrar datos.
4. Configurar webhooks accesibles por HTTPS, firma correcta y eventos Stripe `payment_intent.succeeded`, `payment_intent.payment_failed`, `refund.created`, `refund.updated`, `refund.failed`. Verificar permisos de las claves y cuentas conectadas; una clave de diagnóstico de solo lectura no sirve para procesar cobros.
5. Desplegar assets y código compatibles, refrescar cachés y reiniciar workers según el procedimiento del servidor. Comprobar scheduler y colas.
6. Recorrer con cuentas de prueba reales: registro, correo/SMS, login de cliente/admin, publicación, mensajes, soporte, compra/servicio, liberación, disputa, reembolso, promoción y vacante. Las pruebas automatizadas locales no demuestran que estos servicios externos estén configurados.

## Pendientes que impiden declarar cobros reales listos

- Recuperación operativa y conciliación periódica de fallos, pagos duplicados, reembolsos parciales/externos, contracargos y reversos de transferencias. Algunos casos se registran para revisión manual; no tienen resolución automática completa.
- Verificación actualizada de capacidades de cuentas conectadas y revisión de versión/API de Stripe antes de adoptar el flujo definitivo.
- Pruebas de concurrencia en MySQL, carga y recuperación de respaldos sobre infraestructura representativa.
- Revisión de MFA administrativo, CSP, cuotas de almacenamiento, observabilidad y acceso a soporte de cuentas suspendidas.
- Políticas y términos de cobro, cancelación, disputa y privacidad acordados para el lanzamiento.

El informe del 14 de septiembre conserva la evidencia histórica y los hallazgos no cerrados. No deben interpretarse las correcciones de este documento como resolución automática de todos aquellos hallazgos.
