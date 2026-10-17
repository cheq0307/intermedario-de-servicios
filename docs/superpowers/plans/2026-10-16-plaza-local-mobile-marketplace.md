# Plaza Local Mobile Marketplace Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplicar la nueva identidad móvil de Plaza Local a descubrimiento, resultados y navegación sin alterar los flujos comerciales existentes.

**Architecture:** Extender los tokens Tailwind existentes y concentrar los patrones repetidos en componentes Blade. Las vistas seguirán recibiendo los mismos modelos y enviando los mismos formularios; el cambio será de presentación y jerarquía, con pruebas HTTP y de navegador para preservar funciones y responsividad.

**Tech Stack:** Laravel 12, Blade, Tailwind CSS 4, Vite, JavaScript existente, PHPUnit y Playwright.

**Spec:** `docs/superpowers/specs/2026-10-16-plaza-local-mobile-marketplace-design.md`

## Global Constraints

- Conservar la cuenta comercial unificada y la separación administrativa.
- No eliminar empleo, publicación, pedidos, mensajes, perfil ni Más.
- Mantener rutas, autorización, CSRF y validación del servidor.
- Usar tokens semánticos y controles táctiles de al menos 44 px.
- Validar 320, 375, 390, 412 px y escritorio.

## Review Focus

- Usuario no verificado: el aviso y el acceso a explorar permanecen visibles sin permitir publicar.
- Cuenta suspendida: no recuperar acciones comerciales mediante el rediseño.
- Resultados sin imagen o precio: conservar clasificación y una acción válida.
- Texto largo y pantalla de 320 px: no generar desplazamiento horizontal.
- Invitado: poder explorar y recibir una llamada clara para iniciar sesión en acciones protegidas.

---

### Task 1: System and navigation shell

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/views/components/market-nav.blade.php`
- Modify: `resources/views/components/bottom-nav.blade.php`
- Test: `tests/Feature/NavigationConsistencyTest.php`

**Interfaces:**
- Produces: `.plaza-*` semantic component classes and unchanged Blade component props.

- [ ] Write failing assertions for location context, employment access, five mobile destinations and semantic shell classes.
- [ ] Run `php artisan test tests/Feature/NavigationConsistencyTest.php` and confirm the new assertions fail.
- [ ] Add tokens and rebuild the shared header and bottom navigation without changing routes.
- [ ] Run the focused test and confirm it passes.

### Task 2: Discovery feed

**Files:**
- Modify: `resources/views/dashboard.blade.php`
- Create: `resources/views/components/marketplace/listing-card.blade.php`
- Test: `tests/Feature/FeedPersonalizationTest.php`
- Test: `tests/Feature/PostPublishingTest.php`

**Interfaces:**
- Consumes: existing `$module`, `$feed`, `$showcaseSections`, `$posts` and publication forms.
- Produces: reusable `<x-marketplace.listing-card>` preserving checkout/profile destinations.

- [ ] Add failing assertions for visible type, rubro, local context, feed filters and publish entry point.
- [ ] Run focused feed tests and confirm failure for the new structure.
- [ ] Recompose the discovery header, category rail, showcase and feed card hierarchy.
- [ ] Run focused tests and confirm green.

### Task 3: Search and results

**Files:**
- Modify: `resources/views/explore/index.blade.php`
- Reuse: `resources/views/components/marketplace/listing-card.blade.php`
- Test: `tests/Feature/ExploreTest.php`

**Interfaces:**
- Consumes: existing filters and independent paginators.
- Produces: progressive filter disclosure and consistent product/service/provider/request cards.

- [ ] Add failing assertions for query context, type filters, result classification and empty states.
- [ ] Run `php artisan test tests/Feature/ExploreTest.php` and confirm failure.
- [ ] Implement the mobile-first results surface without changing query parameters.
- [ ] Run focused tests and confirm green.

### Task 4: Browser and build verification

**Files:**
- Modify: `tests/Browser/marketplace.mjs`

**Interfaces:**
- Consumes: existing isolated browser fixture.
- Produces: overflow and action checks at 320, 375, 390, 412 and desktop widths.

- [ ] Add viewport assertions for navigation, feed, exploration, product purchase and employment visibility.
- [ ] Run the browser test against the pre-change UI and record the expected failing assertion.
- [ ] Adjust only presentation defects exposed by the browser test.
- [ ] Run PHP suite, JavaScript tests, Playwright, Pint, Vite build and `git diff --check`.

