<?php
/**
 * API para manipulação de modalidades
 * Sistema de Gestão Dexus
 */

// Incluir configuração de banco de dados
require_once __DIR__ . '/../../config/database.php';

/**
 * Busca lista de modalidades
 * @param array $params Parâmetros de filtro e paginação
 * @return array Resposta com a lista de modalidades
 */
function getModalidades($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'modalidades' => array(),
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
    $sqlBase = "FROM CADMOD WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['codigo'])) {
        $sqlBase .= " AND MODCOD = :codigo";
        $sqlParams[':codigo'] = $params['codigo'];
    }
    
    if (!empty($params['descricao'])) {
        $sqlBase .= " AND MODDES LIKE :descricao";
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
    $sql = "SELECT * " . $sqlBase . " ORDER BY MODCOD LIMIT :inicio, :porPagina";
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
    $response['modalidades'] = $result;
    $response['total'] = $total;
    $response['paginaAtual'] = $pagina;
    $response['totalPaginas'] = $totalPaginas;
    $response['inicio'] = $inicio;
    $response['fim'] = $fim;
    
    return $response;
}

/**
 * Busca uma modalidade específica pelo ID
 * @param int $id ID da modalidade
 * @return array Resposta com os dados da modalidade
 */
function getModalidade($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'modalidade' => null
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Consulta para buscar a modalidade
    $sql = "SELECT * FROM CADMOD WHERE MODCOD = :id";
    $result = executeQuery($sql, array(':id' => $id));
    
    if ($result === false) {
        $response['message'] = 'Erro ao buscar modalidade.';
        return $response;
    }
    
    if (empty($result)) {
        $response['message'] = 'Modalidade não encontrada.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['modalidade'] = $result[0];
    
    return $response;
}

/**
 * Cria uma nova modalidade
 * @param array $data Dados da modalidade
 * @return array Resposta com o resultado da operação
 */
function createModalidade($data) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'id' => null
    );
    
    // Validar dados obrigatórios
    if (empty($data['MODDES'])) {
        $response['message'] = 'A descrição da modalidade é obrigatória.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Inserir modalidade
        $sql = "INSERT INTO CADMOD (MODDES) VALUES (:descricao)";
        $params = array(':descricao' => $data['MODDES']);
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao inserir modalidade.');
        }
        
        // Obter ID gerado
        $id = getLastInsertId();
        
        if (!$id) {
            throw new Exception('Erro ao obter ID da modalidade.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Modalidade cadastrada com sucesso.';
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
 * Atualiza uma modalidade existente
 * @param int $id ID da modalidade
 * @param array $data Dados atualizados da modalidade
 * @return array Resposta com o resultado da operação
 */
function updateModalidade($id, $data) {
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
    if (empty($data['MODDES'])) {
        $response['message'] = 'A descrição da modalidade é obrigatória.';
        return $response;
    }
    
    // Verificar se a modalidade existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADMOD WHERE MODCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar modalidade.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Modalidade não encontrada.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Atualizar modalidade
        $sql = "UPDATE CADMOD SET MODDES = :descricao WHERE MODCOD = :id";
        $params = array(
            ':id' => $id,
            ':descricao' => $data['MODDES']
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao atualizar modalidade.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Modalidade atualizada com sucesso.';
        
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
 * Exclui uma modalidade
 * @param int $id ID da modalidade
 * @return array Resposta com o resultado da operação
 */
function deleteModalidade($id) {
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
    
    // Verificar se a modalidade existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADMOD WHERE MODCOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar modalidade.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Modalidade não encontrada.';
        return $response;
    }
    
    // Verificar se a modalidade pode ser excluída
    $result = canDeleteModalidade($id);
    
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
        // Excluir modalidade
        $sql = "DELETE FROM CADMOD WHERE MODCOD = :id";
        $result = executeQuery($sql, array(':id' => $id));
        
        if ($result === false) {
            throw new Exception('Erro ao excluir modalidade.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Modalidade excluída com sucesso.';
        
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
 * Verifica se uma modalidade pode ser excluída
 * @param int $id ID da modalidade
 * @return array Resposta com o resultado da verificação
 */
function canDeleteModalidade($id) {
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
    
    // Verificar se a modalidade está vinculada a clientes
    $sqlCheckClientes = "SELECT COUNT(*) as total FROM CADCLI WHERE CLIMOD = :id";
    $resultCheckClientes = executeQuery($sqlCheckClientes, array(':id' => $id));
    
    if ($resultCheckClientes === false) {
        $response['message'] = 'Erro ao verificar dependências em clientes.';
        return $response;
    }
    
    if ($resultCheckClientes[0]['total'] > 0) {
        $response['message'] = 'Esta modalidade não pode ser excluída porque está vinculada a um ou mais clientes.';
        return $response;
    }
    
    // Verificar se a modalidade está vinculada a ordens de serviço
    $sqlCheckOS = "SELECT COUNT(*) as total FROM ORDSER WHERE OSMODCOD = :id";
    $resultCheckOS = executeQuery($sqlCheckOS, array(':id' => $id));
    
    if ($resultCheckOS === false) {
        $response['message'] = 'Erro ao verificar dependências em ordens de serviço.';
        return $response;
    }
    
    if ($resultCheckOS[0]['total'] > 0) {
        $response['message'] = 'Esta modalidade não pode ser excluída porque está vinculada a uma ou mais ordens de serviço.';
        return $response;
    }
    
    // Modalidade pode ser excluída
    $response['canDelete'] = true;
    
    return $response;
}

/**
 * Processa requisições para modalidades
 */
function processModalidadesRequest($method, $resource, $id, $params) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Buscar modalidade específica
                return getModalidade($id);
            } else if ($resource === 'can-delete' && isset($params['id'])) {
                // Verificar se pode excluir
                return canDeleteModalidade($params['id']);
            } else {
                // Listar modalidades
                return getModalidades($params);
            }
            
        case 'POST':
            // Criar modalidade
            return createModalidade($params);
            
        case 'PUT':
            // Atualizar modalidade
            return updateModalidade($id, $params);
            
        case 'DELETE':
            // Excluir modalidade
            return deleteModalidade($id);
            
        default:
            return ['success' => false, 'message' => 'Método não permitido'];
    }
}