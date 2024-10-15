<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CommerceHub - Movimentações</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1><a href="../index.php" style="color: white; text-decoration: none;">CommerceHub</a></h1>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../cadastros/cadastros.php">Cadastros</a></li>
            <li><a href="../relatorios/relatorios.php">Relatórios</a></li>
            <li><a href="movimentacoes.php">Movimentações</a></li>
            </ul>
        <!-- Botão Logout -->
        <ul style="position: absolute; bottom: 20px; width: 100%;">
            <li class="logout"><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
         <!-- Movimentação de Vendas-->
         <section class="card">
            <h2>Vendas</h2>
            <p>Movimentação de Vendas.</p>
            <a href="cadastros/cadastro_produtos.php"><button>Exibir Vendas</button></a>
        </section>

        <!-- Movimentação de Compras-->
        <section class="card">
            <h2>Compras</h2>
            <p>Movimentação de Compras.</p>
            <a href="cadastros/cadastro_produtos.php"><button>Exibir Compras</button></a>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
