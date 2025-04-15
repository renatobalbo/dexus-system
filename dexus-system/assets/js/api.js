/**
 * Sistema de Gestão Dexus - API
 * Responsável pela comunicação com o backend
 */

// URL base da API
const API_BASE_URL = '/dexus-system/api';

/**
 * Realiza uma requisição GET para a API
 * @param {string} endpoint - Endpoint da API
 * @param {Object} params - Parâmetros da requisição (opcional)
 * @returns {Promise} - Promessa com a resposta da requisição
 */
function apiGet(endpoint, params = {}) {
    // Construir URL com parâmetros
    const url = new URL(`${API_BASE_URL}/${endpoint}`, window.location.origin);
    
    // Adicionar parâmetros à URL
    if (Object.keys(params).length > 0) {
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
    }
    
    // Realizar requisição
    return fetch(url.toString(), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
        }
        return response.json();
    });
}

/**
 * Realiza uma requisição POST para a API
 * @param {string} endpoint - Endpoint da API
 * @param {Object} data - Dados a serem enviados
 * @returns {Promise} - Promessa com a resposta da requisição
 */
function apiPost(endpoint, data = {}) {
    return fetch(`${API_BASE_URL}/${endpoint}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
        }
        return response.json();
    });
}

/**
 * Realiza uma requisição PUT para a API
 * @param {string} endpoint - Endpoint da API
 * @param {Object} data - Dados a serem enviados
 * @returns {Promise} - Promessa com a resposta da requisição
 */
function apiPut(endpoint, data = {}) {
    return fetch(`${API_BASE_URL}/${endpoint}`, {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
        }
        return response.json();
    });
}

/**
 * Realiza uma requisição DELETE para a API
 * @param {string} endpoint - Endpoint da API
 * @param {Object} params - Parâmetros da requisição (opcional)
 * @returns {Promise} - Promessa com a resposta da requisição
 */
function apiDelete(endpoint, params = {}) {
    // Construir URL com parâmetros
    const url = new URL(`${API_BASE_URL}/${endpoint}`, window.location.origin);
    
    // Adicionar parâmetros à URL
    if (Object.keys(params).length > 0) {
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
    }
    
    // Realizar requisição
    return fetch(url.toString(), {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
        }
        return response.json();
    });
}

// === API de Dashboard ===

/**
 * Busca estatísticas para o dashboard
 * @returns {Promise} - Promessa com as estatísticas
 */
function fetchDashboardStats() {
    return apiGet('dashboard/stats');
}

// === API de Clientes ===

/**
 * Busca lista de clientes
 * @param {Object} params - Parâmetros de filtro e paginação
 * @returns {Promise} - Promessa com a lista de clientes
 */
function fetchClientes(params = {}) {
    return apiGet('clientes', params);
}

/**
 * Busca um cliente específico
 * @param {number} id - ID do cliente
 * @returns {Promise} - Promessa com os dados do cliente
 */
function fetchCliente(id) {
    return apiGet(`clientes/${id}`);
}

/**
 * Cria um novo cliente
 * @param {Object} data - Dados do cliente
 * @returns {Promise} - Promessa com a resposta da criação
 */
function createCliente(data) {
    return apiPost('clientes', data);
}

/**
 * Atualiza um cliente existente
 * @param {number} id - ID do cliente
 * @param {Object} data - Dados atualizados do cliente
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateCliente(id, data) {
    return apiPut(`clientes/${id}`, data);
}

/**
 * Exclui um cliente
 * @param {number} id - ID do cliente
 * @returns {Promise} - Promessa com a resposta da exclusão
 */
function deleteCliente(id) {
    return apiDelete(`clientes/${id}`);
}

/**
 * Verifica se um cliente pode ser excluído
 * @param {number} id - ID do cliente
 * @returns {Promise} - Promessa com o resultado da verificação
 */
function canDeleteCliente(id) {
    return apiGet(`clientes/${id}/can-delete`);
}

/**
 * Realiza a consulta de dados de CPF/CNPJ
 * @param {string} documento - Número do CPF/CNPJ
 * @param {string} tipo - Tipo de documento (F ou J)
 * @returns {Promise} - Promessa com os dados consultados
 */
function consultarDocumento(documento, tipo) {
    return apiGet('consulta/documento', { documento, tipo });
}

// === API de Serviços ===

/**
 * Busca lista de serviços
 * @param {Object} params - Parâmetros de filtro e paginação
 * @returns {Promise} - Promessa com a lista de serviços
 */
function fetchServicos(params = {}) {
    return apiGet('servicos', params);
}

/**
 * Busca um serviço específico
 * @param {number} id - ID do serviço
 * @returns {Promise} - Promessa com os dados do serviço
 */
function fetchServico(id) {
    return apiGet(`servicos/${id}`);
}

/**
 * Cria um novo serviço
 * @param {Object} data - Dados do serviço
 * @returns {Promise} - Promessa com a resposta da criação
 */
function createServico(data) {
    return apiPost('servicos', data);
}

/**
 * Atualiza um serviço existente
 * @param {number} id - ID do serviço
 * @param {Object} data - Dados atualizados do serviço
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateServico(id, data) {
    return apiPut(`servicos/${id}`, data);
}

/**
 * Exclui um serviço
 * @param {number} id - ID do serviço
 * @returns {Promise} - Promessa com a resposta da exclusão
 */
function deleteServico(id) {
    return apiDelete(`servicos/${id}`);
}

/**
 * Verifica se um serviço pode ser excluído
 * @param {number} id - ID do serviço
 * @returns {Promise} - Promessa com o resultado da verificação
 */
function canDeleteServico(id) {
    return apiGet(`servicos/${id}/can-delete`);
}

// === API de Modalidades ===

/**
 * Busca lista de modalidades
 * @param {Object} params - Parâmetros de filtro e paginação
 * @returns {Promise} - Promessa com a lista de modalidades
 */
function fetchModalidades(params = {}) {
    return apiGet('modalidades', params);
}

/**
 * Busca uma modalidade específica
 * @param {number} id - ID da modalidade
 * @returns {Promise} - Promessa com os dados da modalidade
 */
function fetchModalidade(id) {
    return apiGet(`modalidades/${id}`);
}

/**
 * Cria uma nova modalidade
 * @param {Object} data - Dados da modalidade
 * @returns {Promise} - Promessa com a resposta da criação
 */
function createModalidade(data) {
    return apiPost('modalidades', data);
}

/**
 * Atualiza uma modalidade existente
 * @param {number} id - ID da modalidade
 * @param {Object} data - Dados atualizados da modalidade
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateModalidade(id, data) {
    return apiPut(`modalidades/${id}`, data);
}

/**
 * Exclui uma modalidade
 * @param {number} id - ID da modalidade
 * @returns {Promise} - Promessa com a resposta da exclusão
 */
function deleteModalidade(id) {
    return apiDelete(`modalidades/${id}`);
}

/**
 * Verifica se uma modalidade pode ser excluída
 * @param {number} id - ID da modalidade
 * @returns {Promise} - Promessa com o resultado da verificação
 */
function canDeleteModalidade(id) {
    return apiGet(`modalidades/${id}/can-delete`);
}

// === API de Consultores ===

/**
 * Busca lista de consultores
 * @param {Object} params - Parâmetros de filtro e paginação
 * @returns {Promise} - Promessa com a lista de consultores
 */
function fetchConsultores(params = {}) {
    return apiGet('consultores', params);
}

/**
 * Busca um consultor específico
 * @param {number} id - ID do consultor
 * @returns {Promise} - Promessa com os dados do consultor
 */
function fetchConsultor(id) {
    return apiGet(`consultores/${id}`);
}

/**
 * Cria um novo consultor
 * @param {Object} data - Dados do consultor
 * @returns {Promise} - Promessa com a resposta da criação
 */
function createConsultor(data) {
    return apiPost('consultores', data);
}

/**
 * Atualiza um consultor existente
 * @param {number} id - ID do consultor
 * @param {Object} data - Dados atualizados do consultor
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateConsultor(id, data) {
    return apiPut(`consultores/${id}`, data);
}

/**
 * Exclui um consultor
 * @param {number} id - ID do consultor
 * @returns {Promise} - Promessa com a resposta da exclusão
 */
function deleteConsultor(id) {
    return apiDelete(`consultores/${id}`);
}

/**
 * Verifica se um consultor pode ser excluído
 * @param {number} id - ID do consultor
 * @returns {Promise} - Promessa com o resultado da verificação
 */
function canDeleteConsultor(id) {
    return apiGet(`consultores/${id}/can-delete`);
}

// === API de Ordens de Serviço ===

/**
 * Busca lista de ordens de serviço
 * @param {Object} params - Parâmetros de filtro e paginação
 * @returns {Promise} - Promessa com a lista de ordens de serviço
 */
function fetchOrdens(params = {}) {
    return apiGet('os', params);
}

/**
 * Busca uma ordem de serviço específica
 * @param {number} id - ID da ordem de serviço
 * @returns {Promise} - Promessa com os dados da ordem de serviço
 */
function fetchOrdem(id) {
    return apiGet(`os/${id}`);
}

/**
 * Cria uma nova ordem de serviço
 * @param {Object} data - Dados da ordem de serviço
 * @returns {Promise} - Promessa com a resposta da criação
 */
function createOrdem(data) {
    return apiPost('os', data);
}

/**
 * Atualiza uma ordem de serviço existente
 * @param {number} id - ID da ordem de serviço
 * @param {Object} data - Dados atualizados da ordem de serviço
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateOrdem(id, data) {
    return apiPut(`os/${id}`, data);
}

/**
 * Exclui uma ordem de serviço
 * @param {number} id - ID da ordem de serviço
 * @returns {Promise} - Promessa com a resposta da exclusão
 */
function deleteOrdem(id) {
    return apiDelete(`os/${id}`);
}

/**
 * Verifica se uma ordem de serviço pode ser alterada ou excluída
 * @param {number} id - ID da ordem de serviço
 * @returns {Promise} - Promessa com o resultado da verificação
 */
function canModifyOrdem(id) {
    return apiGet(`os/${id}/can-modify`);
}

/**
 * Gera o PDF da ordem de serviço
 * @param {number} id - ID da ordem de serviço
 * @returns {Promise} - Promessa com a URL do PDF gerado
 */
function generateOSPDF(id) {
    return apiGet(`os/${id}/pdf`)
        .then(response => {
            if (response && response.pdfUrl) {
                return response.pdfUrl;
            }
            throw new Error('URL do PDF não encontrada na resposta');
        });
}

/**
 * Envia a ordem de serviço por e-mail
 * @param {number} id - ID da ordem de serviço
 * @returns {Promise} - Promessa com a resposta do envio
 */
function sendOSEmail(id) {
    return apiPost(`os/${id}/send-email`);
}

// === API de Relação de OS ===

/**
 * Busca lista de relação de ordens de serviço
 * @param {Object} params - Parâmetros de filtro e paginação
 * @returns {Promise} - Promessa com a lista de relação de OS
 */
function fetchRelacaoOS(params = {}) {
    return apiGet('relacao', params);
}

/**
 * Atualiza o status de faturamento de uma OS
 * @param {number} id - ID da OS
 * @param {Object} data - Dados de faturamento { faturado: 'S'/'N' }
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateOSFaturamento(id, data) {
    return apiPut(`relacao/${id}/faturamento`, data);
}

/**
 * Atualiza o status de cobrança de uma OS
 * @param {number} id - ID da OS
 * @param {Object} data - Dados de cobrança { cobrado: 'S'/'N' }
 * @returns {Promise} - Promessa com a resposta da atualização
 */
function updateOSCobranca(id, data) {
    return apiPut(`relacao/${id}/cobranca`, data);
}