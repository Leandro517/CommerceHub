<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Movimentação de Vendas</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Movimentação de Vendas</h1>
    <form method="POST" action="">
        <input type="text" name="produto_id" placeholder="ID do Produto" required>
        <input type="text" name="cliente_id" placeholder="ID do Cliente" required>
        <input type="number" name="quantidade" placeholder="Quantidade" required>
        <input type="number" name="preco" placeholder="Preço Total" required>
        <button type="submit">Registrar Venda</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $produto_id = $_POST['produto_id'];
        $cliente_id = $_POST['cliente_id'];
        $quantidade = $_POST['quantidade'];
        $preco = $_POST['preco'];

        $sql = "INSERT INTO vendas (produto_id, cliente_id, quantidade, preco) VALUES ('$produto_id', '$cliente_id', '$quantidade', '$preco')";

        if ($conn->query($sql) === TRUE) {
            echo "Venda registrada com sucesso!";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>
</body>
</html>
