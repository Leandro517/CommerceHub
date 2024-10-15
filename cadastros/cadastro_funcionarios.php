<?php include '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Funcionários</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Cadastro de Funcionários</h1>
    <form method="POST" action="">
        <input type="text" name="nome" placeholder="Nome do Funcionário" required>
        <input type="text" name="cargo" placeholder="Cargo" required>
        <input type="text" name="departamento" placeholder="Departamento" required>
        <input type="text" name="contato" placeholder="Contato" required>
        <button type="submit">Cadastrar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $cargo = $_POST['cargo'];
        $departamento = $_POST['departamento'];
        $contato = $_POST['contato'];

        $sql = "INSERT INTO funcionarios (nome, cargo, departamento, contato) VALUES ('$nome', '$cargo', '$departamento', '$contato')";

        if ($conn->query($sql) === TRUE) {
            echo "Funcionário cadastrado com sucesso!";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>
</body>
</html>
