<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

require_once '../auth/auth.php';
verificarAcesso(['admin']);

if ($decoded->tipo !== 'admin') {
    header("Location: acesso_negado.php");
    exit;
}

// Inicializando as variáveis para evitar avisos
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : 'nome';
$search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';

// Processar o cadastro ou atualização do cliente se o método for POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $cargo = $_POST['cargo'];
    $departamento = $_POST['departamento'];
    $telefone = $_POST['telefone'];
    $funcionario_id = isset($_POST['funcionario_id']) ? $_POST['funcionario_id'] : null;

    $email = $_POST['email'] ?? null;
    $senha = $_POST['senha'] ?? null;

    try {
        if ($funcionario_id) {
            // Atualizar funcionário
            $stmt = $conn->prepare("UPDATE funcionario SET nome = :nome, cargo = :cargo, departamento = :departamento, telefone = :telefone WHERE ID_Funcionario = :id");
            $stmt->bindParam(':id', $funcionario_id);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cargo', $cargo);
            $stmt->bindParam(':departamento', $departamento);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->execute();

            // Atualizar usuário correspondente
            if (!empty($email)) {
                $stmtUser = $conn->prepare("UPDATE usuarios SET nome = :nome, email = :email WHERE ID_Funcionario = :idFunc");
                $stmtUser->bindParam(':nome', $nome);
                $stmtUser->bindParam(':email', $email);
                $stmtUser->bindParam(':idFunc', $funcionario_id);
                $stmtUser->execute();
            } else {
                $stmtUser = $conn->prepare("UPDATE usuarios SET nome = :nome WHERE ID_Funcionario = :idFunc");
                $stmtUser->bindParam(':nome', $nome);
                $stmtUser->bindParam(':idFunc', $funcionario_id);
                $stmtUser->execute();
            }

            // Atualizar senha se enviada
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmtSenha = $conn->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE ID_Funcionario = :idFunc");
                $stmtSenha->bindParam(':senha_hash', $senha_hash);
                $stmtSenha->bindParam(':idFunc', $funcionario_id);
                $stmtSenha->execute();
            }

        } else {
            // Verificar se email já existe
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmtCheck->bindParam(':email', $email);
            $stmtCheck->execute();
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Email já cadastrado.");
            }

            // Inserir funcionário
            $stmt = $conn->prepare("INSERT INTO funcionario (nome, cargo, departamento, telefone) VALUES (:nome, :cargo, :departamento, :telefone)");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cargo', $cargo);
            $stmt->bindParam(':departamento', $departamento);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->execute();

            // Pegar ID novo funcionário
            $novo_funcionario_id = $conn->lastInsertId();

            if (empty($email) || empty($senha)) {
                throw new Exception("Email e senha são obrigatórios para criar usuário.");
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir usuário
            $stmtUser = $conn->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo, ID_Funcionario) VALUES (:nome, :email, :senha_hash, 'funcionario', :idFunc)");
            $stmtUser->bindParam(':nome', $nome);
            $stmtUser->bindParam(':email', $email);
            $stmtUser->bindParam(':senha_hash', $senha_hash);
            $stmtUser->bindParam(':idFunc', $novo_funcionario_id);
            $stmtUser->execute();
        }

        header("Location: ./cadastro_funcionarios.php");
        exit();
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// Verifique se o método é DELETE
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    parse_str(file_get_contents("php://input"), $data);
    $id = isset($data['id']) ? $data['id'] : null;

    if ($id) {
        try {
            $stmt = $conn->prepare("DELETE FROM funcionario WHERE ID_Funcionario = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Retorna uma resposta em formato JSON
            echo json_encode(['status' => 'success', 'message' => 'Funcionário excluído com sucesso']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir funcionário: ' . $e->getMessage()]);
        }
    }
    exit();
}

// Processar pesquisa se o método for GET
$query = "SELECT ID_Funcionario, nome, cargo, departamento, telefone FROM funcionario WHERE $search_field LIKE :search_value ORDER BY nome ASC";
$stmt = $conn->prepare($query);
$stmt->bindValue(':search_value', '%' . $search_value . '%');
$stmt->execute();
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Funcionários - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Cadastro de Funcionários</h1>
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
        <form method="GET" action="./cadastro_funcionarios.php">
            <div class="search-bar">
                <select id="search_field" name="search_field" required>
                    <option value="nome" <?= $search_field === 'nome' ? 'selected' : '' ?>>Pesquisar por Nome</option>
                    <option value="cargo" <?= $search_field === 'cargo' ? 'selected' : '' ?>>Pesquisar por Cargo</option>
                    <option value="departamento" <?= $search_field === 'departamento' ? 'selected' : '' ?>>Pesquisar por Departamento</option>
                    <option value="telefone" <?= $search_field === 'telefone' ? 'selected' : '' ?>>Pesquisar por Telefone</option>
                </select>
                <input type="text" id="search_value" name="search_value" placeholder="Pesquisar..." value="<?= htmlspecialchars($search_value) ?>">
                <button type="submit">Pesquisar</button>
            </div>
        </form>
        <button class="add-button" onclick="openEmployeeModal()">+ Cadastrar Funcionário</button>
    </section>


        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Departamento</th>
                        <th>Telefone</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($funcionarios as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= htmlspecialchars($row['cargo']) ?></td>
                            <td><?= htmlspecialchars($row['departamento']) ?></td>
                            <td><?= htmlspecialchars($row['telefone']) ?></td>
                            <td>
<button class='edit' onclick="editEmployee(
    <?= $row['ID_Funcionario'] ?>,
    '<?= addslashes($row['nome'] ?? '') ?>',
    '<?= addslashes($row['email'] ?? '') ?>',
    '<?= addslashes($row['cargo'] ?? '') ?>',
    '<?= addslashes($row['departamento'] ?? '') ?>',
    '<?= addslashes($row['telefone'] ?? '') ?>'
)">Editar</button>
                                <button class='delete' onclick="deleteEmployee(<?= $row['ID_Funcionario'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal para Cadastro de Funcionário -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Cadastrar Funcionário</h2>
                <span class="close" onclick="closeEmployeeModal()">&times;</span>
            </div>
            <form id="employeeForm" action="./cadastro_funcionarios.php" method="POST">
                <input type="hidden" id="funcionario_id" name="funcionario_id">
                <div class="modal-body">
                    <div class="input-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="input-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" placeholder="Deixe vazio para manter a senha atual">
                    </div>

                    <div class="input-group">
                        <label for="cargo">Cargo:</label>
                        <input type="text" id="cargo" name="cargo" required>
                    </div>

                    <div class="input-group">
                        <label for="departamento">Departamento:</label>
                        <input type="text" id="departamento" name="departamento" required>
                    </div>

                    <div class="input-group">
                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-save">Salvar Funcionário</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Para modal de funcionário
        function openEmployeeModal() {
            document.getElementById('employeeModal').style.display = 'block';
        }

        function closeEmployeeModal() {
            document.getElementById('employeeModal').style.display = 'none';
            document.getElementById('employeeForm').reset();
        }

        function editEmployee(id, nome, email, cargo, departamento, telefone) {
            document.getElementById('funcionario_id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('email').value = email;
            document.getElementById('senha').value = '';
            document.getElementById('cargo').value = cargo;
            document.getElementById('departamento').value = departamento;
            document.getElementById('telefone').value = telefone;
            document.getElementById('modalTitle').innerText = 'Editar Funcionário';
            openEmployeeModal();
            }

        function deleteEmployee(id) {
            if (confirm("Tem certeza que deseja excluir este funcionário?")) {
                fetch(`./cadastro_funcionarios.php`, {
                    method: 'DELETE',
                    body: new URLSearchParams({ id: id }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        alert(data.message);
                        location.reload();// Recarregar a página após a exclusão
                    } else {
                        alert(data.message);
                    }
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
