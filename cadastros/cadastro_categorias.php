<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Processa o cadastro ou edita a categoria
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['categoria_nome'];
    $descricao = $_POST['categoria_descricao'];
    $categoria_id = isset($_POST['categoria_id']) ? $_POST['categoria_id'] : null;

    try {
        if ($categoria_id) {
            $stmt = $conn->prepare("UPDATE categoria SET nome = :nome, descricao = :descricao WHERE ID_categoria = :id");
            $stmt->bindParam(':id', $categoria_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO categoria (nome, descricao) VALUES (:nome, :descricao)");
        }
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();

        header("Location: ./cadastro_categorias.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Processa a exclusão da categoria
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    if (isset($_DELETE['id'])) {
        $categoria_id = $_DELETE['id'];

        try {
            $stmt = $conn->prepare("DELETE FROM categoria WHERE ID_categoria = :id");
            $stmt->bindParam(':id', $categoria_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                header('Content-Type: application/json');
                echo json_encode(["status" => "success", "message" => "Categoria excluída com sucesso."]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(["status" => "error", "message" => "Categoria não encontrada."]);
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

// Seleciona as categorias em ordem alfabética
$categorias = $conn->query("SELECT ID_categoria, nome, descricao FROM categoria ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Categorias - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Cadastro de Categorias</h1>
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
            <div class="search-bar">
                <select id="filterSelect">
                    <option value="nome">Pesquisar por Nome</option>
                    <option value="descricao">Pesquisar por Descrição</option>
                </select>
                <input type="text" id="search" placeholder="Pesquisar categorias...">
                <button onclick="searchCategories()">Pesquisar</button>
            </div>
            <button class="add-button" onclick="openModal()">+ Cadastrar Categoria</button>
        </section>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="categoryTable">
                    <?php foreach ($categorias as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['descricao']) ?></td>
                            <td>
                                <button class='edit' onclick="editCategory(<?= $row['ID_categoria'] ?>, '<?= htmlspecialchars($row['nome']) ?>', '<?= htmlspecialchars($row['descricao']) ?>')">Editar</button>
                                <button class='delete' onclick="deleteCategory(<?= $row['ID_categoria'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal para Cadastro de Categoria -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Cadastrar Categoria</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="categoryForm" action="./cadastro_categorias.php" method="POST" onsubmit="return handleSubmit(event)">
                <input type="hidden" id="categoria_id" name="categoria_id">
                <div class="modal-body">
                    <div class="input-group">
                        <label for="categoria_nome">Nome da Categoria:</label>
                        <input type="text" id="categoria_nome" name="categoria_nome" required>
                    </div>

                    <div class="input-group">
                        <label for="categoria_descricao">Descrição:</label>
                        <input type="text" id="categoria_descricao" name="categoria_descricao" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-save">Salvar Categoria</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    //Função de abrir o Modal
    function openModal() {
        document.getElementById('categoryModal').style.display = 'flex';
    }

    //Função para fechar o Modal
    function closeModal() {
        document.getElementById('categoryModal').style.display = 'none';
        document.getElementById('categoryForm').reset();
    }

    //Função de Pesquisa
    function searchCategories() {
        const query = document.getElementById('search').value.toLowerCase().trim();
        const filter = document.getElementById('filterSelect').value;
        const rows = document.querySelectorAll('#categoryTable tr');

        rows.forEach(row => {
            const categoryName = row.children[0].textContent.toLowerCase();
            const categoryDescription = row.children[1].textContent.toLowerCase();
            
            let shouldDisplay = false;

            if (filter === 'nome') {
                shouldDisplay = categoryName.startsWith(query);
            } else if (filter === 'descricao') {
                shouldDisplay = categoryDescription.startsWith(query);
            }

            row.style.display = shouldDisplay ? '' : 'none';
        });
    }

    //Função de Editar
    function editCategory(id, nome, descricao) {
        document.getElementById('modalTitle').innerText = 'Editar Categoria';
        document.getElementById('categoria_id').value = id;
        document.getElementById('categoria_nome').value = nome;
        document.getElementById('categoria_descricao').value = descricao;
        openModal();
    }

    //Função de Excluir
    function deleteCategory(id) {
        if (confirm('Tem certeza que deseja excluir esta categoria?')) {
            fetch(`./cadastro_categorias.php`, {
                method: 'DELETE',
                body: new URLSearchParams({ id: id }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Erro:', error));
        }
    }

    function handleSubmit(event) {
        event.preventDefault();
        document.getElementById('categoryForm').submit();
    }
    </script>
    
    <!-- Rodapé -->
    <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
