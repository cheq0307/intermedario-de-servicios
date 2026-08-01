# Marketplace local

Base técnica para una plataforma web comunitaria de productos y servicios. El MVP registrará operaciones originadas dentro de la plataforma, automatizará su seguimiento y calculará comisiones sin construir una pasarela de pago propia.

## Stack inicial

- Laravel 12 sobre PHP 8.2.
- Livewire 4 para la interfaz web adaptable.
- Tailwind CSS y Vite para estilos y recursos.
- SQLite durante el arranque; MariaDB/MySQL para el piloto.
- Spatie Laravel Permission 6 para roles y permisos.
- Filament queda pendiente hasta habilitar la extensión PHP `intl`.

## Decisiones del dominio

- Un `vendor` representa al comercio o prestador responsable.
- `listings` unifica productos y servicios mediante `type`.
- El dinero se almacena en centavos y la comisión en puntos base.
- Cada pedido pertenece a un comprador y a un solo proveedor.
- Los artículos guardan nombre y precio como instantánea.
- Los pagos conservan referencias del proveedor externo; no se almacenan datos de tarjeta.
- Los cambios de estado pasan por un mapa explícito de transiciones.
- Comprador y proveedor pueden recibir reputación derivada de una operación.

## Flujo inicial

`pending → accepted → awaiting_payment/paid → in_progress → ready/delivered → completed`

Rutas de excepción: `cancelled`, `disputed` y `refunded`.

El proveedor de pagos definitivo se integrará detrás de un contrato propio después de validar disponibilidad de marketplace, dispersión, reembolsos y cumplimiento para México.

## Arranque local

```bash
composer install
php artisan migrate
npm install
npm run build
composer run dev
```

## Próximos incrementos

1. Publicar migraciones de roles y definir comprador, proveedor, operador y administrador.
2. Implementar autenticación y onboarding verificado de proveedores.
3. Crear modelos, políticas y factories para el núcleo ya migrado.
4. Construir catálogo, búsqueda y separación de pedidos por proveedor.
5. Definir el contrato de pagos y un adaptador falso para pruebas.
6. Automatizar vencimientos, notificaciones, liberación y alertas mediante colas.
7. Agregar panel administrativo después de habilitar `intl`.
