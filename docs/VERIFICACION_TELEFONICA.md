# Pausa temporal de SMS

`PHONE_VERIFICATION_ENABLED=false` (valor predeterminado) pausa tanto el envío como la comprobación de códigos. Las credenciales y la integración Twilio se conservan. Tener claves configuradas no evita esta pausa.

- Registro y perfil conservan celular obligatorio, formato mexicano de 10 dígitos y restricciones de unicidad/vínculos entre identidades.
- Guardar un celular no significa verificarlo. Cambiarlo elimina su verificación anterior; guardar el mismo número conserva la verificación legítima que ya tuviera.
- El formulario telefónico permite guardar el contacto y no solicita códigos.
- El acceso administrativo sigue requiriendo cuenta activa, correo verificado y celular válido guardado. Durante la pausa no exige SMS. Al reactivar el flag vuelve a exigir la verificación telefónica.
- Crear una cuenta de marketplace vinculada a un administrador conserva la exigencia estricta de correo y teléfono verificados; la pausa no autoriza a duplicar identidades sin esas comprobaciones.

Para reactivar posteriormente: configurar Twilio Verify y cambiar `PHONE_VERIFICATION_ENABLED=true` en el entorno correspondiente, regenerar la caché de configuración y reiniciar los workers. Comprobar el flujo con una cuenta de prueba y códigos de cuatro dígitos. No rellenar `phone_verified_at` manualmente ni simular verificación.

Tras cualquier cambio de esta variable en el servidor:

```bash
php artisan config:cache
sudo systemctl restart plaza-local-queue
```

Los tests históricos ejecutan explícitamente el modo SMS habilitado; `PausedPhoneVerificationTest` prueba el modo pausado y que no salgan solicitudes HTTP.
