# Pagos e inventario en staging

## Lo que ya funciona

- Compra directa de un producto de precio fijo.
- Una orden por comercio; no se mezclan vendedores en el mismo pedido.
- Reserva atómica de existencias durante 20 minutos.
- Cálculo y congelamiento de la comisión al crear la orden.
- Pago simulado, preparación, entrega y confirmación del comprador.
- Cancelación antes del pago y devolución inmediata de existencias.
- Liberación automática de reservas vencidas.
- Al aceptar una propuesta de servicio se crea una orden pendiente de pago.
- El proveedor no puede iniciar el trabajo antes de registrarse el pago simulado.
- La confirmación del cliente libera el pago simulado menos la comisión congelada.
- Cancelar antes de iniciar anula el pago pendiente.
- Una resolución administrativa puede liberar o devolver un pago simulado retenido.

## Configuración temporal de staging

El simulador puede habilitarse en preproducción controlada:

```dotenv
APP_ENV=staging
APP_DEBUG=false
MARKETPLACE_PAYMENT_DRIVER=fake
MARKETPLACE_ALLOW_FAKE_PAYMENTS=true
MARKETPLACE_RESERVATION_MINUTES=20
```

El código rechaza el simulador cuando `APP_ENV=production`, aunque alguien intente habilitar la variable. Antes de producción debe existir un adaptador para la pasarela real y `MARKETPLACE_ALLOW_FAKE_PAYMENTS=false`.

## Procesos obligatorios del servidor

Además del servidor web, staging necesita:

```bash
php artisan queue:work --tries=3 --timeout=90
php artisan schedule:run
```

`queue:work` debe mantenerse activo con Supervisor o systemd. `schedule:run` debe ejecutarse mediante cron cada minuto; esa tarea libera las reservas de inventario abandonadas.

## Prueba manual mínima de productos

1. Aprobar un proveedor desde Administración.
2. Publicar un producto de precio fijo con existencias.
3. Entrar con una cuenta cliente y pulsar **Comprar**.
4. Crear el pedido y verificar que disminuyan las existencias.
5. Simular el pago como cliente.
6. Marcarlo listo y entregado como proveedor.
7. Confirmar la entrega y calificar como cliente.
8. Crear otro pedido, cancelarlo antes del pago y comprobar que las existencias regresen.


## Prueba manual mínima de servicios

1. Publicar una solicitud como cliente y enviar una propuesta como proveedor aprobado.
2. Aceptar la propuesta y comprobar que la orden quede pendiente de pago.
3. Intentar iniciar como proveedor y comprobar que el sistema lo impida.
4. Simular el pago como cliente y comprobar el estado pagado.
5. Iniciar y entregar como proveedor; confirmar como cliente.
6. Comprobar que el pago simulado quede liberado y que se habiliten las reseñas.
7. Repetir el flujo, abrir una disputa y resolverla con devolución como administrador.
8. Verificar que el pago simulado quede reembolsado.
El simulador prueba la lógica interna, pero no acredita que SMTP, webhooks, reembolsos o depósitos bancarios reales funcionen. Esos componentes requieren pruebas de extremo a extremo con cuentas sandbox del proveedor seleccionado.
