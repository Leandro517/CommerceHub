<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastros</title>
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
            <li><a href="relatorios.php">Relatórios</a></li>
            <li><a href="../movimentacoes/movimentacoes.php">Movimentações</a></li>
        </ul>
        <!-- Botão Logout -->
        <ul style="position: absolute; bottom: 20px; width: 100%;">
            <li class="logout"><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
        <!-- Relatório de Vendas-->
        <section class="card">
            <h2>Vendas</h2>
            <p>Relatório de vendas.</p>
            <a href="relatorio_vendas.php"><button>Exibir Relatório</button></a>
        </section>

         <!-- Relatório de Compras-->
         <section class="card">
            <h2>Compras</h2>
            <p>Relatório de compras.</p>
            <a href="relatorio_compras.php"><button>Exibir Relatório</button></a>
        </section>

         <!-- Relatório de Estoque-->
         <section class="card">
            <h2>Estoque</h2>
            <p>Relatório de estoque.</p>
            <a href="relatorio_estoque.php"><button>Exibir Relatório</button></a>
        </section>

         <!-- Relatório de Frequencia de Clientes-->
         <section class="card">
            <h2>Frequencia de  Clientes</h2>
            <p>Relatório de frequencia de clientes.</p>
            <a href="relatorio_frequencia_clientes.php"><button>Exibir Relatório</button></a>
        </section>

         <!-- Relatório de Produtos-->
         <section class="card">
            <h2>Vendas por Funcionário</h2>
            <p>Relatório de vendas de funcionario.</p>
            <a href="relatorio_vendas_funcionario.php"><button>Exibir Relatório</button></a>
        </section>
    </main>

   <!-- Rodapé -->
   <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>