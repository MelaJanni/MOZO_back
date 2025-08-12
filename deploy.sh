#!/bin/bash

# Script completo de deploy
# Ejecutar como: sudo ./deploy.sh

echo "=== DEPLOY MOZO APP ==="

cd /var/www/vhosts/mozoqr.com/httpdocs

echo "1. Pulling latest changes..."
git pull origin main

echo "2. Running migrations..."
php artisan migrate --force

echo "3. Fixing permissions..."
./fix-permissions.sh

echo "4. Testing endpoints..."
echo "Test QR system:"
curl -s https://mozoqr.com/test-qr | jq .status

echo "Setup test data:"
curl -s https://mozoqr.com/setup-test-data | jq .status

echo "=== DEPLOY COMPLETADO ==="
echo "URL QR: https://mozoqr.com/QR/mcdonalds/JoA4vw"