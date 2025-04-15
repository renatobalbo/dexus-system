<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir o arquivo de configuração
require_once 'config/database.php';

// Incluir handlers
require_once 'api/handlers/dashboard.php';

// Testar uma função simples
$result = processDashboardRequest('GET', 'stats', null, []);

// Exibir o resultado
header('Content-Type: application/json');
echo json_encode($result);