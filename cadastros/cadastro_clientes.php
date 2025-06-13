<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Inicializar variáveis de pesquisa
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';

// Processar o cadastro ou atualização do cliente se o método for POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = isset($_POST['client_id']) ? $_POST['client_id'] : null;
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];

    try {
        if ($id_cliente) {
            // Atualizar cliente existente
            $stmt = $conn->prepare("UPDATE cliente SET nome = :nome, endereco = :endereco, telefone = :telefone, email = :email WHERE ID_Cliente = :id_cliente");
            $stmt->bindParam(':id_cliente', $id_cliente);
        } else {
            // Inserir novo cliente
            $stmt = $conn->prepare("INSERT INTO cliente (nome, endereco, telefone, email) VALUES (:nome, :endereco, :telefone, :email)");
        }

        // Bind parameters
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':email', $email);

        $stmt->execute();

        header("Location: ./cadastro_clientes.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Processar a solicitação DELETE
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    parse_str(file_get_contents("php://input"), $_DELETE);
    if (isset($_DELETE['id'])) {
        try {
            $id_cliente = $_DELETE['id'];

            // Excluir o cliente do banco de dados
            $stmt = $conn->prepare("DELETE FROM cliente WHERE ID_Cliente = :id_cliente");
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();

            // Retornar uma mensagem de sucesso
            echo json_encode(['message' => 'Cliente excluído com sucesso!']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['message' => 'Erro ao excluir cliente: ' . $e->getMessage()]);
            exit();
        }
    }
}

// Consultar clientes com base na pesquisa
$query = "SELECT * FROM cliente";

if ($search_field && $search_value) {
    $query .= " WHERE " . $search_field . " LIKE :search_value";
}

$query .= " ORDER BY nome ASC";
$stmt = $conn->prepare($query);

if ($search_field && $search_value) {
    $stmt->bindValue(':search_value', '%' . $search_value . '%');
}

$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Clientes - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Cadastro de Clientes</h1>
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
            <form method="GET" action="./cadastro_clientes.php">
                <div class="search-bar">
                    <select id="search_field" name="search_field" required>
                        <option value="nome" <?= $search_field === 'nome' ? 'selected' : '' ?>>Pesquisar por Nome</option>
                        <option value="endereco" <?= $search_field === 'endereco' ? 'selected' : '' ?>>Pesquisar por Endereço</option>
                        <option value="telefone" <?= $search_field === 'telefone' ? 'selected' : '' ?>>Pesquisar por Telefone</option>
                        <option value="email" <?= $search_field === 'email' ? 'selected' : '' ?>>Pesquisar por Email</option>
                    </select>
                    <input type="text" id="search_value" name="search_value" placeholder="Pesquisar..." value="<?= htmlspecialchars($search_value) ?>">
                    <button type="submit">Pesquisar</button>
                </div>
            </form>
            <button class="add-button" onclick="openModal()">+ Cadastrar Cliente</button>
        </section>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Endereço</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="clientTable">
                    <?php foreach ($clientes as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['endereco']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <button class='edit' onclick="editClient(<?= $row['ID_Cliente'] ?>, '<?= addslashes($row['nome']) ?>', '<?= addslashes($row['endereco']) ?>', '<?= addslashes($row['telefone']) ?>', '<?= addslashes($row['email']) ?>')">Editar</button>
                                <button class='delete' onclick="deleteClient(<?= $row['ID_Cliente'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal para Cadastro de Cliente -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Cadastrar Cliente</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="clientForm" action="./cadastro_clientes.php" method="POST">
                <input type="hidden" id="client_id" name="client_id">
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
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-save">Salvar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('clientModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('clientModal').style.display = 'none';
            document.getElementById('clientForm').reset();
        }

        function editClient(id, nome, endereco, telefone, email) {
            document.getElementById('client_id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('endereco').value = endereco;
            document.getElementById('telefone').value = telefone;
            document.getElementById('email').value = email;
            openModal();
            document.getElementById('modalTitle').innerText = 'Editar Cliente';
        }

        function deleteClient(id) {
            if (confirm("Tem certeza que deseja excluir este cliente?")) {
                fetch('./cadastro_clientes.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`,
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                })
                .catch(error => console.error('Erro:', error));
            }
        }
    </script>
    
   <!-- Rodapé -->
   <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
