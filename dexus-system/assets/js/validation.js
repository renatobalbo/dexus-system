/**
 * Sistema de Gestão Dexus - Funções de Validação
 * Responsável pela validação dos formulários e formatação de campos
 */

/**
 * Configura validação para o formulário de cliente
 */
function setupClienteValidation() {
    const form = document.getElementById('form-cliente');
    if (!form) return;
    
    // Validar ao enviar o formulário
    form.addEventListener('submit', function(e) {
        if (!validateClienteForm()) {
            e.preventDefault();
        }
    });
    
    // Configurar campo de tipo de pessoa (CPF/CNPJ)
    setupTipoPessoaField();
    
    // Configurar máscaras para os campos
    setupCPFCNPJMask();
    setupCurrencyMask('CLIVAL');
}

/**
 * Valida o formulário de cliente
 * @returns {boolean} - Indica se o formulário é válido
 */
function validateClienteForm() {
    let isValid = true;
    
    // Validar tipo de pessoa
    const tipoPessoa = document.getElementById('CLITIP');
    if (!tipoPessoa.value) {
        showFieldError('CLITIP', 'Selecione o tipo de pessoa.');
        isValid = false;
    }
    
    // Validar CPF/CNPJ
    const cpfCnpj = document.getElementById('CLIDOC');
    if (!cpfCnpj.value) {
        showFieldError('CLIDOC', 'Informe o CPF ou CNPJ.');
        isValid = false;
    } else {
        // Validar formato específico conforme o tipo de pessoa
        if (tipoPessoa.value === 'F' && !validateCPF(cpfCnpj.value)) {
            showFieldError('CLIDOC', 'CPF inválido.');
            isValid = false;
        } else if (tipoPessoa.value === 'J' && !validateCNPJ(cpfCnpj.value)) {
            showFieldError('CLIDOC', 'CNPJ inválido.');
            isValid = false;
        }
    }
    
    // Validar Razão Social
    const razaoSocial = document.getElementById('CLIRAZ');
    if (!razaoSocial.value) {
        showFieldError('CLIRAZ', 'Informe a razão social.');
        isValid = false;
    }
    
    // Validar Município
    const municipio = document.getElementById('CLIMUN');
    if (!municipio.value) {
        showFieldError('CLIMUN', 'Informe o município.');
        isValid = false;
    }
    
    // Validar UF
    const uf = document.getElementById('CLIEST');
    if (!uf.value) {
        showFieldError('CLIEST', 'Informe a UF.');
        isValid = false;
    }
    
    // Validar E-mail OS
    const emailOS = document.getElementById('CLIEOS');
    if (emailOS.value && !validateEmail(emailOS.value)) {
        showFieldError('CLIEOS', 'E-mail inválido.');
        isValid = false;
    }
    
    // Validar E-mail NF
    const emailNF = document.getElementById('CLIENF');
    if (emailNF.value && !validateEmail(emailNF.value)) {
        showFieldError('CLIENF', 'E-mail inválido.');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Configura o campo de tipo de pessoa (altera máscara e validação)
 */
function setupTipoPessoaField() {
    const tipoPessoa = document.getElementById('CLITIP');
    const cpfCnpj = document.getElementById('CLIDOC');
    const cpfCnpjLabel = document.querySelector('label[for="CLIDOC"]');
    
    if (!tipoPessoa || !cpfCnpj || !cpfCnpjLabel) return;
    
    // Configurar evento para mudança de tipo
    tipoPessoa.addEventListener('change', function() {
        const tipo = this.value;
        
        // Atualizar label
        if (tipo === 'F') {
            cpfCnpjLabel.textContent = 'CPF:';
            cpfCnpj.placeholder = '000.000.000-00';
            cpfCnpj.maxLength = 14;
        } else if (tipo === 'J') {
            cpfCnpjLabel.textContent = 'CNPJ:';
            cpfCnpj.placeholder = '00.000.000/0000-00';
            cpfCnpj.maxLength = 18;
        }
        
        // Limpar campo
        cpfCnpj.value = '';
        clearFieldError('CLIDOC');
    });
    
    // Inicializar com o valor padrão
    if (tipoPessoa.value) {
        tipoPessoa.dispatchEvent(new Event('change'));
    }
}

/**
 * Configura máscaras para CPF/CNPJ
 */
function setupCPFCNPJMask() {
    const cpfCnpj = document.getElementById('CLIDOC');
    if (!cpfCnpj) return;
    
    cpfCnpj.addEventListener('input', function(e) {
        const tipo = document.getElementById('CLITIP').value;
        let value = e.target.value.replace(/\D/g, '');
        
        if (tipo === 'F') {
            // Máscara de CPF: 000.000.000-00
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
        } else if (tipo === 'J') {
            // Máscara de CNPJ: 00.000.000/0000-00
            if (value.length <= 14) {
                value = value.replace(/(\d{2})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1/$2');
                value = value.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }
        }
        
        e.target.value = value;
    });
}

/**
 * Configura máscara para campos de valor monetário
 * @param {string} fieldId - ID do campo
 */
function setupCurrencyMask(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value === '') {
            e.target.value = '';
            return;
        }
        
        // Converter para float (dividir por 100 para considerar centavos)
        value = parseFloat(value) / 100;
        
        // Formatar como moeda brasileira
        e.target.value = value.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    });
}

/**
 * Configura máscara para campos de telefone
 * @param {string} fieldId - ID do campo
 */
function setupPhoneMask(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length > 11) {
            value = value.slice(0, 11);
        }
        
        if (value.length > 10) {
            // Formato: (XX) 9XXXX-XXXX
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length > 6) {
            // Formato: (XX) XXXX-XXXX
            value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else if (value.length > 2) {
            // Formato: (XX)
            value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        }
        
        e.target.value = value;
    });
}

/**
 * Configura validação para o formulário de serviço
 */
function setupServicoValidation() {
    const form = document.getElementById('form-servico');
    if (!form) return;
    
    // Validar ao enviar o formulário
    form.addEventListener('submit', function(e) {
        if (!validateServicoForm()) {
            e.preventDefault();
        }
    });
}

/**
 * Valida o formulário de serviço
 * @returns {boolean} - Indica se o formulário é válido
 */
function validateServicoForm() {
    let isValid = true;
    
    // Validar descrição
    const descricao = document.getElementById('SERDES');
    if (!descricao.value) {
        showFieldError('SERDES', 'Informe a descrição do serviço.');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Configura validação para o formulário de modalidade
 */
function setupModalidadeValidation() {
    const form = document.getElementById('form-modalidade');
    if (!form) return;
    
    // Validar ao enviar o formulário
    form.addEventListener('submit', function(e) {
        if (!validateModalidadeForm()) {
            e.preventDefault();
        }
    });
}

/**
 * Valida o formulário de modalidade
 * @returns {boolean} - Indica se o formulário é válido
 */
function validateModalidadeForm() {
    let isValid = true;
    
    // Validar descrição
    const descricao = document.getElementById('MODDES');
    if (!descricao.value) {
        showFieldError('MODDES', 'Informe a descrição da modalidade.');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Configura validação para o formulário de consultor
 */
function setupConsultorValidation() {
    const form = document.getElementById('form-consultor');
    if (!form) return;
    
    // Validar ao enviar o formulário
    form.addEventListener('submit', function(e) {
        if (!validateConsultorForm()) {
            e.preventDefault();
        }
    });
    
    // Configurar máscaras para os campos
    setupPhoneMask('CONTEL');
    setupCurrencyMask('CONVAL');
}

/**
 * Valida o formulário de consultor
 * @returns {boolean} - Indica se o formulário é válido
 */
function validateConsultorForm() {
    let isValid = true;
    
    // Validar nome
    const nome = document.getElementById('CONNOM');
    if (!nome.value) {
        showFieldError('CONNOM', 'Informe o nome do consultor.');
        isValid = false;
    }
    
    // Validar telefone
    const telefone = document.getElementById('CONTEL');
    if (telefone.value && !validatePhone(telefone.value)) {
        showFieldError('CONTEL', 'Telefone inválido.');
        isValid = false;
    }
    
    // Validar e-mail
    const email = document.getElementById('CONEMA');
    if (email.value && !validateEmail(email.value)) {
        showFieldError('CONEMA', 'E-mail inválido.');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Configura validação para o formulário de OS
 */
function setupOSValidation() {
    const form = document.getElementById('form-os');
    if (!form) return;
    
    // Validar ao enviar o formulário
    form.addEventListener('submit', function(e) {
        if (!validateOSForm()) {
            e.preventDefault();
        }
    });
    
    // Configurar máscaras para os campos
    setupDateMask('OSDATA');
    setupTimeMask('OSHINI');
    setupTimeMask('OSHFIM');
    setupTimeMask('OSHDES');
    setupTimeMask('OSHTRA');
    
    // Configurar cálculo automático do tempo total
    setupTempoTotalCalculation();
}

/**
 * Valida o formulário de OS
 * @returns {boolean} - Indica se o formulário é válido
 */
function validateOSForm() {
    let isValid = true;
    
    // Validar cliente
    const cliente = document.getElementById('OSCLICOD');
    if (!cliente.value) {
        showFieldError('OSCLICOD', 'Selecione o cliente.');
        isValid = false;
    }
    
    // Validar data
    const data = document.getElementById('OSDATA');
    if (!data.value) {
        showFieldError('OSDATA', 'Informe a data de realização.');
        isValid = false;
    } else if (!validateDate(data.value)) {
        showFieldError('OSDATA', 'Data inválida.');
        isValid = false;
    }
    
    // Validar hora início
    const horaInicio = document.getElementById('OSHINI');
    if (!horaInicio.value) {
        showFieldError('OSHINI', 'Informe a hora de início.');
        isValid = false;
    } else if (!validateTime(horaInicio.value)) {
        showFieldError('OSHINI', 'Hora inválida.');
        isValid = false;
    }
    
    // Validar hora fim
    const horaFim = document.getElementById('OSHFIM');
    if (!horaFim.value) {
        showFieldError('OSHFIM', 'Informe a hora de término.');
        isValid = false;
    } else if (!validateTime(horaFim.value)) {
        showFieldError('OSHFIM', 'Hora inválida.');
        isValid = false;
    }
    
    // Validar serviço
    const servico = document.getElementById('OSSERCOD');
    if (!servico.value) {
        showFieldError('OSSERCOD', 'Selecione o serviço.');
        isValid = false;
    }
    
    // Validar consultor
    const consultor = document.getElementById('OSCONCOD');
    if (!consultor.value) {
        showFieldError('OSCONCOD', 'Selecione o consultor.');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Configura o cálculo automático do tempo total
 */
function setupTempoTotalCalculation() {
    const horaInicio = document.getElementById('OSHINI');
    const horaFim = document.getElementById('OSHFIM');
    const descontos = document.getElementById('OSHDES');
    const traslado = document.getElementById('OSHTRA');
    const tempoTotal = document.getElementById('OSHTOT');
    
    if (!horaInicio || !horaFim || !descontos || !traslado || !tempoTotal) return;
    
    // Função para calcular tempo total
    const calcularTempoTotal = () => {
        // Verificar se todos os campos necessários estão preenchidos
        if (!horaInicio.value || !horaFim.value) {
            tempoTotal.value = '';
            return;
        }
        
        // Converter horas para minutos
        const inicioMinutos = timeToMinutes(horaInicio.value);
        const fimMinutos = timeToMinutes(horaFim.value);
        const descontosMinutos = descontos.value ? timeToMinutes(descontos.value) : 0;
        const trasladoMinutos = traslado.value ? timeToMinutes(traslado.value) : 0;
        
        // Calcular tempo total em minutos
        let totalMinutos = fimMinutos - inicioMinutos - descontosMinutos + trasladoMinutos;
        
        // Se o resultado for negativo (ex: trabalho que passa da meia-noite)
        if (totalMinutos < 0) {
            totalMinutos += 24 * 60; // Adicionar 24 horas
        }
        
        // Converter de volta para formato HH:MM
        tempoTotal.value = minutesToTime(totalMinutos);
    };
    
    // Adicionar eventos nos campos para recalcular o tempo total
    horaInicio.addEventListener('input', calcularTempoTotal);
    horaFim.addEventListener('input', calcularTempoTotal);
    descontos.addEventListener('input', calcularTempoTotal);
    traslado.addEventListener('input', calcularTempoTotal);
}

/**
 * Configura máscara para campos de data (DD/MM/YYYY)
 * @param {string} fieldId - ID do campo
 */
function setupDateMask(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length > 8) {
            value = value.slice(0, 8);
        }
        
        if (value.length > 4) {
            // Formato: DD/MM/YYYY
            value = value.replace(/(\d{2})(\d{2})(\d{0,4})/, '$1/$2/$3');
        } else if (value.length > 2) {
            // Formato: DD/MM
            value = value.replace(/(\d{2})(\d{0,2})/, '$1/$2');
        }
        
        e.target.value = value;
    });
}

/**
 * Configura máscara para campos de hora (HH:MM)
 * @param {string} fieldId - ID do campo
 */
function setupTimeMask(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    field.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length > 4) {
            value = value.slice(0, 4);
        }
        
        if (value.length > 2) {
            // Formato: HH:MM
            value = value.replace(/(\d{2})(\d{0,2})/, '$1:$2');
        }
        
        e.target.value = value;
    });
}

/**
 * Converte tempo no formato HH:MM para minutos
 * @param {string} time - Tempo no formato HH:MM
 * @returns {number} - Tempo em minutos
 */
function timeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}

/**
 * Converte minutos para o formato HH:MM
 * @param {number} minutes - Tempo em minutos
 * @returns {string} - Tempo no formato HH:MM
 */
function minutesToTime(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

/**
 * Exibe mensagem de erro para um campo específico
 * @param {string} fieldId - ID do campo
 * @param {string} message - Mensagem de erro
 */
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    // Adicionar classe de erro
    field.classList.add('is-invalid');
    
    // Verificar se já existe mensagem de erro
    let errorElement = document.getElementById(`${fieldId}-error`);
    
    if (!errorElement) {
        // Criar elemento para mensagem de erro
        errorElement = document.createElement('div');
        errorElement.id = `${fieldId}-error`;
        errorElement.className = 'invalid-feedback';
        
        // Adicionar após o campo
        field.parentNode.appendChild(errorElement);
    }
    
    // Atualizar mensagem
    errorElement.textContent = message;
}

/**
 * Remove mensagem de erro de um campo
 * @param {string} fieldId - ID do campo
 */
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    // Remover classe de erro
    field.classList.remove('is-invalid');
    
    // Remover mensagem de erro
    const errorElement = document.getElementById(`${fieldId}-error`);
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Valida um CPF
 * @param {string} cpf - CPF a ser validado
 * @returns {boolean} - Indica se o CPF é válido
 */
function validateCPF(cpf) {
    // Remover caracteres não numéricos
    cpf = cpf.replace(/\D/g, '');
    
    // Verificar se tem 11 dígitos
    if (cpf.length !== 11) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais
    if (/^(\d)\1+$/.test(cpf)) {
        return false;
    }
    
    // Cálculo do primeiro dígito verificador
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let remainder = 11 - (sum % 11);
    let dv1 = remainder > 9 ? 0 : remainder;
    
    // Cálculo do segundo dígito verificador
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    remainder = 11 - (sum % 11);
    let dv2 = remainder > 9 ? 0 : remainder;
    
    // Verificar se os dígitos verificadores estão corretos
    return (parseInt(cpf.charAt(9)) === dv1 && parseInt(cpf.charAt(10)) === dv2);
}

/**
 * Valida um CNPJ
 * @param {string} cnpj - CNPJ a ser validado
 * @returns {boolean} - Indica se o CNPJ é válido
 */
function validateCNPJ(cnpj) {
    // Remover caracteres não numéricos
    cnpj = cnpj.replace(/\D/g, '');
    
    // Verificar se tem 14 dígitos
    if (cnpj.length !== 14) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais
    if (/^(\d)\1+$/.test(cnpj)) {
        return false;
    }
    
    // Cálculo do primeiro dígito verificador
    let size = cnpj.length - 2;
    let numbers = cnpj.substring(0, size);
    const digits = cnpj.substring(size);
    let sum = 0;
    let pos = size - 7;
    
    for (let i = size; i >= 1; i--) {
        sum += parseInt(numbers.charAt(size - i)) * pos--;
        if (pos < 2) {
            pos = 9;
        }
    }
    
    let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    if (result !== parseInt(digits.charAt(0))) {
        return false;
    }
    
    // Cálculo do segundo dígito verificador
    size = size + 1;
    numbers = cnpj.substring(0, size);
    sum = 0;
    pos = size - 7;
    
    for (let i = size; i >= 1; i--) {
        sum += parseInt(numbers.charAt(size - i)) * pos--;
        if (pos < 2) {
            pos = 9;
        }
    }
    
    result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    
    return (result === parseInt(digits.charAt(1)));
}

/**
 * Valida um endereço de e-mail
 * @param {string} email - E-mail a ser validado
 * @returns {boolean} - Indica se o e-mail é válido
 */
function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Valida um número de telefone
 * @param {string} phone - Telefone a ser validado
 * @returns {boolean} - Indica se o telefone é válido
 */
function validatePhone(phone) {
    // Remover caracteres não numéricos
    phone = phone.replace(/\D/g, '');
    
    // Verificar se tem entre 10 e 11 dígitos
    return phone.length >= 10 && phone.length <= 11;
}

/**
 * Valida uma data no formato DD/MM/YYYY
 * @param {string} date - Data a ser validada
 * @returns {boolean} - Indica se a data é válida
 */
function validateDate(date) {
    // Verificar formato
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(date)) {
        return false;
    }
    
    // Extrair partes da data
    const [day, month, year] = date.split('/').map(Number);
    
    // Criar objeto Date
    const dateObj = new Date(year, month - 1, day);
    
    // Verificar se a data é válida
    return (
        dateObj.getFullYear() === year &&
        dateObj.getMonth() === month - 1 &&
        dateObj.getDate() === day
    );
}

/**
 * Valida uma hora no formato HH:MM
 * @param {string} time - Hora a ser validada
 * @returns {boolean} - Indica se a hora é válida
 */
function validateTime(time) {
    // Verificar formato
    if (!/^\d{2}:\d{2}$/.test(time)) {
        return false;
    }
    
    // Extrair horas e minutos
    const [hours, minutes] = time.split(':').map(Number);
    
    // Verificar se os valores estão no intervalo válido
    return hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59;
}