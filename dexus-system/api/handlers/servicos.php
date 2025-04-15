<?php
/**
 * API para manipulação de serviços
 * Sistema de Gestão Dexus
 */

// Incluir configuração de banco de dados
require_once __DIR__ . '/../../config/database.php';

/**
 * Busca lista de serviços
 * @param array $params Parâmetros de filtro e paginação
 * @return array Resposta com a lista de serviços
 */
function getServicos($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'servicos' => array(),
        'total' => 0,
        'paginaAtual' => 1,
        'totalPaginas' => 1,
        'inicio' => 0,
        'fim' => 0
    );
    
    // Parâmetros de paginação
    $pagina = isset($params['pagina']) ? (int)$params['pagina'] : 1;
    $porPagina = isset($params['porPagina']) ? (int)$params['porPagina'] : 10;
    if ($pagina < 1) $pagina = 1;
    if ($porPagina < 1) $porPagina = 10;
    $inicio = ($pagina - 1) * $porPagina;
    
    // Contruir consulta base
    $sqlBase = "FROM CADSER WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['codigo'])) {
        $sqlBase .= " AND SERCOD = :codigo";
        $sqlParams[':codigo'] = $params['codigo'];
    }
    
    if (!empty($params['descricao'])) {
        $sqlBase .= " AND SERDES LIKE :descricao";
        $sqlParams[':descricao'] = '%' . $params['descricao'] . '%';
    }
    
    // Consulta para contar o total de registros
    $sqlCount = "SELECT COUNT(*) as total " . $sqlBase;
    $resultCount = executeQuery($sqlCount, $sqlParams);
    
    if ($resultCount === false) {
        return $response;
    }
    
    $total = $resultCount[0]['total'];
    $totalPaginas = ceil($total / $porPagina);
    
    // Ajustar página atual se necessário
    if ($pagina > $totalPaginas && $total > 0) {
        $pagina = $totalPaginas;
        $inicio = ($pagina - 1) * $porPagina;
    }
    
    // Consulta para buscar os dados
    $sql = "SELECT * " . $sqlBase . " ORDER BY SERCOD LIMIT :inicio, :porPagina";
    $sqlParams[':inicio'] = $inicio;
    $sqlParams[':porPagina'] = $porPagina;
    
    $result = executeQuery($sql, $sqlParams);
    
    if ($result === false) {
        return $response;
    }
    
    // Calcular início e fim para exibição na paginação
    $inicio = ($pagina - 1) * $porPagina + 1;
    $fim = min($inicio + $porPagina - 1, $total);
    if ($total == 0) {
        $inicio = 0;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['servicos'] = $result;
    $response['total'] = $total;
    $response['paginaAtual'] = $pagina;
    $response['totalPaginas'] = $totalPaginas;
    $response['inicio'] = $inicio;
    $response['fim'] = $fim;
    
    return $response;
}

/**
 * Busca um serviço específico pelo ID
 * @param int $id ID do serviço
 * @return array Resposta com os dados do serviço
 */
function getServico($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'servico' => null
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Consulta para buscar o serviço
    $sql = "SELECT * FROM CADSER WHERE SERCOD = :id";
    $result = executeQuery($sql, array(':id' => $id));
    
    if ($result === false) {
        $response['message'] = 'Erro ao buscar serviço.';
        return $response;
    }
    
    if (empty($result)) {
        $response['message'] = 'Serviço não encontrado.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['servico'] = $result[0];
    
    return $response;
}

/**
 * Cria um novo serviço
 * @param array $data Dados do serviço
 * @return array Resposta com o resultado da operação
 */
function createServico($data) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'id' => null
    );
    
    // Validar dados obrigatórios
    if (empty($data['SERDES'])) {
        $response['message'] = 'A descrição do serviço é obrigatória.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Inserir serviço
        $sql = "INSERT INTO CADSER (SERDES) VALUES (:descricao)";
        $params = array(':descricao' => $data['SERDES']);
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao inserir serviço.');
        }
        
        // Obter ID gerado
        $id = getLastInsertId();
        
        if (!$id) {
            throw new Exception('Erro ao obter ID do serviço.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Serviço cadastrado com sucesso.';
        $response['id'] = $id;
        
        return $response;
    } catch (Exception $e) {
        // Desfazer transação
        rollbackTransaction($conn);
        
        // Retornar erro
        $response['message'] = $e->getMessage();
        return $response;
    }
}

/**
 * Atualiza um serviço existente
 * @param int $id ID do serviço
 * @param array $data Dados atualizados do serviço
 * @return array Resposta com o resultado da operação
 */
function updateServico($id, $data) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => ''
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Validar dados obrigatórios
    if (empty($data['SERDES'])) {
        $response['message'] = 'A descrição do serviço é obrigatória.';
        return $response;
    }
    
    // Verificar se o serviço existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADSER WHERE SERCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar serviço.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Serviço não encontrado.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Atualizar serviço
        $sql = "UPDATE CADSER SET SERDES = :descricao WHERE SERCOD = :id";
        $params = array(
            ':id' => $id,
            ':descricao' => $data['SERDES']
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao atualizar serviço.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Serviço atualizado com sucesso.';
        
        return $response;
    } catch (Exception $e) {
        // Desfazer transação
        rollbackTransaction($conn);
        
        // Retornar erro
        $response['message'] = $e->getMessage();
        return $response;
    }
}

/**
 * Exclui um serviço
 * @param int $id ID do serviço
 * @return array Resposta com o resultado da operação
 */
function deleteServico($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => ''
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Verificar se o serviço existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADSER WHERE SERCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar serviço.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Serviço não encontrado.';
        return $response;
    }
    
    // Verificar se o serviço pode ser excluído
    $result = canDeleteServico($id);
    
    if ($result === false) {
        $response['message'] = 'Erro ao verificar dependências.';
        return $response;
    }
    
    if (!$result['canDelete']) {
        $response['message'] = $result['message'];
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Excluir serviço
        $sql = "DELETE FROM CADSER WHERE SERCOD = :id";
        $result = executeQuery($sql, array(':id' => $id));
        
        if ($result === false) {
            throw new Exception('Erro ao excluir serviço.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Serviço excluído com sucesso.';
        
        return $response;
    } catch (Exception $e) {
        // Desfazer transação
        rollbackTransaction($conn);
        
        // Retornar erro
        $response['message'] = $e->getMessage();
        return $response;
    }
}

/**
 * Verifica se um serviço pode ser excluído
 * @param int $id ID do serviço
 * @return array Resposta com o resultado da verificação
 */
function canDeleteServico($id) {
    // Inicializar resposta
    $response = array(
        'canDelete' => false,
        'message' => ''
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Verificar se o serviço está vinculado a ordens de serviço
    $sqlCheck = "SELECT COUNT(*) as total FROM ORDSER WHERE OSSERCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar dependências.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] > 0) {
        $response['message'] = 'Este serviço não pode ser excluído porque está vinculado a uma ou mais Ordens de Serviço.';
        return $response;
    }
    
    // Serviço pode ser excluído
    $response['canDelete'] = true;
    
    return $response;
}

/**
 * Processa requisições para serviços
 */
function processServicosRequest($method, $resource, $id, $params) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Buscar serviço específico
                return getServico($id);
            } else if ($resource === 'can-delete' && isset($params['id'])) {
                // Verificar se pode excluir
                return canDeleteServico($params['id']);
            } else {
                // Listar serviços
                return getServicos($params);
            }
            
        case 'POST':
            // Criar serviço
            return createServico($params);
            
        case 'PUT':
            // Atualizar serviço
            return updateServico($id, $params);
            
        case 'DELETE':
            // Excluir serviço
            return deleteServico($id);
            
        default:
            return ['success' => false, 'message' => 'Método não permitido'];
    }
}