# TechAsset Pro
**Sistema de Inventario de Activos Tecnológicos Empresariales**

---

## Requisitos

| Componente | Versión mínima |
|---|---|
| Ubuntu Server | 22.04 LTS o 24.04 LTS |
| PHP | 8.1+ (probado en 8.3) |
| Apache | 2.4+ |
| Azure SQL Database | — |
| Composer | 2.x |

---

## Instalación en Ubuntu Server

### Paso 1 — Instalar el stack base

```bash
sudo apt-get update && sudo apt-get upgrade -y

# Apache
sudo apt-get install -y apache2
sudo a2enmod rewrite

# PHP 8.3 y extensiones
sudo apt-get install -y \
    php8.3 php8.3-cli php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip \
    libapache2-mod-php8.3

# Driver SQL Server para PHP
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -
curl -sSL https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list \
    | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt-get update
sudo ACCEPT_EULA=Y apt-get install -y msodbcsql18 unixodbc-dev
sudo pecl install sqlsrv pdo_sqlsrv
echo "extension=sqlsrv.so"     | sudo tee -a /etc/php/8.3/apache2/php.ini
echo "extension=pdo_sqlsrv.so" | sudo tee -a /etc/php/8.3/apache2/php.ini

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- \
    --install-dir=/usr/local/bin --filename=composer
```

### Paso 2 — Subir el proyecto

```bash
# Crear directorio
sudo mkdir -p /var/www/techasset-pro

# Copiar archivos (desde tu máquina local)
scp -r ./techasset-pro/* usuario@servidor:/var/www/techasset-pro/

# O clonar desde Git
cd /var/www
sudo git clone https://github.com/tu-org/techasset-pro.git
```

### Paso 3 — Instalar dependencias PHP

```bash
cd /var/www/techasset-pro
sudo composer install --no-dev --optimize-autoloader
```

### Paso 4 — Configurar variables de entorno

```bash
cd /var/www/techasset-pro
sudo cp .env.example .env
sudo nano .env
```

Editar los valores en `.env`:

```env
APP_URL=http://techasset.empresa.com
APP_ENV=production
APP_DEBUG=false

DB_HOST=tu-servidor.database.windows.net
DB_PORT=1433
DB_NAME=TECHASSET
DB_USER=techasset_user
DB_PASS=TuPasswordSeguro123!
DB_ENCRYPT=true
DB_TRUST_CERT=false
```

### Paso 5 — Configurar Apache

```bash
# Copiar virtual host
sudo cp techasset.conf /etc/apache2/sites-available/techasset.conf

# Editar ServerName si es necesario
sudo nano /etc/apache2/sites-available/techasset.conf

# Activar el sitio
sudo a2ensite techasset.conf
sudo a2dissite 000-default.conf

# Reiniciar Apache
sudo systemctl restart apache2
sudo systemctl enable apache2
```

### Paso 6 — Permisos

```bash
sudo chown -R www-data:www-data /var/www/techasset-pro
sudo chmod -R 755 /var/www/techasset-pro
sudo chmod -R 775 /var/www/techasset-pro/public
```

### Paso 7 — Ejecutar scripts SQL en Azure

Ejecutar en orden desde Azure Data Studio o SQL Server Management Studio:

```
1. techasset_pro_sqlserver.sql    ← Crea tablas y vistas
2. techasset_pro_datos_ejemplo.sql ← Carga datos de prueba
```

### Paso 8 — Verificar instalación

```bash
# Verificar Apache
sudo systemctl status apache2

# Verificar PHP y extensiones
php -m | grep -E "sqlsrv|pdo_sqlsrv|mbstring|curl"

# Ver logs si hay errores
sudo tail -f /var/log/apache2/techasset_error.log
```

Acceder en el navegador: `http://IP-DEL-SERVIDOR`

---

## Credenciales de prueba

Después de ejecutar `techasset_pro_datos_ejemplo.sql`:

| Email | Contraseña | Rol |
|---|---|---|
| carlos.mendoza@empresa.com | Admin2025! | Admin |
| laura.jimenez@empresa.com | Admin2025! | TI |
| patricia.gomez@empresa.com | Admin2025! | Auditor |
| felipe.torres@empresa.com | Admin2025! | Usuario |

> ⚠️ Cambiar las contraseñas inmediatamente en producción.

---

## Estructura del proyecto

```
techasset-pro/
├── config/          Configuración (DB, rutas, app)
├── core/            Infraestructura (Database, Router, Auth, Session)
├── helpers/         Utilidades (Validator, DateHelper, Sanitizer)
├── repositories/    Acceso a datos — todo el SQL aquí
├── services/        Lógica de negocio
├── controllers/     Orquestación de peticiones
├── views/           Plantillas HTML/PHP
└── public/          Entry point — único directorio expuesto
```

---

## Comandos útiles

```bash
# Ver errores en tiempo real
sudo tail -f /var/log/apache2/techasset_error.log

# Reiniciar Apache tras cambios
sudo systemctl restart apache2

# Verificar sintaxis PHP
php -l /var/www/techasset-pro/core/Database.php

# Limpiar caché de Composer
cd /var/www/techasset-pro && composer dump-autoload
```

---

**TechAsset Pro** · Feria de Tecnología 2025 · Stack: PHP 8.3 + Apache + Azure SQL
