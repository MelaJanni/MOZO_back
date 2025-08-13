#!/usr/bin/env node

/**
 * Script para desplegar reglas de Firestore usando la Firebase CLI
 * Ejecutar: node scripts/deploy-firestore-rules.js
 */

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const projectId = 'mozoqr-7d32c';
const rulesFile = path.join(__dirname, '../storage/app/firebase/firestore.rules');

console.log('🔥 Desplegando reglas de Firestore para MOZO QR\n');

// Verificar que existe el archivo de reglas
if (!fs.existsSync(rulesFile)) {
    console.error('❌ No se encontró el archivo de reglas:', rulesFile);
    process.exit(1);
}

console.log('✅ Archivo de reglas encontrado:', rulesFile);

// Mostrar las reglas que se van a desplegar
console.log('\n📋 Reglas a desplegar:');
console.log('='.repeat(50));
const rules = fs.readFileSync(rulesFile, 'utf8');
console.log(rules);
console.log('='.repeat(50));

// Crear archivo firebase.json temporal si no existe
const firebaseJsonPath = path.join(__dirname, '../firebase.json');
if (!fs.existsSync(firebaseJsonPath)) {
    const firebaseConfig = {
        firestore: {
            rules: "storage/app/firebase/firestore.rules"
        }
    };
    
    fs.writeFileSync(firebaseJsonPath, JSON.stringify(firebaseConfig, null, 2));
    console.log('✅ Created firebase.json configuration');
}

try {
    // Verificar que Firebase CLI está instalado
    console.log('\n🔍 Verificando Firebase CLI...');
    execSync('firebase --version', { stdio: 'inherit' });
    
    // Hacer login (si es necesario)
    console.log('\n🔑 Verificando autenticación...');
    try {
        execSync('firebase projects:list', { stdio: 'pipe' });
        console.log('✅ Ya autenticado con Firebase CLI');
    } catch (error) {
        console.log('⚠️  Necesitas autenticarte. Ejecutando firebase login...');
        execSync('firebase login', { stdio: 'inherit' });
    }
    
    // Desplegar las reglas
    console.log(`\n🚀 Desplegando reglas de Firestore al proyecto ${projectId}...`);
    execSync(`firebase deploy --only firestore:rules --project ${projectId}`, { 
        stdio: 'inherit',
        cwd: path.join(__dirname, '..')
    });
    
    console.log('\n🎉 ¡Reglas de Firestore desplegadas exitosamente!');
    
    // Verificar el deployment
    console.log('\n🧪 Verificando deployment...');
    console.log(`📱 Puedes verificar las reglas en:`);
    console.log(`   https://console.firebase.google.com/project/${projectId}/firestore/rules`);
    
    // Test de conexión básico
    console.log('\n🔗 URLs de prueba:');
    console.log('   • Configuration: https://mozoqr.com/api/firebase/config');
    console.log('   • Status: https://mozoqr.com/api/firebase/status');
    console.log('   • QR Test: https://mozoqr.com/QR/mcdonalds/JoA4vw');
    
} catch (error) {
    console.error('\n❌ Error durante el deployment:', error.message);
    
    if (error.message.includes('command not found: firebase')) {
        console.log('\n💡 Firebase CLI no instalado. Para instalarlo:');
        console.log('   npm install -g firebase-tools');
        console.log('   # o');
        console.log('   curl -sL https://firebase.tools | bash');
    }
    
    process.exit(1);
}

console.log('\n✅ Script de deployment completado!');