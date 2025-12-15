# 🌿 EcoAventura Backend API

API REST de Laravel para la plataforma EcoAventura - Turismo ecológico y aventura.

## 📋 Requisitos

- PHP >= 8.2
- Composer
- MySQL / PostgreSQL / SQLite
- Node.js (opcional, para compilación de assets)

## 🚀 Instalación

### 1. Clonar e instalar dependencias

```bash
# Clonar repositorio
git clone <url-del-repositorio>
cd ecoAventura-backend

# Instalar dependencias PHP
composer install
```

### 2. Configurar el entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar key de la aplicación
php artisan key:generate
```

### 3. Configurar la base de datos

Edita el archivo `.env` con tus credenciales de base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecoaventura
DB_USERNAME=root
DB_PASSWORD=

# URL del frontend (para CORS)
FRONTEND_URL=http://localhost:3000
```

### 4. Ejecutar migraciones y seeders

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (usuarios de prueba + categorías)
php artisan db:seed
```

### 5. Crear enlace simbólico para storage

```bash
php artisan storage:link
```

Esto crea un enlace simbólico `public/storage` → `storage/app/public` para servir imágenes.

### 6. Iniciar servidor de desarrollo

```bash
php artisan serve
```

El servidor estará disponible en `http://localhost:8000`

---

## 👤 Usuarios de Prueba

| Email | Password | Rol |
|-------|----------|-----|
| admin@ecoaventura.com | password | admin |
| partner@ecoaventura.com | password | partner |
| user@ecoaventura.com | password | user |

---

## 📡 Endpoints de la API

### Autenticación

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/register` | Registrar usuario | No |
| POST | `/api/login` | Iniciar sesión | No |
| GET | `/api/me` | Usuario autenticado | Sí |
| POST | `/api/logout` | Cerrar sesión | Sí |

### Categorías (Público)

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/categories` | Listar categorías | No |
| GET | `/api/categories/{id}` | Ver categoría | No |

### Lugares (Público)

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/places` | Listar lugares aprobados | No |
| GET | `/api/places/{slug}` | Ver lugar por slug | No |

#### Parámetros de query para `/api/places`:

- `category_id`: Filtrar por categoría
- `featured`: `true` para lugares destacados
- `search`: Buscar por nombre, descripción o dirección
- `per_page`: Cantidad por página (default: 12)

### Lugares - Partner/Admin

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/partner/places` | Mis lugares | Partner |
| POST | `/api/partner/places` | Crear lugar | Partner |
| PUT | `/api/partner/places/{id}` | Actualizar lugar | Partner |
| DELETE | `/api/partner/places/{id}` | Eliminar lugar | Partner |

### Lugares - Admin

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| GET | `/api/admin/places` | Todos los lugares | Admin |
| PATCH | `/api/admin/places/{id}/status` | Cambiar estado | Admin |
| PUT | `/api/admin/places/{id}` | Actualizar lugar | Admin |
| DELETE | `/api/admin/places/{id}` | Eliminar lugar | Admin |

---

## 📤 Subida de Imágenes

### Crear lugar con imágenes

```bash
POST /api/partner/places
Content-Type: multipart/form-data
Authorization: Bearer {token}

# Campos:
- category_id: integer (required)
- name: string (required)
- short_description: string (required)
- description: string (optional)
- address: string (optional)
- latitude: float (optional)
- longitude: float (optional)
- images[]: file (required, min:1, max:10)
- primary_image_index: integer (optional, default:0)
```

### Actualizar lugar e imágenes

```bash
PUT /api/partner/places/{id}
Content-Type: multipart/form-data
Authorization: Bearer {token}

# Campos:
- name: string (optional)
- short_description: string (optional)
- ...otros campos
- new_images[]: file (optional, max:10)
- delete_images[]: integer[] (IDs de imágenes a eliminar)
- primary_image_id: integer (ID de nueva imagen principal)
```

---

## 📊 Respuesta de Lugar

```json
{
  "data": {
    "id": 1,
    "name": "Cascada El Salto",
    "slug": "cascada-el-salto",
    "short_description": "Hermosa cascada de 30 metros",
    "description": "...",
    "address": "Montaña Verde, km 45",
    "latitude": "10.1234567",
    "longitude": "-64.1234567",
    "is_featured": false,
    "status": "approved",
    "category": {
      "id": 4,
      "name": "Cascadas"
    },
    "user": {
      "id": 2,
      "name": "Socio Demo"
    },
    "images": [
      {
        "id": 1,
        "url": "http://localhost:8000/storage/places/1/abc123.jpg",
        "filename": "cascada.jpg",
        "is_primary": true,
        "order": 0
      },
      {
        "id": 2,
        "url": "http://localhost:8000/storage/places/1/def456.jpg",
        "filename": "vista.jpg",
        "is_primary": false,
        "order": 1
      }
    ],
    "primary_image_url": "http://localhost:8000/storage/places/1/abc123.jpg",
    "created_at": "2025-12-15T10:00:00+00:00",
    "updated_at": "2025-12-15T10:00:00+00:00"
  }
}
```

---

## 🔐 Autenticación

Esta API usa **Laravel Sanctum** para autenticación basada en tokens.

### Obtener token (Login)

```bash
POST /api/login
Content-Type: application/json

{
  "email": "partner@ecoaventura.com",
  "password": "password"
}
```

Respuesta:
```json
{
  "message": "Login exitoso",
  "user": {...},
  "token": "1|abc123def456..."
}
```

### Usar token en peticiones

```bash
GET /api/partner/places
Authorization: Bearer 1|abc123def456...
```

---

## 🧩 Modelos de Datos Principales

### Place (Lugar)
Tabla: `places`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `user_id` | foreignId | Usuario creador (Partner/Admin) |
| `category_id` | foreignId | Categoría del lugar |
| `name` | string | Nombre del lugar |
| `slug` | string | URL amigable (único) |
| `short_description` | string | Breve descripción para tarjetas |
| `description` | text | Descripción completa (opcional) |
| `address` | string | Dirección física (opcional) |
| `latitude` | decimal | Coordenada: Latitud |
| `longitude` | decimal | Coordenada: Longitud |
| `is_featured` | boolean | ¿Destacado en home? (Solo Admin) |
| `status` | enum | `pending`, `approved`, `rejected`, `needs_fix` |

### PlaceImage (Imágenes de Lugar)
Tabla: `place_images`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `place_id` | foreignId | Lugar asociado |
| `path` | string | Ruta relativa en `storage/app/public` |
| `filename` | string | Nombre original del archivo |
| `is_primary` | boolean | ¿Es la imagen principal? |
| `order` | integer | Orden de visualización |

---

## 📁 Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php      # Autenticación
│   │       ├── CategoryController.php  # Categorías
│   │       ├── PlaceController.php     # Lugares + imágenes
│   │       ├── AdminController.php     # Dashboard admin
│   │       ├── PartnerController.php   # Dashboard partner
│   │       └── UserController.php      # Dashboard user
│   └── Middleware/
│       └── RoleMiddleware.php          # Verificación de roles
├── Models/
│   ├── User.php
│   ├── Place.php
│   ├── PlaceImage.php
│   ├── Category.php
│   ├── Review.php
│   └── Favorite.php
database/
├── migrations/
│   ├── *_create_places_table.php
│   ├── *_create_place_images_table.php
│   └── ...
└── seeders/
    ├── DatabaseSeeder.php
    └── CategorySeeder.php
routes/
└── api.php                              # Rutas de la API
config/
└── cors.php                             # Configuración CORS
```

---

## 🛠️ Comandos Útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas de la API
php artisan route:list --path=api

# Crear nuevo lugar desde tinker
php artisan tinker
>>> $place = App\Models\Place::create([...])

# Regenerar enlace de storage
php artisan storage:link
```

---

## 📝 Notas Importantes

1. **CORS**: La configuración permite peticiones desde `localhost:3000` y `localhost:5173`. Ajusta en `config/cors.php` según tu frontend.

2. **Imágenes**: Se almacenan en `storage/app/public/places/{place_id}/` y se sirven vía `/storage/places/{place_id}/{filename}`

3. **Estados de lugares**:
   - `pending`: Pendiente de aprobación
   - `approved`: Aprobado y visible
   - `rejected`: Rechazado
   - `needs_fix`: Necesita correcciones

4. **Roles**:
   - `user`: Usuario normal (puede ver lugares, favoritos, reviews)
   - `partner`: Socio (puede crear/editar sus lugares)
   - `admin`: Administrador (acceso total)

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.
