<?php
session_start();

// Simulação de dados do usuário (em um projeto real, esses dados viriam de um banco de dados)
$usuario_correto = 'adm';
$senha_correta = '123';

// Obtendo dados do formulário de login
$usuario = $_POST['username'];
$senha = $_POST['password'];

// Verifica se o usuário e a senha estão corretos
if ($usuario === $usuario_correto && $senha === $senha_correta) {
    // Login bem-sucedido: inicia a sessão
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $usuario;

    // Redireciona para o dashboard ou página inicial
    header("Location: index.php");
    exit;
} else {
    // Login falhou: exibe uma mensagem de erro e redireciona para a página de login
    $_SESSION['error'] = 'Usuário ou senha incorretos.';
    header("Location: login.php");
    exit;
}
?>
