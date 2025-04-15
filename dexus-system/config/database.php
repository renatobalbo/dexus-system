<?php
/**
 * Configuração de conexão com o banco de dados
 * Sistema de Gestão Dexus
 */

// Variáveis de conexão
$db_host = 'RENATO-PC';     // Host do banco de dados
$db_name = 'DEXUS';         // Nome do banco de dados
$db_user = 'sa';            // Usuário do banco de dados
$db_pass = '88018155-aS';   // Senha do banco de dados

/**
 * Função para obter uma conexão com o banco de dados
 * @return PDO Objeto de conexão com o banco de dados
 */
function getConnection() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        // Criar conexão PDO
        $conn = new PDO(
            "sqlsrv:Server=$db_host;Database=$db_name",
            $db_user,
            $db_pass,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        );
        
        return $conn;
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro de conexão com o banco de dados: ' . $e->getMessage());
        
        // Retornar null em caso de erro
        return null;
    }
}

/**
 * Função para executar uma query e retornar os resultados
 * @param string $sql Query SQL a ser executada
 * @param array $params Parâmetros para a query
 * @return array|false Resultados da query ou false em caso de erro
 */
function executeQuery($sql, $params = array()) {
    $conn = getConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Preparar statement
        $stmt = $conn->prepare($sql);
        
        // Executar statement com os parâmetros
        $stmt->execute($params);
        
        // Verificar se é uma consulta SELECT
        if (stripos(trim($sql), 'SELECT') === 0) {
            // Retornar resultados
            return $stmt->fetchAll();
        } else {
            // Retornar número de linhas afetadas
            return $stmt->rowCount();
        }
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao executar query: ' . $e->getMessage());
        error_log('SQL: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        
        // Retornar false em caso de erro
        return false;
    } finally {
        // Fechar conexão
        $conn = null;
    }
}

/**
 * Função para obter o último ID inserido
 * @return int|false Último ID inserido ou false em caso de erro
 */
function getLastInsertId() {
    $conn = getConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Retornar último ID
        return $conn->lastInsertId();
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao obter último ID: ' . $e->getMessage());
        
        // Retornar false em caso de erro
        return false;
    } finally {
        // Fechar conexão
        $conn = null;
    }
}

/**
 * Função para iniciar uma transação
 * @return PDO|false Objeto de conexão ou false em caso de erro
 */
function beginTransaction() {
    $conn = getConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Iniciar transação
        $conn->beginTransaction();
        
        // Retornar conexão
        return $conn;
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao iniciar transação: ' . $e->getMessage());
        
        // Retornar false em caso de erro
        return false;
    }
}

/**
 * Função para confirmar uma transação
 * @param PDO $conn Objeto de conexão
 * @return bool Sucesso ou falha
 */
function commitTransaction($conn) {
    if (!$conn) {
        return false;
    }
    
    try {
        // Confirmar transação
        $conn->commit();
        
        // Retornar sucesso
        return true;
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao confirmar transação: ' . $e->getMessage());
        
        // Tentar desfazer transação
        try {
            $conn->rollBack();
        } catch (Exception $ex) {
            error_log('Erro ao desfazer transação: ' . $ex->getMessage());
        }
        
        // Retornar falha
        return false;
    } finally {
        // Fechar conexão
        $conn = null;
    }
}

/**
 * Função para desfazer uma transação
 * @param PDO $conn Objeto de conexão
 * @return bool Sucesso ou falha
 */
function rollbackTransaction($conn) {
    if (!$conn) {
        return false;
    }
    
    try {
        // Desfazer transação
        $conn->rollBack();
        
        // Retornar sucesso
        return true;
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao desfazer transação: ' . $e->getMessage());
        
        // Retornar falha
        return false;
    } finally {
        // Fechar conexão
        $conn = null;
    }
}