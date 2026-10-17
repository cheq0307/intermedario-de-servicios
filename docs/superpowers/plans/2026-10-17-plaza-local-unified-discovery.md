# Plaza Local Unified Discovery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Unificar Inicio, Comida, Servicios, Productos, Transporte y Empleo bajo una experiencia de descubrimiento más limpia, con carruseles en todos los módulos y sin separar innecesariamente la bolsa de trabajo.

**Architecture:** Extender el contrato de `DashboardController` para entregar secciones de descubrimiento específicas por módulo y un resumen acotado de vacantes publicadas. Extraer la presentación repetida a componentes Blade pequeños, reutilizar el comportamiento de carrusel existente y mantener las páginas completas de empleo como destino operativo. Esta base continuará hasta producción sin pasarela: se conservarán límites de dominio que permitan añadir pagos después, pero los flujos actuales serán completos sin cobro.

**Tech Stack:** Laravel 12, Blade, Eloquent, Tailwind CSS 4, JavaScript existente, Vite y PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-17-plaza-local-unified-discovery-design.md`

## Global Constraints

- Conservar la cuenta unificada y todas las funciones de publicaciones, solicitudes, comentarios, propuestas, conversaciones, vacantes y postulaciones.
- No habilitar pagos, cobros, comisiones automáticas ni tarifas en ningún módulo.
- La versión que se desplegará a producción debe funcionar completamente sin pasarela; los pagos quedan fuera del alcance hasta una etapa futura sin fecha comprometida.
- Los webhooks de pasarela quedan suspendidos; los eventos propios para tiempo real permanecen permitidos y separados del subsistema de pagos.
- No agregar migraciones ni dependencias.
- Mantener autorización, CSRF, verificación de correo y bloqueos comerciales por suspensión.
- Usar los tokens semánticos existentes; no introducir hexadecimales en Blade o JavaScript.
- Mantener controles táctiles de al menos 44 px y navegación horizontal accesible.
- No modificar ni incluir en commits los archivos no relacionados que ya están sucios o sin seguimiento.

## Review Focus

- Inicio no debe duplicar el contenido completo de cada módulo.
- Comida y Transporte deben conservar su clasificación por categoría; Productos y Servicios, por tipo y exclusiones existentes.
- Empleo debe mostrar solo vacantes publicadas y nunca convertirlas en posts genéricos.
- Una cuenta suspendida no recupera visibilidad comercial mediante los nuevos carruseles.
- Los carruseles vacíos no dejan contenedores o encabezados huérfanos.
- La navegación de seis módulos no provoca overflow de página a 320 px.
- Los accesos “Publicar vacante” y “Mis vacantes y postulaciones” siguen llevando a sus rutas actuales.
- Ninguna acción visible debe dirigir a pago o quedar bloqueada esperando una transacción.

---

### Task 1: Define the unified discovery data contract

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Test: `tests/Feature/FeedPersonalizationTest.php`
- Test: `tests/Feature/JobVacancyTest.php`

**Interfaces:**
- Consumes: `module`, `feed`, preferencias, comunidad, listings aprobados y vacantes publicadas.
- Produces: módulos válidos incluyendo `home` y `employment`, secciones de listings por categoría y una colección acotada de vacantes para descubrimiento.

- [ ] Agregar pruebas fallidas que soliciten `module=home` y `module=employment`, comprueben que cada módulo conserva su filtro y que Inicio recibe vacantes publicadas sin exponer pendientes.
- [ ] Ejecutar `php artisan test tests/Feature/FeedPersonalizationTest.php tests/Feature/JobVacancyTest.php` y confirmar las fallas esperadas.
- [ ] Centralizar los módulos válidos y aplicar los filtros actuales mediante una sola ruta de consulta legible.
- [ ] Hacer que las secciones de listings estén disponibles en cada módulo comercial sin depender de `feed=for_you`.
- [ ] Cargar un resumen limitado y eager-loaded de vacantes publicadas para Inicio y Empleo, sin mezclarlo con `Post`.
- [ ] Evitar la consulta y paginación de posts comerciales incompatibles cuando el módulo activo sea Empleo.
- [ ] Ejecutar nuevamente las pruebas focalizadas y confirmar verde.
- [ ] Commit: `refactor: unificar datos de descubrimiento por modulo`

### Task 2: Integrate Employment into primary navigation

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Modify: `resources/views/components/market-nav.blade.php`
- Test: `tests/Feature/NavigationConsistencyTest.php`

**Interfaces:**
- Produces: riel principal `Inicio · Comida · Servicios · Productos · Transporte · Empleo`.
- Preserves: `vacancies.index`, `vacancies.create` y `vacancies.mine` como destinos operativos.

- [ ] Agregar pruebas fallidas para los seis módulos, `aria-current`, navegación móvil desplazable y accesos operativos de Empleo.
- [ ] Ejecutar `php artisan test tests/Feature/NavigationConsistencyTest.php` y confirmar las fallas esperadas.
- [ ] Añadir Inicio y Empleo al riel principal del dashboard.
- [ ] Reemplazar el tratamiento visual aislado de “Bolsa de trabajo” en la navegación compartida por un acceso coherente con el módulo, sin eliminar las rutas completas.
- [ ] Mantener visibles “Publicar vacante” y “Mis vacantes y postulaciones” dentro del contexto de Empleo.
- [ ] Ejecutar la prueba focalizada y confirmar verde.
- [ ] Commit: `feat: integrar empleo en la navegacion principal`

### Task 3: Recompose the dashboard and reusable carousels

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Create: `resources/views/components/marketplace/discovery-section.blade.php`
- Create: `resources/views/components/marketplace/vacancy-card.blade.php`
- Modify: `resources/views/vacancies/index.blade.php`
- Modify: `resources/js/app.js` only if the existing generic carousel needs an accessibility correction.
- Test: `tests/Feature/FeedPersonalizationTest.php`
- Test: `tests/Feature/JobVacancyTest.php`

**Interfaces:**
- `<x-marketplace.discovery-section>` consumes title, optional destination and a collection of listing cards.
- `<x-marketplace.vacancy-card>` consumes one published `JobVacancy` and links to `vacancies.show`.
- Existing `data-market-carousel`, `data-carousel-track`, `data-carousel-prev` and `data-carousel-next` remain the JavaScript contract.

- [ ] Añadir pruebas fallidas para carruseles en Comida, Servicios, Productos y Transporte, carrusel resumido de Empleo en Inicio y vista amplia en el módulo Empleo.
- [ ] Ejecutar las pruebas focalizadas y confirmar las fallas esperadas.
- [ ] Sustituir el hero pesado por un encabezado corto con comunidad y una sola acción de publicar.
- [ ] Extraer la sección repetida de carrusel y usarla en todos los módulos que tengan contenido.
- [ ] Crear una tarjeta compacta de vacante consistente con listings: tipo, título, rubro/empresa, comunidad/modalidad y salario cuando exista.
- [ ] Mostrar en Inicio resúmenes acotados de ofertas, solicitudes y empleo sin duplicar feeds completos.
- [ ] Mostrar en Empleo el carrusel de vacantes y sus dos accesos operativos; conservar la página completa para búsqueda y paginación.
- [ ] Eliminar textos explicativos duplicados y la frase de tarifa/pago de la tarjeta “Publicar vacante”.
- [ ] Auditar los accesos visibles del dashboard y Empleo para retirar cualquier cobro, tarifa o estado que haga depender el flujo de una pasarela.
- [ ] Reducir bordes, sombras, tarjetas contenedoras y espacio vertical que no indiquen una agrupación real.
- [ ] Mantener visibles tipo, rubro, comunidad y valor comercial; dar prioridad visual a la fotografía existente.
- [ ] Ejecutar las pruebas focalizadas y confirmar verde.
- [ ] Commit: `feat: limpiar inicio y extender carruseles del marketplace`

### Task 4: Responsive, accessibility and regression verification

**Files:**
- Modify: `tests/Browser/marketplace.mjs` if the current browser fixture supports the dashboard states.
- Modify: only presentation files exposed by verification failures.

**Interfaces:**
- Verifies: mobile touch scrolling, desktop controls, keyboard focus, route destinations and no page overflow.

- [ ] Añadir o ajustar comprobaciones de navegador para 320, 375, 390, 412 px y escritorio: seis módulos alcanzables, carrusel desplazable, Empleo visible y sin overflow de página.
- [ ] Verificar manualmente arrastre con ratón/touch, rueda/trackpad, botones anterior/siguiente y foco de teclado.
- [ ] Ejecutar `php artisan test tests/Feature/FeedPersonalizationTest.php tests/Feature/NavigationConsistencyTest.php tests/Feature/JobVacancyTest.php`.
- [ ] Ejecutar la suite completa con `php artisan test`.
- [ ] Ejecutar `vendor/bin/pint --dirty` y repetir las pruebas afectadas si Pint cambia PHP.
- [ ] Compilar activos con `C:\Users\pccue\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\bin\node.exe .\node_modules\vite\bin\vite.js build`.
- [ ] Ejecutar `git diff --check` y revisar `git status --short` para excluir cambios no relacionados.
- [ ] Hacer una revisión final contra la especificación y confirmar que no se habilitaron pagos ni se degradaron vacantes/postulaciones.
- [ ] Buscar referencias visibles a pago, tarifa, checkout, comisión y pasarela; documentar cuáles quedan solo como arquitectura futura y comprobar que ninguna esté activa para el usuario.
- [ ] Commit: `test: verificar descubrimiento unificado y responsivo`

### Task 5: Enforce payment-free production behavior

**Files:**
- Modify: `config/marketplace.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/JobVacancyController.php`
- Modify: `app/Http/Controllers/ProductOrderController.php`
- Modify: `app/Http/Controllers/ServiceOrderController.php`
- Modify: `resources/views/vacancies/create.blade.php`
- Modify: `resources/views/vacancies/show.blade.php`
- Modify: `resources/views/promotions/index.blade.php`
- Modify: `resources/views/profiles/edit.blade.php`
- Modify: order, conversation and administrative views that expose payment-only actions.
- Test: `tests/Feature/PaymentDisabledProductionTest.php`
- Test: `tests/Feature/JobVacancyTest.php`
- Test: `tests/Feature/ProductOrderTest.php`
- Test: `tests/Feature/ServiceOrderTest.php`
- Test: `tests/Feature/NavigationConsistencyTest.php`

**Interfaces:**
- Produces: a single production-safe feature flag that leaves every live gateway and payment-only route inaccessible.
- Preserves: dormant gateway/domain code and database compatibility for a future, separately authorized payment release.
- Replaces: payment-gated user transitions with explicit agreement, acceptance, delivery and completion transitions that do not claim money was processed by Plaza Local.

- [ ] Escribir pruebas fallidas que demuestren que webhooks, checkout, pago de vacantes, promociones pagadas, conexión de cobros y simuladores no son accesibles cuando los pagos están suspendidos.
- [ ] Escribir pruebas de producto, servicio y empleo que completen su flujo permitido sin crear un pago ni quedar en `pending_payment`/`awaiting_payment`.
- [ ] Ejecutar las pruebas focalizadas y confirmar las fallas esperadas.
- [ ] Definir una bandera global de producto deshabilitada por defecto y sin posibilidad de activación accidental mediante credenciales presentes en `.env`.
- [ ] Desregistrar o bloquear las rutas de pasarelas, webhooks, conexión de depósitos, checkout, simulación de pago y promociones pagadas en la versión lanzable.
- [ ] Mantener intacta la capacidad de broadcasting/WebSockets/SSE para mensajes, soporte, operaciones y notificaciones; no usar webhooks de pago como mecanismo de actualización de interfaz.
- [ ] Cambiar la publicación de vacantes para publicarlas según autorización y validación actuales, sin estado ni paso de pago.
- [ ] Cambiar productos y servicios para registrar acuerdo/pedido/operación sin afirmar que Plaza Local cobró, retuvo, liberó o devolvió dinero.
- [ ] Retirar de la interfaz cobros, depósitos, comisiones, conciliación, “Continuar al pago”, “Comprar” cuando implique checkout interno y cualquier instrucción de mantener pagos dentro de la plataforma.
- [ ] Conservar el código de integración en módulos internos no alcanzables para que una futura fase de pagos requiera una decisión y activación explícitas, no una reconstrucción improvisada.
- [ ] Actualizar demo, fixtures y pruebas de navegador para modelar acuerdos sin pagos de prueba.
- [ ] Ejecutar pruebas focalizadas, suite completa, Pint, build y `git diff --check`.
- [ ] Commit: `feat: lanzar marketplace sin pasarelas de pago`
