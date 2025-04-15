<?php
/**
 * API para manipulação de ordens de serviço
 * Sistema de Gestão Dexus
 */

// Incluir configuração de banco de dados
require_once __DIR__ . '/../../config/database.php';

// Incluir utilitário de PDF
require_once __DIR__ . '/../utils/pdf.php';

/**
 * Busca lista de ordens de serviço
 * @param array $params Parâmetros de filtro e paginação
 * @return array Resposta com a lista de ordens de serviço
 */
function getOrdens($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'ordens' => array(),
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
    
    // Construir consulta base
    $sqlBase = "FROM ORDSER o
                LEFT JOIN CADCLI c ON o.OSCLICOD = c.CLICOD
                LEFT JOIN CADMOD m ON o.OSMODCOD = m.MODCOD
                LEFT JOIN CADSER s ON o.OSSERCOD = s.SERCOD
                LEFT JOIN CADCON co ON o.OSCONCOD = co.CONCOD
                WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['numero'])) {
        $sqlBase .= " AND o.OSNUM = :numero";
        $sqlParams[':numero'] = $params['numero'];
    }
    
    if (!empty($params['dataInicial'])) {
        $dataInicial = formatDateToDB($params['dataInicial']);
        $sqlBase .= " AND o.OSDATA >= :dataInicial";
        $sqlParams[':dataInicial'] = $dataInicial;
    }
    
    if (!empty($params['dataFinal'])) {
        $dataFinal = formatDateToDB($params['dataFinal']);
        $sqlBase .= " AND o.OSDATA <= :dataFinal";
        $sqlParams[':dataFinal'] = $dataFinal;
    }
    
    if (!empty($params['cliente'])) {
        $sqlBase .= " AND o.OSCLICOD = :cliente";
        $sqlParams[':cliente'] = $params['cliente'];
    }
    
    if (!empty($params['modalidade'])) {
        $sqlBase .= " AND o.OSMODCOD = :modalidade";
        $sqlParams[':modalidade'] = $params['modalidade'];
    }
    
    if (!empty($params['servico'])) {
        $sqlBase .= " AND o.OSSERCOD = :servico";
        $sqlParams[':servico'] = $params['servico'];
    }
    
    if (!empty($params['consultor'])) {
        $sqlBase .= " AND o.OSCONCOD = :consultor";
        $sqlParams[':consultor'] = $params['consultor'];
    }
    
    if (isset($params['enviada'])) {
        $sqlBase .= " AND o.OSENV = :enviada";
        $sqlParams[':enviada'] = $params['enviada'];
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
    $sql = "SELECT o.*, c.CLIRAZ, m.MODDES, s.SERDES, co.CONNOM " . $sqlBase . " 
            ORDER BY o.OSNUM DESC LIMIT :inicio, :porPagina";
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
    
    // Formatar datas para exibição
    foreach ($result as &$ordem) {
        if (isset($ordem['OSDATA'])) {
            $ordem['OSDATA'] = formatDateFromDB($ordem['OSDATA']);
        }
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['ordens'] = $result;
    $response['total'] = $total;
    $response['paginaAtual'] = $pagina;
    $response['totalPaginas'] = $totalPaginas;
    $response['inicio'] = $inicio;
    $response['fim'] = $fim;
    
    return $response;
}

/**
 * Busca uma ordem de serviço específica pelo ID
 * @param int $id ID da ordem de serviço
 * @return array Resposta com os dados da ordem de serviço
 */
function getOrdem($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'ordem' => null
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Consulta para buscar a ordem de serviço
    $sql = "SELECT o.*, c.CLIRAZ, c.CLIDOC, m.MODDES, s.SERDES, co.CONNOM 
            FROM ORDSER o
            LEFT JOIN CADCLI c ON o.OSCLICOD = c.CLICOD
            LEFT JOIN CADMOD m ON o.OSMODCOD = m.MODCOD
            LEFT JOIN CADSER s ON o.OSSERCOD = s.SERCOD
            LEFT JOIN CADCON co ON o.OSCONCOD = co.CONCOD
            WHERE o.OSNUM = :id";
    
    $result = executeQuery($sql, array(':id' => $id));
    
    if ($result === false) {
        $response['message'] = 'Erro ao buscar ordem de serviço.';
        return $response;
    }
    
    if (empty($result)) {
        $response['message'] = 'Ordem de serviço não encontrada.';
        return $response;
    }
    
    // Formatar data para exibição
    if (isset($result[0]['OSDATA'])) {
        $result[0]['OSDATA'] = formatDateFromDB($result[0]['OSDATA']);
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['ordem'] = $result[0];
    
    return $response;
}

/**
 * Cria uma nova ordem de serviço
 * @param array $data Dados da ordem de serviço
 * @return array Resposta com o resultado da operação
 */
function createOrdem($data) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'id' => null
    );
    
    // Validar dados obrigatórios
    if (empty($data['OSCLICOD']) || empty($data['OSDATA']) || 
        empty($data['OSSERCOD']) || empty($data['OSCONCOD'])) {
        $response['message'] = 'Cliente, data, serviço e consultor são obrigatórios.';
        return $response;
    }
    
    // Formatar data para o banco de dados
    $data['OSDATA'] = formatDateToDB($data['OSDATA']);
    
    // Calcular tempo total se não informado
    if (empty($data['OSHTOT']) && !empty($data['OSHINI']) && !empty($data['OSHFIM'])) {
        $tempoTotal = calcularTempoTotal(
            $data['OSHINI'], 
            $data['OSHFIM'], 
            isset($data['OSHDES']) ? $data['OSHDES'] : null, 
            isset($data['OSHTRA']) ? $data['OSHTRA'] : null
        );
        $data['OSHTOT'] = $tempoTotal;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Inserir ordem de serviço
        $sql = "INSERT INTO ORDSER (
                    OSCLICOD, OSMODCOD, OSCLIRES, OSDATA, OSHINI, OSHFIM, 
                    OSHDES, OSHTRA, OSHTOT, OSSERCOD, OSCONCOD, OSDET, OSENV
                ) VALUES (
                    :cliente, :modalidade, :responsavel, :data, :horaInicio, :horaFim,
                    :descontos, :traslado, :tempoTotal, :servico, :consultor, :detalhamento, :enviada
                )";
        
        $params = array(
            ':cliente' => $data['OSCLICOD'],
            ':modalidade' => isset($data['OSMODCOD']) && !empty($data['OSMODCOD']) ? $data['OSMODCOD'] : null,
            ':responsavel' => isset($data['OSCLIRES']) ? $data['OSCLIRES'] : null,
            ':data' => $data['OSDATA'],
            ':horaInicio' => isset($data['OSHINI']) ? $data['OSHINI'] : null,
            ':horaFim' => isset($data['OSHFIM']) ? $data['OSHFIM'] : null,
            ':descontos' => isset($data['OSHDES']) ? $data['OSHDES'] : null,
            ':traslado' => isset($data['OSHTRA']) ? $data['OSHTRA'] : null,
            ':tempoTotal' => isset($data['OSHTOT']) ? $data['OSHTOT'] : null,
            ':servico' => $data['OSSERCOD'],
            ':consultor' => $data['OSCONCOD'],
            ':detalhamento' => isset($data['OSDET']) ? $data['OSDET'] : null,
            ':enviada' => isset($data['OSENV']) ? $data['OSENV'] : 'N'
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao inserir ordem de serviço.');
        }
        
        // Obter ID gerado
        $id = getLastInsertId();
        
        if (!$id) {
            throw new Exception('Erro ao obter ID da ordem de serviço.');
        }
        
        // A tabela RELOS será preenchida automaticamente pelo trigger após a inserção na ORDSER
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Ordem de serviço cadastrada com sucesso.';
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
 * Atualiza uma ordem de serviço existente
 * @param int $id ID da ordem de serviço
 * @param array $data Dados atualizados da ordem de serviço
 * @return array Resposta com o resultado da operação
 */
function updateOrdem($id, $data) {
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
    if (empty($data['OSCLICOD']) || empty($data['OSDATA']) || 
        empty($data['OSSERCOD']) || empty($data['OSCONCOD'])) {
        $response['message'] = 'Cliente, data, serviço e consultor são obrigatórios.';
        return $response;
    }
    
    // Verificar se a OS existe
    $sqlCheck = "SELECT COUNT(*) as total, OSENV FROM ORDSER WHERE OSNUM = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar ordem de serviço.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Ordem de serviço não encontrada.';
        return $response;
    }
    
    // Verificar se a OS pode ser modificada (não enviada)
    if ($resultCheck[0]['OSENV'] === 'S' && (!isset($data['OSENV']) || $data['OSENV'] === 'S')) {
        $response['message'] = 'Esta ordem de serviço já foi enviada e não pode ser alterada.';
        return $response;
    }
    
    // Formatar data para o banco de dados
    $data['OSDATA'] = formatDateToDB($data['OSDATA']);
    
    // Calcular tempo total se não informado
    if (empty($data['OSHTOT']) && !empty($data['OSHINI']) && !empty($data['OSHFIM'])) {
        $tempoTotal = calcularTempoTotal(
            $data['OSHINI'], 
            $data['OSHFIM'], 
            isset($data['OSHDES']) ? $data['OSHDES'] : null, 
            isset($data['OSHTRA']) ? $data['OSHTRA'] : null
        );
        $data['OSHTOT'] = $tempoTotal;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Atualizar ordem de serviço
        $sql = "UPDATE ORDSER SET
                    OSCLICOD = :cliente,
                    OSMODCOD = :modalidade,
                    OSCLIRES = :responsavel,
                    OSDATA = :data,
                    OSHINI = :horaInicio,
                    OSHFIM = :horaFim,
                    OSHDES = :descontos,
                    OSHTRA = :traslado,
                    OSHTOT = :tempoTotal,
                    OSSERCOD = :servico,
                    OSCONCOD = :consultor,
                    OSDET = :detalhamento,
                    OSENV = :enviada
                WHERE OSNUM = :id";
        
        $params = array(
            ':id' => $id,
            ':cliente' => $data['OSCLICOD'],
            ':modalidade' => isset($data['OSMODCOD']) && !empty($data['OSMODCOD']) ? $data['OSMODCOD'] : null,
            ':responsavel' => isset($data['OSCLIRES']) ? $data['OSCLIRES'] : null,
            ':data' => $data['OSDATA'],
            ':horaInicio' => isset($data['OSHINI']) ? $data['OSHINI'] : null,
            ':horaFim' => isset($data['OSHFIM']) ? $data['OSHFIM'] : null,
            ':descontos' => isset($data['OSHDES']) ? $data['OSHDES'] : null,
            ':traslado' => isset($data['OSHTRA']) ? $data['OSHTRA'] : null,
            ':tempoTotal' => isset($data['OSHTOT']) ? $data['OSHTOT'] : null,
            ':servico' => $data['OSSERCOD'],
            ':consultor' => $data['OSCONCOD'],
            ':detalhamento' => isset($data['OSDET']) ? $data['OSDET'] : null,
            ':enviada' => isset($data['OSENV']) ? $data['OSENV'] : 'N'
        );
        
        $result = executeQuery($sql, $params);
        
        if ($result === false) {
            throw new Exception('Erro ao atualizar ordem de serviço.');
        }
        
        // A tabela RELOS será atualizada automaticamente pelo trigger após a atualização na ORDSER
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Ordem de serviço atualizada com sucesso.';
        
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
 * Exclui uma ordem de serviço
 * @param int $id ID da ordem de serviço
 * @return array Resposta com o resultado da operação
 */
function deleteOrdem($id) {
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
    
    // Verificar se a OS existe
    $sqlCheck = "SELECT COUNT(*) as total, OSENV FROM ORDSER WHERE OSNUM = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar ordem de serviço.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Ordem de serviço não encontrada.';
        return $response;
    }
    
    // Verificar se a OS pode ser excluída (não enviada)
    if ($resultCheck[0]['OSENV'] === 'S') {
        $response['message'] = 'Esta ordem de serviço já foi enviada e não pode ser excluída.';
        return $response;
    }
    
    // Iniciar transação
    $conn = beginTransaction();
    
    if (!$conn) {
        $response['message'] = 'Erro ao iniciar transação.';
        return $response;
    }
    
    try {
        // Excluir da tabela RELOS primeiro por causa da FK
        $sqlRelos = "DELETE FROM RELOS WHERE RELOSNUM = :id";
        $resultRelos = executeQuery($sqlRelos, array(':id' => $id));
        
        if ($resultRelos === false) {
            throw new Exception('Erro ao excluir da tabela RELOS.');
        }
        
        // Excluir ordem de serviço
        $sql = "DELETE FROM ORDSER WHERE OSNUM = :id";
        $result = executeQuery($sql, array(':id' => $id));
        
        if ($result === false) {
            throw new Exception('Erro ao excluir ordem de serviço.');
        }
        
        // Confirmar transação
        if (!commitTransaction($conn)) {
            throw new Exception('Erro ao confirmar transação.');
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Ordem de serviço excluída com sucesso.';
        
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
 * Verifica se uma ordem de serviço pode ser modificada
 * @param int $id ID da ordem de serviço
 * @return array Resposta com o resultado da verificação
 */
function canModifyOrdem($id) {
    // Inicializar resposta
    $response = array(
        'canModify' => false,
        'message' => ''
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Verificar se a OS existe
    $sqlCheck = "SELECT OSENV FROM ORDSER WHERE OSNUM = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar ordem de serviço.';
        return $response;
    }
    
    if (empty($resultCheck)) {
        $response['message'] = 'Ordem de serviço não encontrada.';
        return $response;
    }
    
    // Verificar status da OS
    if ($resultCheck[0]['OSENV'] === 'S') {
        $response['message'] = 'Esta ordem de serviço já foi enviada e não pode ser modificada.';
        return $response;
    }
    
    // OS pode ser modificada
    $response['canModify'] = true;
    
    return $response;
}

/**
 * Gera o PDF de uma ordem de serviço
 * @param int $id ID da ordem de serviço
 * @return array Resposta com a URL do PDF
 */
function generatePDF($id) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'pdfUrl' => null
    );
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response['message'] = 'ID inválido.';
        return $response;
    }
    
    // Buscar dados da OS
    $result = getOrdem($id);
    
    if (!$result['success']) {
        $response['message'] = $result['message'];
        return $response;
    }
    
    // Gerar PDF da OS
    $pdfUrl = generateOSPDF($result['ordem']);
    
    if ($pdfUrl === false) {
        $response['message'] = 'Erro ao gerar PDF da ordem de serviço.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['pdfUrl'] = $pdfUrl;
    
    return $response;
}

/**
 * Envia uma ordem de serviço por e-mail
 * @param int $id ID da ordem de serviço
 * @return array Resposta com o resultado do envio
 */
function sendEmail($id) {
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
    
    // Buscar dados da OS
    $result = getOrdem($id);
    
    if (!$result['success']) {
        $response['message'] = $result['message'];
        return $response;
    }
    
    $os = $result['ordem'];
    
    // Verificar se o cliente tem e-mail cadastrado
    if (empty($os['CLIEOS'])) {
        $response['message'] = 'O cliente não possui e-mail para OS cadastrado.';
        return $response;
    }
    
    // Gerar PDF da OS
    $pdfUrl = generateOSPDF($os);
    
    if ($pdfUrl === false) {
        $response['message'] = 'Erro ao gerar PDF da ordem de serviço.';
        return $response;
    }
    
    // Converter URL para caminho do arquivo
    $pdfPath = str_replace(PDF_URL, PDF_DIR, $pdfUrl);
    
    // Enviar e-mail (simulação)
    // Em um ambiente real, seria usado PHPMailer ou outra biblioteca
    $enviado = true;
    
    if ($enviado) {
        // Atualizar status da OS para enviada
        $sql = "UPDATE ORDSER SET OSENV = 'S' WHERE OSNUM = :id";
        $result = executeQuery($sql, array(':id' => $id));
        
        if ($result === false) {
            $response['message'] = 'E-mail enviado, mas ocorreu um erro ao atualizar o status da OS.';
            return $response;
        }
        
        // Retornar resposta
        $response['success'] = true;
        $response['message'] = 'Ordem de serviço enviada por e-mail com sucesso.';
        
        return $response;
    } else {
        $response['message'] = 'Erro ao enviar e-mail.';
        return $response;
    }
}

/**
 * Calcula o tempo total de uma OS
 * @param string $horaInicio Hora de início
 * @param string $horaFim Hora de término
 * @param string $descontos Tempo de descontos
 * @param string $traslado Tempo de traslado
 * @return string Tempo total no formato HH:MM
 */
function calcularTempoTotal($horaInicio, $horaFim, $descontos = null, $traslado = null) {
    // Converter horas para minutos
    $inicioMinutos = timeToMinutes($horaInicio);
    $fimMinutos = timeToMinutes($horaFim);
    $descontosMinutos = $descontos ? timeToMinutes($descontos) : 0;
    $trasladoMinutos = $traslado ? timeToMinutes($traslado) : 0;
    
    // Calcular tempo total em minutos
    $totalMinutos = $fimMinutos - $inicioMinutos - $descontosMinutos + $trasladoMinutos;
    
    // Se o resultado for negativo (ex: trabalho que passa da meia-noite)
    if ($totalMinutos < 0) {
        $totalMinutos += 24 * 60; // Adicionar 24 horas
    }
    
    // Converter de volta para formato HH:MM
    return minutesToTime($totalMinutos);
}

/**
 * Converte tempo no formato HH:MM para minutos
 * @param string $time Tempo no formato HH:MM
 * @return int Tempo em minutos
 */
function timeToMinutes($time) {
    $parts = explode(':', $time);
    return (int)$parts[0] * 60 + (int)$parts[1];
}

/**
 * Converte minutos para o formato HH:MM
 * @param int $minutes Tempo em minutos
 * @return string Tempo no formato HH:MM
 */
function minutesToTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Formata uma data para o formato do banco de dados (YYYY-MM-DD)
 * @param string $date Data no formato DD/MM/YYYY
 * @return string Data no formato YYYY-MM-DD
 */
function formatDateToDB($date) {
    if (empty($date)) {
        return null;
    }
    
    // Verificar se a data está no formato correto
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
        return $date;
    }
    
    // Converter para o formato do banco
    $parts = explode('/', $date);
    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
}

/**
 * Formata uma data do banco de dados para exibição (DD/MM/YYYY)
 * @param string $date Data no formato YYYY-MM-DD
 * @return string Data no formato DD/MM/YYYY
 */
function formatDateFromDB($date) {
    if (empty($date)) {
        return '';
    }
    
    // Verificar se a data está no formato correto
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Converter para o formato de exibição
    $parts = explode('-', $date);
    return $parts[2] . '/' . $parts[1] . '/' . $parts[0];
}
/**
 * Processa requisições para ordens de serviço
 */
function processOSRequest($method, $resource, $id, $params) {
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($resource === 'pdf') {
                    // Gerar PDF da OS
                    return generatePDF($id);
                } else if ($resource === 'can-modify') {
                    // Verificar se pode modificar
                    return canModifyOrdem($id);
                } else {
                    // Buscar OS específica
                    return getOrdem($id);
                }
            } else {
                // Listar OS
                return getOrdens($params);
            }
            
        case 'POST':
            if ($id && $resource === 'send-email') {
                // Enviar OS por e-mail
                return sendEmail($id);
            } else {
                // Criar OS
                return createOrdem($params);
            }
            
        case 'PUT':
            // Atualizar OS
            return updateOrdem($id, $params);
            
        case 'DELETE':
            // Excluir OS
            return deleteOrdem($id);
            
        default:
            return ['success' => false, 'message' => 'Método não permitido'];
    }
}