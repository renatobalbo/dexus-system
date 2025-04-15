<?php
/**
 * API para manipulação de consultores
 * Sistema de Gestão Dexus
 */

// Incluir configuração de banco de dados
require_once __DIR__ . '/../../config/database.php';

/**
 * Busca lista de consultores
 * @param array $params Parâmetros de filtro e paginação
 * @return array Resposta com a lista de consultores
 */
function getConsultores($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'consultores' => array(),
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
    $sqlBase = "FROM CADCON WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['codigo'])) {
        $sqlBase .= " AND CONCOD = :codigo";
        $sqlParams[':codigo'] = $params['codigo'];
    }
    
    if (!empty($params['nome'])) {
        $sqlBase .= " AND CONNOM LIKE :nome";
        $sqlParams[':nome'] = '%' . $params['nome'] . '%';
    }
    
    if (!empty($params['email'])) {
        $sqlBase .= " AND CONEMA LIKE :email";
        $sqlParams[':email'] = '%' . $params['email'] . '%';
    }
    
    if (!empty($params['atuacao'])) {
        $sqlBase .= " AND CONATU LIKE :atuacao";
        $sqlParams[':atuacao'] = '%' . $params['atuacao'] . '%';
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
    $sql = "SELECT * " . $sqlBase . " ORDER BY CONNOM LIMIT :inicio, :porPagina";
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
    $response['consultores'] = $result;
    $response['total'] = $total;
    $response['paginaAtual'] = $pagina;
    $response['totalPaginas'] = $totalPaginas;
    $response['inicio'] = $inicio;
    $response['fim'] = $fim;
    
    return $response;
}

/**
 * Busca um consultor específico pelo ID
 * @param int $id ID do consultor
 * @return array Resposta com os dados do consultor
 */
function getConsultor($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'consultor' => null
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Consulta para buscar o consultor
    $sql = "SELECT * FROM CADCON WHERE CONCOD = :id";
    $result = executeQuery($sql, array(':id' => $id));
    
    if ($result === false) {
        $response['message'] = 'Erro ao buscar consultor.';
        return $response;
    }
    
    if (empty($result)) {
        $response['message'] = 'Consultor não encontrado.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['consultor'] = $result[0];
    
    return $response;
}

/**
 * Cria um novo consultor
 * @param array $data Dados do consultor
 * @return array Resposta com o resultado da operação
 */
function createConsultor($data) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'id' => null
    );
    
    // Validar dados obrigatórios
    if (empty($data['CONNOM'])) {
        $response['message'] = 'O nome do consultor é obrigatório.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Inserir consultor
        $sql = "INSERT INTO CADCON (
                    CONNOM, CONTEL, CONEMA, CONATU, CONVAL
                ) VALUES (
                    :nome, :telefone, :email, :atuacao, :valorHora
                )";
        
        $params = array(
            ':nome' => $data['CONNOM'],
            ':telefone' => isset($data['CONTEL']) ? $data['CONTEL'] : null,
            ':email' => isset($data['CONEMA']) ? $data['CONEMA'] : null,
            ':atuacao' => isset($data['CONATU']) ? $data['CONATU'] : null,
            ':valorHora' => isset($data['CONVAL']) && !empty($data['CONVAL']) ? $data['CONVAL'] : null
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao inserir consultor.');
        }
        
        // Obter ID gerado
        $id = getLastInsertId();
        
        if (!$id) {
            throw new Exception('Erro ao obter ID do consultor.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Consultor cadastrado com sucesso.';
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
 * Atualiza um consultor existente
 * @param int $id ID do consultor
 * @param array $data Dados atualizados do consultor
 * @return array Resposta com o resultado da operação
 */
function updateConsultor($id, $data) {
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
    if (empty($data['CONNOM'])) {
        $response['message'] = 'O nome do consultor é obrigatório.';
        return $response;
    }
    
    // Verificar se o consultor existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADCON WHERE CONCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar consultor.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Consultor não encontrado.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Atualizar consultor
        $sql = "UPDATE CADCON SET
                    CONNOM = :nome,
                    CONTEL = :telefone,
                    CONEMA = :email,
                    CONATU = :atuacao,
                    CONVAL = :valorHora
                WHERE CONCOD = :id";
        
        $params = array(
            ':id' => $id,
            ':nome' => $data['CONNOM'],
            ':telefone' => isset($data['CONTEL']) ? $data['CONTEL'] : null,
            ':email' => isset($data['CONEMA']) ? $data['CONEMA'] : null,
            ':atuacao' => isset($data['CONATU']) ? $data['CONATU'] : null,
            ':valorHora' => isset($data['CONVAL']) && !empty($data['CONVAL']) ? $data['CONVAL'] : null
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao atualizar consultor.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Consultor atualizado com sucesso.';
        
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
 * Exclui um consultor
 * @param int $id ID do consultor
 * @return array Resposta com o resultado da operação
 */
function deleteConsultor($id) {
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
    
    // Verificar se o consultor existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADCON WHERE CONCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar consultor.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Consultor não encontrado.';
        return $response;
    }
    
    // Verificar se o consultor pode ser excluído
    $result = canDeleteConsultor($id);
    
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
        // Excluir consultor
        $sql = "DELETE FROM CADCON WHERE CONCOD = :id";
        $result = executeQuery($sql, array(':id' => $id));
        
        if ($result === false) {
            throw new Exception('Erro ao excluir consultor.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Consultor excluído com sucesso.';
        
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
 * Verifica se um consultor pode ser excluído
 * @param int $id ID do consultor
 * @return array Resposta com o resultado da verificação
 */
function canDeleteConsultor($id) {
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
    
    // Verificar se o consultor está vinculado a ordens de serviço
    $sqlCheck = "SELECT COUNT(*) as total FROM ORDSER WHERE OSCONCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar dependências.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] > 0) {
        $response['message'] = 'Este consultor não pode ser excluído porque está vinculado a uma ou mais ordens de serviço.';
        return $response;
    }
    
    // Consultor pode ser excluído
    $response['canDelete'] = true;
    
    return $response;
}

/**
 * Processa requisições para consultores
 */
function processConsultoresRequest($method, $resource, $id, $params) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Buscar consultor específico
                return getConsultor($id);
            } else if ($resource === 'can-delete' && isset($params['id'])) {
                // Verificar se pode excluir
                return canDeleteConsultor($params['id']);
            } else {
                // Listar consultores
                return getConsultores($params);
            }
            
        case 'POST':
            // Criar consultor
            return createConsultor($params);
            
        case 'PUT':
            // Atualizar consultor
            return updateConsultor($id, $params);
            
        case 'DELETE':
            // Excluir consultor
            return deleteConsultor($id);
            
        default:
            return ['success' => false, 'message' => 'Método não permitido'];
    }
}