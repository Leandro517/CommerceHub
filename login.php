<?php
session_start();

// Se o usuário já estiver logado, redirecione para o dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

// Usuário e senha para validação
$valid_username = 'adm';
$valid_password = '123';

// Verifica o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validação do usuário e senha
    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Usuário ou senha inválidos!";
    }
}

if (isset($_SESSION['error'])) {
    echo '<div class="error-message"><strong>Erro:</strong> ' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']); // Remove a mensagem de erro após exibi-la
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Conteúdo principal da tela de login -->
    <div class="login-container">
        <div class="login-box">
            <h2>CommerceHub</h2>
            <form action="process_login.php" method="POST">
                <div class="input-group">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="input-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit">Entrar</button>
            </form>
            <p class="signup-link">Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
        </div>
    </div>

</body>
</html>

