# Plaza Local Unified Discovery Design

## Intent

Plaza Local debe sentirse como una sola plaza digital que se recorre horizontalmente por áreas, no como varios productos pegados. Inicio, Comida, Servicios, Productos, Transporte y Empleo compartirán la misma estructura visual: contexto breve, carruseles de descubrimiento y actividad relacionada. Las fotografías y la clasificación del contenido deben explicar qué está viendo la persona sin repetir textos introductorios ni paneles decorativos.

Esta versión no es un prototipo desechable: es la base que se terminará y llevará a producción. El lanzamiento inicial será deliberadamente sin pasarela de pago ni cobros reales en ningún módulo.

## Goals

- Reducir ruido visual, espacios excesivos, bordes repetidos y explicaciones redundantes.
- Convertir Inicio, Comida, Servicios, Productos, Transporte y Empleo en módulos hermanos dentro de la navegación principal.
- Mostrar carruseles útiles en todos los módulos, no únicamente en el feed “Para ti”.
- Incluir un resumen de Empleo en Inicio y una vista de descubrimiento más completa al entrar al módulo Empleo.
- Mantener fotografías reales como elemento dominante y mostrar tipo, rubro, comunidad y precio o condición con una jerarquía breve.
- Conservar publicaciones, solicitudes, comentarios, propuestas, conversaciones, vacantes y postulaciones existentes.

## Non-goals

- No fusionar una vacante con una solicitud de servicio ni cambiar sus reglas de negocio.
- No eliminar las páginas completas de vacantes, postulaciones o gestión del empleador.
- No habilitar pagos, cobros, depósitos, comisiones automáticas ni tarifas de publicación en ningún módulo.
- No eliminar los límites arquitectónicos que permitan anexar pagos en una etapa futura, pero ningún flujo de esta versión puede depender de una pasarela.
- Suspender únicamente webhooks pertenecientes a pasarelas de pago. Las actualizaciones en tiempo real del producto usarán eventos propios con broadcasting, WebSockets o SSE; no se confundirán con callbacks de Stripe o Mercado Pago.
- No cambiar permisos, estados, moderación, verificación ni reglas de cuentas suspendidas.
- No crear una nueva taxonomía ni migraciones en este bloque.

## Information architecture

### Primary module rail

El orden principal será:

1. Inicio
2. Comida
3. Servicios
4. Productos
5. Transporte
6. Empleo

Inicio será el estado general de descubrimiento. Los demás módulos aplicarán su filtro específico sin cambiar de lenguaje visual.

### Repeated module pattern

Cada módulo tendrá, en este orden:

1. Encabezado corto con nombre y contexto local, sin tarjeta hero pesada.
2. Uno o más carruseles de elementos relevantes para el módulo.
3. Filtros compactos cuando existan opciones reales.
4. Actividad o publicaciones relacionadas.

Los carruseles conservarán arrastre táctil, rueda/trackpad, controles de anterior/siguiente en escritorio, snap y navegación por teclado.

### Inicio

- Mezcla actividad relevante de la comunidad.
- Presenta escaparates resumidos de ofertas, solicitudes y empleo.
- No duplica una descripción del producto ni tarjetas explicativas sobre cómo funciona Plaza Local.
- Conserva una sola entrada principal para publicar.

### Empleo

- Aparece como módulo principal, no como una “bolsa” aislada en un icono independiente.
- Inicio muestra un carrusel breve de vacantes publicadas.
- El módulo Empleo muestra vacantes con más amplitud y accesos a “Publicar vacante” y “Mis vacantes y postulaciones”.
- La página completa de vacantes sigue siendo la superficie de búsqueda, paginación y gestión.

## Visual hierarchy

- Fotografía: domina la tarjeta cuando está disponible.
- Primera lectura: tipo o intención.
- Segunda lectura: título y rubro.
- Tercera lectura: comunidad, distancia o modalidad.
- Cierre: precio, presupuesto, salario o acción principal, cuando corresponda.
- Naranja: acciones principales.
- Verde: confianza, verificación y estados positivos.
- Neutros: estructura, filtros y metadatos.

No se repetirá la misma etiqueta en encabezado, badge y descripción. Se evitarán contenedores anidados que solo agreguen borde, sombra o espacio sin aportar agrupación.

## Responsive behavior

- 320–639 px: navegación y carruseles horizontales con arrastre; una columna de actividad; acciones táctiles de al menos 44 px.
- 640–1023 px: tarjetas ligeramente más anchas y controles de carrusel visibles cuando aporten precisión.
- 1024 px+: columna principal estable con soporte lateral existente; sin extender tarjetas a todo el ancho.
- Ningún módulo, especialmente Empleo, puede desaparecer por falta de espacio: la navegación horizontal debe seguir siendo desplazable.

## States and safeguards

- Carrusel vacío: no renderizar una caja vacía; mostrar la siguiente sección útil.
- Publicación sin imagen: usar el fallback existente sin perder tipo/rubro.
- Vacante sin salario: mostrar modalidad y comunidad sin inventar precio.
- Cuenta no verificada o suspendida: conservar avisos y bloqueos actuales.
- Filtros sin resultados: mantener un estado vacío breve con una acción válida.

## Acceptance criteria

- Los seis módulos aparecen en la navegación principal y son alcanzables en móvil y escritorio.
- Cada módulo comercial puede mostrar carruseles sin depender de `feed=for_you`.
- Inicio contiene un carrusel resumido de Empleo cuando existen vacantes publicadas.
- Empleo ya no depende únicamente del acceso separado “Bolsa de trabajo”.
- Las rutas completas de vacantes y postulaciones siguen funcionando.
- No aparece texto de pago, tarifa, checkout monetario o pasarela activa en publicaciones, productos, servicios, solicitudes, empleo u operaciones.
- Las acciones que actualmente dependan de pago deben quedar desactivadas o reemplazadas por acuerdos y seguimiento sin transacción dentro de la plataforma.
- No hay desplazamiento horizontal de página en 320, 375, 390 y 412 px; solo los rieles previstos se desplazan.
- Las pruebas de feed, navegación y vacantes continúan verdes.
