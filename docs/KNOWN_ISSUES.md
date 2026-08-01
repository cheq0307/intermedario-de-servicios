# Estado guardado: front base

Fecha del punto de restauración: 31 de julio de 2026.

## Estado funcional

- Portada responsive de Plaza Local terminada.
- Base de datos del marketplace y capa social creada.
- Registro, inicio de sesión y panel inicial implementados.
- Pruebas automatizadas aprobadas en el momento del guardado.

## Incidencia conocida

Los botones `Ingresar` y `Crear cuenta` de la portada todavía son elementos visuales y no enlazan a las rutas de autenticación. Las pantallas existen en `/login` y `/register`, pero este punto se conserva sin modificar porque el propietario pidió congelar el front actual.

## Restauración

Este estado se identifica mediante la etiqueta Git `front-base-v1`.
