# Plaza Local V2 Preproduction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publicar el estado local validado como Plaza Local `v2.0.0-rc.1` en preproducción, con datos DEMO actuales, pagos deshabilitados y V1 recuperable por Git.

**Architecture:** La versión funcional permanece en la rama existente y se identifica mediante configuración de release que no contiene secretos. El despliegue usa Git para PHP/Blade, build Vite generado en Windows para assets y el seeder idempotente para datos DEMO; nunca reemplaza la base remota.

**Tech Stack:** Laravel 12, Blade, Vite/Tailwind, SQLite local, servidor Linux/Nginx/PHP 8.2, Git, SCP, Reverb.

**Spec:** `docs/superpowers/specs/2026-10-17-plaza-local-v2-preproduction-design.md`

## Global Constraints

- Conservar `v1.0.0` sin moverla ni sobrescribirla.
- No ejecutar comandos destructivos de base de datos.
- No copiar `.env` ni la base SQLite local.
- Mantener pagos y webhooks de proveedor deshabilitados.
- Preservar el tiempo real propio del producto.
- No incluir cambios locales ajenos al alcance.

## Review Focus

- Variables de versión ausentes: la interfaz funciona sin etiqueta ni error.
- Seeder repetido: no duplica perfiles, publicaciones u operaciones DEMO.
- Assets incompletos o con permisos incorrectos: Nginx debe servir manifiesto, CSS, JS e imágenes.
- Preproducción con datos previos: las migraciones son incrementales y preservan registros.
- Confusión entre webhooks de pago y Reverb: los primeros quedan cerrados y el segundo continúa operativo.

---

### Task 1: Identidad V2 configurable

**Files:**
- Modify: `config/app.php`
- Modify: `.env.example`
- Modify: `resources/views/auth/demo-access.blade.php`
- Test: `tests/Feature/DemoAccessTest.php`

**Interfaces:**
- Produces: `config('app.release_version')` y `config('app.release_channel')`.

- [ ] Escribir una prueba que muestre `V2 · Preproducción` al configurar ambas variables y no lo muestre cuando están vacías.
- [ ] Ejecutar la prueba y confirmar que falla.
- [ ] Agregar configuración y una etiqueta compacta en el acceso DEMO.
- [ ] Ejecutar las pruebas de acceso DEMO.
- [ ] Commit: `feat: identificar preproduccion como Plaza Local V2`.

### Task 2: Preparar release reproducible

**Files:**
- Modify: `docs/PREPRODUCCION.md`
- Create: `docs/releases/V2_PREPRODUCCION.md`

**Interfaces:**
- Consumes: `PLAZA_RELEASE_VERSION=2.0.0-rc.1`, `PLAZA_RELEASE_CHANNEL=preproduction`.
- Produces: secuencia exacta de respaldo, pull, migración, DEMO, assets, optimización y verificación.

- [ ] Documentar preflight remoto y respaldo antes de cualquier mutación.
- [ ] Documentar despliegue sin `migrate:fresh`, sin `.env` local y sin pagos.
- [ ] Documentar transferencia de `public/build` desde Windows y permisos de lectura para Nginx.
- [ ] Documentar rollback a `v1.0.0` como procedimiento de emergencia que requiere restaurar un respaldo compatible de datos.
- [ ] Commit: `docs: definir despliegue de preproduccion V2`.

### Task 3: Verificar y publicar la release

**Files:**
- Modify: solo archivos expuestos por verificaciones fallidas.

**Interfaces:**
- Consumes: rama `feature/feed-social-comercial` y los contratos de Tasks 1-2.
- Produces: commit y tag `v2.0.0-rc.1` remotos.

- [ ] Ejecutar pruebas focalizadas, suite completa, Pint y build Vite.
- [ ] Ejecutar `git diff --check` y excluir archivos ajenos.
- [ ] Confirmar que `v1.0.0` continúa apuntando a `25536c2`.
- [ ] Empujar la rama actual sin force push.
- [ ] Crear la etiqueta anotada `v2.0.0-rc.1` en el commit verificado y empujarla.

### Task 4: Desplegar y comprobar preproducción

**Files:**
- Remote: `/home/ejidos/plaza-local`
- Remote: respaldo fuera del checkout o en la ubicación de respaldos del servidor.

**Interfaces:**
- Consumes: `origin/feature/feed-social-comercial` y tag `v2.0.0-rc.1`.
- Produces: preproducción V2 comprobada.

- [ ] Comprobar conectividad, rama/HEAD, disco, PHP, base configurada y servicios antes de mutar.
- [ ] Crear y verificar un respaldo de la base de preproducción.
- [ ] Activar mantenimiento, hacer pull fast-forward y ejecutar `php artisan migrate --force`.
- [ ] Configurar las dos variables de release en el `.env` remoto sin modificar secretos.
- [ ] Ejecutar `php artisan plaza:demo-marketplace --force` dos veces y comprobar idempotencia.
- [ ] Transferir el build local, corregir permisos legibles y regenerar cachés.
- [ ] Reiniciar/validar worker y Reverb; desactivar mantenimiento.
- [ ] Verificar HTTP, acceso DEMO, administración, medios, rutas de pago 503 y tiempo real.
- [ ] Registrar commit/tag desplegados y cualquier limitación encontrada.
