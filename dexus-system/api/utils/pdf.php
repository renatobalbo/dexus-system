<?php
/**
 * Funções utilitárias para geração de PDFs
 * Sistema de Gestão Dexus
 * 
 * Obs: Esta implementação utiliza a biblioteca mPDF.
 * É necessário instalar a biblioteca via Composer:
 * composer require mpdf/mpdf
 */

// Verificar se a biblioteca mPDF está instalada
if (!class_exists('\\Mpdf\\Mpdf')) {
    // Caso não esteja utilizando o Composer, podemos registrar um autoloader simples
    spl_autoload_register(function ($class) {
        // Verificar se é uma classe mPDF
        if (strpos($class, 'Mpdf\\') === 0) {
            $file = __DIR__ . '/../../vendor/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    });
}

// Definir constantes
define('PDF_DIR', ROOT_DIR . '/tmp/pdf');
define('PDF_URL', '/tmp/pdf');

// Criar diretório para armazenar os PDFs temporários
if (!file_exists(PDF_DIR)) {
    mkdir(PDF_DIR, 0777, true);
}

/**
 * Gera um PDF a partir de HTML
 * @param string $html Conteúdo HTML
 * @param array $options Opções de configuração do PDF
 * @return string|false Caminho do arquivo PDF gerado ou false em caso de erro
 */
function generatePDF($html, $options = array()) {
    try {
        // Verificar se a biblioteca mPDF está disponível
        if (!class_exists('\\Mpdf\\Mpdf')) {
            throw new Exception('A biblioteca mPDF não está instalada.');
        }
        
        // Definir opções padrão
        $defaultOptions = array(
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9
        );
        
        // Mesclar opções
        $options = array_merge($defaultOptions, $options);
        
        // Configurar mPDF
        $mpdf = new \Mpdf\Mpdf([
            'format' => $options['format'],
            'orientation' => $options['orientation'],
            'margin_left' => $options['margin_left'],
            'margin_right' => $options['margin_right'],
            'margin_top' => $options['margin_top'],
            'margin_bottom' => $options['margin_bottom'],
            'margin_header' => $options['margin_header'],
            'margin_footer' => $options['margin_footer'],
            'tempDir' => PDF_DIR
        ]);
        
        // Configurar metadados do PDF
        if (isset($options['title'])) {
            $mpdf->SetTitle($options['title']);
        }
        
        if (isset($options['author'])) {
            $mpdf->SetAuthor($options['author']);
        }
        
        if (isset($options['creator'])) {
            $mpdf->SetCreator($options['creator']);
        }
        
        // Configurar cabeçalho e rodapé
        if (isset($options['header'])) {
            $mpdf->SetHTMLHeader($options['header']);
        }
        
        if (isset($options['footer'])) {
            $mpdf->SetHTMLFooter($options['footer']);
        }
        
        // Adicionar CSS
        if (isset($options['css'])) {
            $mpdf->WriteHTML($options['css'], \Mpdf\HTMLParserMode::HEADER_CSS);
        }
        
        // Adicionar conteúdo HTML
        $mpdf->WriteHTML($html);
        
        // Gerar nome do arquivo
        $filename = isset($options['filename']) ? $options['filename'] : 'document_' . date('YmdHis') . '.pdf';
        $filepath = PDF_DIR . '/' . $filename;
        
        // Salvar arquivo
        $mpdf->Output($filepath, 'F');
        
        return $filepath;
    } catch (Exception $e) {
        // Registrar erro
        error_log('Erro ao gerar PDF: ' . $e->getMessage());
        
        // Retornar false em caso de erro
        return false;
    }
}

/**
 * Gera uma URL para um arquivo PDF
 * @param string $filepath Caminho do arquivo PDF
 * @return string URL do arquivo PDF
 */
function getPDFUrl($filepath) {
    // Converter caminho para URL
    $relativePath = str_replace(PDF_DIR, '', $filepath);
    return PDF_URL . $relativePath;
}

/**
 * Gera um PDF de OS
 * @param array $os Dados da OS
 * @param array $options Opções de configuração do PDF
 * @return string|false URL do arquivo PDF gerado ou false em caso de erro
 */
function generateOSPDF($os, $options = array()) {
    try {
        // Verificar se a OS é válida
        if (!isset($os['OSNUM'])) {
            throw new Exception('OS inválida.');
        }
        
        // Obter dados adicionais da OS
        $cliente = array();
        $modalidade = array();
        $servico = array();
        $consultor = array();
        
        // Dados do cliente
        if (isset($os['OSCLICOD'])) {
            $sql = "SELECT * FROM CADCLI WHERE CLICOD = :id";
            $result = executeQuery($sql, array(':id' => $os['OSCLICOD']));
            
            if ($result !== false && !empty($result)) {
                $cliente = $result[0];
            }
        }
        
        // Dados da modalidade
        if (isset($os['OSMODCOD'])) {
            $sql = "SELECT * FROM CADMOD WHERE MODCOD = :id";
            $result = executeQuery($sql, array(':id' => $os['OSMODCOD']));
            
            if ($result !== false && !empty($result)) {
                $modalidade = $result[0];
            }
        }
        
        // Dados do serviço
        if (isset($os['OSSERCOD'])) {
            $sql = "SELECT * FROM CADSER WHERE SERCOD = :id";
            $result = executeQuery($sql, array(':id' => $os['OSSERCOD']));
            
            if ($result !== false && !empty($result)) {
                $servico = $result[0];
            }
        }
        
        // Dados do consultor
        if (isset($os['OSCONCOD'])) {
            $sql = "SELECT * FROM CADCON WHERE CONCOD = :id";
            $result = executeQuery($sql, array(':id' => $os['OSCONCOD']));
            
            if ($result !== false && !empty($result)) {
                $consultor = $result[0];
            }
        }
        
        // Formatar data
        $data = isset($os['OSDATA']) ? $os['OSDATA'] : '';
        if (!empty($data) && strpos($data, '-') !== false) {
            list($ano, $mes, $dia) = explode('-', $data);
            $data = "$dia/$mes/$ano";
        }
        
        // Definir opções do PDF
        $pdfOptions = array(
            'format' => 'A4',
            'orientation' => 'P',
            'title' => 'Ordem de Serviço #' . $os['OSNUM'],
            'author' => 'Sistema de Gestão Dexus',
            'creator' => 'Sistema de Gestão Dexus',
            'filename' => 'os_' . $os['OSNUM'] . '.pdf'
        );
        
        // Mesclar opções personalizadas
        $pdfOptions = array_merge($pdfOptions, $options);
        
        // CSS do documento
        $css = '
            body {
                font-family: Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.5;
                color: #000;
            }
            .os-container {
                border: 1px solid #000;
                padding: 20px;
                margin: 0 auto;
            }
            .os-header {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
                display: flex;
                align-items: center;
            }
            .os-logo {
                width: 150px;
                height: auto;
            }
            .os-title {
                font-size: 24pt;
                font-weight: bold;
                text-align: center;
                flex-grow: 1;
            }
            .os-number {
                font-weight: bold;
                font-size: 16pt;
            }
            .os-info {
                margin-bottom: 20px;
            }
            .os-info-label {
                font-weight: bold;
            }
            .os-info-group {
                margin-bottom: 10px;
            }
            .os-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .os-table th, .os-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: center;
            }
            .os-table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .os-detail-title {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .os-detail-content {
                border: 1px solid #000;
                padding: 10px;
                min-height: 100px;
                margin-bottom: 20px;
            }
            .os-signatures {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
            }
            .os-signature {
                width: 45%;
                text-align: center;
            }
            .os-signature-line {
                border-top: 1px solid #000;
                padding-top: 5px;
                margin-top: 50px;
            }
            .os-footer {
                margin-top: 50px;
                text-align: center;
                font-size: 9pt;
                color: #666;
            }
        ';
        
        $pdfOptions['css'] = $css;
        
        // Gerar HTML da OS
        $html = '
        <div class="os-container">
            <div class="os-header">
                <div class="os-logo">
                    <img src="' . ROOT_DIR . '/assets/img/logo.png" width="150">
                </div>
                <div class="os-title">
                    ORDEM DE SERVIÇO
                </div>
                <div class="os-number">
                    Nº ' . str_pad($os['OSNUM'], 4, '0', STR_PAD_LEFT) . '
                </div>
            </div>
            
            <div class="os-info">
                <div class="os-info-group">
                    <span class="os-info-label">Cliente:</span> ' . (isset($cliente['CLIRAZ']) ? $cliente['CLIRAZ'] : '') . '<br>
                    <span class="os-info-label">CNPJ/CPF:</span> ' . (isset($cliente['CLIDOC']) ? $cliente['CLIDOC'] : '') . '<br>
                    <span class="os-info-label">Modalidade:</span> ' . (isset($modalidade['MODDES']) ? $modalidade['MODDES'] : '') . '
                </div>
                
                <div class="os-info-group">
                    <span class="os-info-label">Responsável:</span> ' . (isset($os['OSCLIRES']) ? $os['OSCLIRES'] : '') . '<br>
                    <span class="os-info-label">Consultor:</span> ' . (isset($consultor['CONNOM']) ? $consultor['CONNOM'] : '') . '<br>
                    <span class="os-info-label">Data:</span> ' . $data . '
                </div>
                
                <div class="os-info-group">
                    <span class="os-info-label">Serviço:</span> ' . (isset($servico['SERDES']) ? $servico['SERDES'] : '') . '
                </div>
            </div>
            
            <table class="os-table">
                <thead>
                    <tr>
                        <th>Hora Início</th>
                        <th>Hora Fim</th>
                        <th>Descontos</th>
                        <th>Traslado</th>
                        <th>Tempo Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . (isset($os['OSHINI']) ? $os['OSHINI'] : '') . '</td>
                        <td>' . (isset($os['OSHFIM']) ? $os['OSHFIM'] : '') . '</td>
                        <td>' . (isset($os['OSHDES']) ? $os['OSHDES'] : '') . '</td>
                        <td>' . (isset($os['OSHTRA']) ? $os['OSHTRA'] : '') . '</td>
                        <td>' . (isset($os['OSHTOT']) ? $os['OSHTOT'] : '') . '</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="os-detail">
                <div class="os-detail-title">Detalhamento do Serviço:</div>
                <div class="os-detail-content">
                    ' . nl2br(isset($os['OSDET']) ? $os['OSDET'] : '') . '
                </div>
            </div>
            
            <div class="os-signatures">
                <div class="os-signature">
                    <div class="os-signature-line">
                        ' . (isset($consultor['CONNOM']) ? $consultor['CONNOM'] : '') . '
                    </div>
                    <div>Consultor</div>
                </div>
                
                <div class="os-signature">
                    <div class="os-signature-line">
                        ' . (isset($os['OSCLIRES']) ? $os['OSCLIRES'] : '') . '
                    </div>
                    <div>Cliente</div>
                </div>
            </div>
            
            <div class="os-footer">
                Dexus Consultoria - CNPJ: 00.000.000/0000-00<br>
                Av. Exemplo, 1000 - Bairro - Cidade/UF - CEP: 00000-000<br>
                Tel: (00) 0000-0000 | E-mail: contato@dexus.com.br | www.dexus.com.br
            </div>
        </div>
        ';
        
        // Gerar PDF
        $filepath = generatePDF($html, $pdfOptions);
        
        if ($filepath === false) {
            throw new Exception('Erro ao gerar PDF da OS.');
        }
        
        // Retornar URL do PDF
        return getPDFUrl($filepath);
    } catch (Exception $e) {
        // Registrar erro
        error_log('Erro ao gerar PDF da OS: ' . $e->getMessage());
        
        // Retornar false em caso de erro
        return false;
    }
}

/**
 * Gera um PDF com a relação de OS
 * @param array $relacao Dados da relação de OS
 * @param array $options Opções de configuração do PDF
 * @return string|false URL do arquivo PDF gerado ou false em caso de erro
 */
function generateRelacaoPDF($relacao, $options = array()) {
    try {
        // Verificar se a relação é válida
        if (!isset($relacao['ordens']) || !is_array($relacao['ordens'])) {
            throw new Exception('Relação de OS inválida.');
        }
        
        // Definir opções do PDF
        $pdfOptions = array(
            'format' => 'A4',
            'orientation' => 'L', // Paisagem
            'title' => 'Relação de Ordens de Serviço',
            'author' => 'Sistema de Gestão Dexus',
            'creator' => 'Sistema de Gestão Dexus',
            'filename' => 'relacao_os_' . date('YmdHis') . '.pdf'
        );
        
        // Mesclar opções personalizadas
        $pdfOptions = array_merge($pdfOptions, $options);
        
        // CSS do documento
        $css = '
            body {
                font-family: Arial, sans-serif;
                font-size: 10pt;
                line-height: 1.3;
                color: #000;
            }
            h1 {
                font-size: 16pt;
                font-weight: bold;
                text-align: center;
                margin-bottom: 20px;
            }
            .filtros {
                margin-bottom: 20px;
                font-size: 9pt;
            }
            .filtro {
                margin-right: 20px;
                display: inline-block;
            }
            .filtro-label {
                font-weight: bold;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            table th, table td {
                border: 1px solid #000;
                padding: 6px;
            }
            table th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: center;
            }
            tr.faturado {
                background-color: #e6ffe6;
            }
            tr.nao-faturado {
                background-color: #fff6e6;
            }
            .total-row {
                font-weight: bold;
                background-color: #f2f2f2;
            }
            .footer {
                text-align: center;
                font-size: 8pt;
                color: #666;
                margin-top: 30px;
            }
            .page-number {
                text-align: right;
                font-size: 8pt;
            }
        ';
        
        $pdfOptions['css'] = $css;
        
        // Cabeçalho com paginação
        $pdfOptions['header'] = '
            <div class="page-number">
                Página {PAGENO} de {nbpg}
            </div>
        ';
        
        // Rodapé
        $pdfOptions['footer'] = '
            <div class="footer">
                Dexus Consultoria - Relatório gerado em ' . date('d/m/Y H:i:s') . '
            </div>
        ';
        
        // Construir filtros
        $filtrosHtml = '<div class="filtros">';
        
        if (isset($relacao['filtros'])) {
            $filtros = $relacao['filtros'];
            
            // Data
            if (!empty($filtros['dataInicial']) || !empty($filtros['dataFinal'])) {
                $filtrosHtml .= '<span class="filtro"><span class="filtro-label">Período:</span> ';
                
                if (!empty($filtros['dataInicial']) && !empty($filtros['dataFinal'])) {
                    $filtrosHtml .= $filtros['dataInicial'] . ' até ' . $filtros['dataFinal'];
                } else if (!empty($filtros['dataInicial'])) {
                    $filtrosHtml .= 'A partir de ' . $filtros['dataInicial'];
                } else {
                    $filtrosHtml .= 'Até ' . $filtros['dataFinal'];
                }
                
                $filtrosHtml .= '</span>';
            }
            
            // Cliente
            if (!empty($filtros['cliente'])) {
                $filtrosHtml .= '<span class="filtro"><span class="filtro-label">Cliente:</span> ' . $filtros['clienteNome'] . '</span>';
            }
            
            // Faturado
            if (isset($filtros['faturado'])) {
                $faturadoText = $filtros['faturado'] === 'S' ? 'Sim' : 'Não';
                $filtrosHtml .= '<span class="filtro"><span class="filtro-label">Faturado:</span> ' . $faturadoText . '</span>';
            }
            
            // Cobrado
            if (isset($filtros['cobrado'])) {
                $cobradoText = $filtros['cobrado'] === 'S' ? 'Sim' : 'Não';
                $filtrosHtml .= '<span class="filtro"><span class="filtro-label">Cobrado:</span> ' . $cobradoText . '</span>';
            }
        }
        
        $filtrosHtml .= '</div>';
        
        // Iniciar HTML
        $html = '
        <h1>Relação de Ordens de Serviço</h1>
        
        ' . $filtrosHtml . '
        
        <table>
            <thead>
                <tr>
                    <th>Nº OS</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Consultor</th>
                    <th>Tempo Total</th>
                    <th>Faturado</th>
                    <th>Cobrado</th>
                </tr>
            </thead>
            <tbody>
        ';
        
        // Variáveis para totalização
        $totalTempo = 0;
        $totalFaturados = 0;
        $totalNaoFaturados = 0;
        $totalCobrados = 0;
        $totalNaoCobrados = 0;
        
        // Adicionar cada OS
        foreach ($relacao['ordens'] as $os) {
            // Formatar data
            $data = isset($os['RELOSDATA']) ? $os['RELOSDATA'] : '';
            if (!empty($data) && strpos($data, '-') !== false) {
                list($ano, $mes, $dia) = explode('-', $data);
                $data = "$dia/$mes/$ano";
            }
            
            // Calcular status para estilo da linha
            $faturado = isset($os['RELOSFAT']) && $os['RELOSFAT'] === 'S';
            $cobrado = isset($os['RELOSCOB']) && $os['RELOSCOB'] === 'S';
            $rowClass = $faturado ? 'faturado' : 'nao-faturado';
            
            // Incrementar totalizadores
            if (isset($os['RELOSHTOT'])) {
                // Converter tempo total para minutos
                $tempoPartes = explode(':', $os['RELOSHTOT']);
                $tempoMinutos = (int)$tempoPartes[0] * 60 + (int)$tempoPartes[1];
                $totalTempo += $tempoMinutos;
                
                if ($faturado) {
                    $totalFaturados += $tempoMinutos;
                } else {
                    $totalNaoFaturados += $tempoMinutos;
                }
                
                if ($cobrado) {
                    $totalCobrados += $tempoMinutos;
                } else {
                    $totalNaoCobrados += $tempoMinutos;
                }
            }
            
            // Adicionar linha
            $html .= '
                <tr class="' . $rowClass . '">
                    <td style="text-align: center;">' . (isset($os['RELOSNUM']) ? $os['RELOSNUM'] : '') . '</td>
                    <td style="text-align: center;">' . $data . '</td>
                    <td>' . (isset($os['CLIRAZ']) ? $os['CLIRAZ'] : '') . '</td>
                    <td>' . (isset($os['SERDES']) ? $os['SERDES'] : '') . '</td>
                    <td>' . (isset($os['CONNOM']) ? $os['CONNOM'] : '') . '</td>
                    <td style="text-align: center;">' . (isset($os['RELOSHTOT']) ? $os['RELOSHTOT'] : '') . '</td>
                    <td style="text-align: center;">' . ($faturado ? 'Sim' : 'Não') . '</td>
                    <td style="text-align: center;">' . ($cobrado ? 'Sim' : 'Não') . '</td>
                </tr>
            ';
        }
        
        // Converter totais de minutos para formato HH:MM
        $totalTempoFormatado = sprintf('%02d:%02d', floor($totalTempo / 60), $totalTempo % 60);
        $totalFaturadosFormatado = sprintf('%02d:%02d', floor($totalFaturados / 60), $totalFaturados % 60);
        $totalNaoFaturadosFormatado = sprintf('%02d:%02d', floor($totalNaoFaturados / 60), $totalNaoFaturados % 60);
        $totalCobradosFormatado = sprintf('%02d:%02d', floor($totalCobrados / 60), $totalCobrados % 60);
        $totalNaoCobradosFormatado = sprintf('%02d:%02d', floor($totalNaoCobrados / 60), $totalNaoCobrados % 60);
        
        // Adicionar linha de total
        $html .= '
                <tr class="total-row">
                    <td colspan="5" style="text-align: right;">Total:</td>
                    <td style="text-align: center;">' . $totalTempoFormatado . '</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        
        <h2>Resumo</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Quantidade</th>
                    <th>Tempo Total</th>
                    <th>Percentual</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Faturados</td>
                    <td style="text-align: center;">' . $relacao['estatisticas']['qtdFaturados'] . '</td>
                    <td style="text-align: center;">' . $totalFaturadosFormatado . '</td>
                    <td style="text-align: center;">' . ($totalTempo > 0 ? round($totalFaturados / $totalTempo * 100) : 0) . '%</td>
                </tr>
                <tr>
                    <td>Não Faturados</td>
                    <td style="text-align: center;">' . $relacao['estatisticas']['qtdNaoFaturados'] . '</td>
                    <td style="text-align: center;">' . $totalNaoFaturadosFormatado . '</td>
                    <td style="text-align: center;">' . ($totalTempo > 0 ? round($totalNaoFaturados / $totalTempo * 100) : 0) . '%</td>
                </tr>
                <tr>
                    <td>Cobrados</td>
                    <td style="text-align: center;">' . $relacao['estatisticas']['qtdCobrados'] . '</td>
                    <td style="text-align: center;">' . $totalCobradosFormatado . '</td>
                    <td style="text-align: center;">' . ($totalTempo > 0 ? round($totalCobrados / $totalTempo * 100) : 0) . '%</td>
                </tr>
                <tr>
                    <td>Não Cobrados</td>
                    <td style="text-align: center;">' . $relacao['estatisticas']['qtdNaoCobrados'] . '</td>
                    <td style="text-align: center;">' . $totalNaoCobradosFormatado . '</td>
                    <td style="text-align: center;">' . ($totalTempo > 0 ? round($totalNaoCobrados / $totalTempo * 100) : 0) . '%</td>
                </tr>
                <tr class="total-row">
                    <td>Total</td>
                    <td style="text-align: center;">' . $relacao['estatisticas']['qtdTotal'] . '</td>
                    <td style="text-align: center;">' . $totalTempoFormatado . '</td>
                    <td style="text-align: center;">100%</td>
                </tr>
            </tbody>
        </table>
        ';
        
        // Gerar PDF
        $filepath = generatePDF($html, $pdfOptions);
        
        if ($filepath === false) {
            throw new Exception('Erro ao gerar PDF da relação de OS.');
        }
        
        // Retornar URL do PDF
        return getPDFUrl($filepath);
    } catch (Exception $e) {
        // Registrar erro
        error_log('Erro ao gerar PDF da relação de OS: ' . $e->getMessage());
        
        // Retornar false em caso de erro
        return false;
    }
}

/**
 * Limpa arquivos PDF temporários antigos
 * @param int $maxAge Idade máxima dos arquivos em segundos (padrão: 24 horas)
 * @return int Número de arquivos removidos
 */
function cleanupPDFFiles($maxAge = 86400) {
    $count = 0;
    
    // Verificar se o diretório existe
    if (!file_exists(PDF_DIR)) {
        return $count;
    }
    
    // Abrir diretório
    $handle = opendir(PDF_DIR);
    if (!$handle) {
        return $count;
    }
    
    // Tempo limite
    $limit = time() - $maxAge;
    
    // Percorrer arquivos
    while (($file = readdir($handle)) !== false) {
        // Ignorar diretórios
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filepath = PDF_DIR . '/' . $file;
        
        // Verificar se é um arquivo PDF
        if (is_file($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'pdf') {
            // Verificar idade do arquivo
            $fileTime = filemtime($filepath);
            
            if ($fileTime && $fileTime < $limit) {
                // Remover arquivo
                if (unlink($filepath)) {
                    $count++;
                }
            }
        }
    }
    
    // Fechar diretório
    closedir($handle);
    
    return $count;
}