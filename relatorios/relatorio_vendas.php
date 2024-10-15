<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Relatório de Vendas</h1>
    <?php
    $sql = "SELECT * FROM vendas";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Produto ID</th><th>Cliente ID</th><th>Quantidade</th><th>Preço</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['produto_id']}</td><td>{$row['cliente_id']}</td><td>{$row['quantidade']}</td><td>{$row['preco']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "Nenhuma venda encontrada.";
    }
    ?>
</body>
</html>
