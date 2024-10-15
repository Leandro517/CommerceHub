<?php 
        session_start();
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header("Location: login.php");
            exit;
        }

?>
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
            <li><a href="index.php">Home</a></li>
            <li><a href="cadastros/cadastros.php">Cadastros</a></li>
            <li><a href="relatorios/relatorios.php">Relatórios</a></li>
            <li><a href="movimentacoes/movimentacoes.php">Movimentações</a></li>
        </ul>
        <!-- Botão Logout -->
        <ul style="position: absolute; bottom: 20px; width: 100%;">
            <li class="logout"><a href="logout.php">Logout</a></li>
        </ul>
    </nav>


    <!-- Conteúdo principal -->
    <main>

    </main>

    <!-- Rodapé -->
    <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
