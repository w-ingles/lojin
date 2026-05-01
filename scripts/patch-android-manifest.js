/**
 * Patch AndroidManifest.xml para adicionar permissões de câmera
 * necessárias para o @capacitor-community/barcode-scanner funcionar.
 *
 * Uso: node scripts/patch-android-manifest.js
 * Executar APÓS: npx cap add android
 */

const fs   = require('fs');
const path = require('path');

const manifestPath = path.join(__dirname, '../android/app/src/main/AndroidManifest.xml');

if (!fs.existsSync(manifestPath)) {
    console.error('AndroidManifest.xml não encontrado. Execute "npx cap add android" primeiro.');
    process.exit(1);
}

let manifest = fs.readFileSync(manifestPath, 'utf8');

const permissions = [
    '<uses-permission android:name="android.permission.CAMERA" />',
    '<uses-feature android:name="android.hardware.camera" android:required="false" />',
    '<uses-feature android:name="android.hardware.camera.autofocus" android:required="false" />',
];

let alterado = false;

for (const perm of permissions) {
    const tag = perm.match(/android:name="([^"]+)"/)[1];
    if (!manifest.includes(tag)) {
        manifest = manifest.replace(
            '<application',
            `${perm}\n    <application`
        );
        alterado = true;
        console.log(`✓ Adicionado: ${tag}`);
    } else {
        console.log(`— Já existe: ${tag}`);
    }
}

if (alterado) {
    fs.writeFileSync(manifestPath, manifest, 'utf8');
    console.log('\nAndroidManifest.xml atualizado com sucesso.');
} else {
    console.log('\nNenhuma alteração necessária.');
}
