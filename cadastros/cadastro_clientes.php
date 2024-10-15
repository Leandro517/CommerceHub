<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Clientes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Cadastro de Clientes</h1>
    <form method="POST" action="">
        <input type="text" name="nome" placeholder="Nome do Cliente" required>
        <input type="text" name="endereco" placeholder="Endereço" required>
        <input type="text" name="telefone" placeholder="Telefone" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Cadastrar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $endereco = $_POST['endereco'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];

        $sql = "INSERT INTO clientes (nome, endereco, telefone, email) VALUES ('$nome', '$endereco', '$telefone', '$email')";

        if ($conn->query($sql) === TRUE) {
            echo "Cliente cadastrado com sucesso!";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>
</body>
</html>
