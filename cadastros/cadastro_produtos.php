<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produtos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Cadastro de Produtos</h1>
    <form method="POST" action="">
        <input type="text" name="nome" placeholder="Nome do Produto" required>
        <input type="text" name="descricao" placeholder="Descrição" required>
        <input type="number" name="preco" placeholder="Preço" required>
        <input type="text" name="categoria" placeholder="Categoria" required>
        <button type="submit">Cadastrar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
        $categoria = $_POST['categoria'];

        $sql = "INSERT INTO produtos (nome, descricao, preco, categoria) VALUES ('$nome', '$descricao', '$preco', '$categoria')";

        if ($conn->query($sql) === TRUE) {
            echo "Produto cadastrado com sucesso!";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>
</body>
</html>
