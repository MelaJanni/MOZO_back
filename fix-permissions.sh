#!/bin/bash

# ============================================================================
# Script de saneo de permisos para Laravel adaptado a Plesk / FPM
# Objetivo: NO romper ownership global, sólo normalizar storage/ y bootstrap/cache.
# Uso: sudo ./fix-permissions.sh [/ruta/proyecto] [usuario_suscriptor]
# ============================================================================
set -euo pipefail

PROJECT_PATH="${1:-/var/www/vhosts/mozoqr.com/httpdocs}"

# Detectar usuario del suscriptor si no se pasa segundo parámetro
SUSCRIPTOR_USER="${2:-}"
if [ -z "$SUSCRIPTOR_USER" ]; then
	# Heurística: dueño del archivo .env si existe, si no, dueño del docroot
	if [ -f "$PROJECT_PATH/.env" ]; then
		SUSCRIPTOR_USER=$(stat -c '%U' "$PROJECT_PATH/.env") || true
	else
		SUSCRIPTOR_USER=$(stat -c '%U' "$PROJECT_PATH") || true
	fi
fi

# Detectar posibles usuarios de php-fpm (puede haber varios; daremos ACL a todos)
mapfile -t FPM_USERS < <(ps -eo user,comm 2>/dev/null | grep -E 'php-fpm|php8|php7' | awk '{print $1}' | sort -u | grep -v root || true)

# Fallback típico
if [ ${#FPM_USERS[@]} -eq 0 ]; then
	FPM_USERS=(www-data)
fi

echo "==> Proyecto: $PROJECT_PATH"
echo "==> Usuario suscriptor: $SUSCRIPTOR_USER"
echo "==> Usuarios FPM detectados: ${FPM_USERS[*]}"

if [ ! -d "$PROJECT_PATH" ]; then
	echo "[ERROR] Ruta inválida: $PROJECT_PATH" >&2; exit 1
fi

echo "==> Creando estructura requerida"
mkdir -p "$PROJECT_PATH/bootstrap/cache" \
				 "$PROJECT_PATH/storage/framework/cache" \
				 "$PROJECT_PATH/storage/framework/sessions" \
				 "$PROJECT_PATH/storage/framework/views" \
				 "$PROJECT_PATH/storage/logs"

echo "==> Normalizando ownership SOLO en directorios escribibles"
chown -R "$SUSCRIPTOR_USER":psacln "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache"

echo "==> Aplicando permisos (dirs 2775, files 664)"
find "$PROJECT_PATH/storage" -type d -exec chmod 2775 {} +
find "$PROJECT_PATH/bootstrap/cache" -type d -exec chmod 2775 {} +
find "$PROJECT_PATH/storage" -type f -exec chmod 664 {} +
find "$PROJECT_PATH/bootstrap/cache" -type f -exec chmod 664 {} +

echo "==> Setgid para herencia de grupo"
chmod g+s "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache" || true
find "$PROJECT_PATH/storage" -type d -exec chmod g+s {} + 2>/dev/null || true
find "$PROJECT_PATH/bootstrap/cache" -type d -exec chmod g+s {} + 2>/dev/null || true

if command -v setfacl >/dev/null 2>&1; then
	echo "==> Aplicando ACL rwX para usuarios FPM detectados"
	for U in "${FPM_USERS[@]}"; do
		setfacl -R -m u:"$U":rwX "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache" || true
		setfacl -dR -m u:"$U":rwX "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache" || true
	done
	# Asegurar ACL de lectura al suscriptor (ya es owner, pero por consistencia en defaults)
	setfacl -dR -m u:"$SUSCRIPTOR_USER":rwX "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache" || true
else
	echo "[AVISO] setfacl no disponible. Considera instalarlo para ACLs finas."
fi

echo "==> Limpiando caches Laravel (como $SUSCRIPTOR_USER)"
cd "$PROJECT_PATH"
sudo -u "$SUSCRIPTOR_USER" php artisan view:clear  || true
sudo -u "$SUSCRIPTOR_USER" php artisan cache:clear || true
sudo -u "$SUSCRIPTOR_USER" php artisan config:clear || true
sudo -u "$SUSCRIPTOR_USER" php artisan route:clear  || true
sudo -u "$SUSCRIPTOR_USER" php artisan optimize:clear || true

echo "==> (Opcional) Regenerando config / route cache"
sudo -u "$SUSCRIPTOR_USER" php artisan config:cache || true
sudo -u "$SUSCRIPTOR_USER" php artisan route:cache  || true

echo "==> Prueba de escritura en storage/framework/views"
TMP_FILE="$PROJECT_PATH/storage/framework/views/.perm_test_$$.txt"
if sudo -u "$SUSCRIPTOR_USER" bash -c "echo test > '$TMP_FILE'"; then
	rm -f "$TMP_FILE"
	echo "[OK] Escritura como $SUSCRIPTOR_USER"
else
	echo "[ERROR] El usuario $SUSCRIPTOR_USER no pudo escribir en storage/framework/views" >&2
fi

for U in "${FPM_USERS[@]}"; do
	if sudo -u "$U" bash -c "echo test > '$TMP_FILE'" 2>/dev/null; then
		rm -f "$TMP_FILE"
		echo "[OK] Escritura como $U"
	else
		echo "[ERROR] El usuario FPM $U no pudo escribir en storage/framework/views" >&2
	fi
done

echo "==> Resumen rápido"
ls -ld "$PROJECT_PATH/storage/framework/views" || true
getfacl "$PROJECT_PATH/storage/framework/views" 2>/dev/null | sed -n '1,15p' || true

echo "==> Permisos saneados"