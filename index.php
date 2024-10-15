<?php include 'config/config.php'; // Caminho atualizado para o config.php ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CommerceHub - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">

</head>
<body>
    <!-- Cabeçalho -->
    <header>
        <h1><a href="index.php" style="color: white; text-decoration: none;">CommerceHub</a></h1>
    </header>

    <!-- Menu lateral -->
    <nav>
    <ul>
        <li><a href="cadastros/cadastros.php">Cadastros</a></li>
        <li><a href="relatorios/relatorios.php">Relatórios</a></li>
        <li><a href="movimentacoes/movimentacoes.php">Movimentações</a></li>
    </ul>
</nav>


    <!-- Conteúdo principal -->
    <main>
        <!-- Seção para Produtos-->
        <section class="card">
            <h2>Produtos</h2>
            <p>Aqui você pode gerenciar seus produtos cadastrados.</p>
            <a href="cadastros/cadastro_produtos.php"><button>Cadastrar Produto</button></a>
        </section>

        <!-- Seção para Clientes-->
        <section class="card">
            <h2>Clientes</h2>
            <p>Aqui você pode gerenciar seus clientes.</p>
            <a href="cadastros/cadastro_clientes.php"><button>Cadastrar Cliente</button></a>
        </section>

        <!-- Seção para Relatórios-->
        <section class="card">
            <h2>Relatórios</h2>
            <p>Acesse os relatórios de vendas, compras e estoques.</p>
            <a href="relatorios/relatorio_vendas.php"><button>Ver Relatórios</button></a>
        </section>

        <!-- Seção para Registrar Venda -->
        <section class="card movimentacoes">
            <h2>Registrar Venda</h2>
            <p>Registre suas vendas aqui.</p>
            <a href="movimentacoes/movimentacao_vendas.php"><button>Registrar Venda</button></a>
        </section>

        <!-- Seção para Registrar Compra -->
        <section class="card movimentacoes">
            <h2>Registrar Compra</h2>
            <p>Registre suas compras aqui.</p>
            <a href="movimentacoes/movimentacao_compras.php"><button>Registrar Compra</button></a>
        </section>
    </main>

    <!-- Rodapé -->
    <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
