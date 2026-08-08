# Decisiones funcionales pendientes

## Modelo de capacidades de cuenta

Estado: implementado el 8 de agosto de 2026; pendiente de validación manual en staging.

La cuenta de usuario no debe quedar limitada a escoger permanentemente entre
`client` y `provider`. El diseño futuro debe separar dos dimensiones:

- Capacidades comerciales combinables: comprar/solicitar trabajos y
  vender/ofrecer servicios.
- Permisos internos: operador, administrador y superadministrador.

Combinaciones que deben soportarse explícitamente:

- Solo cliente.
- Solo proveedor.
- Cliente y proveedor con una sola cuenta y una sola sesión.
- Solo personal administrativo, sin actividad comercial.
- Cliente o proveedor con permisos administrativos delegados.
- Superadministrador con o sin capacidades comerciales.

### Reglas de diseño

- No crear inicios de sesión separados para cada capacidad.
- El usuario podrá cambiar de contexto desde la misma cuenta cuando posea ambas
  capacidades comerciales.
- Otorgar `admin` o `superadmin` no debe conceder automáticamente capacidades
  de cliente o proveedor.
- Activar la capacidad de proveedor debe crear o completar su perfil comercial
  y continuar sujeto a verificación/aprobación.
- Las políticas de autorización deben comprobar capacidades concretas, no asumir
  que existe un único "tipo de cuenta".
- La migración debe preservar los roles actuales y evitar duplicar perfiles,
  publicaciones, pedidos o reputación.

### Trabajo técnico previsto

- Roles `client` y `provider` combinables en una sola identidad.
- Activación personal de capacidades y cambio de contexto desde la misma sesión.
- Activar proveedor crea un perfil comercial pendiente de aprobación.
- `admin` y `superadmin` son permisos internos independientes de las capacidades comerciales.
- El superadministrador puede otorgar o retirar capacidades sin borrar historial comercial.
- Las cuentas administrativas pueden existir sin `client` ni `provider`.
- Registro, perfil, navegación, publicación, compra y panel administrativo autorizan por capacidad.
- Migración de compatibilidad conserva el campo histórico `account_type`, pero este ya no es la fuente de autorización.
- Pruebas automatizadas cubren cuentas simples, duales, administrativas y cambios de contexto.

