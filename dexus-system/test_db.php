<?php
// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configuração de banco de dados
require_once 'config/database.php';

echo "<h2>Teste de Conexão com Banco de Dados</h2>";

try {
    // Tentar obter conexão
    $conn = getConnection();
    
    if ($conn) {
        echo "<p style='color:green'>Conexão estabelecida com sucesso!</p>";
        
        // Testar uma consulta simples
        $sql = "SELECT 1 AS test";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Consulta de teste: " . ($result['test'] === '1' ? 'OK' : 'Falha') . "</p>";
        
        // Testar consulta às tabelas do sistema
        $tables = ['CADCLI', 'CADSER', 'CADMOD', 'CADCON', 'ORDSER', 'RELOS'];
        
        echo "<h3>Verificação de Tabelas:</h3>";
        echo "<ul>";
        
        foreach ($tables as $table) {
            try {
                $sql = "SELECT COUNT(*) AS total FROM $table";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $count = $stmt->fetchColumn();
                
                echo "<li style='color:green'>Tabela $table: $count registros</li>";
            } catch (Exception $e) {
                echo "<li style='color:red'>Erro ao acessar tabela $table: " . $e->getMessage() . "</li>";
            }
        }
        
        echo "</ul>";
    } else {
        echo "<p style='color:red'>Falha ao estabelecer conexão!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
}
?>