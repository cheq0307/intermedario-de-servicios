# Auditoría de preparación para producción

> Informe histórico. Las correcciones y comprobaciones posteriores se documentan en `VERIFICACION_PRODUCCION_2026-09-20.md`. Las pruebas de regresión se trasladaron a `tests/Feature/PaymentReadinessAuditTest.php` y ahora forman parte de la suite normal; los resultados originales de este informe no representan su estado actual.

Fecha: 14 de septiembre de 2026. Código: `68924d1`, rama `feature/feed-social-comercial`.

## Dictamen

**NO APTO todavía para lanzamiento con cobros reales.** No se retiraron ni deshabilitaron pasarelas o cobros. Esta revisión no cambió lógica de aplicación, tarifas, configuración, credenciales ni datos del servidor. No se hizo commit ni despliegue.

El dictamen se refiere al código local revisado y a las comprobaciones descritas; no certifica toda la aplicación ni sustituye una auditoría independiente. No se probaron pagos reales, entrega real de SMS/correo, concurrencia MySQL, carga, restauración ni navegación responsive en dispositivos.

## Evidencia ejecutada

- Suite existente: **239 pruebas, 1346 aserciones, todas aprobadas**. PHP 8.2.12, PHPUnit 11.5.56, SQLite en memoria, correo array y cola síncrona según phpunit.xml.
- JavaScript: **10 pruebas aprobadas** de polling, orden de mensajes, borradores, desconexión y sesiones expiradas.
- Build Vite aprobado, salida aislada en `storage/framework/audit-build-20260914`; no se sobrescribió `public/build`.
- Cinco pruebas de expectativas de seguridad/integridad añadidas exclusivamente como evidencia en `docs/audit/PaymentReadinessAuditTest.php`. **Las cinco fallan y reproducen defectos**; están fuera de la suite normal, no ocultas como pruebas aprobadas. Cola falsa para impedir movimientos de dinero; no requieren claves ni acceso externo.
- `composer audit --locked --no-interaction --format=json`: un aviso high para `league/commonmark` 2.9.2; alcance explicado abajo. No se actualizaron dependencias.
- SSH de lectura a `ejidos@192.168.1.91`: rechazado (`Permission denied`). La versión y configuración actuales del servidor no fueron verificadas.
- Git: se conservaron el cambio preexistente de `package-lock.json` y los archivos no rastreados del usuario. Entre archivos rastreados con nombre de entorno se encontró únicamente `.env.example`; esto no constituye escaneo completo de secretos del historial.
- No se ejecutó auditoría npm: no se encontró su CLI en el runtime consultado. Build aprobado no equivale a dependencias frontend libres de vulnerabilidades.

## Hallazgos bloqueantes

### P1-01 — Una segunda confirmación Stripe puede provocar un reembolso indebido

Ubicación: `app/Http/Controllers/StripeWebhookController.php:21-45`.

La deduplicación solo considera event_id. Dos eventos con IDs distintos para el mismo PaymentIntent exitoso hacen que el segundo encuentre una orden que ya no está awaiting_payment y la envíe a refund_pending. Prueba `test_second_success_event_must_not_refund_paid_order`: esperado paid, observado refund_pending. No se ejecutó el reembolso externo porque la cola está simulada.

Cierre: idempotencia por operación y estado, distinguir pagos tardíos de confirmaciones repetidas, mantener terminales y probar repetición secuencial/concurrente y orden de entrega variable.

### P1-02 — Notificaciones tardías pueden degradar pagos confirmados

Mismo controlador, rama payment_failed. Prueba `test_delayed_failure_must_not_downgrade_paid_payment`: tras éxito seguido de fallo, el pago termina failed y la orden permanece paid. Esto puede impedir la liberación al completar, que busca un pago paid.

Cierre: transiciones financieras explícitas, recuperación del estado vigente del proveedor cuando sea necesario y pruebas de eventos desordenados.

### P1-03 — La confirmación Stripe no contrasta importe y moneda de la orden

Mismo controlador. Solo se busca provider_reference; no se comprueban amount_received, currency ni coherencia test/live. Prueba firmada local `test_wrong_amount_must_not_fulfill_order`: 1 centavo confirma una orden de 10,000 centavos.

Esto demuestra ausencia de defensa ante una discrepancia de integración, NO que cualquiera pueda falsificar una firma de Stripe. La firma inválida sí se rechaza.

Cierre: validar importe, moneda, entorno y asociación; registrar discrepancias sin entregar ni liberar automáticamente.

### P1-04 — Un administrador puede resolver su propia disputa comercial

Ubicación: `app/Http/Controllers/DisputeController.php:89`, `authorizeAdmin` e `isAdmin`.

El control comprueba el rol, pero no si su identidad está vinculada al comprador o al proveedor. Prueba `test_admin_must_not_resolve_own_linked_client_dispute`: se observa status resolved en un expediente propio. La separación de tablas no evita este conflicto de interés. Otras acciones, como revisar documentos, sí consultan ownsMarketplaceAccount.

Cierre: impedir resolución por cualquiera de las partes vinculadas y asignar otro administrador; comprobar también los demás actos administrativos sensibles. Nunca sustituir esta validación por ocultar botones.

### P1-05 — Mercado Pago ignora reembolsos posteriores de promociones activas

Ubicación: `app/Services/Payments/MercadoPagoPromotionCheckout.php:93`, retorno anticipado para promociones activas.

Prueba `test_refunded_promotion_must_not_remain_active`: después de conciliar refunded para el mismo pago, sigue active. El mismo retorno evita revisar posteriores contracargos. Un segundo pago solo genera un reporte, no un proceso de recuperación.

Cierre: separar estado de campaña y de pago, conciliar reembolsos/contracargos/duplicados, conservar historial y definir la política comercial correspondiente.

### P1-06 — El cobro para publicar vacantes no tiene implementación real

Ubicación: `app/Http/Controllers/JobVacancyController.php:81-93`.

pay únicamente admite entorno fake y genera fake_job_. En producción responde 422, independientemente de configurar Stripe para pedidos. La vacante se crea pending_payment y no dispone aquí de cobro real para publicarse.

Cierre: integrar este concepto de cobro y sus webhooks/idempotencia, conservando la tarifa y módulo. No eliminar el cobro para encubrir el bloqueo.

### P1-07 — Recuperación financiera incompleta

Ubicaciones: `app/Jobs/FinalizeMarketplacePayment.php`, `app/Services/Payments/StripeConnectGateway.php`, `StripeWebhookController.php`, `routes/console.php`.

- El webhook solo procesa éxito/fallo de PaymentIntent. No hay reconciliación de refund.updated, contracargos ni transferencias/reversiones en los archivos revisados. Un reembolso devuelto como pendiente puede quedarse así después de que el job finalice satisfactoriamente.
- FinalizeMarketplacePayment usa el gateway configurado globalmente, no selecciona por payment.provider. Un cambio de driver con trabajos pendientes puede procesarlos con la implementación incorrecta.
- Reembolsar un pago no contiene recuperación de una transferencia previa. Hace falta diseñar y probar qué ocurre tras liberar fondos o ante un contracargo.
- El alta Connect refresca las capacidades solo al volver del onboarding y releasePayment confía en valores locales; no hay actualización de cuenta por webhook en el controlador revisado.

Cierre: resolución de gateway por pago, registro persistente de operaciones/reintentos y conciliación, actualización de habilitaciones y pruebas de reembolso pendiente, saldo insuficiente, caída del worker y recuperación tras una respuesta perdida.

### P1-08 — Falta limitar abuso en registro y escrituras pesadas

Ubicaciones: `routes/web.php:51-54`, registro, publicaciones, mensajes y soporte; `PostController.php:36-40`.

No se encontraron límites de frecuencia de aplicación para esas escrituras (sí existen para login, SMS y algunos endpoints de lectura). Publicar admite hasta seis archivos de 50 MB por solicitud. El token de publicación evita duplicar el mismo envío, no limita nuevos envíos. Hay riesgo de agotamiento de CPU, disco, base y correo, especialmente en el servidor de prueba.

Cierre: límites por IP/cuenta/acción, cuotas de almacenamiento y tamaño global, tratamiento de abuso y límites coherentes en servidor web/PHP. Verificar primero si existe protección externa; no se pudo inspeccionar.

## Endurecimiento y pruebas adicionales

### P2-01 — Dependencia con aviso de seguridad; explotación no demostrada

`composer.lock` fija league/commonmark 2.9.2. Aviso GHSA-8rr7-cvq3-gmfh, corregido en 2.10.0. Requiere registrar AttributesExtension; no se encontró ese registro en app/config/resources. No se afirma que Plaza Local sea explotable por esta vía actualmente.

Fuente primaria: https://github.com/thephpleague/commonmark/security/advisories/GHSA-8rr7-cvq3-gmfh

Cierre: actualizar de forma acotada, ejecutar pruebas y repetir auditoría; revisar dependencias frontend por separado. No cambiar todas las versiones automáticamente.

### P2-02 — Texto de simulación convive con el formulario Stripe real

`resources/views/orders/show.blade.php:51-53` y `orders/product-checkout.blade.php:29` dicen que no se mueve dinero real sin condicionar esos avisos al driver. La vista SÍ contiene Stripe Payment Element y confirmPayment en líneas 92-102; no falta todo el checkout, falta validar su funcionamiento de extremo a extremo y corregir el mensaje.

### P2-03 — Concurrencia y efectos externos requieren prueba en MySQL

Creación de pago dentro de transacciones en ProductOrderController y JobProposalController; una respuesta externa exitosa seguida de rollback puede dejar operaciones huérfanas. Expiración bloquea reserva→orden→listing, cancelación bloquea orden→listing, y el webhook bloquea pago sin bloquear explícitamente la orden antes de decidir. SQLite en memoria no reproduce estos bloqueos ni demuestra ausencia de deadlocks/carreras.

Cierre: orden consistente de locks, recuperación de efectos externos y pruebas paralelas de última unidad, cancelar contra webhook, vencer contra pago, y doble aceptación. No se declara sobreventa reproducida: es riesgo estático pendiente de prueba concurrente.

### P2-04 — Endurecimiento administrativo y de navegador

La verificación histórica de correo/teléfono no es un segundo factor de cada inicio de sesión. El login administrativo acepta contraseña y comprueba verificaciones persistentes después. No hay MFA de sesión configurado en las rutas/Fortify revisados. SecurityHeaders agrega cabeceras útiles pero no CSP.

Cierre propuesto: MFA para personal privilegiado y confirmación reciente para actos financieros; CSP compatible con Stripe/Livewire, primero report-only y después exigida tras pruebas. No romper la interfaz imponiendo una política a ciegas.

### P2-05 — Suspensión de cuenta impide acceder al soporte interno

EnsureAccountIsActive cierra sesión en toda ruta del grupo, incluido soporte. El mensaje pide contactar soporte sin que ese canal interno quede accesible. Distinguir suspensión comercial, bloqueo por seguridad y baja; proporcionar canal de apelación seguro. No cambiar las reglas sin acordar esa política.

### P2-06 — Monitorización interna no detecta por sí sola un scheduler/worker detenido

OperationalHealthCheck verifica conectividad, escritura y failed_jobs, no antigüedad de cola pendiente ni heartbeat externo. Si el scheduler muere, su comprobación programada tampoco corre. Se requiere observación independiente y alertas fuera del mismo punto de fallo.

## Controles existentes observados

- Firmas de webhook, deduplicación básica y claves de idempotencia presentes; requieren ampliar casos, no empezar desde cero.
- Límites de SMS, caducidad de desafío y confirmación con Twilio; no se envió SMS en esta auditoría.
- Identidades y sesiones administrativas separadas, validación de acceso activo y verificación de contactos.
- Pruebas de acceso de terceros a órdenes, conversaciones, disputas y documentos en la suite existente.
- Documentos con MIME/tamaño validados en backend, disco privado por defecto y descarga autorizada sin caché. Configuración real del disco pendiente de comprobar.
- Reserva de stock y bloqueos transaccionales presentes; falta probar concurrencia con el motor real.
- Simulación de pagos bloqueada en production por entorno. Eso no vuelve real el flujo de vacantes.

## Puertas de salida antes de autorizar producción

1. Corregir P1, integrar las regresiones en tests/Feature y aprobar suite completa. Conservar cobros y pasarelas.
2. Validar en sandbox pedidos, servicios, vacantes y promociones: éxito, rechazo, abandono, reintento, importe incorrecto, doble notificación, reversión, disputa, reembolso y transferencia fallida.
3. Probar MySQL/MariaDB aislado y el driver de colas de despliegue, no solo SQLite/sync. Validar migraciones sobre copia restaurada.
4. Comprobar servidor real: commit, APP_ENV, APP_DEBUG=false, HTTPS/dominio estable, cookies seguras, proxies, permisos, document root public, .env y copias inaccesibles, firewall y exposición de base de datos. No imprimir secretos.
5. Comprobar worker persistente, cron, retraso de jobs, alertas externas y recuperación tras reiniciar procesos/servidor.
6. Restaurar backup de base y archivos en otra instancia; medir y acordar pérdida de datos tolerable y tiempo de recuperación. Un dump existente no prueba restauración.
7. Confirmar onboarding/capacidades de cuentas reales y separación de credenciales test/live cuando se autorice operar dinero. No usar la clave de diagnóstico de lectura para procesar cobros.
8. Validar registro, email, SMS, recuperación de acceso, cliente/proveedor/admin y responsive en navegador/móvil con cuentas piloto; revisar errores 500 y logs anonimizados.
9. Revisar carga representativa: usuarios simultáneos, polling, adjuntos y consultas. No existe una cifra de capacidad medida en esta auditoría.
10. Acordar y publicar términos, privacidad, cobros, cancelaciones, disputas y tratamiento fiscal con revisión competente. No se localizaron rutas legales ni aceptación en el registro consultado; confirmar si existen documentos externos.
11. Actualizar runbook: PREPRODUCCION.md conserva métricas sin medición y menciona plaza:grant-admin, que la suite confirma retirado. No seguirlo como procedimiento actual de despliegue.

## Reproducir la evidencia

Desde la raíz del repositorio local, con dependencias instaladas:

```powershell
php vendor/phpunit/phpunit/phpunit --no-progress
node --test tests/js/live-polling.test.js
php vendor/phpunit/phpunit/phpunit docs/audit/PaymentReadinessAuditTest.php --no-progress --do-not-cache-result
composer audit --locked --no-interaction --format=json
```

La tercera orden debe fallar en la versión auditada; esas fallas documentan los cinco defectos. No ejecutar pruebas contra la base del servidor: esta evidencia usa phpunit.xml y SQLite en memoria. No activar claves reales para reproducirla.

## Siguiente bloque de trabajo

Reparar integridad de webhooks y conflicto de interés primero; después completar cobro de vacantes y recuperación financiera, endurecer abuso y verificar despliegue. La estrategia para retener usuarios y monetización sigue pendiente de decisión y no fue alterada por esta auditoría.
