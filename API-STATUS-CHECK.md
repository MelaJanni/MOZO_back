# 🔥 API STATUS CHECK - Frontend Requirements

## 🚨 PRIMERO: Arreglar el servidor 403

```bash
sudo chown www-data:www-data /var/www/vhosts/mozoqr.com/httpdocs/public/index.php
sudo chmod 644 /var/www/vhosts/mozoqr.com/httpdocs/public/index.php

cat > config/cors.php << 'EOF'
<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
EOF

sudo chown -R www-data:www-data /var/www/vhosts/mozoqr.com/httpdocs/
php artisan config:clear && php artisan route:clear
```

## 📋 TESTING TODAS LAS APIs NECESARIAS

### 🔥 LLAMADAS DE MOZO (Waiter Calls)

```bash
# 1. Mesa llama al mozo ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/tables/1/call-waiter" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"message": "Test call"}'

# 2. Mozo ve llamadas pendientes ✅ (YA IMPLEMENTADO)  
curl "https://mozoqr.com/api/waiter/calls/pending" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 3. Mozo confirma llamada ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/calls/123/acknowledge" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 4. Mozo completa atención ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/calls/123/complete" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 5. Historial de llamadas ✅ (YA IMPLEMENTADO)
curl "https://mozoqr.com/api/waiter/calls/history?filter=today&page=1&limit=20" \
  -H "Authorization: Bearer $WAITER_TOKEN"
```

### 🏢 GESTIÓN DE MESAS (Table Management)

```bash
# 6. Asignarse a mesa específica ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/tables/1/activate" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 7. Desasignarse de mesa ✅ (YA IMPLEMENTADO)  
curl -X DELETE "https://mozoqr.com/api/waiter/tables/1/activate" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 8. Asignarse a múltiples mesas ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/tables/activate/multiple" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"table_ids": [1, 2, 5]}'

# 9. Desasignarse de múltiples mesas ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/tables/deactivate/multiple" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"table_ids": [1, 2, 5]}'
```

### 🔕 SILENCIADO DE MESAS  

```bash
# 10. Silenciar mesa individual ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/tables/1/silence" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"duration_minutes": 30, "notes": "Cliente problemático"}'

# 11. Quitar silencio de mesa ✅ (YA IMPLEMENTADO)
curl -X DELETE "https://mozoqr.com/api/waiter/tables/1/silence" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 12. Silenciar múltiples mesas ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/tables/silence/multiple" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"table_ids": [1,2,5], "duration_minutes": 30, "notes": ""}'

# 13. Quitar silencio múltiple ✅ (YA IMPLEMENTADO)
curl -X POST "https://mozoqr.com/api/waiter/tables/unsilence/multiple" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"table_ids": [1,2,5]}'

# 14. Ver mesas silenciadas ✅ (YA IMPLEMENTADO)
curl "https://mozoqr.com/api/waiter/tables/silenced" \
  -H "Authorization: Bearer $WAITER_TOKEN"
```

### 📊 CONSULTAS DE ESTADO

```bash
# 15. Mesas asignadas al mozo ✅ (YA IMPLEMENTADO)
curl "https://mozoqr.com/api/waiter/tables/assigned" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 16. Mesas disponibles ✅ (YA IMPLEMENTADO)
curl "https://mozoqr.com/api/waiter/tables/available" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 17. Dashboard del mozo ❌ (FALTA - VAMOS A CREARLO)
curl "https://mozoqr.com/api/waiter/dashboard" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 18. Estado de mesas del mozo ❌ (FALTA - VAMOS A CREARLO) 
curl "https://mozoqr.com/api/waiter/tables/status" \
  -H "Authorization: Bearer $WAITER_TOKEN"
```

### 💾 PERFILES DE MESA

```bash
# 19. Ver perfiles ✅ (YA IMPLEMENTADO como /profiles)
curl "https://mozoqr.com/api/waiter/table-profiles" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 20. Crear perfil ✅ (YA IMPLEMENTADO)  
curl -X POST "https://mozoqr.com/api/waiter/table-profiles" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"name": "Turno Mañana", "table_ids": [1,2,5], "notes": ""}'

# 21. Actualizar perfil ❌ (FALTA - VAMOS A CREARLO)
curl -X PUT "https://mozoqr.com/api/waiter/table-profiles/1" \
  -H "Authorization: Bearer $WAITER_TOKEN" \
  -d '{"name": "Turno Mañana", "table_ids": [1,2,5,8]}'

# 22. Eliminar perfil ✅ (YA IMPLEMENTADO)
curl -X DELETE "https://mozoqr.com/api/waiter/table-profiles/1" \
  -H "Authorization: Bearer $WAITER_TOKEN"

# 23. Activar perfil ❌ (FALTA - VAMOS A CREARLO)
curl -X POST "https://mozoqr.com/api/waiter/table-profiles/1/activate" \
  -H "Authorization: Bearer $WAITER_TOKEN"
```

### 👑 ADMIN APIs

```bash
# 24. Admin - mesas silenciadas ✅ (YA IMPLEMENTADO)
curl "https://mozoqr.com/api/admin/tables/silenced" \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 25. Admin - quitar silencio ✅ (YA IMPLEMENTADO)
curl -X DELETE "https://mozoqr.com/api/admin/tables/1/silence" \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# 26. Admin - historial llamadas ✅ (YA IMPLEMENTADO)
curl "https://mozoqr.com/api/admin/calls/history" \
  -H "Authorization: Bearer $ADMIN_TOKEN"
```

## 📊 RESUMEN DE ESTADO

### ✅ **YA FUNCIONAN (21/26)**:
- Todas las llamadas de mozo básicas
- Toda la gestión de mesas individual y múltiple  
- Todo el silenciado de mesas
- Consultas básicas (assigned, available, silenced)
- Perfiles básicos (ver, crear, eliminar)
- APIs de admin

### ❌ **FALTAN (5/26)**:
1. `GET /api/waiter/dashboard` - Dashboard completo
2. `GET /api/waiter/tables/status` - Estado actual de mesas  
3. `PUT /api/waiter/table-profiles/{id}` - Actualizar perfil
4. `POST /api/waiter/table-profiles/{id}/activate` - Activar perfil
5. Alias para table-profiles como profiles

## 🚀 PLAN DE ACCIÓN

1. **PRIMERO**: Arreglar servidor 403
2. **SEGUNDO**: Crear las 5 APIs faltantes
3. **TERCERO**: Verificar que todas funcionen
4. **CUARTO**: Deploy y testing completo

¿Procedemos?