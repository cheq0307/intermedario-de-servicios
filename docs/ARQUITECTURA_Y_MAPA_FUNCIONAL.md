# Plaza Local — Arquitectura, alcance y mapa funcional

Estado de referencia: 20 de agosto de 2026. Rama de trabajo: `feature/feed-social-comercial`.

Este documento es la fuente de verdad funcional y técnica de Plaza Local. Su propósito es permitir auditar el sistema, detectar pérdidas accidentales de funcionalidad y reconstruir sus módulos sin depender de la memoria de una conversación.

## 1. Objetivo del producto

Plaza Local es un marketplace social multicomunidad que permite a una sola cuenta:

- descubrir productos, servicios y oportunidades de empleo;
- publicar lo que ofrece o solicita;
- negociar dentro de la plataforma;
- convertir una propuesta o compra en una operación trazable;
- pagar, entregar, disputar y calificar;
- construir reputación verificable mediante operaciones reales.

El superadministrador gobierna todas las comunidades. Los administradores operan tareas delegadas. Las cuentas administrativas exclusivas no participan comercialmente.

## 2. Principios de diseño

1. Una persona, una cuenta; puede comprar, solicitar, vender y ofrecer.
2. Autoridad administrativa separada de actividad comercial.
3. La aprobación para operar no equivale a una insignia de identidad verificada.
4. Datos públicos y documentos privados se almacenan y autorizan por separado.
5. La plataforma cobra cuando aporta valor verificable.
6. Toda operación sensible deja trazabilidad: pago, moderación, verificación y disputa.
7. La ubicación se expresa por comunidad y, cuando existan coordenadas, por cercanía real.
8. Los invitados pueden explorar; deben registrarse para interactuar o contratar.
9. La interfaz prioriza navegación móvil y evita duplicar menús.
10. Las integraciones externas deben poder cambiarse mediante adaptadores y variables de entorno.

## 3. Actores y permisos

### Invitado

- Consulta portada, búsqueda y perfiles públicos.
- No reacciona, comenta, contacta, compra, propone ni publica.

### Usuario comercial unificado

- Puede solicitar productos o servicios.
- Puede ofrecer productos, servicios y promociones.
- Puede seguir perfiles, reaccionar, comentar y compartir.
- Puede operar como comprador o proveedor según cada operación.
- Puede crear un perfil comercial y solicitar su aprobación.

### Administrador

- Revisa proveedores, documentos, publicaciones, soporte y disputas.
- Aprueba, devuelve, rechaza o suspende perfiles comerciales.
- Edita y suspende comunidades.
- No delega administradores ni elimina comunidades salvo permiso expreso de superadministrador.

### Superadministrador

- Cuenta exclusivamente administrativa.
- Tiene control global y puede delegar o retirar administradores.
- Puede eliminar comunidades cuando las restricciones de integridad lo permitan.
- Puede conceder o retirar insignias de verificación documentada.
- Si necesita comprar u ofrecer, utiliza otra cuenta comercial.

## 4. Tipos de contenido y diferencias

- Producto: bien físico que una persona vende. Puede tener precio, inventario y pedido.
- Servicio: actividad que una persona ofrece. Puede cotizarse por evento o por trabajo.
- Solicitud: necesidad puntual publicada por un usuario para recibir propuestas.
- Vacante: puesto de empleo formal o temporal que un empleador publica, normalmente con tarifa de publicación.
- Postulación: solicitud de una persona para participar en una vacante.
- Trabajo realizado: evidencia de una operación completada; puede mostrar imágenes y resultado.
- Reseña: calificación/opinión vinculada a una operación real. No es una publicación libre.

Una vacante no es una solicitud de servicio: contratar a un plomero para una reparación es una operación; contratar personal para ocupar un puesto es empleo.

## 5. Navegación funcional

Barra inferior móvil:

- Inicio: descubrimiento y feed.
- Pedidos: operaciones como comprador y proveedor.
- Publicar: acción central; abre producto, servicio, solicitud o vacante.
- Mensajes: conversaciones y acceso a notificaciones.
- Más: módulos secundarios, ayuda y cierre de sesión.

El icono superior de usuario abre el perfil. El perfil propio muestra configuración; un perfil ajeno nunca muestra controles privados.

## 6. Inicio, búsqueda y recomendación

El inicio combina contenido orgánico y anuncios nativos:

1. promociones activas pagadas y vigentes;
2. coincidencia con intereses/rubros del usuario;
3. coincidencia territorial por comunidad y cercanía;
4. rotación aleatoria del inventario orgánico para evitar una portada estática.

Toda promoción se identifica como `Patrocinado`. Pagar mejora posición durante el periodo contratado, pero no garantiza ventas ni insignia de confianza. El carrusel puede mezclar campañas patrocinadas con contenido orgánico rotativo sin interrumpir el feed.

Filtros principales: comida, servicios, productos, transporte y empleo. Subfiltros: para ti, ofertas y solicitudes. La búsqueda funciona independientemente de publicar una solicitud.

## 7. Comunidades y cobertura

- El código postal ayuda a seleccionar asentamiento, municipio y estado.
- Latitud/longitud representan el centro de referencia de una comunidad.
- El radio permite descubrir comunidades y proveedores cercanos.
- Una solicitud puede seleccionar explícitamente comunidades de atención.
- Los proveedores de comunidades seleccionadas reciben prioridad; los cercanos pueden recibir una oportunidad secundaria cuando la distancia lo justifique.
- Administradores agregan, editan y suspenden comunidades; solo superadministración elimina.

## 8. Publicaciones sociales

- Creación separada por producto, servicio, solicitud y vacante.
- Texto, imágenes y video según validación.
- Me gusta reversible, comentarios editables/eliminables por autor y compartir.
- Comentarios paginados para no cargar cientos a la vez.
- Moderación administrativa con motivo y auditoría.
- El perfil público separa lo que ofrece, lo que necesita y trabajos reales.

## 9. Solicitud, propuesta y operación

Flujo de servicio:

1. Usuario publica solicitud.
2. Proveedores envían propuestas con precio, plazo y alcance.
3. Usuario acepta una propuesta.
4. Se crea una orden y pago pendiente.
5. Pago aprobado/reservado.
6. Proveedor inicia y entrega.
7. Comprador confirma o abre disputa.
8. Se libera/cancela/reembolsa según resultado.
9. Participantes califican conforme a reglas de la operación.

Flujo de producto:

1. Usuario abre una oferta comprable.
2. Se valida disponibilidad y se crea reserva temporal de inventario.
3. Se crea pedido y pago.
4. Proveedor marca listo/entregado.
5. Comprador confirma o disputa.

## 10. Empleo

- Publicar vacante es una función distinta de publicar una solicitud.
- La vacante contiene puesto, descripción, comunidad, categoría, modalidad, salario y vigencia.
- Una campaña de empleo puede requerir pago antes de publicarse.
- Los candidatos consultan y se postulan.
- El empleador administra vacantes y postulaciones en `Mi empleo`.

## 11. Mensajería, notificaciones y soporte

- Conversaciones directas protegidas dentro de Plaza Local.
- Lectura/no lectura, último mensaje y contador pendiente.
- Notificaciones sociales, operativas, de empleo, pagos, moderación y soporte.
- Abrir una notificación la marca leída; existe lectura masiva.
- Soporte usa tickets y conversación con canal oficial identificable.
- Administradores reciben, responden y cambian estado del ticket.

## 12. Reputación y verificación

La reputación se basa en operaciones reales, reseñas y cumplimiento. La insignia verificada es un control adicional.

Nivel `Identidad`:

- identificación oficial vigente;
- comprobante de domicilio reciente.

Nivel `Identidad y negocio`:

- los dos documentos anteriores;
- constancia fiscal o evidencia formal del negocio.

No se solicita por defecto CURP escrita, selfie con identificación, datos bancarios ni documentos sin propósito definido. La pasarela conserva su propio KYC financiero.

Reglas:

- archivos PDF/JPG/PNG, máximo 5 MB;
- almacenamiento privado, nunca dentro del directorio público;
- descarga solo por propietario o administración;
- hash SHA-256, estado, revisor, fecha y nota;
- aprobar el perfil comercial no concede la insignia;
- pagar puede cubrir una tarifa de revisión, nunca comprar el resultado;
- una insignia requiere todos los documentos aprobados del nivel elegido.

## 13. Publicidad no invasiva

Modelo actual:

- el usuario elige una publicación propia elegible;
- elige duración de 7, 15 o 30 días;
- se genera campaña pendiente de pago;
- Mercado Pago crea el checkout;
- el webhook confirma el pago y activa fechas;
- el contenido aparece como patrocinado en carrusel/feed;
- al vencer vuelve al orden orgánico.

El administrador debe poder consultar campañas, pagos, vigencia y publicación asociada. Futuro: límites de frecuencia, reporte por impresiones/clics y segmentación por comunidad/rubro.

## 14. Pagos: estado real

### Implementado

- abstracción de pasarela para órdenes;
- pagos falsos limitados a entorno autorizado;
- estados de orden y pago;
- reserva de inventario;
- comisión calculada;
- Stripe Connect presente como integración previa;
- Mercado Pago para promociones, retorno y webhook.

### No listo solo con credenciales

El cobro integral de órdenes del marketplace todavía requiere cerrar Mercado Pago Split Payments:

- aplicación Marketplace y OAuth por proveedor;
- token por cuenta conectada;
- comisión de aplicación;
- creación idempotente del cobro;
- firma de webhook validada;
- conciliación periódica;
- reembolsos y contracargos;
- payout/disponibilidad de fondos;
- pruebas sandbox y aprobación productiva.

Hasta completar esto, `MARKETPLACE_PAYMENT_DRIVER=fake` no debe habilitarse en producción y no debe prometerse custodia propia de fondos.

## 15. Almacenamiento

Discos lógicos:

- `public`: desarrollo local de avatares y medios.
- `local`: documentos privados locales.
- `marketplace_media`: bucket público/CDN para avatares y publicaciones.
- `marketplace_private`: bucket privado para documentos de verificación.

Variables:

```env
MARKETPLACE_MEDIA_DISK=marketplace_media
MARKETPLACE_PRIVATE_DISK=marketplace_private
CLOUD_STORAGE_ACCESS_KEY=
CLOUD_STORAGE_SECRET_KEY=
CLOUD_STORAGE_REGION=auto
CLOUD_STORAGE_BUCKET=
CLOUD_STORAGE_PRIVATE_BUCKET=
CLOUD_STORAGE_ENDPOINT=
CLOUD_STORAGE_URL=
CLOUD_STORAGE_PATH_STYLE=false
```

Compatible con Amazon S3 y proveedores S3-compatible como Cloudflare R2 o Backblaze B2. Se recomienda separar buckets públicos y privados, habilitar versionado y reglas de ciclo de vida.

## 16. Seguridad

Controles existentes o configurados:

- contraseñas robustas y hashing;
- verificación de correo y recuperación;
- autorización por usuario, participante y rol;
- CSRF y sesión cifrada/segura en HTTPS;
- validación de archivos por tipo y tamaño;
- documentos fuera del webroot;
- auditoría administrativa;
- límites de solicitudes en acciones sensibles;
- cabeceras `nosniff`, `SAMEORIGIN`, política de referencia, permisos, COOP y HSTS en HTTPS;
- webhooks fuera de CSRF pero sujetos a firma del proveedor;
- cola, scheduler, health check, backups y prueba de restauración.

Pendiente antes de pagos reales:

- CSP ajustada a Mercado Pago y almacenamiento;
- rate limits distribuidos con Redis;
- rotación de secretos y gestor de secretos;
- alertas centralizadas y seguimiento de excepciones;
- análisis de dependencias en CI;
- pruebas de autorización negativas de cada recurso;
- política de retención/borrado de KYC;
- MFA obligatorio para administración;
- revisión externa de seguridad antes de producción.

## 17. Administración

- indicadores operativos;
- directorios filtrables de usuarios, proveedores y publicaciones;
- aprobación/rechazo/suspensión de proveedores;
- revisión documental e insignias;
- comunidades, códigos postales y rubros;
- soporte y disputas;
- moderación de publicaciones;
- delegación de administradores solo por superadministración;
- auditoría de acciones.

## 18. Datos principales

- `users`, roles y capacidades;
- `vendors`, categorías y documentos de verificación;
- `communities`, códigos postales y preferencias territoriales;
- `posts`, `post_media`, reacciones, comentarios, compartidos y seguidores;
- `listings` e inventario;
- `job_requests`, comunidades objetivo y propuestas;
- `orders`, items, pagos, reservas, disputas y reseñas;
- `conversations`, participantes y mensajes;
- `notifications` y tickets de soporte;
- `job_vacancies` y postulaciones;
- `post_promotions` y datos de Mercado Pago;
- `audit_logs`.

## 19. Operación y despliegue

- Nginx apunta a `public/`.
- PHP-FPM sirve Laravel.
- Worker de cola permanente mediante systemd.
- Scheduler cada minuto mediante cron.
- `APP_DEBUG=false` y HTTPS obligatorio fuera de desarrollo.
- Migraciones antes de optimizar cachés.
- Assets Vite se compilan localmente si el servidor no tiene Node.
- Backups diarios de base y archivos; restauración se prueba en base separada.

Secuencia mínima:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
php artisan plaza:health-check
```

## 20. Pruebas y definición de terminado

Cada módulo se considera terminado cuando:

1. tiene autorización y validación del servidor;
2. cuenta con flujo feliz y pruebas negativas;
3. funciona en móvil y escritorio;
4. registra fallos o auditoría cuando corresponde;
5. puede desplegarse sin editar código ni exponer secretos;
6. está representado en este documento.

## 21. Inventario pendiente priorizado

Bloqueadores para dinero real:

1. Mercado Pago Split Payments completo.
2. KYC productivo y política de privacidad/retención.
3. almacenamiento S3/R2 probado con buckets separados.
4. conciliación, reembolsos y contracargos.
5. revisión legal, fiscal y términos.
6. MFA administrativo, observabilidad y auditoría de seguridad.

Antes del piloto:

1. pruebas E2E con comprador, proveedor, admin y superadmin;
2. moderación y soporte con tiempos de atención;
3. accesibilidad y responsividad en iOS/Android;
4. métricas de publicidad e informe al anunciante;
5. política de suspensión, apelación y eliminación de datos.

## 22. Archivos locales no incorporados

Los siguientes archivos aparecieron sin seguimiento y no forman parte de esta etapa porque son restos incompletos de scaffolding de Fortify/passkeys:

- `app/Actions/Fortify/CreateNewUser.php`
- `app/Actions/Fortify/UpdateUserPassword.php`
- `app/Actions/Fortify/UpdateUserProfileInformation.php`
- `database/migrations/2026_08_04_075740_create_passkeys_table.php`

No deben publicarse ni eliminarse por accidente. Primero se decide si se implementarán passkeys; después se integran completos con pruebas o se retiran conscientemente.

## 23. Regla de reconstrucción

Si un cambio futuro elimina funcionalidad, se compara contra:

1. este mapa funcional;
2. las rutas nombradas;
3. las migraciones y relaciones;
4. las pruebas automatizadas;
5. los documentos operativos en `docs/`.

Una característica no debe considerarse restaurada únicamente porque vuelve a verse un botón: deben recuperarse autorización, persistencia, estados, notificaciones, auditoría y pruebas.
