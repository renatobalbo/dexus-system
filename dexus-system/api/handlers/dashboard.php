<?php
/**
 * Processa requisições para o dashboard
 */
function processDashboardRequest($method, $resource, $id, $params) {
    if ($method === 'GET') {
        if ($resource === 'stats') {
            // Exemplo de resposta simulada para estatísticas do dashboard
            return [
                'success' => true,
                'totalClientes' => 10,
                'osMes' => 5,
                'osPendentes' => 3,
                'osNaoFaturadas' => 7,
                'osMonthly' => [
                    'labels' => ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho'],
                    'values' => [5, 8, 12, 7, 10, 5]
                ],
                'modalidades' => [
                    'labels' => ['Consultoria', 'Desenvolvimento', 'Suporte', 'Treinamento'],
                    'values' => [40, 25, 20, 15]
                ]
            ];
        }
    }
    
    return ['success' => false, 'message' => 'Recurso não encontrado'];
}