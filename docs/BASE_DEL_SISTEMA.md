# Base funcional de Plaza Local

## Definición

Plaza Local es un marketplace comunitario para descubrir productos, contratar servicios y publicar necesidades dentro de una localidad. Incluye una capa social para mostrar trabajos, novedades y solicitudes, pero no pretende ser una red social general.

La plataforma debe registrar el origen y avance de cada operación para que la comisión, la reputación y cualquier protección se basen en evidencia del sistema.

## Tipos de cuenta

### Cliente

- Busca productos, servicios y proveedores.
- Publica solicitudes de trabajo con presupuesto, ubicación aproximada y urgencia.
- Realiza pedidos, solicita cotizaciones y conserva conversaciones relacionadas.
- Califica solamente después de una operación completada.

### Proveedor

- Publica productos, servicios y muestras de trabajos realizados.
- Mantiene disponibilidad, zona de atención, precios orientativos y horarios.
- Responde solicitudes, presenta cotizaciones y acepta pedidos.
- Recibe pagos mediante el proveedor externo integrado y la comisión configurada por la plataforma.

Una sola identidad puede combinar las capacidades de cliente y proveedor sin duplicar correo, historial ni reputación. Si posee ambas, cambia el contexto visible desde la misma sesión.

Los permisos administrativos no conceden capacidades comerciales automáticamente. Un administrador delegado puede conservar capacidades comerciales si la operación lo requiere, pero la cuenta `superadmin` es exclusivamente administrativa: no entra al feed, no compra, no publica y no opera como proveedor. Si la persona propietaria desea usar la plaza, debe registrar otra cuenta comercial.

El campo histórico `account_type` se conserva temporalmente para compatibilidad de datos, pero las autorizaciones se basan en roles/capacidades.

Activar la capacidad de proveedor crea un perfil comercial pendiente; no permite operar como proveedor hasta su aprobación administrativa.

## Escalabilidad territorial

Plaza Local se expandirá como un solo sistema central, no como una instalación independiente por pueblo. La unidad territorial será `community` (comunidad/localidad) y cada proveedor, publicación, solicitud, pedido y configuración operativa deberá pertenecer a una comunidad o a un alcance territorial explícito.

El `superadmin` tendrá visibilidad y control global sobre todas las comunidades. Los administradores delegados podrán limitarse más adelante a una o varias comunidades mediante asignaciones de alcance; nunca deberán obtener acceso territorial solo por conocer una URL o un identificador.

Abrir una nueva plaza significará crear y configurar una comunidad dentro del mismo despliegue: nombre, slug, centro geográfico, radio, estado, reglas y administradores responsables. Las consultas y políticas deberán aplicar siempre el alcance comunitario, mientras los reportes globales quedarán reservados al `superadmin`.

## Catálogo territorial y códigos postales

Los códigos postales mexicanos se importan desde el Catálogo Nacional de Códigos Postales de Correos de México. Plaza Local conserva una copia local para consultar sin depender de una API externa durante el registro.

El código postal se utiliza al registrar una comunidad administrada: propone estado, municipio y asentamientos oficiales. El catálogo conserva también el tipo de asentamiento, pero no se solicita en la interfaz porque no modifica el flujo comercial. No contiene coordenadas y no define distancias.

La importación es mantenimiento global reservado al `superadmin`: normalmente se realiza una sola vez y solo se repite cuando Correos de México publica una actualización. Se ejecuta con `php artisan plaza:import-postal-codes RUTA_AL_TXT`; el proceso actualiza registros coincidentes sin duplicarlos.

## Gestión administrativa

El panel administrativo no debe limitarse a contadores. Cada indicador operativo enlaza a una superficie de gestión. Los directorios de usuarios y proveedores son paginados y permiten buscar o filtrar por identidad, capacidad, comunidad, verificación y estado del proveedor.

La administración mantiene superficies separadas para comunidades, solicitudes de proveedor, moderación, disputas, delegación de administradores y auditoría. El `superadmin` conserva visibilidad global; la futura restricción territorial de administradores se resolverá mediante asignaciones explícitas, no ocultando datos únicamente en la interfaz.

## Visibilidad social y alcance comercial

El feed social mantiene visibles las publicaciones y solicitudes de todas las comunidades activas para favorecer descubrimiento e interacción. La exploración comercial es independiente: el usuario puede delimitar productos, proveedores y solicitudes seleccionando una comunidad administrada.

No existe una sede geográfica para las búsquedas. La cobertura de una solicitud se expresa primero mediante comunidades elegidas explícitamente por la persona. Las coordenadas del centro y el radio son configuración avanzada y auxiliar para “cerca de mí”, sugerencias y expansión automática; nunca sustituyen la selección de pueblos. Si faltan coordenadas, el sistema conserva las comunidades seleccionadas y nunca inventa distancias.

## Superficies principales

1. **Explorar:** mezcla controlada de productos, servicios, portafolios y solicitudes vigentes.
2. **Buscar:** resultados adaptados al tipo de cuenta, con categoría, distancia, disponibilidad, precio y reputación.
3. **Publicar:** producto, servicio, trabajo realizado o solicitud de trabajo.
4. **Mensajes:** conversaciones iniciadas desde una publicación, solicitud, pedido o cotización.
5. **Actividad:** pedidos, cotizaciones, trabajos, pagos, disputas y notificaciones.
6. **Perfil:** identidad pública, reputación, experiencia, catálogo y datos verificados visibles.
7. **Configuración:** cuenta, privacidad, notificaciones, pagos e idioma.

## Reglas que no se negocian

- La comisión no la elige libremente el proveedor. La define la plataforma por categoría o tipo de operación y se muestra antes de aceptar.
- Los importes se guardan en centavos; las comisiones se expresan en puntos base.
- Una calificación requiere una operación completada y solo puede existir una por participante.
- La ubicación pública es aproximada. La dirección exacta se revela únicamente cuando el flujo lo requiere.
- El teléfono y otros datos privados no se muestran antes de establecer una operación o conversación autorizada.
- Los pagos con tarjeta no se procesan ni almacenan directamente; se delegan a un proveedor autorizado.
- Productos y servicios utilizan flujos diferentes: compra directa para precio fijo y cotización para trabajos variables.
- Los acuerdos realizados fuera de la plataforma no reciben protección ni generan evidencia para disputas.
- La moderación automática resuelve vencimientos y reglas simples; fraude, identidad y evidencia contradictoria se escalan a una persona.

## Estados principales

### Solicitud de trabajo

`draft → published → in_conversation → assigned → completed`

Salidas excepcionales: `cancelled` y `expired`.

### Pedido o servicio

`pending → accepted → awaiting_payment/paid → in_progress → ready/delivered → completed`

Salidas excepcionales: `cancelled`, `disputed` y `refunded`.

### Pago

`pending → authorized/paid → release_pending → released`

Salidas excepcionales: `failed`, `cancelled`, `refund_pending` y `refunded`.

## Alcance del MVP

- Registro con correo y contraseña.
- Selección inicial de cliente o proveedor y activación posterior de ambas capacidades.
- Perfil básico y verificación manual de proveedores.
- Productos, servicios y solicitudes de trabajo.
- Búsqueda por categoría y ubicación.
- Cotización y pedido con estados auditables.
- Conversaciones de texto vinculadas a operaciones.
- Favoritos.
- Reputación bilateral.
- Comisión configurable y registro de pagos.
- Panel administrativo de excepciones.

## Fuera del MVP

- Aplicaciones nativas para Android o iOS.
- Inicio con Google o Facebook.
- Llamadas y videollamadas.
- Historias, seguidores o contenido viral.
- Reproducción y edición avanzada de video.
- Deslizamiento horizontal como navegación principal.
- Repartidores propios.
- Comisión voluntaria elegida por el proveedor.
- Custodia manual de dinero de terceros.

Estas funciones podrán evaluarse después de validar que las personas encuentran proveedores, concretan operaciones y regresan a la plataforma.

## Métricas de validación

- Proveedores verificados y activos por semana.
- Solicitudes que reciben al menos una respuesta.
- Conversión de solicitud o publicación a operación.
- Tiempo medio de respuesta del proveedor.
- Pedidos completados y tasa de cancelación.
- Compradores que vuelven dentro de 30 días.
- Disputas por cada 100 operaciones.
- Volumen procesado y comisión neta generada.

## Orden de construcción

1. Identidad, autenticación y perfiles.
2. Catálogo, solicitudes y búsqueda.
3. Cotizaciones, pedidos y estados.
4. Mensajería vinculada a operaciones.
5. Pagos, comisión y reembolsos.
6. Reputación, disputas y automatizaciones.
7. Piloto con proveedores reales de la comunidad.
