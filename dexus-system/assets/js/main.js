/**
 * Sistema de Gestão Dexus - JavaScript Principal
 * Responsável pelo gerenciamento da aplicação, carregamento de conteúdo e interações com o usuário
 */

// Configuração inicial
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar a aplicação
    initApp();
    
    // Configurar navegação
    setupNavigation();
    
    // Carregar dados do dashboard
    loadDashboardData();
});

/**
 * Inicializa a aplicação e verifica dependências
 */
function initApp() {
    console.log('Inicializando Sistema de Gestão Dexus...');
    
    // Verificar conexão com o banco de dados
    checkDatabaseConnection()
        .then(response => {
            console.log('Conexão com o banco de dados estabelecida.');
        })
        .catch(error => {
            showAlert('Erro ao conectar ao banco de dados. Verifique as configurações.', 'danger');
            console.error('Erro de conexão:', error);
        });
}

/**
 * Configura a navegação entre as páginas
 */
function setupNavigation() {
    // Adicionar listeners aos links de navegação
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover classe active de todos os links
            document.querySelectorAll('.nav-link').forEach(item => {
                item.classList.remove('active');
            });
            
            // Adicionar classe active ao link clicado
            this.classList.add('active');
            
            // Obter a página a ser carregada
            const page = this.getAttribute('data-page');
            
            // Atualizar o título da página
            const pageTitle = this.textContent.trim();
            document.querySelector('main h1').textContent = pageTitle;
            
            // Carregar o conteúdo da página
            loadPageContent(page);
        });
    });
}

/**
 * Carrega o conteúdo da página solicitada
 * @param {string} page - Nome da página a ser carregada
 */
function loadPageContent(page) {
    const contentArea = document.getElementById('content-area');
    contentArea.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    
    // Determinar qual conteúdo carregar com base na página
    switch(page) {
        case 'dashboard':
            loadDashboardContent();
            break;
        case 'clientes':
            loadClientesContent();
            break;
        case 'servicos':
            loadServicosContent();
            break;
        case 'modalidades':
            loadModalidadesContent();
            break;
        case 'consultores':
            loadConsultoresContent();
            break;
        case 'os':
            loadOSContent();
            break;
        case 'relacao':
            loadRelacaoContent();
            break;
        default:
            contentArea.innerHTML = '<div class="alert alert-warning">Página não encontrada.</div>';
    }
}

/**
 * Carrega o conteúdo do Dashboard
 */
function loadDashboardContent() {
    // Recarregar o conteúdo original do dashboard
    loadDashboardData();
    
    // Mostrar o conteúdo do dashboard
    const contentArea = document.getElementById('content-area');
    
    // O dashboard já está no HTML inicial, apenas exibimos ele novamente
    fetch('/dexus-system/views/dashboard/dashboard.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            // Inicializar gráficos após carregar o conteúdo
            initCharts();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar o dashboard.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega os dados para o dashboard a partir da API
 */
function loadDashboardData() {
    // Se você confirmou que api/dashboard/stats funciona:
    fetch('api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            console.log("Dados recebidos do dashboard:", data); // Para depuração
            if (data.success) {
                // Atualizar contadores
                document.getElementById('total-clientes').textContent = data.totalClientes || 0;
                document.getElementById('os-mes').textContent = data.osMes || 0;
                document.getElementById('os-pendentes').textContent = data.osPendentes || 0;
                document.getElementById('os-nao-faturadas').textContent = data.osNaoFaturadas || 0;
                
                // Atualizar dados para os gráficos
                updateChartData(data);
            } else {
                console.error("Erro nos dados do dashboard:", data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar estatísticas do dashboard:', error);
            showAlert('Erro ao carregar estatísticas do dashboard.', 'danger');
        });
}

/**
 * Inicializa os gráficos do dashboard
 */
function initCharts() {
    // Gráfico de linhas para OS nos últimos 6 meses
    const osChartElement = document.getElementById('osChart');
    if (osChartElement) {
        const osChart = new Chart(osChartElement, {
            type: 'line',
            data: {
                labels: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho'],
                datasets: [{
                    label: 'Ordens de Serviço',
                    data: [0, 0, 0, 0, 0, 0], // Dados iniciais vazios
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Guardar referência ao gráfico para atualização posterior
        window.osChart = osChart;
    }
    
    // Gráfico de pizza para modalidades
    const modalidadesChartElement = document.getElementById('modalidadesChart');
    if (modalidadesChartElement) {
        const modalidadesChart = new Chart(modalidadesChartElement, {
            type: 'pie',
            data: {
                labels: ['Consultoria', 'Desenvolvimento', 'Suporte', 'Treinamento'],
                datasets: [{
                    data: [0, 0, 0, 0], // Dados iniciais vazios
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)"
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Guardar referência ao gráfico para atualização posterior
        window.modalidadesChart = modalidadesChart;
    }
}

/**
 * Atualiza os dados dos gráficos
 * @param {Object} data - Dados recebidos da API
 */
function updateChartData(data) {
    // Atualizar gráfico de OS
    if (window.osChart && data.osMonthly) {
        window.osChart.data.labels = data.osMonthly.labels || [];
        window.osChart.data.datasets[0].data = data.osMonthly.values || [];
        window.osChart.update();
    }
    
    // Atualizar gráfico de modalidades
    if (window.modalidadesChart && data.modalidades) {
        window.modalidadesChart.data.labels = data.modalidades.labels || [];
        window.modalidadesChart.data.datasets[0].data = data.modalidades.values || [];
        window.modalidadesChart.update();
    }
}

/**
 * Carrega o conteúdo da tela de Clientes
 */
function loadClientesContent() {
    const contentArea = document.getElementById('content-area');
    
    // Carregar a tela de listagem de clientes
    fetch('/dexus-system/views/clientes/list.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar o botão de novo cliente
            document.getElementById('btn-novo-cliente').addEventListener('click', () => {
                loadClienteForm();
            });
            
            // Carregar os dados dos clientes
            loadClientesData();
            
            // Configurar a busca de clientes
            setupClienteSearch();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar a lista de clientes.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o formulário de cliente (novo ou edição)
 * @param {number} id - ID do cliente a ser editado (opcional)
 */
function loadClienteForm(id = null) {
    const contentArea = document.getElementById('content-area');
    
    // Carregar o formulário de cliente
    fetch('/dexus-system/views/clientes/form.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar validação do formulário
            setupClienteValidation();
            
            // Se for edição, carregar os dados do cliente
            if (id) {
                loadClienteData(id);
            } else {
                // Configurar o campo de tipo de pessoa (CPF/CNPJ)
                setupTipoPessoaField();
                
                // Carregar modalidades para o select
                loadModalidadesSelect();
            }
            
            // Configurar o botão de voltar
            document.getElementById('btn-voltar').addEventListener('click', () => {
                loadClientesContent();
            });
            
            // Configurar o botão de salvar
            document.getElementById('form-cliente').addEventListener('submit', (e) => {
                e.preventDefault();
                saveCliente(id);
            });
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar o formulário de cliente.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o conteúdo da tela de Serviços
 */
function loadServicosContent() {
    const contentArea = document.getElementById('content-area');
    
    // Carregar a tela de listagem de serviços
    fetch('/dexus-system/views/servicos/list.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar o botão de novo serviço
            document.getElementById('btn-novo-servico').addEventListener('click', () => {
                loadServicoForm();
            });
            
            // Carregar os dados dos serviços
            loadServicosData();
            
            // Configurar a busca de serviços
            setupServicoSearch();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar a lista de serviços.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o formulário de serviço (novo ou edição)
 * @param {number} id - ID do serviço a ser editado (opcional)
 */
function loadServicoForm(id = null) {
    const contentArea = document.getElementById('content-area');
    
    // Carregar o formulário de serviço
    fetch('/dexus-system/views/servicos/form.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar validação do formulário
            setupServicoValidation();
            
            // Se for edição, carregar os dados do serviço
            if (id) {
                loadServicoData(id);
            }
            
            // Configurar o botão de voltar
            document.getElementById('btn-voltar').addEventListener('click', () => {
                loadServicosContent();
            });
            
            // Configurar o botão de salvar
            document.getElementById('form-servico').addEventListener('submit', (e) => {
                e.preventDefault();
                saveServico(id);
            });
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar o formulário de serviço.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o conteúdo da tela de Modalidades
 */
function loadModalidadesContent() {
    const contentArea = document.getElementById('content-area');
    
    // Carregar a tela de listagem de modalidades
    fetch('/dexus-system/views/modalidades/list.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar o botão de nova modalidade
            document.getElementById('btn-nova-modalidade').addEventListener('click', () => {
                loadModalidadeForm();
            });
            
            // Carregar os dados das modalidades
            loadModalidadesData();
            
            // Configurar a busca de modalidades
            setupModalidadeSearch();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar a lista de modalidades.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o conteúdo da tela de Consultores
 */
function loadConsultoresContent() {
    const contentArea = document.getElementById('content-area');
    
    // Carregar a tela de listagem de consultores
    fetch('/dexus-system/views/consultores/list.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar o botão de novo consultor
            document.getElementById('btn-novo-consultor').addEventListener('click', () => {
                loadConsultorForm();
            });
            
            // Carregar os dados dos consultores
            loadConsultoresData();
            
            // Configurar a busca de consultores
            setupConsultorSearch();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar a lista de consultores.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o conteúdo da tela de Ordens de Serviço
 */
function loadOSContent() {
    const contentArea = document.getElementById('content-area');
    
    // Carregar a tela de listagem de ordens de serviço
    fetch('/dexus-system/views/os/list.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar o botão de nova OS
            document.getElementById('btn-nova-os').addEventListener('click', () => {
                loadOSForm();
            });
            
            // Carregar os dados das ordens de serviço
            loadOSData();
            
            // Configurar a busca de OS
            setupOSSearch();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar a lista de ordens de serviço.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o formulário de OS (novo ou edição)
 * @param {number} id - ID da OS a ser editada (opcional)
 */
function loadOSForm(id = null) {
    const contentArea = document.getElementById('content-area');
    
    // Carregar o formulário de OS
    fetch('/dexus-system/views/os/form.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Configurar validação do formulário
            setupOSValidation();
            
            // Carregar dados para os selects
            loadClientesSelect();
            loadServicosSelect();
            loadConsultoresSelect();
            loadModalidadesSelect();
            
            // Se for edição, carregar os dados da OS
            if (id) {
                loadOSData(id);
            } else {
                // Configurar campos de data e hora
                setupDateTimeFields();
            }
            
            // Configurar o botão de voltar
            document.getElementById('btn-voltar').addEventListener('click', () => {
                loadOSContent();
            });
            
            // Configurar o botão de salvar
            document.getElementById('form-os').addEventListener('submit', (e) => {
                e.preventDefault();
                saveOS(id);
            });
            
            // Configurar o cálculo automático do tempo total
            setupTempoTotalCalculation();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar o formulário de OS.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Carrega o conteúdo da tela de Relação de OS
 */
function loadRelacaoContent() {
    const contentArea = document.getElementById('content-area');
    
    // Carregar a tela de relação de OS
    fetch('/dexus-system/views/relacao/list.html')
        .then(response => response.text())
        .then(html => {
            contentArea.innerHTML = html;
            
            // Carregar os dados da relação de OS
            loadRelacaoData();
            
            // Configurar a busca e filtros
            setupRelacaoFilters();
        })
        .catch(error => {
            contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar a relação de ordens de serviço.</div>';
            console.error('Erro:', error);
        });
}

/**
 * Exibe uma mensagem de alerta na tela
 * @param {string} message - Mensagem a ser exibida
 * @param {string} type - Tipo de alerta (success, danger, warning, info)
 * @param {number} duration - Duração em milissegundos (0 para não fechar automaticamente)
 */
function showAlert(message, type = 'info', duration = 5000) {
    // Criar elemento de alerta
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.role = 'alert';
    
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    `;
    
    // Adicionar ao DOM
    const alertContainer = document.querySelector('.alert-container');
    if (alertContainer) {
        alertContainer.appendChild(alertElement);
    } else {
        const container = document.createElement('div');
        container.className = 'alert-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1050';
        container.appendChild(alertElement);
        document.body.appendChild(container);
    }
    
    // Configurar fechamento automático
    if (duration > 0) {
        setTimeout(() => {
            alertElement.classList.remove('show');
            setTimeout(() => {
                alertElement.remove();
            }, 150);
        }, duration);
    }
    
    // Criar alerta do Bootstrap
    const bsAlert = new bootstrap.Alert(alertElement);
    
    // Retornar referência para manipulação externa
    return {
        element: alertElement,
        close: () => bsAlert.close()
    };
}

/**
 * Verifica a conexão com o banco de dados
 * @returns {Promise} Promessa com o resultado da verificação
 */
function checkDatabaseConnection() {
    return new Promise((resolve, reject) => {
        // Simular verificação de conexão com o banco
        setTimeout(() => {
            resolve({ success: true, message: 'Conexão estabelecida com sucesso' });
        }, 500);
    });
}