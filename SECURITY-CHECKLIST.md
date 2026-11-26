# Lista de verificación de seguridad — M&T Tienda Virtual

## Antes de publicar
- [ ] Publicar únicamente detrás de HTTPS.
- [ ] Definir `MT_ENV=production`.
- [ ] Definir `MT_ALLOWED_ORIGINS` con el origen exacto del frontend, sin `*`.
- [ ] Definir `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` fuera del código.
- [ ] Crear un usuario MySQL exclusivo para la aplicación; no usar `root`.
- [ ] Usar contraseñas únicas de al menos 12 caracteres para cada cuenta.
- [ ] Crear cuentas de personal mediante CLI; no exponer el instalador por HTTP.
- [ ] Eliminar `backend/crear_personal.php` después de crear las cuentas.
- [ ] Confirmar que Apache aplica los archivos `.htaccess` (AllowOverride habilitado).
- [ ] Confirmar que `private_uploads` no puede abrirse desde el navegador.
- [ ] Desactivar `display_errors` y revisar logs del servidor.
- [ ] Configurar copias de seguridad automáticas y probar una restauración.
- [ ] Configurar `upload_max_filesize` y `post_max_size` con límites razonables.
- [ ] Probar login, logout, recuperación de sesión, permisos de cada rol y CSRF.
- [ ] Probar bloqueo por intentos de acceso y por claves de entrega incorrectas.
- [ ] Revisar que los registros de auditoría no almacenen contraseñas, tokens ni datos bancarios.

## Configuración de producción
Variables mínimas del entorno:
```text
MT_ENV=production
MT_ALLOWED_ORIGINS=https://tienda.tu-dominio.mx
DB_HOST=127.0.0.1
DB_NAME=mt_tienda
DB_USER=mt_app
DB_PASS=una-clave-larga-y-aleatoria
```

No incluyas valores reales en repositorios, capturas de pantalla, ZIP públicos o conversaciones compartidas.

## Límites funcionales que siguen requiriendo configuración
- La tienda no cobra tarjetas: integrar una pasarela real y verificar webhooks antes de marcar pagos como confirmados.
- El QR de consulta pública muestra datos limitados, pero debe servirse siempre sobre HTTPS.
- El GPS se registra con permiso y acción del personal; no es seguimiento continuo en segundo plano.
- La política de devoluciones y garantías debe revisarse para cumplir los términos comerciales y la normativa aplicable.
