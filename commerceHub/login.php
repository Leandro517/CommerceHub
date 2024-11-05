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
$hasError = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validação do usuário e senha
    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $hasError = true;
        $error = "Usuário ou senha inválidos!";
    }
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
        <div class="login-box <?php echo $hasError ? 'shake' : ''; ?>">
            <h2>CommerceHub</h2>
            <form action="" method="POST">
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
        </div>
        <?php if (isset($error)) : ?>
    <div class="error-message">
        <div class="error-icon"></div>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

    </div>

</body>
</html>
