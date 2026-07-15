#!/bin/bash

# ── CRM Macmillan SI — Script de deploy ──
# Uso: bash deploy.sh
# Coloca este archivo en la raíz del proyecto en el servidor.

set -e  # Detener si cualquier comando falla

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
RESET='\033[0m'

step() { echo -e "\n${CYAN}${BOLD}▶ $1${RESET}"; }
ok()   { echo -e "${GREEN}✓ $1${RESET}"; }
warn() { echo -e "${YELLOW}⚠ $1${RESET}"; }

echo -e "${BOLD}"
echo "╔══════════════════════════════════════╗"
echo "║    CRM Macmillan SI — Deploy         ║"
echo "╚══════════════════════════════════════╝"
echo -e "${RESET}"

# 1. Git pull
step "Descargando últimos cambios..."
git pull origin main
ok "Código actualizado"

# 2. Composer (solo si cambió composer.lock)
if git diff HEAD@{1} --name-only 2>/dev/null | grep -q "composer.lock"; then
  step "Actualizando dependencias PHP..."
  composer install --no-dev --optimize-autoloader --no-interaction
  ok "Dependencias actualizadas"
else
  warn "composer.lock sin cambios — se omite composer install"
fi

# 3. Migraciones
step "Ejecutando migraciones..."
php artisan migrate --force
ok "Migraciones aplicadas"

# 4. Caché
step "Regenerando caché..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "Caché regenerada"

# 5. Permisos
step "Ajustando permisos..."
chmod -R 775 storage bootstrap/cache
ok "Permisos OK"

# 6. Reiniciar PHP-FPM para limpiar opcache
step "Limpiando opcache PHP..."
if systemctl restart php8.3-fpm 2>/dev/null; then
  ok "PHP 8.3 FPM reiniciado"
elif systemctl restart php8.2-fpm 2>/dev/null; then
  ok "PHP 8.2 FPM reiniciado"
elif service php8.3-fpm restart 2>/dev/null; then
  ok "PHP 8.3 FPM reiniciado (service)"
elif service php8.2-fpm restart 2>/dev/null; then
  ok "PHP 8.2 FPM reiniciado (service)"
else
  warn "No se pudo reiniciar PHP-FPM — el opcache puede servir código viejo hasta que expire"
fi

echo -e "\n${GREEN}${BOLD}✅ Deploy completado exitosamente.${RESET}\n"
