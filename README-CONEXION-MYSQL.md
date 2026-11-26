# M&T Tienda Virtual — instalación y actualización

## Requisitos
- XAMPP con Apache y MySQL/MariaDB.
- Node.js 18 o superior.
- PHP con PDO MySQL, Fileinfo y soporte para cargas de archivos.

## Instalación nueva
1. Enciende Apache y MySQL en XAMPP.
2. Abre phpMyAdmin e importa `database.sql` una sola vez.
3. Importa `database-upgrade.sql` sobre la base `mt_tienda`.
4. Copia la carpeta `mt-tienda-virtual` a `C:\xampp\htdocs\mt-tienda-virtual`.
5. Revisa `backend/config.php` y configura usuario/contraseña de MySQL.
6. Crea las cuentas del administrador y vendedor (y opcionalmente repartidor) desde una terminal local (no desde el navegador). Define las variables de entorno `MT_ADMIN_EMAIL`, `MT_ADMIN_PASSWORD`, `MT_SELLER_EMAIL`, `MT_SELLER_PASSWORD`, `MT_COURIER_EMAIL` y `MT_COURIER_PASSWORD`, con contraseñas únicas de al menos 12 caracteres, y ejecuta `php backend/crear_personal.php`.
7. El script de alta solo funciona por CLI, no utiliza credenciales predefinidas y no cambia cuentas existentes. Elimina el script del servidor después de usarlo.

Ejemplo en PowerShell (usa contraseñas propias, no copies este ejemplo literalmente):
```powershell
$env:MT_ADMIN_EMAIL = "admin@tu-dominio.mx"
$env:MT_ADMIN_PASSWORD = "UnaFraseLarga-Aleatoria-2026!"
$env:MT_SELLER_EMAIL = "ventas@tu-dominio.mx"
$env:MT_SELLER_PASSWORD = "OtraFraseLarga-Aleatoria-2026!"
$env:MT_COURIER_EMAIL = "reparto@tu-dominio.mx"
$env:MT_COURIER_PASSWORD = "UnaTerceraFraseLarga-2026!"
php backend/crear_personal.php
```
Al terminar, cierra esa terminal para descartar las variables de entorno.

## Actualizar una instalación que ya tiene datos
1. Haz una copia de seguridad desde phpMyAdmin (Exportar → SQL).
2. NO vuelvas a importar `database.sql`: ese archivo contiene la estructura/semillas de instalación inicial.
3. Importa únicamente `database-upgrade.sql` sobre `mt_tienda`. Es una migración aditiva y no elimina registros.
4. Si necesitas cuentas de personal, créalas desde CLI con variables de entorno como se explica arriba. El script no reasigna automáticamente pedidos ni productos históricos; revisa y asigna esos registros de forma controlada.
5. Actualiza los archivos del proyecto sin sobrescribir `backend/config.php` con credenciales locales propias.

> `database-upgrade.sql` usa `ADD COLUMN IF NOT EXISTS`; utiliza una versión reciente de MySQL 8 o MariaDB compatible con esa sintaxis. Si phpMyAdmin marca error de sintaxis, consulta la versión de MySQL/MariaDB antes de ejecutar ALTERs manuales.

## Ejecutar React
```bash
npm install
npm run dev
```
Abre la URL de Vite (normalmente `http://localhost:5173`). El proxy de Vite dirige `/mt-tienda-virtual` a Apache.

## Funciones incorporadas
- Vendedor: alta/edición/desactivación de productos propios, precio, stock, características y carga múltiple de fotografías desde el equipo.
- Catálogo cliente: galería de fotografías y características técnicas.
- Pedidos: historial de estados y registro de ubicación/GPS mediante permiso explícito del navegador.
- Repartidor: cuenta separada, pedidos asignados y confirmación de entrega mediante clave de 6 dígitos.
- Comprobante: QR de verificación pública limitada al folio/estado, código de barras y clave privada de recepción.
- Posventa: solicitud de devolución, cambio o garantía con evidencia; revisión y resolución por vendedor.
- Productos de bajo valor: se marcan para revisión rápida cuando el precio de compra es igual o menor al umbral `MT_LOW_VALUE_RETURN_LIMIT` (por defecto $1,000 MXN). El vendedor sigue tomando la decisión.
- Reseñas: solo clientes autenticados que hayan recibido el producto; una reseña por cliente/producto.
- Seguridad: sesiones regeneradas, cookies HttpOnly/SameSite, CSRF, límite de intentos de inicio de sesión, contraseñas hash, control de propiedad por vendedor, auditoría y validación de imágenes.

## Notas de operación y seguridad
- El QR y el código de barras se renderizan mediante servicios externos (QR Server y TEC-IT); el QR comparte solo un token aleatorio de verificación y el código de barras comparte el folio. Si necesitas operación totalmente offline, integra una librería QR/Code128 local y empaquetada.
- La clave de recepción se muestra al cliente al finalizar la compra. Debe guardarla; el servidor conserva únicamente su hash y la invalida después de la entrega.
- El GPS solo se registra cuando el repartidor/vendedor pulsa el botón y concede permiso. No se hace rastreo oculto ni continuo en segundo plano.
- El seguimiento por calle depende de que el repartidor registre ubicación/estado. Para seguimiento continuo en tiempo real falta una app móvil/PWA que envíe posiciones periódicas con consentimiento y controles de privacidad.
- La política de devoluciones/garantías debe ajustarse a los términos comerciales y a la legislación aplicable en México. El módulo registra y aprueba solicitudes; no ejecuta pagos ni reembolsos bancarios automáticos.
- El proyecto sigue usando pagos de demostración/contra entrega; no procesa tarjetas ni pagos reales.
- En `php.ini`, ajusta `upload_max_filesize=6M` y `post_max_size=35M` para permitir las cargas múltiples; reinicia Apache después del cambio.
- Antes de publicar en internet configura `MT_ENV=production`, HTTPS, `MT_ALLOWED_ORIGINS` con el dominio exacto del frontend, `DB_PASS` y un usuario MySQL de privilegios mínimos; aplica permisos de archivos, copias de seguridad y secretos fuera del código. En producción, el backend rechaza HTTP y orígenes no permitidos. Las carpetas de evidencias privadas tienen acceso HTTP denegado.


## Endurecimiento de seguridad adicional
- Las solicitudes POST requieren token CSRF. El cliente obtiene el token mediante `bootstrap` antes de autenticarse.
- En producción configura `MT_ALLOWED_ORIGINS=https://tu-dominio.example` (sin comodines). Si frontend y API usan distintos dominios, añade solo el origen exacto del frontend.
- Configura `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` como variables de entorno. En producción no se permite una contraseña vacía de MySQL.
- Las cuentas iniciales se crean únicamente desde CLI con variables de entorno; el instalador ya no contiene contraseñas demo ni crea cuentas por HTTP.
- Las cargas de imágenes validan MIME real, dimensiones, peso y extensiones; se impide el listado y la ejecución de scripts en la carpeta pública de imágenes. Las evidencias de devolución permanecen en carpeta denegada por Apache y se consultan mediante endpoint autenticado.
- Se recomienda crear un usuario MySQL exclusivo para la aplicación con permisos solo sobre `mt_tienda` (SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX durante migraciones; retirar ALTER/CREATE al terminar).


## Cuentas de administrador y vendedor
Las cuentas se insertan en la tabla `users` mediante `backend/crear_personal.php`. No se incluyen contraseñas en texto plano ni hashes inventados en `database.sql`: PHP genera cada hash con `password_hash()` y el script no sobrescribe usuarios existentes. Define `MT_ADMIN_EMAIL`, `MT_ADMIN_PASSWORD`, `MT_SELLER_EMAIL` y `MT_SELLER_PASSWORD` en PowerShell y ejecuta `php backend/crear_personal.php`. El repartidor es opcional: solo se crea si configuras también sus dos variables de entorno.

## Arma tu PC y publicación de productos
El configurador obtiene procesadores, memorias RAM, unidades de almacenamiento y tarjetas gráficas de los productos activos de MySQL; muestra el precio individual y suma los cuatro componentes seleccionados. Para instalaciones existentes, importa `database-upgrade.sql` para agregar el procesador y SSD de demostración si no existen. Los productos guardados por administrador o vendedor se consultan en el `bootstrap` público del catálogo; el cliente actualiza el catálogo al volver a la pestaña y cada 15 segundos mientras la tienda está abierta.
