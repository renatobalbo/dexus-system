<?php
/**
 * Funções utilitárias para validação de dados
 * Sistema de Gestão Dexus
 */

/**
 * Valida um CPF
 * @param string $cpf CPF a ser validado
 * @return bool Indica se o CPF é válido
 */
function validateCPF($cpf) {
    // Remover caracteres não numéricos
    $cpf = preg_replace('/\D/', '', $cpf);
    
    // Verificar se tem 11 dígitos
    if (strlen($cpf) !== 11) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais
    if (preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }
    
    // Cálculo do primeiro dígito verificador
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int)$cpf[$i] * (10 - $i);
    }
    $remainder = $sum % 11;
    $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
    
    // Cálculo do segundo dígito verificador
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += (int)$cpf[$i] * (11 - $i);
    }
    $remainder = $sum % 11;
    $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
    
    // Verificar se os dígitos calculados são iguais aos dígitos informados
    return ((int)$cpf[9] === $digit1 && (int)$cpf[10] === $digit2);
}

/**
 * Valida um CNPJ
 * @param string $cnpj CNPJ a ser validado
 * @return bool Indica se o CNPJ é válido
 */
function validateCNPJ($cnpj) {
    // Remover caracteres não numéricos
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    // Verificar se tem 14 dígitos
    if (strlen($cnpj) !== 14) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais
    if (preg_match('/^(\d)\1+$/', $cnpj)) {
        return false;
    }
    
    // Cálculo do primeiro dígito verificador
    $sum = 0;
    $multiplier = 5;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$cnpj[$i] * $multiplier;
        $multiplier = ($multiplier === 2) ? 9 : $multiplier - 1;
    }
    $remainder = $sum % 11;
    $digit1 = $remainder < 2 ? 0 : 11 - $remainder;
    
    // Cálculo do segundo dígito verificador
    $sum = 0;
    $multiplier = 6;
    for ($i = 0; $i < 13; $i++) {
        $sum += (int)$cnpj[$i] * $multiplier;
        $multiplier = ($multiplier === 2) ? 9 : $multiplier - 1;
    }
    $remainder = $sum % 11;
    $digit2 = $remainder < 2 ? 0 : 11 - $remainder;
    
    // Verificar se os dígitos calculados são iguais aos dígitos informados
    return ((int)$cnpj[12] === $digit1 && (int)$cnpj[13] === $digit2);
}

/**
 * Valida um e-mail
 * @param string $email E-mail a ser validado
 * @return bool Indica se o e-mail é válido
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida uma data no formato DD/MM/AAAA
 * @param string $date Data a ser validada
 * @return bool Indica se a data é válida
 */
function validateDate($date) {
    // Verificar se a data está no formato correto
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
        return false;
    }
    
    // Extrair dia, mês e ano
    list($day, $month, $year) = explode('/', $date);
    
    // Verificar se a data é válida
    return checkdate((int)$month, (int)$day, (int)$year);
}

/**
 * Valida uma hora no formato HH:MM
 * @param string $time Hora a ser validada
 * @return bool Indica se a hora é válida
 */
function validateTime($time) {
    // Verificar se a hora está no formato correto
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return false;
    }
    
    // Extrair hora e minuto
    list($hour, $minute) = explode(':', $time);
    
    // Verificar se a hora e o minuto são válidos
    return (int)$hour >= 0 && (int)$hour <= 23 && (int)$minute >= 0 && (int)$minute <= 59;
}

/**
 * Valida um número de telefone
 * @param string $phone Telefone a ser validado
 * @return bool Indica se o telefone é válido
 */
function validatePhone($phone) {
    // Remover caracteres não numéricos
    $phone = preg_replace('/\D/', '', $phone);
    
    // Verificar se tem entre 10 e 11 dígitos (telefone fixo ou celular)
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

/**
 * Valida um CEP
 * @param string $cep CEP a ser validado
 * @return bool Indica se o CEP é válido
 */
function validateCEP($cep) {
    // Remover caracteres não numéricos
    $cep = preg_replace('/\D/', '', $cep);
    
    // Verificar se tem 8 dígitos
    return strlen($cep) === 8;
}

/**
 * Valida um valor monetário
 * @param string $value Valor a ser validado
 * @return bool Indica se o valor é válido
 */
function validateMoney($value) {
    // Remover caracteres de formatação
    $value = str_replace(['R$', '.', ','], ['', '', '.'], $value);
    
    // Verificar se é um número válido
    return is_numeric($value) && $value >= 0;
}

/**
 * Valida uma UF
 * @param string $uf UF a ser validada
 * @return bool Indica se a UF é válida
 */
function validateUF($uf) {
    $validUFs = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO',
        'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR',
        'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO'
    ];
    
    return in_array(strtoupper($uf), $validUFs);
}

/**
 * Formata um CPF
 * @param string $cpf CPF a ser formatado
 * @return string CPF formatado
 */
function formatCPF($cpf) {
    // Remover caracteres não numéricos
    $cpf = preg_replace('/\D/', '', $cpf);
    
    // Verificar se tem 11 dígitos
    if (strlen($cpf) !== 11) {
        return $cpf;
    }
    
    // Aplicar formatação
    return sprintf('%s.%s.%s-%s', 
        substr($cpf, 0, 3),
        substr($cpf, 3, 3),
        substr($cpf, 6, 3),
        substr($cpf, 9, 2)
    );
}

/**
 * Formata um CNPJ
 * @param string $cnpj CNPJ a ser formatado
 * @return string CNPJ formatado
 */
function formatCNPJ($cnpj) {
    // Remover caracteres não numéricos
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    // Verificar se tem 14 dígitos
    if (strlen($cnpj) !== 14) {
        return $cnpj;
    }
    
    // Aplicar formatação
    return sprintf('%s.%s.%s/%s-%s', 
        substr($cnpj, 0, 2),
        substr($cnpj, 2, 3),
        substr($cnpj, 5, 3),
        substr($cnpj, 8, 4),
        substr($cnpj, 12, 2)
    );
}

/**
 * Formata um telefone
 * @param string $phone Telefone a ser formatado
 * @return string Telefone formatado
 */
function formatPhone($phone) {
    // Remover caracteres não numéricos
    $phone = preg_replace('/\D/', '', $phone);
    
    // Verificar tamanho
    $length = strlen($phone);
    
    if ($length === 11) {
        // Celular: (XX) 9XXXX-XXXX
        return sprintf('(%s) %s%s-%s',
            substr($phone, 0, 2),
            substr($phone, 2, 1),
            substr($phone, 3, 4),
            substr($phone, 7, 4)
        );
    } else if ($length === 10) {
        // Fixo: (XX) XXXX-XXXX
        return sprintf('(%s) %s-%s',
            substr($phone, 0, 2),
            substr($phone, 2, 4),
            substr($phone, 6, 4)
        );
    }
    
    return $phone;
}

/**
 * Formata um CEP
 * @param string $cep CEP a ser formatado
 * @return string CEP formatado
 */
function formatCEP($cep) {
    // Remover caracteres não numéricos
    $cep = preg_replace('/\D/', '', $cep);
    
    // Verificar se tem 8 dígitos
    if (strlen($cep) !== 8) {
        return $cep;
    }
    
    // Aplicar formatação
    return sprintf('%s-%s', 
        substr($cep, 0, 5),
        substr($cep, 5, 3)
    );
}

/**
 * Formata um valor monetário
 * @param float $value Valor a ser formatado
 * @return string Valor formatado
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata uma data para o formato do banco de dados (YYYY-MM-DD)
 * @param string $date Data no formato DD/MM/YYYY
 * @return string Data no formato YYYY-MM-DD
 */
function formatDateToDB($date) {
    // Verificar se a data está no formato correto
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
        return $date;
    }
    
    // Converter para o formato do banco
    list($day, $month, $year) = explode('/', $date);
    return "$year-$month-$day";
}

/**
 * Formata uma data do banco de dados para exibição (DD/MM/YYYY)
 * @param string $date Data no formato YYYY-MM-DD
 * @return string Data no formato DD/MM/YYYY
 */
function formatDateFromDB($date) {
    // Verificar se a data está no formato correto
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }
    
    // Converter para o formato de exibição
    list($year, $month, $day) = explode('-', $date);
    return "$day/$month/$year";
}

/**
 * Valida um arquivo enviado
 * @param array $file Informações do arquivo ($_FILES)
 * @param array $options Opções de validação (tamanho máximo, tipos permitidos, etc)
 * @return array Resultado da validação
 */
function validateFile($file, $options = array()) {
    // Inicializar resposta
    $response = array(
        'valid' => false,
        'message' => ''
    );
    
    // Verificar se o arquivo foi enviado
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Erro no envio do arquivo.';
        return $response;
    }
    
    // Verificar tamanho máximo
    $maxSize = isset($options['maxSize']) ? $options['maxSize'] : 2 * 1024 * 1024; // 2MB padrão
    if ($file['size'] > $maxSize) {
        $response['message'] = 'O arquivo excede o tamanho máximo permitido.';
        return $response;
    }
    
    // Verificar tipos permitidos
    if (isset($options['allowedTypes']) && !empty($options['allowedTypes'])) {
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, $options['allowedTypes'])) {
            $response['message'] = 'Tipo de arquivo não permitido.';
            return $response;
        }
    }
    
    // Arquivo válido
    $response['valid'] = true;
    
    return $response;
}

/**
 * Limita o tamanho de uma string
 * @param string $str String a ser limitada
 * @param int $maxLength Tamanho máximo
 * @param string $suffix Sufixo para indicar que a string foi cortada
 * @return string String limitada
 */
function limitString($str, $maxLength = 100, $suffix = '...') {
    if (strlen($str) <= $maxLength) {
        return $str;
    }
    
    return substr($str, 0, $maxLength - strlen($suffix)) . $suffix;
}

/**
 * Remove acentos de uma string
 * @param string $str String a ser processada
 * @return string String sem acentos
 */
function removeAccents($str) {
    $search = ['á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'Á', 'À', 'Â', 'Ã', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç'];
    $replace = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C'];
    
    return str_replace($search, $replace, $str);
}

/**
 * Converte uma string para um slug (URL amigável)
 * @param string $str String a ser convertida
 * @return string Slug
 */
function slugify($str) {
    // Remover acentos
    $str = removeAccents($str);
    
    // Converter para minúsculas
    $str = strtolower($str);
    
    // Remover caracteres especiais
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    
    // Substituir espaços por hífens
    $str = preg_replace('/[\s-]+/', '-', $str);
    
    // Remover hífens do início e do fim
    $str = trim($str, '-');
    
    return $str;
}

/**
 * Sanitiza uma string para evitar XSS
 * @param string $str String a ser sanitizada
 * @return string String sanitizada
 */
function sanitizeString($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza um array recursivamente
 * @param array $array Array a ser sanitizado
 * @return array Array sanitizado
 */
function sanitizeArray($array) {
    $result = array();
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result[$key] = sanitizeArray($value);
        } else {
            $result[$key] = is_string($value) ? sanitizeString($value) : $value;
        }
    }
    
    return $result;
}

/**
 * Valida os dados obrigatórios em um array
 * @param array $data Array de dados a serem validados
 * @param array $requiredFields Campos obrigatórios
 * @return array Resultado da validação
 */
function validateRequiredFields($data, $requiredFields) {
    // Inicializar resposta
    $response = array(
        'valid' => true,
        'missingFields' => array()
    );
    
    // Verificar campos obrigatórios
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $response['valid'] = false;
            $response['missingFields'][] = $field;
        }
    }
    
    return $response;
}

/**
 * Valida o tipo de dados em um array
 * @param array $data Array de dados a serem validados
 * @param array $fieldTypes Tipos esperados para cada campo
 * @return array Resultado da validação
 */
function validateDataTypes($data, $fieldTypes) {
    // Inicializar resposta
    $response = array(
        'valid' => true,
        'invalidFields' => array()
    );
    
    // Verificar tipos de dados
    foreach ($fieldTypes as $field => $type) {
        if (isset($data[$field])) {
            $value = $data[$field];
            $isValid = false;
            
            switch ($type) {
                case 'int':
                case 'integer':
                    $isValid = is_numeric($value) && (int)$value == $value;
                    break;
                    
                case 'float':
                case 'double':
                    $isValid = is_numeric($value);
                    break;
                    
                case 'boolean':
                case 'bool':
                    $isValid = is_bool($value) || in_array($value, [0, 1, '0', '1', true, false, 'true', 'false']);
                    break;
                    
                case 'string':
                    $isValid = is_string($value);
                    break;
                    
                case 'array':
                    $isValid = is_array($value);
                    break;
                    
                case 'email':
                    $isValid = validateEmail($value);
                    break;
                    
                case 'date':
                    $isValid = validateDate($value);
                    break;
                    
                case 'time':
                    $isValid = validateTime($value);
                    break;
                    
                case 'cpf':
                    $isValid = validateCPF($value);
                    break;
                    
                case 'cnpj':
                    $isValid = validateCNPJ($value);
                    break;
                    
                case 'phone':
                    $isValid = validatePhone($value);
                    break;
                    
                case 'cep':
                    $isValid = validateCEP($value);
                    break;
                    
                case 'uf':
                    $isValid = validateUF($value);
                    break;
                    
                case 'money':
                    $isValid = validateMoney($value);
                    break;
                    
                default:
                    $isValid = true;
                    break;
            }
            
            if (!$isValid) {
                $response['valid'] = false;
                $response['invalidFields'][$field] = "Formato inválido para o campo '$field'. Esperado: $type.";
            }
        }
    }
    
    return $response;
}

/**
 * Converte um valor para um tipo específico
 * @param mixed $value Valor a ser convertido
 * @param string $type Tipo de destino
 * @return mixed Valor convertido
 */
function convertDataType($value, $type) {
    switch ($type) {
        case 'int':
        case 'integer':
            return (int)$value;
            
        case 'float':
        case 'double':
            return (float)$value;
            
        case 'boolean':
        case 'bool':
            if (is_string($value)) {
                return in_array(strtolower($value), ['true', '1', 'yes', 'sim']);
            }
            return (bool)$value;
            
        case 'string':
            return (string)$value;
            
        case 'date':
            // Converter para formato do banco de dados
            if (validateDate($value)) {
                return formatDateToDB($value);
            }
            return $value;
            
        case 'money':
            // Remover formatação
            if (is_string($value)) {
                return (float)str_replace(['R, '.', ','], ['', '', '.'], $value);
            }
            return $value;
            
        default:
            return $value;
    }
}

/**
 * Valida os limites de valores numéricos
 * @param mixed $value Valor a ser validado
 * @param array $options Opções de validação (min, max)
 * @return bool Indica se o valor está dentro dos limites
 */
function validateNumericLimits($value, $options) {
    // Verificar se é um número
    if (!is_numeric($value)) {
        return false;
    }
    
    // Verificar limite mínimo
    if (isset($options['min']) && $value < $options['min']) {
        return false;
    }
    
    // Verificar limite máximo
    if (isset($options['max']) && $value > $options['max']) {
        return false;
    }
    
    return true;
}

/**
 * Valida o comprimento de uma string
 * @param string $value String a ser validada
 * @param array $options Opções de validação (minLength, maxLength)
 * @return bool Indica se a string tem comprimento válido
 */
function validateStringLength($value, $options) {
    // Verificar se é uma string
    if (!is_string($value)) {
        return false;
    }
    
    $length = strlen($value);
    
    // Verificar comprimento mínimo
    if (isset($options['minLength']) && $length < $options['minLength']) {
        return false;
    }
    
    // Verificar comprimento máximo
    if (isset($options['maxLength']) && $length > $options['maxLength']) {
        return false;
    }
    
    return true;
}

/**
 * Valida uma data em relação a limites
 * @param string $date Data a ser validada (formato DD/MM/YYYY)
 * @param array $options Opções de validação (minDate, maxDate)
 * @return bool Indica se a data está dentro dos limites
 */
function validateDateLimits($date, $options) {
    // Verificar se a data é válida
    if (!validateDate($date)) {
        return false;
    }
    
    // Converter para timestamp
    list($day, $month, $year) = explode('/', $date);
    $timestamp = mktime(0, 0, 0, $month, $day, $year);
    
    // Verificar data mínima
    if (isset($options['minDate'])) {
        $minDate = $options['minDate'];
        if (is_string($minDate) && validateDate($minDate)) {
            list($minDay, $minMonth, $minYear) = explode('/', $minDate);
            $minTimestamp = mktime(0, 0, 0, $minMonth, $minDay, $minYear);
            
            if ($timestamp < $minTimestamp) {
                return false;
            }
        }
    }
    
    // Verificar data máxima
    if (isset($options['maxDate'])) {
        $maxDate = $options['maxDate'];
        if (is_string($maxDate) && validateDate($maxDate)) {
            list($maxDay, $maxMonth, $maxYear) = explode('/', $maxDate);
            $maxTimestamp = mktime(0, 0, 0, $maxMonth, $maxDay, $maxYear);
            
            if ($timestamp > $maxTimestamp) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Valida uma senha
 * @param string $password Senha a ser validada
 * @param array $options Opções de validação (minLength, requireNumbers, requireSpecial, requireUpper, requireLower)
 * @return array Resultado da validação
 */
function validatePassword($password, $options = array()) {
    // Inicializar resposta
    $response = array(
        'valid' => true,
        'errors' => array()
    );
    
    // Definir opções padrão
    $options = array_merge(array(
        'minLength' => 8,
        'requireNumbers' => true,
        'requireSpecial' => true,
        'requireUpper' => true,
        'requireLower' => true
    ), $options);
    
    // Verificar comprimento mínimo
    if (strlen($password) < $options['minLength']) {
        $response['valid'] = false;
        $response['errors'][] = "A senha deve ter pelo menos {$options['minLength']} caracteres.";
    }
    
    // Verificar números
    if ($options['requireNumbers'] && !preg_match('/[0-9]/', $password)) {
        $response['valid'] = false;
        $response['errors'][] = "A senha deve conter pelo menos um número.";
    }
    
    // Verificar caracteres especiais
    if ($options['requireSpecial'] && !preg_match('/[^\w\s]/', $password)) {
        $response['valid'] = false;
        $response['errors'][] = "A senha deve conter pelo menos um caractere especial.";
    }
    
    // Verificar letras maiúsculas
    if ($options['requireUpper'] && !preg_match('/[A-Z]/', $password)) {
        $response['valid'] = false;
        $response['errors'][] = "A senha deve conter pelo menos uma letra maiúscula.";
    }
    
    // Verificar letras minúsculas
    if ($options['requireLower'] && !preg_match('/[a-z]/', $password)) {
        $response['valid'] = false;
        $response['errors'][] = "A senha deve conter pelo menos uma letra minúscula.";
    }
    
    return $response;
}

/**
 * Gera um código aleatório
 * @param int $length Comprimento do código
 * @param string $chars Caracteres permitidos
 * @return string Código gerado
 */
function generateRandomCode($length = 8, $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $code = '';
    $charsLength = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[mt_rand(0, $charsLength)];
    }
    
    return $code;
}

/**
 * Gera um token JWT simples
 * @param array $payload Dados do payload
 * @param string $key Chave secreta
 * @param int $expiration Tempo de expiração em segundos
 * @return string Token JWT
 */
function generateJWT($payload, $key, $expiration = 3600) {
    // Definir cabeçalho
    $header = array(
        'alg' => 'HS256',
        'typ' => 'JWT'
    );
    
    // Codificar cabeçalho
    $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    
    // Adicionar timestamp de expiração
    $payload['exp'] = time() + $expiration;
    
    // Codificar payload
    $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    
    // Criar assinatura
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $key, true);
    $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // Retornar token completo
    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

/**
 * Verifica se um token JWT é válido
 * @param string $token Token JWT
 * @param string $key Chave secreta
 * @return array Resultado da verificação
 */
function verifyJWT($token, $key) {
    // Inicializar resposta
    $response = array(
        'valid' => false,
        'payload' => null,
        'error' => null
    );
    
    // Verificar formato do token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        $response['error'] = 'Token mal formatado.';
        return $response;
    }
    
    // Decodificar cabeçalho e payload
    $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    
    // Verificar se o cabeçalho e payload são válidos
    if ($header === null || $payload === null) {
        $response['error'] = 'Token inválido.';
        return $response;
    }
    
    // Verificar assinatura
    $signature = base64_decode(strtr($parts[2], '-_', '+/'));
    $expectedSignature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $key, true);
    
    if (!hash_equals($signature, $expectedSignature)) {
        $response['error'] = 'Assinatura inválida.';
        return $response;
    }
    
    // Verificar expiração
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        $response['error'] = 'Token expirado.';
        return $response;
    }
    
    // Token válido
    $response['valid'] = true;
    $response['payload'] = $payload;
    
    return $response;
}