# Plaza Local Mobile Marketplace Design

## Intent

Plaza Local debe sentirse como la plaza comercial digital de una comunidad: cercana, confiable y sencilla de recorrer desde el teléfono. La referencia visual aportada define la dirección, pero la interfaz conservará el modelo real de cuenta unificada, publicaciones sociales, productos, servicios, solicitudes, vacantes, operaciones y chats independientes.

## Goals

- Dar una identidad visual consistente a las superficies comerciales públicas y autenticadas.
- Hacer evidente qué está viendo la persona: producto, servicio, solicitud o vacante, junto con rubro y comunidad.
- Mantener Inicio como descubrimiento y separar resultados, perfil, contratación, chat y seguimiento.
- Conservar navegación móvil: Inicio, Pedidos, Publicar, Mensajes y Más.
- Mantener todas las rutas, permisos, formularios y acciones existentes.
- Responder correctamente desde 320 px hasta escritorio y respetar áreas seguras móviles.

## Non-goals

- No crear roles comerciales rígidos Cliente/Proveedor.
- No simular pagos reales ni cambiar reglas de negocio.
- No reemplazar administración con el diseño del marketplace.
- No copiar marcas, colores exactos, fotografías o iconografía propietaria de otras aplicaciones.

## Visual direction

### Domain

Comunidad, calle principal, mercado local, oficio, confianza vecinal, rutas cortas, acuerdos registrados y reputación comprobable.

### Color world

- `Plaza ink` `#103f3b`: letreros pintados y confianza.
- `Mercado orange` `#eb6a2c`: toldos, fruta y acción.
- `Maize cream` `#fbf7ee`: papel y muros cálidos.
- `Mint route` `#e4f2ea`: cercanía, estados seguros y mapas.
- `Clay line` `#ddd5c7`: estructura sin dureza.
- `Town gold` `#e3a93b`: reputación y calificación.

### Signature

Cada tarjeta comercial lleva una “ruta local”: tipo y rubro arriba, comunidad/distancia abajo y una acción inequívoca. Es una pieza reconocible de Plaza Local, no una tarjeta genérica de e-commerce.

### Rejected defaults

- No usar cuadrículas de tarjetas idénticas sin jerarquía.
- No llenar cada sección de píldoras de colores.
- No convertir Inicio en un dashboard administrativo.
- No depender de fotografías para explicar el tipo de contenido.

## System

- Tipografía: Instrument Sans existente; peso y color construirán la jerarquía.
- Escala: 12 / 14 / 17 / 22 / 30 px.
- Espaciado: unidad de 4 px, controles táctiles mínimos de 44 px.
- Profundidad: superficies cálidas con borde tenue y una sola sombra corta; overlays elevan un nivel.
- Radios: 14 px controles, 22 px tarjetas, 30 px contenedores principales.
- Movimiento: solo respuesta a interacción; respetar `prefers-reduced-motion`.

## Information architecture

- Encabezado: regreso opcional, ubicación contextual, búsqueda, notificaciones y cuenta.
- Inicio: saludo/contexto local, accesos por área, escaparate, filtros Para ti/Ofertas/Solicitudes y feed.
- Explorar: consulta visible, filtros progresivos, conteo y resultados con tarjetas específicas.
- Detalles: autor/proveedor, clasificación, información, acción principal y vínculo a conversación u operación.
- Operaciones: estado y siguiente acción; no reutilizar una conversación para operaciones distintas.

## Responsive behavior

- 320–639 px: una columna, barra inferior fija y filtros progresivos.
- 640–1023 px: dos columnas cuando el contenido lo permita.
- 1024 px+: contenido central con soporte lateral; sin estirar tarjetas indefinidamente.
- Nunca ocultar empleo ni acciones por simplificar la navegación.

