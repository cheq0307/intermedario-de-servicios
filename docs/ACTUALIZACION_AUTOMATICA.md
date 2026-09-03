# Actualización automática de actividad

Implementación local: 2 de septiembre de 2026. No modifica el contexto maestro.

## Alcance

| Superficie abierta | Intervalo normal |
| --- | --- |
| Chat directo, de publicación u operación | 4 segundos |
| Hilo de soporte (usuario o personal administrativo) | 4 segundos |
| Bandeja de conversaciones | 8 segundos |
| Campana, contadores y centro de notificaciones | 15 segundos |
| Bandeja de soporte del usuario | 15 segundos |
| Bandeja administrativa de soporte | 20 segundos |
| Indicadores y pendientes del panel administrativo | 30 segundos |

Se actualizan partes de la pantalla, no la página completa. No se han convertido
todos los módulos (feed, directorio de cuentas, pagos, etc.) en vistas en tiempo
real. Sus indicadores sí pueden cambiar sin que sus listados sean automáticos.

## Decisiones técnicas

- Se reutiliza Blade y los endpoints Laravel autorizados. No se agrega un servidor
  WebSocket ni dependencias JavaScript. El código compartido está en
  `resources/js/live-polling.js` y `resources/js/live-updates.js`.
- Cada coordinador permite una sola consulta pendiente, pausa con la pestaña
  oculta o sin conexión y vuelve a consultar al regresar. También contempla Atrás
  del navegador cuando este conserva la página en memoria.
- Los fallos de conexión espacian los reintentos hasta 60 segundos. Cada solicitud
  tiene un límite de 15 segundos. Una sesión expirada o permiso revocado detiene
  las actualizaciones y avisa sin recargar automáticamente ni borrar borradores.
- Las respuestas de mensajes contienen como máximo 100 registros y un cursor.
  El cursor solo avanza después de incorporar la respuesta de lectura: un envío
  propio nunca salta mensajes entrantes anteriores. Los elementos se deduplican y
  ordenan por ID. Los envíos fallidos no se reintentan automáticamente.
- Las listas mantienen paginación/filtros. Su revisión refleja el contenido de la
  página acotada, no solo fechas con precisión de segundos. Una respuesta 204 evita
  reemplazar HTML sin cambios. Se preservan controles enfocados y la posición
  interna de la campana.
- El servidor conserva autorización por participante, dueño de soporte o rol
  administrativo. Los resúmenes no habilitan facultades comerciales para personal
  administrativo. Los fragmentos privados usan `Cache-Control: no-store, private`.
- Los mensajes se insertan con `textContent`; las notificaciones se escapan con
  Blade. Las mutaciones siguen usando CSRF y validación del servidor.
- Los cambios de estado de soporte son asincrónicos. Cerrar oculta el formulario;
  reabrir lo habilita nuevamente. Los borradores siguen en el DOM.
- Se usa una migración aditiva para `conversation_participants.last_read_message_id`.
  El estado anterior basado en fecha sigue funcionando para filas todavía sin
  cursor. Las lecturas no retroceden el cursor y los envíos no marcan como leídos
  mensajes entrantes no consultados. No se borran conversaciones.

## Verificación ejecutada

- `php artisan test --compact`: 209 pruebas, 1,124 comprobaciones, sin fallos.
- `node --test tests/js/live-polling.test.js`: 10 pruebas, sin fallos.
- `npm run build`: compilación local correcta.
- `php artisan view:cache`: vistas compiladas correctamente.
- `git diff --check`: sin errores de espacios.
- Pendiente: comprobación visual interactiva. El navegador de pruebas no pudo
  iniciarse por el error ACL del entorno. No se comprobó carga concurrente en el
  servidor físico ni se desplegó este bloque.

## Comprobación manual antes de desplegar

1. En dos sesiones de prueba, abrir el mismo chat, enviar desde ambas y comprobar
   orden, ausencia de duplicados, indicador de leído y conservación de un borrador.
2. Abrir un caso desde usuario y administrador. Ver respuestas sin recargar;
   cerrar y reabrir desde administración, conservando el borrador del usuario.
3. Abrir la campana, elegir Sociales/Administrativas, desplazar su lista y recibir
   una notificación nueva. Confirmar filtro y desplazamiento.
4. Abrir soporte con un filtro y página 2. Cambiar el estado de un caso desde otra
   sesión y confirmar que el listado continúa con el filtro y la paginación.
5. Crear un caso en una sesión y comprobar el contador administrativo en otra.
6. Ocultar la pestaña o desconectar la red: no debe haber consultas continuas.
   Recuperar conexión, usar Atrás y verificar que vuelve a actualizar.
7. Expirar la sesión con un borrador escrito: debe avisar y conservarlo, no intentar
   envíos automáticos ni sustituir la pantalla por el inicio de sesión.

## Despliegue posterior

Requiere aplicar la migración aditiva antes de habilitar el código nuevo a los
usuarios. Respaldar la base y usar una ventana de mantenimiento para evitar que
peticiones consulten la columna antes de crearla.

Se conserva el procedimiento habitual: compilar con npm en la PC; Git para el
código del servidor; copiar los assets a `public/build/assets` y después el
`manifest.json` de esa misma compilación. Revisar permisos de lectura/recorrido de
`public/build` y `public/build/assets` para Nginx, y regenerar cachés de Laravel.
No requiere instalar npm en el servidor.

Los intervalos son una primera configuración para pruebas, no una garantía de
capacidad. Medir latencia, CPU, consultas y sesiones concurrentes. Para crecimiento
se puede migrar el transporte a eventos/WebSockets manteniendo estos límites,
permisos y cursores; contratar nube por sí solo no implementa ese transporte.
