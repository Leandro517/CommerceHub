<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Categorias</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Cadastro de Categorias de Produtos</h1>
    <form method="POST" action="">
        <input type="text" name="nome" placeholder="Nome da Categoria" required>
        <input type="text" name="descricao" placeholder="Descrição" required>
        <button type="submit">Cadastrar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];

        $sql = "INSERT INTO categorias (nome, descricao) VALUES ('$nome', '$descricao')";

        if ($conn->query($sql) === TRUE) {
            echo "Categoria cadastrada com sucesso!";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>
</body>
</html>
