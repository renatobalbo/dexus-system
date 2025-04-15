<?php
/**
 * API para manipulação de clientes
 * Sistema de Gestão Dexus
 */

// Incluir configuração de banco de dados
require_once __DIR__ . '/../../config/database.php';

/**
 * Busca lista de clientes
 * @param array $params Parâmetros de filtro e paginação
 * @return array Resposta com a lista de clientes
 */
function getClientes($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'clientes' => array(),
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
    $sqlBase = "FROM CADCLI c
                LEFT JOIN CADMOD m ON c.CLIMOD = m.MODCOD
                WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['codigo'])) {
        $sqlBase .= " AND c.CLICOD = :codigo";
        $sqlParams[':codigo'] = $params['codigo'];
    }
    
    if (!empty($params['tipo'])) {
        $sqlBase .= " AND c.CLITIP = :tipo";
        $sqlParams[':tipo'] = $params['tipo'];
    }
    
    if (!empty($params['razao'])) {
        $sqlBase .= " AND c.CLIRAZ LIKE :razao";
        $sqlParams[':razao'] = '%' . $params['razao'] . '%';
    }
    
    if (!empty($params['documento'])) {
        $sqlBase .= " AND c.CLIDOC LIKE :documento";
        $sqlParams[':documento'] = '%' . $params['documento'] . '%';
    }
    
    if (!empty($params['municipio'])) {
        $sqlBase .= " AND c.CLIMUN LIKE :municipio";
        $sqlParams[':municipio'] = '%' . $params['municipio'] . '%';
    }
    
    if (!empty($params['uf'])) {
        $sqlBase .= " AND c.CLIEST = :uf";
        $sqlParams[':uf'] = $params['uf'];
    }
    
    if (!empty($params['modalidade'])) {
        $sqlBase .= " AND c.CLIMOD = :modalidade";
        $sqlParams[':modalidade'] = $params['modalidade'];
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
    $sql = "SELECT c.*, m.MODDES " . $sqlBase . " ORDER BY c.CLICOD LIMIT :inicio, :porPagina";
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
    $response['clientes'] = $result;
    $response['total'] = $total;
    $response['paginaAtual'] = $pagina;
    $response['totalPaginas'] = $totalPaginas;
    $response['inicio'] = $inicio;
    $response['fim'] = $fim;
    
    return $response;
}

/**
 * Busca um cliente específico pelo ID
 * @param int $id ID do cliente
 * @return array Resposta com os dados do cliente
 */
function getCliente($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'cliente' => null
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Consulta para buscar o cliente
    $sql = "SELECT c.*, m.MODDES 
            FROM CADCLI c
            LEFT JOIN CADMOD m ON c.CLIMOD = m.MODCOD
            WHERE c.CLICOD = :id";
    
    $result = executeQuery($sql, array(':id' => $id));
    
    if ($result === false) {
        $response['message'] = 'Erro ao buscar cliente.';
        return $response;
    }
    
    if (empty($result)) {
        $response['message'] = 'Cliente não encontrado.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['cliente'] = $result[0];
    
    return $response;
}

/**
 * Cria um novo cliente
 * @param array $data Dados do cliente
 * @return array Resposta com o resultado da operação
 */
function createCliente($data) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'id' => null
    );
    
    // Validar dados obrigatórios
    if (empty($data['CLITIP']) || empty($data['CLIDOC']) || empty($data['CLIRAZ'])) {
        $response['message'] = 'Dados obrigatórios não informados.';
        return $response;
    }
    
    // Verificar se já existe um cliente com o mesmo documento
    $sqlCheck = "SELECT COUNT(*) as total FROM CADCLI WHERE CLIDOC = :documento";
    $resultCheck = executeQuery($sqlCheck, array(':documento' => $data['CLIDOC']));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar documento.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] > 0) {
        $response['message'] = 'Já existe um cliente cadastrado com este documento.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Inserir cliente
        $sql = "INSERT INTO CADCLI (
                    CLITIP, CLIDOC, CLIRAZ, CLIFAN, CLIMUN, CLIEST,
                    CLIRES, CLIEOS, CLIENF, CLIMOD, CLIVAL
                ) VALUES (
                    :tipo, :documento, :razao, :fantasia, :municipio, :uf,
                    :responsavel, :emailOS, :emailNF, :modalidade, :valorHora
                )";
        
        $params = array(
            ':tipo' => $data['CLITIP'],
            ':documento' => $data['CLIDOC'],
            ':razao' => $data['CLIRAZ'],
            ':fantasia' => isset($data['CLIFAN']) ? $data['CLIFAN'] : null,
            ':municipio' => isset($data['CLIMUN']) ? $data['CLIMUN'] : null,
            ':uf' => isset($data['CLIEST']) ? $data['CLIEST'] : null,
            ':responsavel' => isset($data['CLIRES']) ? $data['CLIRES'] : null,
            ':emailOS' => isset($data['CLIEOS']) ? $data['CLIEOS'] : null,
            ':emailNF' => isset($data['CLIENF']) ? $data['CLIENF'] : null,
            ':modalidade' => isset($data['CLIMOD']) && !empty($data['CLIMOD']) ? $data['CLIMOD'] : null,
            ':valorHora' => isset($data['CLIVAL']) && !empty($data['CLIVAL']) ? $data['CLIVAL'] : null
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao inserir cliente.');
        }
        
        // Obter ID gerado
        $id = getLastInsertId();
        
        if (!$id) {
            throw new Exception('Erro ao obter ID do cliente.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Cliente cadastrado com sucesso.';
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
 * Atualiza um cliente existente
 * @param int $id ID do cliente
 * @param array $data Dados atualizados do cliente
 * @return array Resposta com o resultado da operação
 */
function updateCliente($id, $data) {
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
    if (empty($data['CLITIP']) || empty($data['CLIDOC']) || empty($data['CLIRAZ'])) {
        $response['message'] = 'Dados obrigatórios não informados.';
        return $response;
    }
    
    // Verificar se o cliente existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADCLI WHERE CLICOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar cliente.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Cliente não encontrado.';
        return $response;
    }
    
    // Verificar se já existe outro cliente com o mesmo documento
    $sqlCheck = "SELECT COUNT(*) as total FROM CADCLI WHERE CLIDOC = :documento AND CLICOD != :id";
    $resultCheck = executeQuery($sqlCheck, array(':documento' => $data['CLIDOC'], ':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar documento.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] > 0) {
        $response['message'] = 'Já existe outro cliente cadastrado com este documento.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Atualizar cliente
        $sql = "UPDATE CADCLI SET
                    CLITIP = :tipo,
                    CLIDOC = :documento,
                    CLIRAZ = :razao,
                    CLIFAN = :fantasia,
                    CLIMUN = :municipio,
                    CLIEST = :uf,
                    CLIRES = :responsavel,
                    CLIEOS = :emailOS,
                    CLIENF = :emailNF,
                    CLIMOD = :modalidade,
                    CLIVAL = :valorHora
                WHERE CLICOD = :id";
        
        $params = array(
            ':id' => $id,
            ':tipo' => $data['CLITIP'],
            ':documento' => $data['CLIDOC'],
            ':razao' => $data['CLIRAZ'],
            ':fantasia' => isset($data['CLIFAN']) ? $data['CLIFAN'] : null,
            ':municipio' => isset($data['CLIMUN']) ? $data['CLIMUN'] : null,
            ':uf' => isset($data['CLIEST']) ? $data['CLIEST'] : null,
            ':responsavel' => isset($data['CLIRES']) ? $data['CLIRES'] : null,
            ':emailOS' => isset($data['CLIEOS']) ? $data['CLIEOS'] : null,
            ':emailNF' => isset($data['CLIENF']) ? $data['CLIENF'] : null,
            ':modalidade' => isset($data['CLIMOD']) && !empty($data['CLIMOD']) ? $data['CLIMOD'] : null,
            ':valorHora' => isset($data['CLIVAL']) && !empty($data['CLIVAL']) ? $data['CLIVAL'] : null
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao atualizar cliente.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Cliente atualizado com sucesso.';
        
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
 * Exclui um cliente
 * @param int $id ID do cliente
 * @return array Resposta com o resultado da operação
 */
function deleteCliente($id) {
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
    
    // Verificar se o cliente existe
    $sqlCheck = "SELECT COUNT(*) as total FROM CADCLI WHERE CLICOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar cliente.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Cliente não encontrado.';
        return $response;
    }
    
    // Verificar se o cliente pode ser excluído
    $result = canDeleteCliente($id);
    
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
        // Excluir cliente
        $sql = "DELETE FROM CADCLI WHERE CLICOD = :id";
        $result = executeQuery($sql, array(':id' => $id));
        
        if ($result === false) {
            throw new Exception('Erro ao excluir cliente.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Cliente excluído com sucesso.';
        
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
 * Verifica se um cliente pode ser excluído
 * @param int $id ID do cliente
 * @return array Resposta com o resultado da verificação
 */
function canDeleteCliente($id) {
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
    
    // Verificar se o cliente está vinculado a ordens de serviço
    $sqlCheck = "SELECT COUNT(*) as total FROM ORDSER WHERE OSCLICOD = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar dependências.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] > 0) {
        $response['message'] = 'Este cliente não pode ser excluído porque está vinculado a uma ou mais Ordens de Serviço.';
        return $response;
    }
    
    // Cliente pode ser excluído
    $response['canDelete'] = true;
    
    return $response;
}

/**
 * Consulta dados de CPF/CNPJ em API externa (simulação)
 * @param string $documento Número do CPF/CNPJ
 * @param string $tipo Tipo de documento (F ou J)
 * @return array Resposta com os dados consultados
 */
function consultarDocumento($documento, $tipo) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'razaoSocial' => null,
        'nomeFantasia' => null,
        'municipio' => null,
        'uf' => null
    );
    
    // Validar parâmetros
    if (empty($documento)) {
        $response['message'] = 'Documento não informado.';
        return $response;
    }
    
    if (empty($tipo) || !in_array($tipo, array('F', 'J'))) {
        $response['message'] = 'Tipo de documento inválido.';
        return $response;
    }
    
    // Remover caracteres não numéricos
    $documento = preg_replace('/\D/', '', $documento);
    
    // Validar formato do documento
    if (($tipo === 'F' && strlen($documento) !== 11) || 
        ($tipo === 'J' && strlen($documento) !== 14)) {
        $response['message'] = 'Formato de documento inválido.';
        return $response;
    }
    
    // Simular consulta (em um ambiente real, aqui seria feita uma chamada a uma API externa)
    // Apenas para fins de demonstração, estamos retornando dados fictícios
    if ($tipo === 'F') {
        // Exemplo para CPF
        $response['success'] = true;
        $response['razaoSocial'] = 'Pessoa Física Exemplo';
        $response['nomeFantasia'] = '';
        $response['municipio'] = 'São Paulo';
        $response['uf'] = 'SP';
    } else {
        // Exemplo para CNPJ
        $response['success'] = true;
        $response['razaoSocial'] = 'Empresa Exemplo Ltda';
        $response['nomeFantasia'] = 'Exemplo Comercial';
        $response['municipio'] = 'Rio de Janeiro';
        $response['uf'] = 'RJ';
    }
    
    return $response;
}

/**
 * Processa requisições para clientes
 */
function processClientesRequest($method, $resource, $id, $params) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Buscar cliente específico
                return getCliente($id);
            } else if ($resource === 'can-delete' && isset($params['id'])) {
                // Verificar se pode excluir
                return canDeleteCliente($params['id']);
            } else {
                // Listar clientes
                return getClientes($params);
            }
            
        case 'POST':
            // Criar cliente
            return createCliente($params);
            
        case 'PUT':
            // Atualizar cliente
            return updateCliente($id, $params);
            
        case 'DELETE':
            // Excluir cliente
            return deleteCliente($id);
            
        default:
            return ['success' => false, 'message' => 'Método não permitido'];
    }
}