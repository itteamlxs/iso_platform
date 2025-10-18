<?php
/**
 * Test de configuración de uploads
 */

require_once __DIR__ . '/app/config/database.php';

echo "<h2>Diagnóstico de Uploads</h2>";
echo "<pre>";

echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "Existe: " . (file_exists(UPLOAD_PATH) ? 'SÍ' : 'NO') . "\n";
echo "Es directorio: " . (is_dir(UPLOAD_PATH) ? 'SÍ' : 'NO') . "\n";
echo "Escribible: " . (is_writable(UPLOAD_PATH) ? 'SÍ' : 'NO') . "\n";
echo "Permisos: " . substr(sprintf('%o', fileperms(UPLOAD_PATH)), -4) . "\n";

$stat = stat(UPLOAD_PATH);
$owner = posix_getpwuid($stat['uid']);
echo "Owner: " . $owner['name'] . "\n";
echo "Group: " . posix_getgrgid($stat['gid'])['name'] . "\n";

echo "UPLOAD_MAX_SIZE: " . (UPLOAD_MAX_SIZE / 1024 / 1024) . " MB\n";
echo "PHP upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "PHP post_max_size: " . ini_get('post_max_size') . "\n";

// Test de escritura
$test_file = UPLOAD_PATH . '/test_' . time() . '.txt';
$write_test = @file_put_contents($test_file, 'test');

if ($write_test !== false) {
    echo "\n✓ TEST DE ESCRITURA: EXITOSO\n";
    unlink($test_file);
} else {
    echo "\n✗ TEST DE ESCRITURA: FALLIDO\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
}

echo "</pre>";