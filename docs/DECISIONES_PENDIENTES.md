# Decisiones funcionales pendientes

## Modelo de capacidades de cuenta

Estado: pendiente para una iteración posterior a la estabilización de preproducción.

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

- Sustituir el selector exclusivo de tipo de cuenta por capacidades combinables.
- Añadir activación y desactivación segura de la capacidad de proveedor.
- Adaptar registro, perfil, navegación, publicación, búsqueda y panel admin.
- Definir cuentas internas de staff que no necesiten el rol `client`.
- Agregar pruebas de autorización para todas las combinaciones anteriores.

