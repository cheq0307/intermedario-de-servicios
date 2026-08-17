# Verificación, confianza y evidencias

## Decisión de arquitectura

Plaza Local utiliza una cuenta comercial unificada: una misma persona puede solicitar, comprar, vender u ofrecer servicios. Los roles `admin` y `superadmin` son autoridad administrativa y no sustituyen la verificación comercial.

La verificación es progresiva y se solicita según el riesgo de la acción, no toda durante el registro.

## Niveles

1. **Cuenta básica:** nombre, correo verificado, teléfono verificado, comunidad y aceptación de términos.
2. **Identidad verificada:** identificación oficial y validación de identidad/prueba de vida mediante un proveedor KYC.
3. **Habilitada para cobrar:** RFC y datos fiscales necesarios, cuenta bancaria a nombre de la persona o negocio y revisión de actividad.
4. **Actividad regulada:** permisos, licencias, cédulas o certificaciones exigibles para el rubro.

Los distintivos deben ser específicos: `Identidad verificada`, `Cuenta bancaria verificada`, `Datos fiscales verificados`, `Credencial profesional verificada` o `Negocio verificado`. No se debe afirmar que Plaza Local garantiza la calidad total del trabajo.

## Documentos por tipo

### Persona física que ofrece o cobra

- Identificación oficial vigente.
- Resultado de selfie/prueba de vida gestionada por KYC.
- RFC, nombre fiscal, código postal y régimen cuando corresponda.
- CLABE o cuenta de depósito validada por la pasarela.
- Comprobante de domicilio únicamente si es necesario acreditar ubicación.
- Evidencia de actividad, negocio, herramientas o trabajos.
- Permisos y credenciales según el rubro.

### Persona moral

- Razón social y RFC.
- Acta constitutiva.
- Identificación del representante.
- Poder o documento de facultades.
- Cuenta bancaria de la empresa.
- Domicilio comercial y permisos aplicables.

### Comprador

Para operaciones ordinarias no se exige identificación documental. Se verifica correo, teléfono y método de pago. La identificación se solicita por riesgo, monto o categoría restringida.

## Expediente de operación

Cada operación conserva versiones inmutables de:

- Publicación y propuesta aceptada.
- Alcance, precio, fechas y lugar.
- Conversación interna.
- Pago, comisión, reembolso y depósito.
- Fotografías o evidencias antes y después.
- Código OTP/QR de entrega o aceptación.
- Cambios aprobados por ambas partes.
- Cancelaciones, disputa, resolución y calificaciones.

## Protección de datos

- Aviso de privacidad simplificado e integral.
- Finalidad específica para cada dato.
- Minimización y plazos de conservación.
- Cifrado en tránsito y reposo.
- Acceso por mínimo privilegio y auditoría.
- Derechos ARCO y procedimiento de incidentes.
- KYC y pagos mediante proveedores especializados.
- Plaza Local conserva preferentemente el resultado/token de validación, no imágenes biométricas o documentos indefinidamente.

Usar infraestructura en la nube no transfiere automáticamente la responsabilidad. Plaza Local sigue siendo responsable de configurar accesos, cifrado, secretos, respaldos, retención, monitoreo y respuesta a incidentes.

## Catálogo postal

El catálogo oficial de Correos de México se usa internamente para autocompletar CP, asentamiento, municipio/ciudad y estado. No se comercializa ni redistribuye como producto independiente. La administración decide qué comunidades del catálogo se habilitan para los usuarios.
