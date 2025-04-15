<?php
// Definir constantes
define('API_VERSION', '1.0.0');
define('ROOT_DIR', dirname(__DIR__));
define('API_DIR', __DIR__);

// Incluir configuração do banco de dados
require_once ROOT_DIR . '/config/database.php';

// Função para obter parâmetros de requisições
function getRequestParams() {
    $params = [];
    
    // Parâmetros GET
    if (!empty($_GET)) {
        $params = array_merge($params, $_GET);
    }
    
    // Parâmetros POST, PUT
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $json_params = json_decode($input, true);
        if ($json_params) {
            $params = array_merge($params, $json_params);
        }
    }
    
    // Parâmetros form-data
    if (!empty($_POST)) {
        $params = array_merge($params, $_POST);
    }
    
    return $params;
}