<?php
/**
 * Test de Conexión a Base de Datos
 * Ejecutar: php test-conexion.php
 */

echo "\n=== TEST DE CONEXIÓN - ISO 27001 Platform ===\n\n";

// 1. Verificar archivo .env
echo "[1/5] Verificando archivo .env... ";
if (!file_exists(__DIR__ . '/.env')) {
    echo "✗ FALLO\n";
    echo "ERROR: Archivo .env no encontrado\n";
    exit(1);
}
echo "✓ OK\n";

// 2. Cargar configuración
echo "[2/5] Cargando configuración... ";
require_once __DIR__ . '/app/config/database.php';
echo "✓ OK\n";

// 3. Verificar constantes
echo "[3/5] Verificando constantes... ";
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'];
foreach ($required as $const) {
    if (!defined($const)) {
        echo "✗ FALLO\n";
        echo "ERROR: Constante {$const} no definida\n";
        exit(1);
    }
}
echo "✓ OK\n";

// 4. Intentar conexión
echo "[4/5] Conectando a base de datos... ";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ OK\n";
} catch (PDOException $e) {
    echo "✗ FALLO\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Verificar tablas necesarias
echo "[5/5] Verificando estructura de tablas... ";
$tablas_requeridas = [
    'empresas',
    'controles',
    'controles_dominio',
    'soa_entries',
    'gap_items',
    'evidencias',
    'requerimientos_base',
    'empresa_requerimientos'
];

$stmt = $pdo->query("SHOW TABLES");
$tablas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$faltantes = array_diff($tablas_requeridas, $tablas_existentes);

if (count($faltantes) > 0) {
    echo "✗ FALLO\n";
    echo "ERROR: Faltan las siguientes tablas:\n";
    foreach ($faltantes as $tabla) {
        echo "  - {$tabla}\n";
    }
    exit(1);
}
echo "✓ OK\n";

// Resumen
echo "\n=== RESUMEN ===\n";
echo "Base de datos: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . "\n";
echo "Tablas encontradas: " . count($tablas_existentes) . "\n";

// Verificar datos iniciales
$stmt = $pdo->query("SELECT COUNT(*) FROM controles");
$total_controles = $stmt->fetchColumn();
echo "Controles ISO: {$total_controles}/93\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM empresas");
$total_empresas = $stmt->fetchColumn();
echo "Empresas registradas: {$total_empresas}\n";

echo "\n✓ CONEXIÓN EXITOSA - Todo funcionando correctamente\n\n";