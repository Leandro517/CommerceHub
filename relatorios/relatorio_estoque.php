<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Estoque</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Relatório de Estoque</h1>
    <?php
    $sql = "SELECT * FROM produtos";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Nome</th><th>Descrição</th><th>Preço</th><th>Categoria</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['nome']}</td><td>{$row['descricao']}</td><td>{$row['preco']}</td><td>{$row['categoria']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "Nenhum produto encontrado.";
    }
    ?>
</body>
</html>
