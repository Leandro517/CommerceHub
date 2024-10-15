<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Fornecedores</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Cadastro de Fornecedores</h1>
    <form method="POST" action="">
        <input type="text" name="nome" placeholder="Nome do Fornecedor" required>
        <input type="text" name="endereco" placeholder="Endereço" required>
        <input type="text" name="telefone" placeholder="Telefone" required>
        <input type="text" name="condicoes_pagamento" placeholder="Condições de Pagamento" required>
        <button type="submit">Cadastrar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $endereco = $_POST['endereco'];
        $telefone = $_POST['telefone'];
        $condicoes_pagamento = $_POST['condicoes_pagamento'];

        $sql = "INSERT INTO fornecedores (nome, endereco, telefone, condicoes_pagamento) VALUES ('$nome', '$endereco', '$telefone', '$condicoes_pagamento')";

        if ($conn->query($sql) === TRUE) {
            echo "Fornecedor cadastrado com sucesso!";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>
</body>
</html>
