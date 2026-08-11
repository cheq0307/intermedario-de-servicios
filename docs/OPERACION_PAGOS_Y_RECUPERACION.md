# Operacion, pagos y recuperacion

## Stripe Connect

La integracion usa **separate charges and transfers**: el cliente paga a la plataforma y el neto del proveedor se transfiere solo despues de confirmar la entrega. Stripe aloja el onboarding y recopila identidad/cuenta bancaria; Plaza Local solo conserva el identificador y los estados de habilitacion.

Variables requeridas:

```env
MARKETPLACE_PAYMENT_DRIVER=stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Registrar en Stripe el endpoint `https://DOMINIO/webhooks/stripe` con los eventos `payment_intent.succeeded` y `payment_intent.payment_failed`. El endpoint verifica firma y registra cada `event_id` una sola vez. Las liberaciones y reembolsos tienen clave de idempotencia y cinco intentos por cola.

Antes de aceptar dinero real:

1. Completar el alta de la plataforma Stripe Connect.
2. Ejecutar onboarding de dos proveedores desde **Mi perfil > Cobros y depositos**.
3. Probar tarjeta aprobada, rechazada, webhook repetido, pago tardio, reembolso, transferencia y transferencia fallida en modo test.
4. Conciliar importes bruto, comision y neto contra el Dashboard de Stripe.
5. Cambiar a llaves live solo tras revision legal/fiscal y una prueba de restauracion firmada.

Documentacion oficial: <https://docs.stripe.com/connect>, <https://docs.stripe.com/connect/payouts-connected-accounts>.

## Alertas

El scheduler ejecuta `php artisan plaza:health-check --notify` cada cinco minutos. Comprueba base de datos, cache, almacenamiento y `failed_jobs`. Para correo:

```env
OPERATIONS_ALERT_EMAIL=operacion@dominio.com
```

Para alertas de excepciones por Slack, crear un incoming webhook y configurar:

```env
LOG_CHANNEL=stack
LOG_STACK=daily,slack
LOG_LEVEL=error
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

Después: `php artisan optimize:clear && php artisan optimize`.

## Respaldo y restauracion

Validacion no destructiva:

```bash
php artisan plaza:verify-backup /ruta/database.sql.gz /ruta/uploads.tar.gz
```

Restauracion completa en una base aislada (el script rechaza nombres sin el prefijo de seguridad y nunca elimina la base):

```bash
export RESTORE_TEST_DATABASE=plaza_local_restore_test_$(date +%Y%m%d)
export BACKUP_DATABASE=/home/ejidos/backups/plaza-local/database_FECHA.sql.gz
export BACKUP_DEFAULTS_FILE=/home/ejidos/.plaza-local-backup.cnf
bash ops/test-restore.sh
```

La cuenta de MariaDB indicada debe poder crear la base aislada. Al terminar se deben revisar conteos, levantar una copia de la aplicacion contra esa base, completar el flujo de acceso/orden y documentar fecha y responsable. La eliminacion de la base de prueba es manual para evitar borrados accidentales.
