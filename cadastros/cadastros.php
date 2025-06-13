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
            <li><a href="cadastros.php">Cadastros</a></li>
            <li><a href="../relatorios/relatorios.php">Relatórios</a></li>
            <li><a href="../movimentacoes/movimentacoes.php">Movimentações</a></li>
            </ul>
        <!-- Botão Logout -->
        <ul style="position: absolute; bottom: 20px; width: 100%;">
            <li class="logout"><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <main>
         <!-- Cadastro de Produtos-->
        <section class="card">
            <h2>Cadastrar Produtos</h2>
            <p>Cadastre seus produtos</p>
            <a href="cadastro_produtos.php"><button>Cadastrar Produto</button></a>
        </section>

        <!-- Cadastro de Categoria-->
         <section class="card">
            <h2>Cadastrar Categoria</h2>
            <p>Cadastre suas categorias</p>
            <a href="cadastro_categorias.php"><button>Cadastrar Categoria</button></a>
        </section>

        <!-- Cadastro de Funcionarios-->
        <section class="card">
            <h2>Cadastrar Funcionario</h2>
            <p>Cadastre seus Funcionarios</p>
            <a href="cadastro_funcionarios.php"><button>Cadastrar Funcionario</button></a>
        </section>

        <!-- Cadastro de Fornecedores-->
        <section class="card">
            <h2>Cadastrar Fornecedores</h2>
            <p>Cadastre os fornecedores de sua empresa</p>
            <a href="cadastro_fornecedores.php"><button>Cadastrar Fornecedor</button></a>
        </section>

        <!-- Cadastro de Produtos-->
        <section class="card">
            <h2>Cadastrar Cliente</h2>
            <p>Cadastre seus clientes</p>
            <a href="cadastro_clientes.php"><button>Cadastrar Cliente</button></a>
        </section>

    </main>

   <!-- Rodapé -->
   <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>