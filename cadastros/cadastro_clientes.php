<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = isset($_POST['client_id']) ? $_POST['client_id'] : null;
    $nome = $_POST['nome'];
    $cep = $_POST['cep'];
    $rua = $_POST['rua'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];

    try {
        if ($id_cliente) {
            $stmt = $conn->prepare("UPDATE cliente SET nome = :nome, email = :email, telefone = :telefone, cep = :cep, rua = :rua, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado WHERE ID_Cliente = :id_cliente");
            $stmt->bindParam(':id_cliente', $id_cliente);
        } else {
            $stmt = $conn->prepare("INSERT INTO cliente (nome, email, telefone, cep, rua, numero, bairro, cidade, estado) VALUES (:nome, :email, :telefone, :cep, :rua, :numero, :bairro, :cidade, :estado)");
        }

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':rua', $rua);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':bairro', $bairro);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':estado', $estado);

        $stmt->execute();

        header("Location: ./cadastro_clientes.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    parse_str(file_get_contents("php://input"), $_DELETE);
    if (isset($_DELETE['id'])) {
        try {
            $id_cliente = $_DELETE['id'];
            $stmt = $conn->prepare("DELETE FROM cliente WHERE ID_Cliente = :id_cliente");
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            echo json_encode(['message' => 'Cliente excluído com sucesso!']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['message' => 'Erro ao excluir cliente: ' . $e->getMessage()]);
            exit();
        }
    }
}

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cadastro de Clientes - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
    <header><h1>Cadastro de Clientes</h1></header>

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
                        <option value="cep" <?= $search_field === 'cep' ? 'selected' : '' ?>>Pesquisar por CEP</option>
                        <option value="rua" <?= $search_field === 'rua' ? 'selected' : '' ?>>Pesquisar por Rua</option>
                        <option value="bairro" <?= $search_field === 'bairro' ? 'selected' : '' ?>>Pesquisar por Bairro</option>
                        <option value="cidade" <?= $search_field === 'cidade' ? 'selected' : '' ?>>Pesquisar por Cidade</option>
                        <option value="estado" <?= $search_field === 'estado' ? 'selected' : '' ?>>Pesquisar por Estado</option>
                        <option value="telefone" <?= $search_field === 'telefone' ? 'selected' : '' ?>>Pesquisar por Telefone</option>
                        <option value="email" <?= $search_field === 'email' ? 'selected' : '' ?>>Pesquisar por Email</option>
                    </select>
                    <input
                        type="text"
                        id="search_value"
                        name="search_value"
                        placeholder="Pesquisar..."
                        value="<?= htmlspecialchars($search_value) ?>"
                    />
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
                        <th>CEP</th>
                        <th>Rua</th>
                        <th>Número</th>
                        <th>Bairro</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="clientTable">
                    <?php foreach ($clientes as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nome']) ?></td>
                        <td><?= htmlspecialchars($row['cep']) ?></td>
                        <td><?= htmlspecialchars($row['rua']) ?></td>
                        <td><?= htmlspecialchars($row['numero']) ?></td>
                        <td><?= htmlspecialchars($row['bairro']) ?></td>
                        <td><?= htmlspecialchars($row['cidade']) ?></td>
                        <td><?= htmlspecialchars($row['estado']) ?></td>
                        <td><?= htmlspecialchars($row['telefone']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <button
                                class="edit"
                                onclick="editClient(
                                    <?= $row['ID_Cliente'] ?>,
                                    '<?= addslashes($row['nome']) ?>',
                                    '<?= addslashes($row['cep']) ?>',
                                    '<?= addslashes($row['rua']) ?>',
                                    '<?= addslashes($row['numero']) ?>',
                                    '<?= addslashes($row['bairro']) ?>',
                                    '<?= addslashes($row['cidade']) ?>',
                                    '<?= addslashes($row['estado']) ?>',
                                    '<?= addslashes($row['telefone']) ?>',
                                    '<?= addslashes($row['email']) ?>'
                                )"
                            >
                                Editar
                            </button>
                            <button class="delete" onclick="deleteClient(<?= $row['ID_Cliente'] ?>)">Excluir</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal Cadastro/Editar -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Cadastrar Cliente</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="clientForm" action="./cadastro_clientes.php" method="POST">
                <input type="hidden" id="client_id" name="client_id" />
                <div class="modal-body">
                    <div class="input-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" required />
                    </div>

                    <div class="input-group">
                        <label for="cep">CEP:</label>
                        <input type="text" id="cep" name="cep" maxlength="9" required />
                    </div>

                    <div class="input-group">
                        <label for="rua">Rua:</label>
                        <input type="text" id="rua" name="rua" required readonly />
                    </div>

                    <div class="input-group">
                        <label for="numero">Número:</label>
                        <input type="text" id="numero" name="numero" required />
                    </div>

                    <div class="input-group">
                        <label for="bairro">Bairro:</label>
                        <input type="text" id="bairro" name="bairro" required readonly />
                    </div>

                    <div class="input-group">
                        <label for="cidade">Cidade:</label>
                        <input type="text" id="cidade" name="cidade" required readonly />
                    </div>

                    <div class="input-group">
                        <label for="estado">Estado:</label>
                        <input type="text" id="estado" name="estado" maxlength="2" required readonly />
                    </div>

                    <div class="input-group">
                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" required />
                    </div>

                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required />
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
        document.getElementById('modalTitle').innerText = 'Cadastrar Cliente';
    }

    function editClient(id, nome, cep, rua, numero, bairro, cidade, estado, telefone, email) {
        document.getElementById('client_id').value = id;
        document.getElementById('nome').value = nome;
        document.getElementById('cep').value = cep;
        document.getElementById('rua').value = rua;
        document.getElementById('numero').value = numero;
        document.getElementById('bairro').value = bairro;
        document.getElementById('cidade').value = cidade;
        document.getElementById('estado').value = estado;
        document.getElementById('telefone').value = telefone;
        document.getElementById('email').value = email;

        openModal();
        document.getElementById('modalTitle').innerText = 'Editar Cliente';
    }

    function deleteClient(id) {
        if (confirm('Tem certeza que deseja excluir este cliente?')) {
            fetch('./cadastro_clientes.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
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

    document.getElementById('cep').addEventListener('blur', function () {
        let cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('rua').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                    } else {
                        alert('CEP não encontrado.');
                        ['rua','bairro','cidade','estado'].forEach(id => {
                            document.getElementById(id).value = '';
                        });
                    }
                })
                .catch(() => {
                    alert('Erro ao buscar o CEP.');
                    ['rua','bairro','cidade','estado'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                });
        } else {
            alert('CEP inválido. Deve conter 8 números.');
            ['rua','bairro','cidade','estado'].forEach(id => {
                document.getElementById(id).value = '';
            });
        }
    });
    </script>

    <!-- Rodapé -->
    <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>

</html>
