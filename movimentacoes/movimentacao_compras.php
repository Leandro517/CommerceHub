<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Consultar a lista de fornecedores e funcionários para incluir no formulário
$fornecedores = $conn->query("SELECT ID_Fornecedor, nome FROM fornecedor ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$funcionarios = $conn->query("SELECT ID_Funcionario, nome FROM funcionario ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Consultar compras
$query = "SELECT c.ID_Compra, f.nome AS fornecedor, func.nome AS funcionario, p.nome AS produto, cp.quantidade, 
                 cp.preco, c.data_compra, 
                 (cp.quantidade * cp.preco) AS valor_total
          FROM compra c
          JOIN compra_produto cp ON c.ID_Compra = cp.ID_Compra
          JOIN produtos p ON cp.ID_Produto = p.ID_Produto
          LEFT JOIN fornecedor f ON c.ID_Fornecedor = f.ID_Fornecedor
          LEFT JOIN funcionario func ON c.ID_Funcionario = func.ID_Funcionario
          ORDER BY c.ID_Compra ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Exclusão de compra
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $conn->beginTransaction();
        try {
            // Deletar registros da tabela compra_produto
            $stmt = $conn->prepare("DELETE FROM compra_produto WHERE ID_Compra = :id");
            $stmt->execute(['id' => $delete_id]);

            // Deletar registro da tabela compra
            $stmt = $conn->prepare("DELETE FROM compra WHERE ID_Compra = :id");
            $stmt->execute(['id' => $delete_id]);

            $conn->commit();
            echo json_encode(['message' => 'Compra excluída com sucesso!']);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['message' => 'Erro ao excluir compra: ' . $e->getMessage()]);
            exit;
        }
    }

    // Inserção e edição de compra
    $compra_id = $_POST['compra_id'] ?? null;
    $fornecedor_id = $_POST['fornecedor_id'];
    $funcionario_id = $_POST['funcionario_id'];
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    $preco = $_POST['preco'];
    $data_compra = $_POST['data_compra'];

    try {
        $conn->beginTransaction();
        if (empty($compra_id)) {
            // Inserir a compra
            $stmt = $conn->prepare("INSERT INTO compra (ID_Fornecedor, ID_Funcionario, data_compra)  VALUES (:fornecedor_id, :funcionario_id, :data_compra)");
            $stmt->execute(['fornecedor_id' => $fornecedor_id, 'funcionario_id' => $funcionario_id, 'data_compra' => $data_compra]);
            $last_id = $conn->lastInsertId();

            // Inserir o produto na compra
            $stmt = $conn->prepare("INSERT INTO compra_produto (ID_Compra, ID_Produto, quantidade, preco) 
                                    VALUES (:compra_id, :produto_id, :quantidade, :preco)");
            $stmt->execute([ 
                'compra_id' => $last_id,
                'produto_id' => $produto_id,
                'quantidade' => $quantidade,
                'preco' => $preco
            ]);

            // Atualizar o estoque (aumentar a quantidade)
            $stmt = $conn->prepare("INSERT INTO estoque (ID_Produto, quantidade)
                                    VALUES (:produto_id, :quantidade)");

            $stmt->execute([ 
                'produto_id' => $produto_id,
                'quantidade' => $quantidade
            ]);

            // Atualizar a quantidade do produto na tabela produtos
            $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :quantidade WHERE ID_Produto = :produto_id");
            $stmt->execute([
                'quantidade' => $quantidade,
                'produto_id' => $produto_id
            ]);

        } else {
            // Edição de compra
            if ($compra_id) {
                // Primeiro, obtenha a quantidade anterior na tabela compra_produto
                $stmt_produto_antigo = $conn->prepare("SELECT quantidade FROM compra_produto WHERE ID_Compra = :compra_id AND ID_Produto = :produto_id");
                $stmt_produto_antigo->execute([
                    'compra_id' => $compra_id,
                    'produto_id' => $produto_id
                ]);
                $produto_antigo = $stmt_produto_antigo->fetch(PDO::FETCH_ASSOC);
                $quantidade_antiga = $produto_antigo ? $produto_antigo['quantidade'] : 0;

                // Atualizar a tabela de compra
                $stmt = $conn->prepare("UPDATE compra SET ID_Fornecedor = :fornecedor_id, ID_Funcionario = :funcionario_id, data_compra = :data_compra WHERE ID_Compra = :compra_id");
                $stmt->execute([
                    'fornecedor_id' => $fornecedor_id,
                    'funcionario_id' => $funcionario_id,
                    'data_compra' => $data_compra,
                    'compra_id' => $compra_id
                ]);

                // Atualizar a tabela de compra_produto
                $stmt_produto = $conn->prepare("UPDATE compra_produto SET ID_Produto = :produto_id, quantidade = :quantidade, preco = :preco WHERE ID_Compra = :compra_id");
                $stmt_produto->execute([
                    'produto_id' => $produto_id,
                    'quantidade' => $quantidade,
                    'preco' => $preco,
                    'compra_id' => $compra_id
                ]);

                // Calcular a diferença na quantidade
                $diferenca_quantidade = $quantidade - $quantidade_antiga;

                // Atualizar o estoque após edição (aumentar ou diminuir a quantidade)
                $stmt_estoque = $conn->prepare("UPDATE estoque
                    SET quantidade = quantidade + :diferenca_quantidade
                    WHERE ID_Produto = :produto_id
                ");
                $stmt_estoque->execute([
                    'produto_id' => $produto_id,
                    'diferenca_quantidade' => $diferenca_quantidade
                ]);
            }
        }

        $conn->commit();
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Erro ao salvar a compra: " . $e->getMessage();
    }
}

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $query = "SELECT c.ID_Compra, c.ID_Fornecedor, c.ID_Funcionario, cp.ID_Produto, cp.quantidade, cp.preco, c.data_compra
              FROM compra c
              JOIN compra_produto cp ON c.ID_Compra = cp.ID_Compra
              WHERE c.ID_Compra = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $edit_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($data);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentação de Compras - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Movimentação de Compras</h1>
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
            <button class="motion-button" onclick="openModal()">+ Registrar Compra</button>
        </section>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fornecedor</th>
                        <th>Funcionário</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Data da Compra</th>
                        <th>Valor Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $row): ?>
                        <tr>
                            <td><?= isset($row['ID_Compra']) ? htmlspecialchars($row['ID_Compra']) : '' ?></td>
                            <td><?= isset($row['fornecedor']) ? htmlspecialchars($row['fornecedor']) : '' ?></td>
                            <td><?= isset($row['funcionario']) ? htmlspecialchars($row['funcionario']) : '' ?></td>
                            <td><?= isset($row['produto']) ? htmlspecialchars($row['produto']) : '' ?></td>
                            <td><?= isset($row['quantidade']) ? htmlspecialchars($row['quantidade']) : '' ?></td>
                            <td><?= isset($row['preco']) ? htmlspecialchars($row['preco']) : '' ?></td>
                            <td><?= isset($row['data_compra']) ? htmlspecialchars($row['data_compra']) : '' ?></td>
                            <td><?= isset($row['valor_total']) ? htmlspecialchars($row['valor_total']) : '' ?></td>
                            <td>
                                <button class="edit" onclick="editPurchase(<?= $row['ID_Compra'] ?>)">Editar</button>
                                <button class="delete" onclick="deletePurchase(<?= $row['ID_Compra'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal de Cadastro -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registrar Compra</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="purchaseForm" action="" method="POST">
                <input type="hidden" id="compra_id" name="compra_id">
                <div class="modal-body">
                    <div class="input-group">
                        <label for="fornecedor_id">Fornecedor:</label>
                        <select id="fornecedor_id" name="fornecedor_id" required>
                            <option value="">Selecionar Fornecedor</option>
                            <?php
                            foreach ($fornecedores as $fornecedor) {
                                echo "<option value='{$fornecedor['ID_Fornecedor']}'>{$fornecedor['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="funcionario_id">Funcionário:</label>
                        <select id="funcionario_id" name="funcionario_id" required>
                            <option value="">Selecionar Funcionário</option>
                            <?php
                            foreach ($funcionarios as $funcionario) {
                                echo "<option value='{$funcionario['ID_Funcionario']}'>{$funcionario['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="produto_id">Produto:</label>
                        <select id="produto_id" name="produto_id" required>
                            <option value="">Selecionar Produto</option>
                            <?php
                            $produtos = $conn->query("SELECT ID_Produto, nome FROM produtos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($produtos as $produto) {
                                echo "<option value='{$produto['ID_Produto']}'>{$produto['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" id="quantidade" name="quantidade" required>
                    </div>

                    <div class="input-group">
                        <label for="preco">Preço Unitário:</label>
                        <input type="number" id="preco" name="preco" step="0.01" required>
                    </div>

                    <div class="input-group">
                        <label for="data_compra">Data da Compra:</label>
                        <input type="date" id="data_compra" name="data_compra" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-save">Salvar</button>
                    <button type="button" class="btn-save" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para abrir o modal
        function openModal() {
            document.getElementById('purchaseModal').style.display = 'block';
        }

        // Função para fechar o modal
        function closeModal() {
            document.getElementById('purchaseModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById("purchaseModal")) {
                closeModal();
            }
        }
        // Função para editar uma compra
        function editPurchase(id) {
            fetch(`?edit_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('compra_id').value = data.ID_Compra;
                    document.getElementById('fornecedor_id').value = data.ID_Fornecedor;
                    document.getElementById('funcionario_id').value = data.ID_Funcionario;
                    document.getElementById('produto_id').value = data.ID_Produto;
                    document.getElementById('quantidade').value = data.quantidade;
                    document.getElementById('preco').value = data.preco;
                    document.getElementById('data_compra').value = data.data_compra;
                    openModal();
                })
                .catch(error => console.error('Erro ao editar compra:', error));
        }

        // Função para excluir uma compra
        function deletePurchase(id) {
            if (confirm("Tem certeza de que deseja excluir esta compra?")) {
                const formData = new FormData();
                formData.append('delete_id', id);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                })
                .catch(error => console.error('Erro ao excluir compra:', error));
            }
        }
    </script>
</body>
</html>
