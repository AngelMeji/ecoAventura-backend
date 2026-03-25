# Guía de Despliegue en Dockploy - EcoAventura

Este proyecto está configurado para un despliegue automático continuo mediante **Dockploy**. Cualquier cambio subido a la rama principal (`main`) del repositorio de GitHub activará automáticamente un nuevo "build" y despliegue en el servidor.

## Configuración para Nuevas Organizaciones

Si una nueva organización desea utilizar este proyecto, debe seguir estos pasos para configurar su propio entorno de despliegue:

### 1. Variables de Entorno (.env)

Es fundamental configurar correctamente las URLs en ambos entornos para que la comunicación entre el Frontend y el Backend, así como la carga de imágenes, funcione correctamente.

#### Backend (.env)
- `APP_URL`: La URL pública de tu API (ej: `https://api.tu-organizacion.com`). Esta URL es vital para que el servidor genere correctamente los enlaces a las imágenes almacenadas.
- `FRONTEND_URL`: La URL de tu aplicación frontend (ej: `https://tu-organizacion.com`). Se utiliza para configurar CORS y permitir peticiones desde el navegador.

#### Frontend (.env)
- `VITE_API_URL`: La URL de la API del backend con el sufijo `/api` (ej: `https://api.tu-organizacion.com/api`).

### 2. Flujo de Despliegue Automático

1. **Ediciones**: Realiza los cambios necesarios en el código (vistas, lógica de negocio, estilos, etc.).
2. **Git Push**: Sube tus cambios al repositorio: `git push origin main`.
3. **Webhooks**: Dockploy recibirá una notificación de GitHub mediante un Webhook.
4. **Auto-Build**: El servidor ejecutará automáticamente los comandos de construcción:
   - `composer install` y `php artisan migrate` en el Backend.
   - `npm install` y `npm run build` en el Frontend.
5. **Live Update**: Una vez finalizado el build, los cambios se reflejarán inmediatamente en el sitio en vivo.

### 3. Manejo de Almacenamiento (Storage)

Asegúrate de que el comando `php artisan storage:link` se haya ejecutado en el servidor para que las imágenes (avatares y lugares) sean accesibles públicamente a través de la URL de almacenamiento (`/storage`).

---
> [!IMPORTANT]
> Nunca compartas tu archivo `.env` en el repositorio público. Dockploy permite configurar estas variables de forma segura en su panel de administración.
