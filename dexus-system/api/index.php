<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configuração da API
require_once __DIR__ . '/config.php';

// Definir cabeçalhos para permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Obter caminho da requisição
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/dexus-system/api'; // Ajuste se necessário
$path = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
$path_parts = explode('/', trim($path, '/'));

// Identificar o endpoint
$endpoint = isset($path_parts[0]) ? $path_parts[0] : '';
$resource = isset($path_parts[1]) ? $path_parts[1] : '';
$id = isset($path_parts[2]) ? $path_parts[2] : null;

// Obter método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Obter parâmetros da requisição
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

// Log para depuração
error_log("API Request: $method $endpoint/$resource/$id");
error_log("Params: " . json_encode($params));

// Rotear a requisição
try {
    switch ($endpoint) {
        case 'dashboard':
            require_once __DIR__ . '/handlers/dashboard.php';
            $response = processDashboardRequest($method, $resource, $id, $params);
            break;
            
        case 'clientes':
            require_once __DIR__ . '/handlers/clientes.php';
            $response = processClientesRequest($method, $resource, $id, $params);
            break;
            
        case 'servicos':
            require_once __DIR__ . '/handlers/servicos.php';
            $response = processServicosRequest($method, $resource, $id, $params);
            break;
            
        case 'modalidades':
            require_once __DIR__ . '/handlers/modalidades.php';
            $response = processModalidadesRequest($method, $resource, $id, $params);
            break;
            
        case 'consultores':
            require_once __DIR__ . '/handlers/consultores.php';
            $response = processConsultoresRequest($method, $resource, $id, $params);
            break;
            
        case 'os':
            require_once __DIR__ . '/handlers/os.php';
            $response = processOSRequest($method, $resource, $id, $params);
            break;
            
        case 'relacao':
            require_once __DIR__ . '/handlers/relatorio.php';
            $response = processRelacaoRequest($method, $resource, $id, $params);
            break;
            
        default:
            // Endpoint não encontrado
            http_response_code(404);
            $response = ['success' => false, 'message' => 'Endpoint não encontrado'];
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    error_log("API Error: " . $e->getMessage());
}

// Retornar resposta
echo json_encode($response);