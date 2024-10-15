<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Movimentação de Compras</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1><a href="../index.php" style="color: white; text-decoration: none;">CommerceHub</a></h1>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="produtos.php">Produtos</a></li>
            <li><a href="fornecedores.php">Fornecedores</a></li>
            <li><a href="clientes.php">Clientes</a></li>
            <li>Movimentações
                <ul class="submenu">
                    <li><a href="registro_venda.php">Registrar Venda</a></li>
                    <li><a href="registro_compra.php">Registrar Compra</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <main>
        <h1>Movimentação de Compras</h1>
        <form method="POST" action="">
            <input type="text" name="fornecedor_id" placeholder="ID do Fornecedor" required>
            <input type="text" name="produto_id" placeholder="ID do Produto" required>
            <input type="number" name="quantidade" placeholder="Quantidade" required>
            <input type="number" name="preco" placeholder="Preço Total" required>
            <button type="submit">Registrar Compra</button>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $fornecedor_id = $_POST['fornecedor_id'];
            $produto_id = $_POST['produto_id'];
            $quantidade = $_POST['quantidade'];
            $preco = $_POST['preco'];

            $sql = "INSERT INTO compras (fornecedor_id, produto_id, quantidade, preco) VALUES ('$fornecedor_id', '$produto_id', '$quantidade', '$preco')";

            if ($conn->query($sql) === TRUE) {
                echo "Compra registrada com sucesso!";
            } else {
                echo "Erro: " . $sql . "<br>" . $conn->error;
            }
        }
        ?>
    </main>

    <footer>
        <p>© 2024 Seu Sistema. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
