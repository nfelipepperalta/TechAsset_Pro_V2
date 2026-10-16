#!/bin/bash
# ============================================================
#  TechAsset Pro — Script de instalación en Ubuntu Server
#  Ejecutar como root o con sudo
#  uso: sudo bash install.sh
# ============================================================

set -e  # Detener si algún comando falla

# ── Colores ───────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

ok()   { echo -e "${GREEN}✅ $1${NC}"; }
info() { echo -e "${BLUE}ℹ  $1${NC}"; }
warn() { echo -e "${YELLOW}⚠  $1${NC}"; }
err()  { echo -e "${RED}❌ $1${NC}"; exit 1; }

echo ""
echo "============================================"
echo "   TechAsset Pro — Instalación en Ubuntu"
echo "============================================"
echo ""

# ── Variables — ajustar antes de ejecutar ─────────────────────
PROJECT_DIR="/var/www/techasset-pro"
APACHE_CONF="/etc/apache2/sites-available/techasset.conf"
PHP_VERSION="8.3"

# ── 1. Actualizar sistema ─────────────────────────────────────
info "Actualizando sistema..."
apt-get update -qq && apt-get upgrade -y -qq
ok "Sistema actualizado"

# ── 2. Instalar Apache ────────────────────────────────────────
info "Instalando Apache2..."
apt-get install -y apache2
a2enmod rewrite
ok "Apache2 instalado"

# ── 3. Instalar PHP 8.3 + extensiones ─────────────────────────
info "Instalando PHP ${PHP_VERSION} y extensiones..."
apt-get install -y \
    php${PHP_VERSION} \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    libapache2-mod-php${PHP_VERSION}

# Extensión SQL Server para PHP (driver Microsoft)
info "Instalando driver PHP para SQL Server..."
apt-get install -y curl apt-transport-https
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
curl -sSL https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list \
    > /etc/apt/sources.list.d/mssql-release.list
apt-get update -qq
ACCEPT_EULA=Y apt-get install -y msodbcsql18 unixodbc-dev
pecl install sqlsrv pdo_sqlsrv
echo "extension=sqlsrv.so"     >> /etc/php/${PHP_VERSION}/apache2/php.ini
echo "extension=pdo_sqlsrv.so" >> /etc/php/${PHP_VERSION}/apache2/php.ini
ok "PHP ${PHP_VERSION} + driver SQL Server instalados"

# ── 4. Instalar Composer ──────────────────────────────────────
info "Instalando Composer..."
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    err "Checksum de Composer inválido"
fi
php composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
rm composer-setup.php
ok "Composer instalado"

# ── 5. Crear estructura del proyecto ──────────────────────────
info "Creando directorio del proyecto en ${PROJECT_DIR}..."
mkdir -p ${PROJECT_DIR}
ok "Directorio creado: ${PROJECT_DIR}"

# ── 6. Copiar archivos del proyecto ───────────────────────────
warn "Copia manual requerida:"
warn "Copia el contenido de techasset-pro/ a ${PROJECT_DIR}/"
warn "Luego continúa ejecutando los siguientes pasos manualmente:"
echo ""
echo "  cd ${PROJECT_DIR}"
echo "  composer install --no-dev --optimize-autoloader"
echo "  cp .env.example .env"
echo "  nano .env   # Editar credenciales Azure SQL"
echo ""

# ── 7. Configurar Apache ──────────────────────────────────────
info "Configurando Virtual Host de Apache..."
cat > ${APACHE_CONF} << 'EOF'
<VirtualHost *:80>
    ServerName techasset.empresa.com
    DocumentRoot /var/www/techasset-pro/public

    <Directory /var/www/techasset-pro/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <Directory /var/www/techasset-pro/core>
        Require all denied
    </Directory>
    <Directory /var/www/techasset-pro/config>
        Require all denied
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/techasset_error.log
    CustomLog ${APACHE_LOG_DIR}/techasset_access.log combined
</VirtualHost>
EOF

a2ensite techasset.conf
a2dissite 000-default.conf 2>/dev/null || true
ok "Virtual Host configurado"

# ── 8. Permisos ───────────────────────────────────────────────
info "Configurando permisos..."
chown -R www-data:www-data ${PROJECT_DIR}
chmod -R 755 ${PROJECT_DIR}
chmod -R 775 ${PROJECT_DIR}/public
ok "Permisos configurados"

# ── 9. Reiniciar Apache ───────────────────────────────────────
info "Reiniciando Apache..."
systemctl restart apache2
systemctl enable apache2
ok "Apache reiniciado y habilitado"

# ── 10. Verificar estado ──────────────────────────────────────
echo ""
echo "============================================"
echo "   Instalación completada"
echo "============================================"
echo ""
php --version | head -1
apache2 -v | head -1
composer --version
echo ""
ok "Stack instalado correctamente"
echo ""
warn "PASOS MANUALES PENDIENTES:"
echo "  1. Copiar proyecto a:     ${PROJECT_DIR}/"
echo "  2. Ejecutar:              cd ${PROJECT_DIR} && composer install"
echo "  3. Configurar entorno:    cp .env.example .env && nano .env"
echo "  4. Ejecutar SQL en Azure: techasset_pro_sqlserver.sql"
echo "  5. Cargar datos ejemplo:  techasset_pro_datos_ejemplo.sql"
echo "  6. Acceder en:            http://$(hostname -I | awk '{print $1}')"
echo ""
