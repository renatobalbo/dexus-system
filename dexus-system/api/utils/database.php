<?php
/**
 * Funções utilitárias para operações de banco de dados
 * Sistema de Gestão Dexus
 */

/**
 * Formata um valor para a consulta SQL conforme o tipo
 * @param mixed $value Valor a ser formatado
 * @return string Valor formatado para SQL
 */
function formatSqlValue($value) {
    if (is_null($value)) {
        return 'NULL';
    } elseif (is_bool($value)) {
        return $value ? '1' : '0';
    } elseif (is_numeric($value)) {
        return $value;
    } else {
        return "'" . addslashes($value) . "'";
    }
}

/**
 * Constrói uma cláusula WHERE com base em um array de filtros
 * @param array $filters Array associativo de filtros (campo => valor)
 * @param array &$params Array para armazenar os parâmetros da consulta
 * @param array $options Opções adicionais (exact, prefix, between, etc)
 * @return string Cláusula WHERE formatada
 */
function buildWhereClause($filters, &$params, $options = array()) {
    if (empty($filters)) {
        return '1=1';
    }
    
    $whereConditions = array();
    $index = 0;
    
    foreach ($filters as $field => $value) {
        if (is_null($value) || $value === '') {
            continue;
        }
        
        // Verificar opção de comparação para este campo
        $comparison = isset($options[$field]) ? $options[$field] : 'like';
        
        // Gerar nome de parâmetro único
        $paramName = ':' . str_replace('.', '_', $field) . '_' . $index++;
        
        switch ($comparison) {
            case 'exact':
                $whereConditions[] = "$field = $paramName";
                $params[$paramName] = $value;
                break;
                
            case 'like':
                $whereConditions[] = "$field LIKE $paramName";
                $params[$paramName] = '%' . $value . '%';
                break;
                
            case 'prefix':
                $whereConditions[] = "$field LIKE $paramName";
                $params[$paramName] = $value . '%';
                break;
                
            case 'suffix':
                $whereConditions[] = "$field LIKE $paramName";
                $params[$paramName] = '%' . $value;
                break;
                
            case 'greater':
                $whereConditions[] = "$field > $paramName";
                $params[$paramName] = $value;
                break;
                
            case 'greater_equal':
                $whereConditions[] = "$field >= $paramName";
                $params[$paramName] = $value;
                break;
                
            case 'less':
                $whereConditions[] = "$field < $paramName";
                $params[$paramName] = $value;
                break;
                
            case 'less_equal':
                $whereConditions[] = "$field <= $paramName";
                $params[$paramName] = $value;
                break;
                
            case 'between':
                if (is_array($value) && count($value) == 2) {
                    $paramName1 = $paramName . '_1';
                    $paramName2 = $paramName . '_2';
                    $whereConditions[] = "($field BETWEEN $paramName1 AND $paramName2)";
                    $params[$paramName1] = $value[0];
                    $params[$paramName2] = $value[1];
                }
                break;
                
            case 'in':
                if (is_array($value) && !empty($value)) {
                    $inParams = array();
                    foreach ($value as $i => $val) {
                        $inParamName = $paramName . '_' . $i;
                        $inParams[] = $inParamName;
                        $params[$inParamName] = $val;
                    }
                    $whereConditions[] = "$field IN (" . implode(', ', $inParams) . ")";
                }
                break;
                
            case 'not_in':
                if (is_array($value) && !empty($value)) {
                    $inParams = array();
                    foreach ($value as $i => $val) {
                        $inParamName = $paramName . '_' . $i;
                        $inParams[] = $inParamName;
                        $params[$inParamName] = $val;
                    }
                    $whereConditions[] = "$field NOT IN (" . implode(', ', $inParams) . ")";
                }
                break;
                
            case 'null':
                if ($value) {
                    $whereConditions[] = "$field IS NULL";
                } else {
                    $whereConditions[] = "$field IS NOT NULL";
                }
                break;
                
            default:
                $whereConditions[] = "$field = $paramName";
                $params[$paramName] = $value;
                break;
        }
    }
    
    if (empty($whereConditions)) {
        return '1=1';
    }
    
    return implode(' AND ', $whereConditions);
}

/**
 * Constrói uma cláusula ORDER BY com base em um array de ordenação
 * @param array $orderBy Array de campos para ordenação e direção
 * @return string Cláusula ORDER BY formatada
 */
function buildOrderByClause($orderBy) {
    if (empty($orderBy)) {
        return '';
    }
    
    $orderClauses = array();
    
    foreach ($orderBy as $field => $direction) {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $orderClauses[] = "$field $direction";
    }
    
    if (empty($orderClauses)) {
        return '';
    }
    
    return 'ORDER BY ' . implode(', ', $orderClauses);
}

/**
 * Verifica se uma tabela existe no banco de dados
 * @param string $tableName Nome da tabela
 * @return bool Indica se a tabela existe
 */
function tableExists($tableName) {
    $conn = getConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Verificar se a tabela existe
        $sql = "SHOW TABLES LIKE :tableName";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':tableName' => $tableName]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao verificar tabela: ' . $e->getMessage());
        
        return false;
    } finally {
        // Fechar conexão
        $conn = null;
    }
}

/**
 * Obtém informações sobre as colunas de uma tabela
 * @param string $tableName Nome da tabela
 * @return array|false Informações das colunas ou false em caso de erro
 */
function getTableColumns($tableName) {
    $conn = getConnection();
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Obter informações das colunas
        $sql = "SHOW COLUMNS FROM $tableName";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Registrar erro
        error_log('Erro ao obter colunas da tabela: ' . $e->getMessage());
        
        return false;
    } finally {
        // Fechar conexão
        $conn = null;
    }
}

/**
 * Verifica se uma coluna existe em uma tabela
 * @param string $tableName Nome da tabela
 * @param string $columnName Nome da coluna
 * @return bool Indica se a coluna existe
 */
function columnExists($tableName, $columnName) {
    $columns = getTableColumns($tableName);
    
    if ($columns === false) {
        return false;
    }
    
    foreach ($columns as $column) {
        if ($column['Field'] === $columnName) {
            return true;
        }
    }
    
    return false;
}

/**
 * Sanitiza um nome de tabela ou coluna para prevenir SQL injection
 * @param string $name Nome a ser sanitizado
 * @return string Nome sanitizado
 */
function sanitizeTableName($name) {
    // Remover caracteres não alfanuméricos e underscore
    $name = preg_replace('/[^\w]/', '', $name);
    
    // Verificar se o nome não está vazio
    if (empty($name)) {
        return 'invalid_name';
    }
    
    return $name;
}

/**
 * Importa dados de um CSV para uma tabela do banco de dados
 * @param string $filename Caminho do arquivo CSV
 * @param string $tableName Nome da tabela
 * @param array $columnMap Mapeamento de colunas CSV para colunas da tabela
 * @param bool $hasHeader Indica se o CSV tem cabeçalho
 * @return array Resultado da importação
 */
function importCSVToTable($filename, $tableName, $columnMap = array(), $hasHeader = true) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'imported' => 0,
        'failed' => 0
    );
    
    // Verificar se o arquivo existe
    if (!file_exists($filename)) {
        $response['message'] = 'Arquivo não encontrado.';
        return $response;
    }
    
    // Verificar se a tabela existe
    if (!tableExists($tableName)) {
        $response['message'] = 'Tabela não encontrada.';
        return $response;
    }
    
    // Abrir arquivo
    $file = fopen($filename, 'r');
    if (!$file) {
        $response['message'] = 'Erro ao abrir arquivo.';
        return $response;
    }
    
    // Ler cabeçalho se necessário
    $headers = array();
    if ($hasHeader) {
        $headers = fgetcsv($file);
        
        // Validar cabeçalho
        if ($headers === false) {
            fclose($file);
            $response['message'] = 'Erro ao ler cabeçalho do arquivo.';
            return $response;
        }
        
        // Sanitizar cabeçalhos
        foreach ($headers as &$header) {
            $header = trim($header);
        }
    }
    
    // Obter colunas da tabela
    $tableColumns = getTableColumns($tableName);
    if ($tableColumns === false) {
        fclose($file);
        $response['message'] = 'Erro ao obter estrutura da tabela.';
        return $response;
    }
    
    $validColumns = array();
    foreach ($tableColumns as $column) {
        $validColumns[] = $column['Field'];
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    if (!$conn) {
        fclose($file);
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        $imported = 0;
        $failed = 0;
        $lineNumber = $hasHeader ? 2 : 1; // Iniciar da linha 2 se tiver cabeçalho
        
        // Ler registros
        while (($data = fgetcsv($file)) !== false) {
            // Verificar se o registro tem dados
            if (empty($data) || count($data) === 1 && empty($data[0])) {
                $lineNumber++;
                continue;
            }
            
            // Mapear colunas
            $record = array();
            
            // Se tiver cabeçalho e mapeamento de colunas
            if ($hasHeader && !empty($columnMap)) {
                foreach ($columnMap as $csvColumn => $dbColumn) {
                    // Verificar se a coluna existe no CSV
                    $columnIndex = array_search($csvColumn, $headers);
                    if ($columnIndex !== false && in_array($dbColumn, $validColumns)) {
                        $record[$dbColumn] = isset($data[$columnIndex]) ? $data[$columnIndex] : null;
                    }
                }
            }
            // Se tiver cabeçalho mas não tiver mapeamento, usar cabeçalho como mapeamento
            elseif ($hasHeader) {
                foreach ($headers as $i => $header) {
                    if (in_array($header, $validColumns) && isset($data[$i])) {
                        $record[$header] = $data[$i];
                    }
                }
            }
            // Se não tiver cabeçalho mas tiver mapeamento, usar índices
            elseif (!empty($columnMap)) {
                foreach ($columnMap as $csvColumnIndex => $dbColumn) {
                    if (is_numeric($csvColumnIndex) && in_array($dbColumn, $validColumns)) {
                        $record[$dbColumn] = isset($data[$csvColumnIndex]) ? $data[$csvColumnIndex] : null;
                    }
                }
            }
            
            // Verificar se tem dados para inserir
            if (empty($record)) {
                $failed++;
                $lineNumber++;
                continue;
            }
            
            // Construir query de inserção
            $columns = array_keys($record);
            $placeholders = array_map(function($col) { return ":$col"; }, $columns);
            
            $sql = "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            // Preparar params
            $params = array();
            foreach ($record as $column => $value) {
                $params[":$column"] = $value;
            }
            
            // Executar query
            $result = executeQuery($sql, $params);
            
            if ($result === false) {
                $failed++;
            } else {
                $imported++;
            }
            
            $lineNumber++;
        }
        
        // Fechar arquivo
        fclose($file);
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = "Importação concluída. $imported registros importados, $failed falhas.";
        $response['imported'] = $imported;
        $response['failed'] = $failed;
        
        return $response;
    } catch (Exception $e) {
        // Fechar arquivo
        fclose($file);
        
        // Desfazer transação
        rollbackTransaction($conn);
        
        // Retornar erro
        $response['message'] = $e->getMessage();
        return $response;
    }
}