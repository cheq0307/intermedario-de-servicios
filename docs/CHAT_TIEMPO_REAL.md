# Chat de Plaza Local por fases

## Fase 1 — Esquema y modelos

Se amplía el sistema existente; no se reemplazan conversaciones, mensajes ni participantes y no se ejecuta `migrate:fresh`. El chat actual sigue usando sus controladores y polling. Esta fase no activa WebSockets, subida de imágenes ni nuevos estados en pantalla.

### Correspondencia con la especificación

| Concepto solicitado | Implementación del proyecto |
| --- | --- |
| Publication / publication_id | `Post` / `conversations.post_id`, nullable; relación adicional `publication()` sobre la misma clave |
| conversation_user | `conversation_participants`, existente, con unicidad por conversación/usuario |
| Orden opcional | `order_id` y `agreement_order_id` existentes; se respeta la separación entre negociación y operación |
| Messages | `messages` existente, conservando `attachment_path` para compatibilidad |
| Attachments | `message_attachments`, modelo `MessageAttachment`, múltiples archivos por mensaje |
| read_at | `message_receipts.read_at`, por destinatario; se conservan los cursores históricos del pivot |

No se inventa una segunda entidad Publication ni una segunda tabla de participantes. Oferta/Solicitud son publicaciones `Post`; Vacante usa actualmente `JobVacancy` y su flujo de postulaciones, que no se transforma en negociación comercial en esta fase. Los participantes no tienen un rol comprador/vendedor fijo dentro del chat.

### Archivos completos

Los archivos del repositorio contienen el código completo, no fragmentos para pegar:

- `database/migrations/2026_09_21_000000_prepare_realtime_chat_storage.php`
- `app/Models/Conversation.php`
- `app/Models/Message.php`
- `app/Models/MessageAttachment.php`
- `app/Models/MessageReceipt.php`
- `app/Domain/Marketplace/Enums/MessageDeliveryStatus.php`
- `tests/Feature/RealtimeChatStorageTest.php`

### Contratos para las fases siguientes

- **Enviado:** mensaje persistido por el servidor. `created_at` representa ese momento; no se considera entregado solo porque se emitió el evento.
- **Entregado:** el cliente destinatario confirma recepción; `delivered_at` se establece en el servidor tras autorizar su participación.
- **Leído:** el destinatario ve el hilo activo y confirma el mensaje. `read_at` se establece en el servidor y también implica entrega. No se aceptarán timestamps arbitrarios del navegador ni se marcarán mensajes de otros hilos.
- Recibos únicos por mensaje/destinatario. Las acciones deben comprobar que el destinatario participa en la conversación y no es el remitente, hacer escrituras monotónicas e idempotentes y sincronizar los cursores existentes. Estas acciones/autorizaciones son parte de la fase 2, no del modelo de datos por sí solo.
- `client_message_id` es un UUID del cliente para reconciliar el envío optimista. La restricción única comprende conversación, remitente y UUID. La acción de envío deberá validar el token y recuperar el mensaje existente al reintentar; la migración sola no implementa ese comportamiento. Los mensajes anteriores conservan NULL.
- Índice `(conversation_id, id)` para paginación por cursor hacia atrás; sin OFFSET sobre historiales largos. `latestMessage()` permite cargar el último mensaje de cada conversación sin consultas individuales.
- Adjuntos por defecto en disco `local`, que en este proyecto apunta a `storage/app/private`. No hay URL pública en el modelo; `path` y `disk` se excluyen de la serialización. Esto no sustituye una policy ni la autorización de descargas.
- Antes de habilitar imágenes deben implementarse validación de MIME real, tamaño/cantidad/dimensiones, autorización de carga/descarga, eliminación de metadatos sensibles cuando corresponda y limpieza de archivos tras purgar mensajes. El cascade elimina filas, **no archivos físicos**. El disco privado debe mantenerse privado también al migrar a almacenamiento externo.
- No se fabrican fechas de entrega/lectura para mensajes históricos. El chat existente conserva `last_read_at` y `last_read_message_id`; la fase 2 debe definir la compatibilidad antes de que la nueva UI consuma recibos.
- No se alteran cierres, vencimientos, archivo o retención de negociaciones, ni reglas de eliminación de órdenes existentes.

## Instalación y verificación de esta fase

Verificación local del 21 de septiembre de 2026: 17 pruebas focalizadas aprobadas (81 aserciones); suite completa con 272 pruebas aprobadas (1488 aserciones). Pint y `git diff --check` sin errores.

No se requieren paquetes Composer/npm nuevos ni claves de broadcasting para la fase 1. Con dependencias del proyecto instaladas:

```bash
php artisan test --compact --filter='RealtimeChatStorageTest|ConversationTest|PublicationNegotiationConversationTest'
```

La migración se prueba sobre SQLite en memoria, incluyendo actualización con datos históricos y rollback. No implica validación de concurrencia en MariaDB/MySQL ni se aplica a la base local/servidor durante la implementación.

En el despliegue autorizado, después de respaldo y con workers detenidos/mantenimiento según el procedimiento del proyecto:

```bash
php artisan migrate --force
```

El rollback de esta nueva migración conserva mensajes y participantes, pero elimina las tablas nuevas de recibos/adjuntos y los tokens nuevos. No debe ejecutarse en producción sin respaldar los datos que se hayan generado con las fases posteriores.

## Fases pendientes — requieren confirmación

2. Acciones autorizadas, policy, eventos `MessageSent`, `UserTyping`, `MessageRead` y confirmación de entrega; `PrivateChannel`, `routes/channels.php`, Laravel Notifications, Reverb/Echo y configuración del servidor. Los eventos se emitirán después del commit de base de datos. Se documentarán comandos Composer/npm y `.env` al implementar esta fase, sin activar un servicio incompleto ahora.
3. `ConversationList`, `ChatWindow`, `MessageInput` en Livewire; búsqueda y agrupación por publicación, paginación anterior, estados, adjuntos, notificaciones fuera del hilo activo, envío optimista y layout responsive con tokens de Plaza Local. Pruebas de permisos, reconexión, deduplicación y vista móvil/escritorio.

La implementación se detiene al terminar cada fase para obtener confirmación. No se declara el chat en tiempo real listo con solo la fase de almacenamiento.
