<?php
session_start();

require_once 'config/config.php'; // conexão PDO com $conn
require_once 'vendor/autoload.php'; // JWT

use Firebase\JWT\JWT;

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

$hasError = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['username'] ?? '';
    $senha = $_POST['password'] ?? '';

    if (!$email || !$senha) {
        $hasError = true;
        $error = "Preencha todos os campos.";
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Criar o token JWT
            $payload = [
                "iss" => "commercehub",
                "aud" => "commercehub_usuarios",
                "iat" => time(),
                "exp" => time() + 3600, // token válido por 1 hora
                "data" => [
                    "id" => $usuario['id'],
                    "nome" => $usuario['nome'],
                    "email" => $usuario['email'],
                    "tipo" => $usuario['tipo']
                ]
            ];

            $chave_secreta = 'leandro_commercehub2024_seguro';
            $token = JWT::encode($payload, $chave_secreta, 'HS256');

            //Caso seja necessário mostrar o TOKEN
            //echo '<pre>Token gerado: ' . $token . '</pre>';
            //exit;

            // Armazena dados na sessão
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nome'] = $usuario['nome'];
            $_SESSION['user_tipo'] = $usuario['tipo'];

            // Armazena o token JWT na sessão
            $_SESSION['token'] = $token;

            // Redireciona para o dashboard
            header("Location: index.php");
            exit;
        } else {
            $hasError = true;
            $error = "E-mail ou senha inválidos!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Login - CommerceHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<div class="login-container">
    <div class="login-box <?php echo $hasError ? 'shake' : ''; ?>">
        <h2>CommerceHub</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">E-mail</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($email ?? '') ?>" />
            </div>

            <div class="input-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required />
            </div>

            <button type="submit">Entrar</button>
        </form>
    </div>
    <?php if ($hasError): ?>
        <div class="error-message">
            <div class="error-icon"></div>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
