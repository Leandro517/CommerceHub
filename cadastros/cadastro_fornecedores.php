<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Inicializar variáveis de pesquisa
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';

// Processar o cadastro do fornecedor se o método for POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fornecedor_id = isset($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : '';
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $condicoes_pagamento = $_POST['condicoes_pagamento'];

    try {
        if ($fornecedor_id) {
            // Atualizar fornecedor existente
            $stmt = $conn->prepare("UPDATE fornecedor SET nome = :nome, endereco = :endereco, telefone = :telefone, condicoes_pagamento = :condicoes_pagamento WHERE ID_Fornecedor = :fornecedor_id");
            $stmt->bindParam(':fornecedor_id', $fornecedor_id);
        } else {
            // Inserir novo fornecedor
            $stmt = $conn->prepare("INSERT INTO fornecedor (nome, endereco, telefone, condicoes_pagamento) VALUES (:nome, :endereco, :telefone, :condicoes_pagamento)");
        }
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':condicoes_pagamento', $condicoes_pagamento);
        
        $stmt->execute();

        header("Location: ./cadastro_fornecedores.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Verifique se o método é DELETE
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    if ($id) {
        try {
            $stmt = $conn->prepare("DELETE FROM fornecedor WHERE ID_Fornecedor = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            header("Location: ./cadastro_fornecedores.php"); // Redireciona após exclusão
            exit();
        } catch (PDOException $e) {
            echo "Erro ao excluir fornecedor: " . $e->getMessage();
        }
    }
}

// Consultar fornecedores com base na pesquisa
$query = "SELECT * FROM fornecedor";

if ($search_field && $search_value) {
    $query .= " WHERE " . $search_field . " LIKE :search_value";
}

$query .= " ORDER BY nome ASC";
$stmt = $conn->prepare($query);

if ($search_field && $search_value) {
    $stmt->bindValue(':search_value', '%' . $search_value . '%');
}

$stmt->execute();
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Fornecedores - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Cadastro de Fornecedores</h1>
    </header>

    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../cadastros/cadastros.php">Cadastros</a></li>
            <li><a href="../relatorios/relatorios.php">Relatórios</a></li>
            <li><a href="../movimentacoes/movimentacoes.php">Movimentações</a></li>
        </ul>
        <ul style="position: absolute; bottom: 20px; width: 100%;">
            <li class="logout"><a href="../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <section class="search-section">
            <form method="GET" action="./cadastro_fornecedores.php">
                <div class="search-bar">
                    <select id="search_field" name="search_field" required>
                        <option value="nome" <?= $search_field === 'nome' ? 'selected' : '' ?>>Pesquisar por Nome</option>
                        <option value="endereco" <?= $search_field === 'endereco' ? 'selected' : '' ?>>Pesquisar por Endereço</option>
                        <option value="telefone" <?= $search_field === 'telefone' ? 'selected' : '' ?>>Pesquisar por Telefone</option>
                        <option value="condicoes_pagamento" <?= $search_field === 'condicoes_pagamento' ? 'selected' : '' ?>>Pesquisar por Condições de Pagamento</option>
                    </select>
                    <input type="text" id="search_value" name="search_value" placeholder="Pesquisar..." value="<?= htmlspecialchars($search_value) ?>">
                    <button type="submit">Pesquisar</button>
                </div>
            </form>
            <button class="add-button" onclick="openModal()">+ Cadastrar Fornecedor</button>
        </section>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Endereço</th>
                        <th>Telefone</th>
                        <th>Condições de Pagamento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="supplierTable">
                    <?php foreach ($fornecedores as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['endereco']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            <td><?= htmlspecialchars($row['condicoes_pagamento']) ?></td>
                            <td>
                                <button class='edit' onclick="editSupplier(<?= $row['ID_Fornecedor'] ?>, '<?= addslashes($row['nome']) ?>', '<?= addslashes($row['endereco']) ?>', '<?= addslashes($row['telefone']) ?>', '<?= addslashes($row['condicoes_pagamento']) ?>')">Editar</button>
                                <button class='delete' onclick="deleteSupplier(<?= $row['ID_Fornecedor'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal para Cadastro de Fornecedor -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Cadastrar Fornecedor</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="supplierForm" action="./cadastro_fornecedores.php" method="POST">
                <input type="hidden" id="fornecedor_id" name="fornecedor_id">
                <div class="modal-body">
                    <div class="input-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>

                    <div class="input-group">
                        <label for="endereco">Endereço:</label>
                        <input type="text" id="endereco" name="endereco" required>
                    </div>

                    <div class="input-group">
                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" required>
                    </div>

                    <div class="input-group">
                        <label for="condicoes_pagamento">Condições de Pagamento:</label>
                        <input type="text" id="condicoes_pagamento" name="condicoes_pagamento" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-save">Salvar Fornecedor</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('supplierModal').style.display = 'block';
            document.getElementById('modalTitle').innerText = 'Cadastrar Fornecedor';
            document.getElementById('supplierForm').reset(); // Resetar o formulário
            document.getElementById('fornecedor_id').value = ''; // Limpar ID
        }

        function closeModal() {
            document.getElementById('supplierModal').style.display = 'none';
        }

        function editSupplier(id, nome, endereco, telefone, condicoes_pagamento) {
            document.getElementById('fornecedor_id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('endereco').value = endereco;
            document.getElementById('telefone').value = telefone;
            document.getElementById('condicoes_pagamento').value = condicoes_pagamento;
            document.getElementById('supplierModal').style.display = 'block';
            document.getElementById('modalTitle').innerText = 'Editar Fornecedor';
        }

        function deleteSupplier(id) {
            if (confirm('Tem certeza de que deseja excluir este fornecedor?')) {
                window.location.href = './cadastro_fornecedores.php?delete_id=' + id;
            }
        }
    </script>
    
   <!-- Rodapé -->
   <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
