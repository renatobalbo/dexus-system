<?php
/**
 * API para geração de relatórios
 * Sistema de Gestão Dexus
 */

// Incluir configuração de banco de dados
require_once __DIR__ . '/../../config/database.php';

// Incluir utilitário de PDF
require_once __DIR__ . '/../utils/pdf.php';

/**
 * Busca relação de ordens de serviço para controle
 * @param array $params Parâmetros de filtro e paginação
 * @return array Resposta com a relação de ordens de serviço
 */
function getRelacaoOS($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'relacao' => array(),
        'total' => 0,
        'paginaAtual' => 1,
        'totalPaginas' => 1,
        'inicio' => 0,
        'fim' => 0,
        'estatisticas' => null,
        'filtros' => $params
    );
    
    // Parâmetros de paginação
    $pagina = isset($params['pagina']) ? (int)$params['pagina'] : 1;
    $porPagina = isset($params['porPagina']) ? (int)$params['porPagina'] : 10;
    if ($pagina < 1) $pagina = 1;
    if ($porPagina < 1) $porPagina = 10;
    $inicio = ($pagina - 1) * $porPagina;
    
    // Contruir consulta base
    $sqlBase = "FROM RELOS r
                LEFT JOIN ORDSER o ON r.RELOSNUM = o.OSNUM
                LEFT JOIN CADCLI c ON r.RELCLICOD = c.CLICOD
                LEFT JOIN CADMOD m ON o.OSMODCOD = m.MODCOD
                LEFT JOIN CADSER s ON o.OSSERCOD = s.SERCOD
                LEFT JOIN CADCON co ON o.OSCONCOD = co.CONCOD
                WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['numero'])) {
        $sqlBase .= " AND r.RELOSNUM = :numero";
        $sqlParams[':numero'] = $params['numero'];
    }
    
    if (!empty($params['dataInicial'])) {
        $dataInicial = formatDateToDB($params['dataInicial']);
        $sqlBase .= " AND r.RELOSDATA >= :dataInicial";
        $sqlParams[':dataInicial'] = $dataInicial;
    }
    
    if (!empty($params['dataFinal'])) {
        $dataFinal = formatDateToDB($params['dataFinal']);
        $sqlBase .= " AND r.RELOSDATA <= :dataFinal";
        $sqlParams[':dataFinal'] = $dataFinal;
    }
    
    if (!empty($params['cliente'])) {
        $sqlBase .= " AND r.RELCLICOD = :cliente";
        $sqlParams[':cliente'] = $params['cliente'];
        
        // Buscar nome do cliente para exibição nos filtros
        $sqlCliente = "SELECT CLIRAZ FROM CADCLI WHERE CLICOD = :cliente";
        $resultCliente = executeQuery($sqlCliente, array(':cliente' => $params['cliente']));
        if ($resultCliente !== false && !empty($resultCliente)) {
            $response['filtros']['clienteNome'] = $resultCliente[0]['CLIRAZ'];
        }
    }
    
    if (isset($params['faturado']) && in_array($params['faturado'], ['S', 'N'])) {
        $sqlBase .= " AND r.RELOSFAT = :faturado";
        $sqlParams[':faturado'] = $params['faturado'];
    }
    
    if (isset($params['cobrado']) && in_array($params['cobrado'], ['S', 'N'])) {
        $sqlBase .= " AND r.RELOSCOB = :cobrado";
        $sqlParams[':cobrado'] = $params['cobrado'];
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
    $sql = "SELECT r.*, o.OSCLIRES, o.OSHINI, o.OSHFIM, o.OSHDES, o.OSHTRA, o.OSDET, o.OSENV,
                  c.CLIRAZ, c.CLIDOC, m.MODDES, s.SERDES, co.CONNOM " . 
            $sqlBase . " ORDER BY r.RELOSNUM DESC LIMIT :inicio, :porPagina";
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
    foreach ($result as &$os) {
        if (isset($os['RELOSDATA'])) {
            $os['RELOSDATA'] = formatDateFromDB($os['RELOSDATA']);
        }
    }
    
    // Buscar estatísticas
    $estatisticas = getRelacaoEstatisticas($params);
    
    // Retornar resposta
    $response['success'] = true;
    $response['relacao'] = $result;
    $response['total'] = $total;
    $response['paginaAtual'] = $pagina;
    $response['totalPaginas'] = $totalPaginas;
    $response['inicio'] = $inicio;
    $response['fim'] = $fim;
    $response['estatisticas'] = $estatisticas;
    
    return $response;
}

/**
 * Busca estatísticas da relação de ordens de serviço
 * @param array $params Parâmetros de filtro
 * @return array Estatísticas
 */
function getRelacaoEstatisticas($params = array()) {
    // Inicializar estatísticas
    $estatisticas = array(
        'qtdTotal' => 0,
        'qtdFaturados' => 0,
        'qtdNaoFaturados' => 0,
        'qtdCobrados' => 0,
        'qtdNaoCobrados' => 0,
        'tempoTotal' => '00:00',
        'tempoFaturados' => '00:00',
        'tempoNaoFaturados' => '00:00',
        'tempoCobrados' => '00:00',
        'tempoNaoCobrados' => '00:00',
        'percFaturados' => 0,
        'percNaoFaturados' => 0,
        'percCobrados' => 0,
        'percNaoCobrados' => 0,
        'clientes' => array()
    );
    
    // Contruir consulta base
    $sqlBase = "FROM RELOS r
                LEFT JOIN ORDSER o ON r.RELOSNUM = o.OSNUM
                LEFT JOIN CADCLI c ON r.RELCLICOD = c.CLICOD
                WHERE 1=1";
    $sqlParams = array();
    
    // Adicionar filtros
    if (!empty($params['numero'])) {
        $sqlBase .= " AND r.RELOSNUM = :numero";
        $sqlParams[':numero'] = $params['numero'];
    }
    
    if (!empty($params['dataInicial'])) {
        $dataInicial = formatDateToDB($params['dataInicial']);
        $sqlBase .= " AND r.RELOSDATA >= :dataInicial";
        $sqlParams[':dataInicial'] = $dataInicial;
    }
    
    if (!empty($params['dataFinal'])) {
        $dataFinal = formatDateToDB($params['dataFinal']);
        $sqlBase .= " AND r.RELOSDATA <= :dataFinal";
        $sqlParams[':dataFinal'] = $dataFinal;
    }
    
    if (!empty($params['cliente'])) {
        $sqlBase .= " AND r.RELCLICOD = :cliente";
        $sqlParams[':cliente'] = $params['cliente'];
    }
    
    if (isset($params['faturado']) && in_array($params['faturado'], ['S', 'N'])) {
        $sqlBase .= " AND r.RELOSFAT = :faturado";
        $sqlParams[':faturado'] = $params['faturado'];
    }
    
    if (isset($params['cobrado']) && in_array($params['cobrado'], ['S', 'N'])) {
        $sqlBase .= " AND r.RELOSCOB = :cobrado";
        $sqlParams[':cobrado'] = $params['cobrado'];
    }
    
    // === ESTATÍSTICAS GERAIS ===
    
    // Consulta para contar o total de registros
    $sqlCount = "SELECT COUNT(*) as total " . $sqlBase;
    $resultCount = executeQuery($sqlCount, $sqlParams);
    
    if ($resultCount !== false) {
        $estatisticas['qtdTotal'] = $resultCount[0]['total'];
    }
    
    // Consulta para contar registros faturados
    $sqlFaturados = "SELECT COUNT(*) as total " . $sqlBase . " AND r.RELOSFAT = 'S'";
    $resultFaturados = executeQuery($sqlFaturados, $sqlParams);
    
    if ($resultFaturados !== false) {
        $estatisticas['qtdFaturados'] = $resultFaturados[0]['total'];
    }
    
    // Calcular não faturados
    $estatisticas['qtdNaoFaturados'] = $estatisticas['qtdTotal'] - $estatisticas['qtdFaturados'];
    
    // Consulta para contar registros cobrados
    $sqlCobrados = "SELECT COUNT(*) as total " . $sqlBase . " AND r.RELOSCOB = 'S'";
    $resultCobrados = executeQuery($sqlCobrados, $sqlParams);
    
    if ($resultCobrados !== false) {
        $estatisticas['qtdCobrados'] = $resultCobrados[0]['total'];
    }
    
    // Calcular não cobrados
    $estatisticas['qtdNaoCobrados'] = $estatisticas['qtdTotal'] - $estatisticas['qtdCobrados'];
    
    // === ESTATÍSTICAS DE TEMPO ===
    
    // Função para converter tempo total para minutos
    $convertToMinutes = function($tempoTotal) {
        if (empty($tempoTotal)) {
            return 0;
        }
        
        $partes = explode(':', $tempoTotal);
        if (count($partes) !== 2) {
            return 0;
        }
        
        return (int)$partes[0] * 60 + (int)$partes[1];
    };
    
    // Função para formatar minutos em HH:MM
    $formatMinutes = function($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    };
    
    // Consulta para somar tempo total
    $sqlTempoTotal = "SELECT r.RELOSHTOT " . $sqlBase;
    $resultTempoTotal = executeQuery($sqlTempoTotal, $sqlParams);
    
    $totalMinutos = 0;
    
    if ($resultTempoTotal !== false) {
        foreach ($resultTempoTotal as $row) {
            $totalMinutos += $convertToMinutes($row['RELOSHTOT']);
        }
        
        $estatisticas['tempoTotal'] = $formatMinutes($totalMinutos);
    }
    
    // Consulta para somar tempo total de faturados
    $sqlTempoFaturados = "SELECT r.RELOSHTOT " . $sqlBase . " AND r.RELOSFAT = 'S'";
    $resultTempoFaturados = executeQuery($sqlTempoFaturados, $sqlParams);
    
    $totalMinutosFaturados = 0;
    
    if ($resultTempoFaturados !== false) {
        foreach ($resultTempoFaturados as $row) {
            $totalMinutosFaturados += $convertToMinutes($row['RELOSHTOT']);
        }
        
        $estatisticas['tempoFaturados'] = $formatMinutes($totalMinutosFaturados);
    }
    
    // Calcular tempo não faturado
    $totalMinutosNaoFaturados = $totalMinutos - $totalMinutosFaturados;
    $estatisticas['tempoNaoFaturados'] = $formatMinutes($totalMinutosNaoFaturados);
    
    // Consulta para somar tempo total de cobrados
    $sqlTempoCobrados = "SELECT r.RELOSHTOT " . $sqlBase . " AND r.RELOSCOB = 'S'";
    $resultTempoCobrados = executeQuery($sqlTempoCobrados, $sqlParams);
    
    $totalMinutosCobrados = 0;
    
    if ($resultTempoCobrados !== false) {
        foreach ($resultTempoCobrados as $row) {
            $totalMinutosCobrados += $convertToMinutes($row['RELOSHTOT']);
        }
        
        $estatisticas['tempoCobrados'] = $formatMinutes($totalMinutosCobrados);
    }
    
    // Calcular tempo não cobrado
    $totalMinutosNaoCobrados = $totalMinutos - $totalMinutosCobrados;
    $estatisticas['tempoNaoCobrados'] = $formatMinutes($totalMinutosNaoCobrados);
    
    // Calcular percentuais
    if ($totalMinutos > 0) {
        $estatisticas['percFaturados'] = round(($totalMinutosFaturados / $totalMinutos) * 100);
        $estatisticas['percNaoFaturados'] = round(($totalMinutosNaoFaturados / $totalMinutos) * 100);
        $estatisticas['percCobrados'] = round(($totalMinutosCobrados / $totalMinutos) * 100);
        $estatisticas['percNaoCobrados'] = round(($totalMinutosNaoCobrados / $totalMinutos) * 100);
    }
    
    // === ESTATÍSTICAS POR CLIENTE ===
    
    // Consulta para agrupar por cliente
    $sqlClientes = "SELECT r.RELCLICOD, c.CLIRAZ, SUM(TIME_TO_SEC(r.RELOSHTOT)) as tempo_segundos
                   FROM RELOS r
                   LEFT JOIN CADCLI c ON r.RELCLICOD = c.CLICOD
                   " . str_replace('WHERE 1=1', 'WHERE 1=1 GROUP BY r.RELCLICOD, c.CLIRAZ', $sqlBase);
    
    $resultClientes = executeQuery($sqlClientes, $sqlParams);
    
    if ($resultClientes !== false) {
        foreach ($resultClientes as $row) {
            // Converter segundos para minutos
            $minutos = floor($row['tempo_segundos'] / 60);
            
            $estatisticas['clientes'][] = array(
                'id' => $row['RELCLICOD'],
                'nome' => $row['CLIRAZ'],
                'tempo' => $formatMinutes($minutos)
            );
        }
    }
    
    return $estatisticas;
}

/**
 * Atualiza o status de faturamento de uma OS
 * @param int $id ID da OS
 * @param array $data Dados do faturamento
 * @return array Resposta com o resultado da operação
 */
function updateFaturamento($id, $data) {
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
    
    // Validar dados
    if (!isset($data['faturado']) || !in_array($data['faturado'], ['S', 'N'])) {
        $response['message'] = 'Status de faturamento inválido.';
        return $response;
    }
    
    // Verificar se a OS existe
    $sqlCheck = "SELECT COUNT(*) as total FROM RELOS WHERE RELOSNUM = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar ordem de serviço.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Ordem de serviço não encontrada.';
        return $response;
    }
    
    // Atualizar status de faturamento
    $sql = "UPDATE RELOS SET RELOSFAT = :faturado WHERE RELOSNUM = :id";
    $result = executeQuery($sql, array(':id' => $id, ':faturado' => $data['faturado']));
    
    if ($result === false) {
        $response['message'] = 'Erro ao atualizar status de faturamento.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['message'] = 'Status de faturamento atualizado com sucesso.';
    
    return $response;
}

/**
 * Atualiza o status de cobrança de uma OS
 * @param int $id ID da OS
 * @param array $data Dados da cobrança
 * @return array Resposta com o resultado da operação
 */
function updateCobranca($id, $data) {
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
    
    // Validar dados
    if (!isset($data['cobrado']) || !in_array($data['cobrado'], ['S', 'N'])) {
        $response['message'] = 'Status de cobrança inválido.';
        return $response;
    }
    
    // Verificar se a OS existe
    $sqlCheck = "SELECT COUNT(*) as total FROM RELOS WHERE RELOSNUM = :id";
    $resultCheck = executeQuery($sqlCheck, array(':id' => $id));
    
    if ($resultCheck === false) {
        $response['message'] = 'Erro ao verificar ordem de serviço.';
        return $response;
    }
    
    if ($resultCheck[0]['total'] == 0) {
        $response['message'] = 'Ordem de serviço não encontrada.';
        return $response;
    }
    
    // Atualizar status de cobrança
    $sql = "UPDATE RELOS SET RELOSCOB = :cobrado WHERE RELOSNUM = :id";
    $result = executeQuery($sql, array(':id' => $id, ':cobrado' => $data['cobrado']));
    
    if ($result === false) {
        $response['message'] = 'Erro ao atualizar status de cobrança.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['message'] = 'Status de cobrança atualizado com sucesso.';
    
    return $response;
}

/**
 * Gera um relatório em PDF da relação de OS
 * @param array $params Parâmetros de filtro
 * @return array Resposta com a URL do PDF
 */
function generateRelatorioRelacaoPDF($params = array()) {
    // Inicializar resposta
    $response = array(
        'success' => false,
        'message' => '',
        'pdfUrl' => null
    );
    
    // Buscar relação de OS
    $result = getRelacaoOS($params);
    
    if (!$result['success']) {
        $response['message'] = 'Erro ao buscar dados da relação de OS.';
        return $response;
    }
    
    // Gerar PDF da relação
    $pdfUrl = generateRelacaoPDF($result);
    
    if ($pdfUrl === false) {
        $response['message'] = 'Erro ao gerar PDF da relação de OS.';
        return $response;
    }
    
    // Retornar resposta
    $response['success'] = true;
    $response['pdfUrl'] = $pdfUrl;
    
    return $response;
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
 * Processa requisições para relatórios e relação de OS
 */
function processRelacaoRequest($method, $resource, $id, $params) {
    switch ($method) {
        case 'GET':
            if ($resource === 'estatisticas') {
                // Buscar estatísticas da relação
                return getRelacaoEstatisticas($params);
            } else {
                // Buscar relação de OS
                return getRelacaoOS($params);
            }
            
        case 'PUT':
            if ($id && $resource === 'faturamento') {
                // Atualizar status de faturamento
                return updateFaturamento($id, $params);
            } else if ($id && $resource === 'cobranca') {
                // Atualizar status de cobrança
                return updateCobranca($id, $params);
            } else {
                return ['success' => false, 'message' => 'Recurso não encontrado'];
            }
            
        case 'POST':
            if ($resource === 'pdf') {
                // Gerar relatório PDF
                return generateRelatorioRelacaoPDF($params);
            } else {
                return ['success' => false, 'message' => 'Recurso não encontrado'];
            }
            
        default:
            return ['success' => false, 'message' => 'Método não permitido'];
    }
}