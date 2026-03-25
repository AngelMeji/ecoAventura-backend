# 🚀 Guía de Despliegue - EcoAventura (Dockploy)

Este documento explica cómo gestionar el despliegue automático del proyecto EcoAventura utilizando **Dockploy**. El proyecto está dividido en dos partes principales: el **Backend (Laravel)** y el **Frontend (React/Vite)**.

## 🔄 Flujo de CI/CD (Despliegue Automático)

El sistema está configurado para un despligue continuo (CI/CD):

1. **Push a Git**: Cada vez que se realiza un `git push` a la rama configurada (generalmente `main`), Dockploy detecta el cambio.
2. **Build Automático**: El servidor inicia automáticamente un proceso de "rebuild" que incluye:
   - Instalación de dependencias (Composer para PHP, NPM para JS).
   - Generación de archivos estáticos (Frontend build).
   - Ejecución de optimizaciones de caché en Laravel.
3. **Despliegue**: Una vez terminada la construcción, la nueva versión se publica sin intervención manual.

---

## ⚙️ Configuración para Nuevas Organizaciones

Si una nueva organización desea implementar este proyecto, debe seguir estos pasos:

### 1. Variables de Entorno (.env)

Es crucial configurar correctamente las URLs y credenciales en el panel de Dockploy o en los archivos `.env` del servidor.

#### 🖥️ Backend (ecoAventura-backend)
Modificar las siguientes variables para que apunten al dominio de producción:

- `APP_URL`: URL base de la API (ej: `https://api.tu-organizacion.com`).
- `FRONTEND_URL`: URL donde estará alojado el frontend (ej: `https://tu-organizacion.com`).
- `SANCTUM_STATEFUL_DOMAINS`: Los dominios permitidos para sesiones (ej: `tu-organizacion.com,api.tu-organizacion.com`).
- **Base de Datos**: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- **Servicio de Correo**: `RESEND_API_KEY` (Obligatorio para el envío de correos de recuperación).
- **Google Login**: `GOOGLE_CLIENT_ID` (Si se usa login social).

#### 🌐 Frontend (ecoAventura-frontend)
- `VITE_API_URL`: Debe apuntar a la URL pública del backend terminada en `/api` (ej: `https://api.tu-organizacion.com/api`).

---

## 🛠️ Cómo realizar cambios

Para que los cambios se vean reflejados en el servidor:

1. Realice las ediciones necesarias localmente.
2. Asegúrese de que las pruebas pasen (`composer test-db` en el backend).
3. Realice un commit con un mensaje descriptivo.
4. Suba los cambios a su repositorio remoto:
   ```bash
   git push origin main
   ```
5. El servidor recibirá el hook de Dockploy y comenzará el proceso de build de inmediato. No es necesario reiniciar servicios manualmente.

---

## 📝 Notas Adicionales
- Si se añaden nuevas variables de entorno, asegúrese de agregarlas también en la configuración de la aplicación en el dashboard de Dockploy para que estén disponibles durante el tiempo de ejecución.
- El servidor ejecuta `php artisan migrate` automáticamente si hay nuevas migraciones en el código subido.
