# 🚀 Auto Deploy Setup para MOZO

He configurado un sistema de auto-deploy usando GitHub Actions. Cada vez que hagas `git push` a la rama `main`, se desplegará automáticamente en producción.

## ⚙️ Configuración requerida

### 1. GitHub Secrets
Ve a tu repositorio en GitHub → Settings → Secrets and Variables → Actions y agrega estos secrets:

- `HOST`: La IP de tu servidor (ej: `137.184.101.68`)
- `USERNAME`: Usuario SSH (generalmente `root`)
- `SSH_KEY`: Tu clave SSH privada

### 2. Clave SSH
Si no tienes clave SSH configurada, en el servidor ejecuta:

```bash
# Generar nueva clave SSH
ssh-keygen -t rsa -b 4096 -C "github-deploy"

# Copiar clave pública al authorized_keys
cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys

# Mostrar clave privada (para copiar al GitHub Secret SSH_KEY)
cat ~/.ssh/id_rsa
```

## 🔄 Cómo funciona

1. Haces `git push` al repositorio
2. GitHub Actions se ejecuta automáticamente
3. Se conecta al servidor via SSH
4. Ejecuta `git pull`
5. Arregla permisos automáticamente
6. Ejecuta migraciones
7. Limpia cache
8. Reinicia PHP-FPM
9. ¡Listo! 🎉

## 🧪 Testing

Una vez configurado, haz un push de prueba:

```bash
git add .
git commit -m "Test auto deploy"
git push
```

Ve a GitHub → Actions para ver el progreso del deploy.

## 📋 Log de deploy

Después de cada push podrás ver en GitHub Actions:
- Si el deploy fue exitoso
- Logs detallados de cada paso
- Tiempo que tomó el proceso

## 🛡️ Beneficios

- ✅ **Automático**: No necesitas conectarte al servidor
- ✅ **Consistente**: Mismos pasos cada vez
- ✅ **Seguro**: Permisos se arreglan automáticamente
- ✅ **Rápido**: Deploy en ~30 segundos
- ✅ **Visible**: Historial en GitHub Actions

## 🚨 Fallback manual

Si necesitas hacer deploy manual:

```bash
cd /var/www/vhosts/mozoqr.com/httpdocs
sudo ./deploy.sh
```