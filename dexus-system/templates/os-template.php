<?php
/**
 * Template para impressão de Ordem de Serviço
 * Sistema de Gestão Dexus
 * 
 * Variáveis esperadas:
 * - $os: Dados da OS
 * - $cliente: Dados do cliente
 * - $modalidade: Dados da modalidade
 * - $servico: Dados do serviço
 * - $consultor: Dados do consultor
 */

// Formatar data
$data = isset($os['OSDATA']) ? $os['OSDATA'] : '';
if (!empty($data) && strpos($data, '-') !== false) {
    list($ano, $mes, $dia) = explode('-', $data);
    $data = "$dia/$mes/$ano";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordem de Serviço <?php echo str_pad($os['OSNUM'], 4, '0', STR_PAD_LEFT); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .os-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
        }
        
        .os-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .os-logo {
            width: 150px;
            height: auto;
        }
        
        .os-title {
            text-align: center;
            flex-grow: 1;
            font-size: 20px;
            font-weight: bold;
            margin: 0 20px;
        }
        
        .os-number {
            text-align: right;
            font-weight: bold;
        }
        
        .os-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .os-info-group {
            flex: 1;
            margin-right: 20px;
        }
        
        .os-info-label {
            font-weight: bold;
        }
        
        .os-info-value {
            margin-bottom: 10px;
        }
        
        .os-times {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .os-times th, .os-times td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        
        .os-times th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .os-times tfoot {
            font-weight: bold;
        }
        
        .os-detail {
            margin-bottom: 20px;
        }
        
        .os-detail-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .os-detail-content {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 150px;
        }
        
        .os-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .os-signature {
            text-align: center;
            width: 45%;
        }
        
        .os-signature-line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 5px;
        }
        
        .os-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .os-container {
                border: none;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="os-container">
        <div class="os-header">
            <div class="os-logo">
                <img src="/assets/img/logo.png" alt="Logo Dexus Consultoria" width="150">
            </div>
            <div class="os-title">
                ORDEM DE SERVIÇO
            </div>
            <div class="os-number">
                Nº <?php echo str_pad($os['OSNUM'], 4, '0', STR_PAD_LEFT); ?>
            </div>
        </div>
        
        <div class="os-info">
            <div class="os-info-group">
                <div class="os-info-label">Cliente:</div>
                <div class="os-info-value"><?php echo isset($cliente['CLIRAZ']) ? $cliente['CLIRAZ'] : ''; ?></div>
                
                <div class="os-info-label">CNPJ/CPF:</div>
                <div class="os-info-value"><?php echo isset($cliente['CLIDOC']) ? $cliente['CLIDOC'] : ''; ?></div>
                
                <div class="os-info-label">Modalidade:</div>
                <div class="os-info-value"><?php echo isset($modalidade['MODDES']) ? $modalidade['MODDES'] : ''; ?></div>
            </div>
            
            <div class="os-info-group">
                <div class="os-info-label">Responsável:</div>
                <div class="os-info-value"><?php echo isset($os['OSCLIRES']) ? $os['OSCLIRES'] : ''; ?></div>
                
                <div class="os-info-label">Consultor:</div>
                <div class="os-info-value"><?php echo isset($consultor['CONNOM']) ? $consultor['CONNOM'] : ''; ?></div>
                
                <div class="os-info-label">Data:</div>
                <div class="os-info-value"><?php echo $data; ?></div>
            </div>
        </div>
        
        <div class="os-info">
            <div class="os-info-group">
                <div class="os-info-label">Serviço:</div>
                <div class="os-info-value"><?php echo isset($servico['SERDES']) ? $servico['SERDES'] : ''; ?></div>
            </div>
        </div>
        
        <table class="os-times">
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
                    <td><?php echo isset($os['OSHINI']) ? $os['OSHINI'] : ''; ?></td>
                    <td><?php echo isset($os['OSHFIM']) ? $os['OSHFIM'] : ''; ?></td>
                    <td><?php echo isset($os['OSHDES']) ? $os['OSHDES'] : ''; ?></td>
                    <td><?php echo isset($os['OSHTRA']) ? $os['OSHTRA'] : ''; ?></td>
                    <td><?php echo isset($os['OSHTOT']) ? $os['OSHTOT'] : ''; ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="os-detail">
            <div class="os-detail-title">Detalhamento do Serviço:</div>
            <div class="os-detail-content">
                <?php echo nl2br(isset($os['OSDET']) ? $os['OSDET'] : ''); ?>
            </div>
        </div>
        
        <div class="os-signatures">
            <div class="os-signature">
                <div class="os-signature-line"><?php echo isset($consultor['CONNOM']) ? $consultor['CONNOM'] : ''; ?></div>
                <div>Consultor</div>
            </div>
            
            <div class="os-signature">
                <div class="os-signature-line"><?php echo isset($os['OSCLIRES']) ? $os['OSCLIRES'] : ''; ?></div>
                <div>Cliente</div>
            </div>
        </div>
        
        <div class="os-footer">
            Dexus Consultoria - CNPJ: 00.000.000/0000-00<br>
            Av. Exemplo, 1000 - Bairro - Cidade/UF - CEP: 00000-000<br>
            Tel: (00) 0000-0000 | E-mail: contato@dexus.com.br | www.dexus.com.br
        </div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Fechar</button>
    </div>
</body>
</html>