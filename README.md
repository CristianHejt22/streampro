# 🍿 CStreame - Plataforma de Control & Venta de Cuentas de Streaming 🎬

**CStreame** es un sistema integral, profesional y ultra liviano para la **gestión, control de stock, venta, garantías y distribución de cuentas de streaming** (Netflix, Disney+, Max/HBO, Prime Video, Spotify, MagisTV/IPTV, Crunchyroll, Paramount+, etc.).

Está diseñado tanto para vendedores mayoristas, revendedores independientes y agencias digitales, optimizado para ejecutarse en **hostings compartidos estándar (cPanel, Hostinger, Plesk)** o VPS utilizando **PHP 8.1 / 8.2 (Laravel 10)** y **MySQL 8.0+ / MariaDB**.

---

## ⚡ Características Principales y Módulos

### 1. 📺 Gestión de Cuentas Maestras, Pantallas y PINs de Seguridad
- **Modalidades Flexibles:**
  - **Perfil / Pantalla Individual:** Asigna 1 perfil específico de una cuenta compartida con su **PIN de 4 dígitos** exclusivo para el cliente.
  - **Cuenta Completa:** Asigna todos los perfiles a un solo cliente (ideal para familias o clientes premium).
- **Generador Inteligente de Perfiles:** Al dar de alta una cuenta maestra, el sistema genera automáticamente las pantallas (ej. *Pantalla 1 - Rojo*, *Pantalla 2 - Azul*, etc.) con PINs de seguridad aleatorios de 4 dígitos.
- **Detección Automática de Ocupación:** Monitorea en tiempo real cuántas pantallas están libres, asignadas o en revisión técnica.

### 2. 🍿 Entrega Instantánea de Credenciales vía WhatsApp
- **Copia con 1 Clic & Enlace Directo:**
  - Genera automáticamente el mensaje formateado con negritas, emojis y código para WhatsApp listo para enviar con 1 clic:
    ```text
    🍿 *¡TU CUENTA DE Netflix 4K UHD ESTÁ LISTA!* 🍿
    De parte de *CStreame Master*.

    👤 *Usuario / Correo:* `netstream.latam01@gmail.com`
    🔑 *Contraseña:* `LatamNflx2026#`
    📺 *Perfil:* *Pantalla 1 - Rojo*
    🔢 *PIN de Perfil:* `1249`
    📅 *Vencimiento:* 10/11/2026

    ⚠️ *REGLAS DE USO OBLIGATORIAS:*
    - Prohibido cambiar correo o contraseña de la cuenta.
    - Ingresar ÚNICAMENTE al perfil asignado con tu PIN.
    - No modificar perfiles de otros clientes.
    - Cuenta válida para 1 solo dispositivo simultáneo.

    💬 Cualquier consulta responde a este mensaje. ¡Que disfrutes tu contenido! ✨
    ```

### 3. 🚨 CRM de Ventas, Vencimientos y Cobros
- **Semáforo Visual de Vencimientos:**
  - 🟢 **Activa:** Servicio al día con cuenta regresiva en días.
  - 🟡 **Por Vencer (<= 3 días):** Alerta preventiva para avisar al cliente antes del corte.
  - 🔴 **Vencida:** Servicio finalizado listo para renovación o liberación de stock.
- **Botón de Renovación Inmediata (+30 días):** Renueva la fecha de corte sumando 30 días con 1 clic y registra el historial de renovaciones.
- **Mensaje de Cobro Automático por WhatsApp:**
  - Genera el aviso de renovación personalizado con el nombre del cliente, servicio, días restantes y monto a abonar.
- **Exportación Contable a CSV/Excel:** Descarga de base de ventas en UTF-8 con BOM compatible con Microsoft Excel.

### 4. 🛡️ Módulo de Garantías y Reemplazo en 1 Clic
- ¿El proveedor cambió la contraseña o la cuenta se cayó antes de tiempo?
- **Reemplazo Automático de Stock:**
  - Al reportar una incidencia, el sistema busca automáticamente otra pantalla disponible del mismo servicio.
  - Marca la pantalla anterior en estado `in_review` (En revisión técnica).
  - Asigna la nueva pantalla a la venta, actualiza el snapshot de credenciales y PIN, y genera el nuevo mensaje de WhatsApp para el cliente en segundos.
  - Registro de auditoría y motivo de garantía (caída de cuenta, cambio de contraseña, error de pago de origen).

### 5. 📦 Proveedores de Cuentas & Control de Costos
- **Directorio de Proveedores:** Registra datos de contacto de tus proveedores (WhatsApp, Telegram, email) y calificación de fiabilidad (1 a 5 estrellas).
- **Alerta de Vencimiento de Proveedor:** Monitorea cuándo vence la cuenta maestra que le compraste al proveedor para que puedas pagarle a tiempo y evitar que tus clientes sufran cortes.
- **Cálculo de Margen y Ganancia Neta:** El sistema calcula automáticamente la ganancia por cada venta restando el costo estimado del precio cobrado al cliente.

### 6. 💼 Billetera de Revendedores & Precios Mayoristas
- **Billetera de Saldo Prepago:** Los revendedores cargan saldo en su billetera.
- **Deducción Automática:** Cada vez que un revendedor realiza una venta desde su panel, se descuenta automáticamente el costo mayorista (`reseller_price`) de su balance.
- **Protección de Saldo:** Si el revendedor no tiene saldo suficiente, el sistema bloquea la operación y solicita recarga.
- **Recargas de Saldo y Reseteo de Credenciales:** El Super Admin puede recargar saldo y gestionar revendedores fácilmente.

### 7. 🏷️ Marca Blanca (Whitelabel) & Catálogo Público con Referidos
- **Catálogo Digital (`/catalogo`):**
  - Tienda visual para clientes finales adaptada a celulares y tablets.
  - Muestra servicios, planes, características, precios y stock disponible en tiempo real.
- **Enlaces de Referido (`/ref/{codigo}`):**
  - Cada revendedor tiene su enlace único.
  - Al entrar a través de su link, el catálogo adopta la marca del revendedor (Nombre comercial, logo, colores y botón de WhatsApp de contacto directo).
- **Checkout Integrado:**
  - El cliente selecciona el plan, ingresa sus datos y elige método de pago (Mercado Pago o Transferencia / Binance).

### 8. 💰 Tesorería & Mini Nube Anti-Saturación de Hosting
- **Configuración de Cobros:** CBU, CVU, Alias, Titular, USDT Binance Pay y pasarela Mercado Pago.
- **Carga de Comprobante:** En transferencias, el cliente adjunta su comprobante de pago.
- **Aprobación con 1 Clic & Limpieza de Disco:**
  - Al hacer clic en **"Aprobar y Cerrar"**:
    1. Se valida el stock disponible.
    2. Se asigna la cuenta/pantalla y se crea la venta.
    3. **SE ELIMINA DE INMEDIATO Y DE FORMA PERMANENTE EL COMPROBANTE DEL SERVIDOR.**
  - **Ventaja Exclusiva:** Los comprobantes nunca saturan el espacio en disco de tu hosting compartido (cPanel / Hostinger).

### 9. ⏱️ Cron Job de Mantenimiento (`cstreame:cron`)
- Comando nativo de mantenimiento programado:
  ```bash
  php artisan cstreame:cron
  ```
  - Actualiza automáticamente las ventas que llegaron a su fecha de vencimiento a estado `expired`.
  - Marca ventas que vencen en 3 días o menos en estado `expiring_soon`.
  - Audita el vencimiento de las cuentas maestras de proveedores.
  - Purga comprobantes temporales abandonados con más de 48 horas.

---

## 👥 Sistema de Registro y Activación por Correo y Contraseña

Similar a la dinámica operativa de **Strampro**, las activaciones y el acceso de clientes se realizan directamente mediante **correo electrónico y contraseña**:

1. **Página de Registro del Reseller / Super Reseller (`/ref/{code}/registro` o `/registro`):**
   - Cada revendedor tiene su enlace único con su marca blanca.
   - El cliente se registra con su **Nombre, Correo Electrónico, WhatsApp y Contraseña**.
   - Queda vinculado automáticamente a la cartera de ese revendedor.
2. **Activación de Cuentas / Licencias de Streaming:**
   - Desde el CRM de Clientes (`/customers`), el revendedor o administrador puede activar inmediatamente un servicio al cliente seleccionando la plataforma deseada (**Netflix, HBO Max, Disney Estándar, Disney Premium**).
   - El sistema asigna automáticamente una pantalla libre en stock con su PIN exclusivo de 4 dígitos.
3. **Bóveda Privada del Cliente (`/boveda` o `/boveda/login`):**
   - El cliente ingresa con su correo y contraseña.
   - Visualiza en tiempo real sus cuentas activas:
     - Servicio y Plan contratado.
     - **Correo de acceso y Contraseña** (con botón de copiar y mostrar/ocultar).
     - **Perfil asignado y PIN de 4 dígitos**.
     - **Vencimiento y cuenta regresiva de días de garantía (SLA)**.
     - Reglas de uso oficiales de la plataforma.
     - Botón de **"Reportar Falla"** para solicitar soporte o reemplazo automático si la cuenta sufre una caída.
4. **Login Unificado (`/login`):**
   - El formulario de inicio de sesión detecta si el usuario es Administrador / Revendedor (redirigiendo a `/dashboard`) o Cliente final (redirigiendo a `/boveda`).

---

## 🔑 Credenciales de Acceso Demo

El seeder del sistema (`php artisan db:seed`) incluye las siguientes cuentas preconfiguradas:

| Rol | Correo | Contraseña | Enlace / Funcionalidad |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@cstreame.com` | `admin123` | Control total del sistema, inventario global, proveedores, usuarios y tesorería. |
| **Super Reseller** | `super@revendedor.com` | `super123` | Gestión de sub-revendedores, stock mayorista, enlace de registro: `/ref/SUPER-CARLOS/registro`. |
| **Revendedor VIP** | `marcos@revendedor.com` | `reseller123` | Billetera prepago, venta de pantallas, enlace de registro: `/ref/VIP-MARCOS/registro`. |
| **Cliente Demo** | `cliente@cstreame.com` | `cliente123` | Bóveda de cliente (`/boveda`), visualización de Netflix 4K y Disney+ Premium activos. |

---

## 🛠️ Instalación y Puesta en Marcha Local

### Prerrequisitos
- PHP 8.1 o superior con extensiones `pdo_mysql`, `mbstring`, `openssl`, `curl`.
- MySQL 8.0+ o MariaDB 10.4+.
- Servidor web Apache o servidor embebido de PHP / Laravel.

### Pasos
1. Clonar o posicionarse en el directorio del proyecto:
   ```powershell
   cd D:\CStreame
   ```
2. Configurar el archivo `.env`:
   ```ini
   APP_NAME=CStreame
   APP_URL=http://localhost:8080
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cstreame
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Ejecutar las migraciones y sembrar la base de datos:
   ```powershell
   php artisan migrate:fresh --seed
   ```
4. Iniciar el servidor local (en puerto 8080):
   ```powershell
   php artisan serve --port=8080
   ```
5. Abrir en el navegador:
   - **Login Unificado:** `http://localhost:8080/login`
   - **Bóveda de Clientes:** `http://localhost:8080/boveda/login`
   - **Registro de Clientes (General):** `http://localhost:8080/registro`
   - **Registro de Clientes Referido (Super Reseller):** `http://localhost:8080/ref/SUPER-CARLOS/registro`
   - **Registro de Clientes Referido (Reseller VIP):** `http://localhost:8080/ref/VIP-MARCOS/registro`
   - **Catálogo Digital:** `http://localhost:8080/catalogo`

---

## 🌐 Despliegue en Servidores cPanel / Hostinger

1. **Subir archivos:** Sube los archivos a tu hosting. La carpeta `public` de Laravel debe apuntar a la raíz web (`public_html` o subdominio asignado).
2. **Base de Datos:** Crea la base de datos MySQL en cPanel y carga los datos de conexión en el archivo `.env`.
3. **Migraciones:** Ejecuta las migraciones desde la Terminal de cPanel o importa el volcado SQL generado.
4. **Configurar Tarea Cron:**
   En cPanel ve a **Cron Jobs (Tareas Cron)** y añade la siguiente instrucción:
   ```bash
   0 * * * * cd /home/TU_USUARIO/public_html && php artisan cstreame:cron >> /dev/null 2>&1
   ```

---

## 📋 Estructura de Rutas y Módulos

- `/login` & `/logout`: Autenticación unificada con detección de roles y clientes.
- `/registro`: Registro público de nuevos clientes.
- `/ref/{code}/registro`: Registro de clientes bajo la marca blanca del Super Reseller o Reseller.
- `/boveda`: Bóveda privada de credenciales de streaming del cliente (perfiles, PIN, contraseñas, SLA y reclamos).
- `/boveda/login`: Acceso directo exclusivo para clientes.
- `/dashboard`: Escritorio analítico con stock en vivo por plataforma, cuentas críticas y facturación.
- `/accounts`: Inventario de cuentas maestras con resumen de stock en vivo (`🟢 Stock Óptimo`, `🟡 Stock Bajo`, `🔴 Agotado`), reposición rápida `+1 Cuenta` y actualización de clave privada.
- `/customers`: CRM de clientes con enlaces de registro de referidos, activación directa de streaming y reseteo de claves de acceso a la bóveda.
- `/sales`: Gestión de ventas, entrega de WhatsApp, renovaciones y garantías.
- `/services`: Catálogo de plataformas (Netflix, Max, Disney Estándar, Disney Premium) y planes de suscripción.
- `/suppliers`: Directorio de proveedores mayoristas y control de vencimientos.
- `/warranties`: Gestión y resolución de garantías por caídas de cuenta.
- `/resellers`: Administración de Super Resellers y Resellers con recargas de billetera prepago.
- `/treasury`: Auditoría de pagos, cobros bancarios, Mercado Pago y Mini Nube.
- `/whitelabel`: Personalización de marca blanca, logotipos y redes.
- `/catalogo`: Tienda digital pública para clientes finales.
- `/ref/{code}`: Acceso al catálogo con la marca y contacto de un revendedor específico.

