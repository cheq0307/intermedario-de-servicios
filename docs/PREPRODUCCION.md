# Estado y salida a preproducción de Plaza Local

## Release vigente

- Candidato: `v2.0.0-rc.1`.
- Rama: `feature/feed-social-comercial`.
- Canal visible: `V2 · Preproducción`.
- Recuperación de la versión anterior: etiqueta inmutable `v1.0.0`.
- Pagos, cobros, comisiones y publicidad pagada: deshabilitados para esta salida y para el primer lanzamiento a producción.

## Flujo funcional disponible

- Una cuenta verificada puede comprar, solicitar, ofrecer y participar en su comunidad según sus capacidades activas.
- Perfiles públicos, reputación, publicaciones con medios, seguimiento e interacciones sociales.
- Descubrimiento unificado por Inicio, Comida, Servicios, Productos, Transporte y Empleo.
- Propuestas privadas, pedidos, acuerdos de servicio, entrega, confirmación, disputas y reseñas sin pasarela interna.
- Vacantes y postulaciones integradas al descubrimiento general.
- Conversaciones, soporte y notificaciones con actualización en tiempo real mediante Reverb y refresco de respaldo.
- Administración separada para cuentas, publicaciones, empleo, operaciones, disputas, soporte y configuración.
- Escenario DEMO idempotente con perfiles, publicaciones, imágenes, actividad, operaciones, vacantes y postulaciones visibles.

## Estado de infraestructura

- SMTP, HTTPS temporal, worker, scheduler y respaldos deben comprobarse en cada despliegue; una configuración histórica no cuenta como verificación actual.
- PHP-FPM/CLI requiere GD para el procesamiento de imágenes privadas del chat.
- Nginx necesita lectura y travesía sobre `public/build` y `storage/app/public`.
- Reverb es independiente de los webhooks de Stripe/Mercado Pago: estos últimos permanecen cerrados, Reverb permanece activo.

## Despliegue

El procedimiento vinculante para V2 está en [`docs/releases/V2_PREPRODUCCION.md`](releases/V2_PREPRODUCCION.md). Reglas obligatorias:

1. Preflight y respaldo verificable antes de modificar el servidor.
2. Nunca ejecutar `migrate:fresh`, `db:wipe` ni copiar la base SQLite local.
3. No copiar `.env`; solo ajustar las variables de release en el servidor.
4. Compilar Vite en Windows y transferir `public/build`.
5. Ejecutar migraciones incrementales y `plaza:demo-marketplace --force`.
6. Mantener la aplicación en mantenimiento si falla una migración, transferencia o verificación crítica.

## Matriz mínima de aceptación V2

1. Abrir acceso DEMO y comprobar `V2 · Preproducción`.
2. Entrar como perfiles ficticios de comprador, proveedor, empleador, postulante y administrador.
3. Visualizar publicaciones e imágenes reales en todos los módulos de descubrimiento.
4. Crear un pedido sin pago y recorrer aceptación, preparación/ejecución, entrega y confirmación.
5. Publicar una solicitud, enviar una propuesta y aceptarla.
6. Publicar una vacante y enviar una postulación.
7. Enviar mensajes entre dos navegadores sin recargar y comprobar campana/lectura.
8. Abrir y responder soporte sin recargar.
9. Confirmar que un tercero no puede leer conversaciones, documentos ni operaciones.
10. Confirmar que rutas de pasarela, publicidad pagada y webhooks de proveedor responden como servicio no disponible.
11. Confirmar que CSS, JavaScript, manifiesto e imágenes responden correctamente.
12. Registrar commit/tag desplegado y comprobar que existe un respaldo recuperable.

## Pendientes antes de producción

- Restaurar un respaldo completo en una instancia separada.
- Definir almacenamiento de medios persistente para nube y separar archivos públicos/privados.
- Configurar dominio estable, certificados, observabilidad, alertas y rotación de logs.
- Ejecutar pruebas representativas sobre MySQL/MariaDB y la cola real de despliegue.
- Revisar legalmente términos, privacidad, moderación, disputas y tratamiento de documentos.
- Completar y firmar la matriz de aceptación con cuentas piloto.
