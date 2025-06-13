<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Inicializar variáveis de pesquisa
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';

// Processar o cadastro ou edição do produto se o método for POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['produto_nome'];
    $descricao = $_POST['produto_descricao'];
    $id_categoria = $_POST['ID_categoria'];
    $produto_id = isset($_POST['produto_id']) ? $_POST['produto_id'] : null;

    try {
        if ($produto_id) {
            // Atualizar produto existente
            $stmt = $conn->prepare("UPDATE produtos SET nome = :nome, descricao = :descricao, ID_categoria = :id_categoria WHERE ID_produto = :id");
            $stmt->bindParam(':id', $produto_id);
        } else {
            // Criar novo produto
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, ID_categoria) VALUES (:nome, :descricao, :id_categoria)");
        }
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':id_categoria', $id_categoria);

        $stmt->execute();

        header("Location: ./cadastro_produtos.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Processar a exclusão do produto se o método for DELETE
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    if (isset($_DELETE['id'])) {
        $produto_id = $_DELETE['id'];

        try {
            $stmt = $conn->prepare("DELETE FROM produtos WHERE ID_produto = :id");
            $stmt->bindParam(':id', $produto_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                header('Content-Type: application/json');
                echo json_encode(["status" => "success", "message" => "Produto excluído com sucesso."]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(["status" => "error", "message" => "Produto não encontrado."]);
            }
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(["status" => "error", "message" => "Erro: " . $e->getMessage()]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "ID não fornecido."]);
    }
    exit();
}

// Selecionar categorias em ordem alfabética
$categorias = $conn->query("SELECT ID_categoria, nome FROM categoria ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Consultar produtos com base na pesquisa
$query = "SELECT p.ID_produto, p.nome, p.descricao, c.nome AS categoria
          FROM produtos p
          LEFT JOIN categoria c ON p.ID_categoria = c.ID_categoria";

if ($search_field && $search_value) {
    // Corrigir a cláusula WHERE para especificar a tabela de 'nome' e 'descricao'
    if ($search_field === 'nome') {
        $query .= " WHERE p.nome LIKE :search_value";
    } elseif ($search_field === 'descricao') {
        $query .= " WHERE p.descricao LIKE :search_value";
    } elseif ($search_field === 'c.nome') {
        $query .= " WHERE c.nome LIKE :search_value";
    }
}

$query .= " ORDER BY p.nome ASC";
$stmt = $conn->prepare($query);

if ($search_field && $search_value) {
    $stmt->bindValue(':search_value', '%' . $search_value . '%');
}

$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Cadastro de Produtos</h1>
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
            <form method="GET" action="./cadastro_produtos.php">
                <div class="search-bar">
                    <select id="search_field" name="search_field" required>
                        <option value="nome" <?= $search_field === 'nome' ? 'selected' : '' ?>>Pesquisar por Nome</option>
                        <option value="descricao" <?= $search_field === 'descricao' ? 'selected' : '' ?>>Pesquisar por Descrição</option>
                        <option value="c.nome" <?= $search_field === 'c.nome' ? 'selected' : '' ?>>Pesquisar por Categoria</option>
                    </select>
                    <input type="text" id="search_value" name="search_value" placeholder="Pesquisar..." value="<?= htmlspecialchars($search_value) ?>">
                    <button type="submit">Pesquisar</button>
                </div>
            </form>
            <button class="add-button" onclick="openModal()">+ Cadastrar Produto</button>
        </section>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="productTable">
                    <?php foreach ($produtos as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['descricao']) ?></td>
                            <td><?= htmlspecialchars($row['categoria']) ?></td>
                            <td>
                                <button class='edit' onclick="editProduct(<?= $row['ID_produto'] ?>, '<?= addslashes($row['nome']) ?>', '<?= addslashes($row['descricao']) ?>')">Editar</button>
                                <button class='delete' onclick="deleteProduct(<?= $row['ID_produto'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal para Cadastro de Produto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Cadastrar Produto</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="productForm" action="./cadastro_produtos.php" method="POST">
                <input type="hidden" id="produto_id" name="produto_id">
                <div class="modal-body">
                    <div class="input-group">
                        <label for="produto_nome">Nome:</label>
                        <input type="text" id="produto_nome" name="produto_nome" required>
                    </div>

                    <div class="input-group">
                        <label for="produto_descricao">Descrição:</label>
                        <input type="text" id="produto_descricao" name="produto_descricao" required>
                    </div>

                    <div class="input-group">
                        <label for="ID_categoria">Categoria:</label>
                        <select id="ID_categoria" name="ID_categoria" required>
                            <option value="">Selecionar Categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['ID_categoria'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-save">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Cadastrar Produto';
            document.getElementById('productForm').reset();
            document.getElementById('produto_id').value = '';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function editProduct(id, nome, descricao) {
        document.getElementById('productModal').style.display = 'block';
        document.getElementById('modalTitle').textContent = 'Editar Produto';
        document.getElementById('produto_id').value = id;
        document.getElementById('produto_nome').value = nome;
        document.getElementById('produto_descricao').value = descricao;
    }


        function deleteProduct(id) {
            if (confirm('Tem certeza que deseja excluir este produto?')) {
                fetch('./cadastro_produtos.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('Erro ao excluir o produto');
                    console.error('Erro:', error);
                });
            }
        }
    </script>
</body>
</html>
