# 🔥 Desplegar Reglas de Firestore para MOZO QR

## 🎯 Problema a Resolver
Los errores 400 de Firestore ocurren porque las reglas de seguridad no permiten acceso público a los QR codes para notificaciones en tiempo real.

## 🚀 Solución: Desplegar Nuevas Reglas

### Opción 1: Usar Firebase CLI (Recomendado)

```bash
# 1. Instalar Firebase CLI (si no está instalado)
npm install -g firebase-tools

# 2. Autenticarse
firebase login

# 3. Desplegar reglas desde el directorio del proyecto
cd /path/to/MOZO_back
firebase deploy --only firestore:rules --project mozoqr-7d32c
```

### Opción 2: Usar el Script Automatizado

```bash
# Ejecutar el script de deployment
node scripts/deploy-firestore-rules.js
```

### Opción 3: Manual desde Firebase Console

1. Ve a: https://console.firebase.google.com/project/mozoqr-7d32c/firestore/rules
2. Copia y pega el contenido de `storage/app/firebase/firestore.rules`
3. Click en "Publish"

---

## 📋 Reglas Actuales (Resumen)

### ✅ QR Codes (Acceso Público)
- **Lectura**: ✅ Permitida para todos (necesario para tiempo real)
- **Escritura**: ❌ Solo backend autenticado

### 🔒 Mozos y Administración  
- **Lectura/Escritura**: ❌ Solo usuarios autenticados

### 🧪 Testing
- **Colección `/testing/`**: ✅ Acceso completo (para debugging)

---

## 🔍 Verificación Post-Deployment

### 1. Verificar en Console
- URL: https://console.firebase.google.com/project/mozoqr-7d32c/firestore/rules
- Las reglas deben mostrar acceso público para `tables/{tableId}/waiter_calls`

### 2. Test de Endpoints
```bash
# Configuración
curl https://mozoqr.com/api/firebase/status

# Config del frontend  
curl https://mozoqr.com/api/firebase/config
```

### 3. Test de QR en Navegador
- Abre: https://mozoqr.com/QR/mcdonalds/JoA4vw
- Abre Developer Tools → Console
- Busca: `🎉 Firebase ready!` y `✅ Autenticación anónima exitosa`
- NO debe haber errores 400 de Firestore

---

## 🐛 Troubleshooting

### Errores Comunes:

**"Firebase CLI not found"**
```bash
npm install -g firebase-tools
```

**"Not authenticated"**
```bash
firebase login
firebase use mozoqr-7d32c
```

**"Permission denied"**
- Verificar que tienes acceso de editor/owner al proyecto Firebase
- Contactar admin del proyecto

**"Rules syntax error"**
- Verificar sintaxis en `storage/app/firebase/firestore.rules`
- Validar en Firebase Console antes de desplegar

---

## ⚡ Resultado Esperado

Después del deployment:
- ❌ **Sin más errores 400** en Firestore Listen API
- ✅ **Notificaciones en tiempo real** funcionando
- ✅ **Acceso público controlado** desde QR codes
- ✅ **Seguridad mantenida** para datos sensibles

---

## 📞 Ayuda

Si necesitas ayuda:
1. Verifica logs en Developer Console
2. Ejecuta `php artisan firebase:setup --test` 
3. Revisa el endpoint `/api/firebase/status`

**¡Una vez desplegado, los errores 400 desaparecerán completamente!** 🎉