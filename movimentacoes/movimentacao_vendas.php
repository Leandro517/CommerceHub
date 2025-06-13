<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

$clientes = $conn->query("SELECT ID_Cliente, nome FROM cliente ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$funcionarios = $conn->query("SELECT ID_Funcionario, nome FROM funcionario ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT v.ID_Venda, c.nome AS cliente, func.nome AS funcionario, p.nome AS produto, vp.quantidade, vp.preco, v.data_venda, 
                 (vp.quantidade * vp.preco) AS valor_total
          FROM venda v
          JOIN venda_produto vp ON v.ID_Venda = vp.ID_Venda
          JOIN produtos p ON vp.ID_Produto = p.ID_Produto
          LEFT JOIN cliente c ON v.ID_Cliente = c.ID_Cliente
          LEFT JOIN funcionario func ON v.ID_Funcionario = func.ID_Funcionario
          ORDER BY v.ID_Venda ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("DELETE FROM venda_produto WHERE ID_Venda = :id");
            $stmt->execute(['id' => $delete_id]);

            $stmt = $conn->prepare("DELETE FROM venda WHERE ID_Venda = :id");
            $stmt->execute(['id' => $delete_id]);

            $conn->commit();
            echo json_encode(['message' => 'Venda excluída com sucesso!']);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['message' => 'Erro ao excluir venda: ' . $e->getMessage()]);
            exit;
        }
    }

    $venda_id = $_POST['venda_id'] ?? null;
    $cliente_id = $_POST['cliente_id'];
    $funcionario_id = $_POST['funcionario_id'];
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    $preco = $_POST['preco'];
    $data_venda = $_POST['data_venda'];

    try {
        $conn->beginTransaction();

        // VERIFICA ESTOQUE DISPONÍVEL
        $stmt = $conn->prepare("SELECT quantidade FROM produtos WHERE ID_Produto = :produto_id");
        $stmt->execute(['produto_id' => $produto_id]);
        $estoque = $stmt->fetchColumn();

        if ($estoque === false || $quantidade > $estoque) {
            throw new Exception("Estoque insuficiente para o produto selecionado.");
        }

        if (empty($venda_id)) {
            $stmt = $conn->prepare("INSERT INTO venda (ID_Cliente, ID_Funcionario, data_venda) VALUES (:cliente_id, :funcionario_id, :data_venda)");
            $stmt->execute([
                'cliente_id' => $cliente_id,
                'funcionario_id' => $funcionario_id,
                'data_venda' => $data_venda
            ]);
            $last_id = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO venda_produto (ID_Venda, ID_Produto, quantidade, preco) VALUES (:venda_id, :produto_id, :quantidade, :preco)");
            $stmt->execute([
                'venda_id' => $last_id,
                'produto_id' => $produto_id,
                'quantidade' => $quantidade,
                'preco' => $preco
            ]);

            $stmt = $conn->prepare("INSERT INTO estoque (ID_Produto, tipo_movimento, quantidade) VALUES (:produto_id, 'saida', :quantidade)");
            $stmt->execute([
                'produto_id' => $produto_id,
                'quantidade' => $quantidade
            ]);

            $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :quantidade WHERE ID_Produto = :produto_id");
            $stmt->execute([
                'quantidade' => $quantidade,
                'produto_id' => $produto_id
            ]);
        } else {
            $stmt_produto_antigo = $conn->prepare("SELECT quantidade FROM venda_produto WHERE ID_Venda = :venda_id AND ID_Produto = :produto_id");
            $stmt_produto_antigo->execute([
                'venda_id' => $venda_id,
                'produto_id' => $produto_id
            ]);
            $quantidade_antiga = $stmt_produto_antigo->fetchColumn() ?? 0;

            $diferenca_quantidade = $quantidade - $quantidade_antiga;

            // Verifica se há estoque suficiente para a diferença
            if ($diferenca_quantidade > 0 && $diferenca_quantidade > $estoque) {
                throw new Exception("Estoque insuficiente para atualizar a venda.");
            }

            $stmt = $conn->prepare("UPDATE venda SET ID_Cliente = :cliente_id, ID_Funcionario = :funcionario_id, data_venda = :data_venda WHERE ID_Venda = :venda_id");
            $stmt->execute([
                'cliente_id' => $cliente_id,
                'funcionario_id' => $funcionario_id,
                'data_venda' => $data_venda,
                'venda_id' => $venda_id
            ]);

            $stmt = $conn->prepare("UPDATE venda_produto SET ID_Produto = :produto_id, quantidade = :quantidade WHERE ID_Venda = :venda_id");
            $stmt->execute([
                'produto_id' => $produto_id,
                'quantidade' => $quantidade,
                'venda_id' => $venda_id
            ]);

            $stmt = $conn->prepare("INSERT INTO estoque (ID_Produto, tipo_movimento, quantidade) VALUES (:produto_id, 'saida', :quantidade)");
            $stmt->execute([
                'produto_id' => $produto_id,
                'quantidade' => $diferenca_quantidade
            ]);

            $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :quantidade WHERE ID_Produto = :produto_id");
            $stmt->execute([
                'quantidade' => $diferenca_quantidade,
                'produto_id' => $produto_id
            ]);
        }

        $conn->commit();
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Erro: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $query = "SELECT v.ID_Venda, v.ID_Cliente, v.ID_Funcionario, vp.ID_Produto, vp.quantidade, v.data_venda
              FROM venda v
              JOIN venda_produto vp ON v.ID_Venda = vp.ID_Venda
              WHERE v.ID_Venda = :id";
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
    <title>Movimentação de Vendas - CommerceHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>Movimentação de Vendas</h1>
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
            <button class="motion-button" onclick="openModal()">+ Registrar Venda</button>
        </section>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Funcionário</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Data da Venda</th>
                        <th>Valor Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $row): ?>
                        <tr>
                            <td><?= isset($row['ID_Venda']) ? htmlspecialchars($row['ID_Venda']) : '' ?></td>
                            <td><?= isset($row['cliente']) ? htmlspecialchars($row['cliente']) : '' ?></td>
                            <td><?= isset($row['produto']) ? htmlspecialchars($row['produto']) : '' ?></td>
                            <td><?= isset($row['funcionario']) ? htmlspecialchars($row['funcionario']) : '' ?></td>
                            <td><?= isset($row['quantidade']) ? htmlspecialchars($row['quantidade']) : '' ?></td>
                            <td><?= isset($row['preco']) && $row['preco'] > 0 ? number_format($row['preco'], 2, ',', '.') : 'R$ 0,00' ?></td>
                            <td><?= isset($row['valor_total']) && $row['valor_total'] > 0 ? 'R$ ' . number_format($row['valor_total'], 2, ',', '.') : 'R$ 0,00' ?></td>
                            <td><?= isset($row['data_venda']) ? htmlspecialchars($row['data_venda']) : '' ?></td>
                            <td>
                                <button class="edit" onclick="editSale(<?= $row['ID_Venda'] ?>)">Editar</button>
                                <button class="delete" onclick="deleteSale(<?= $row['ID_Venda'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal de Cadastro -->
    <div id="saleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Registrar Venda</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="saleForm" action="" method="POST">
                <input type="hidden" id="venda_id" name="venda_id">
                <div class="modal-body">
                    <div class="input-group">
                        <label for="cliente_id">Cliente:</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Selecionar Cliente</option>
                            <?php
                            $clientes = $conn->query("SELECT ID_Cliente, nome FROM cliente")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($clientes as $cliente) {
                                echo "<option value='{$cliente['ID_Cliente']}'>{$cliente['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="produto_id">Produto:</label>
                        <select id="produto_id" name="produto_id" required>
                            <option value="">Selecionar Produto</option>
                            <?php
                            $produtos = $conn->query("SELECT ID_Produto, nome FROM produtos")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($produtos as $produto) {
                                echo "<option value='{$produto['ID_Produto']}'>{$produto['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="funcionario_id">Funcionário:</label>
                        <select id="funcionario_id" name="funcionario_id" required>
                            <option value="">Selecionar Funcionário</option>
                            <?php foreach ($funcionarios as $funcionario) { ?>
                                <option value="<?= $funcionario['ID_Funcionario'] ?>"><?= $funcionario['nome'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" id="quantidade" name="quantidade" required min="1">
                    </div>

                    <div class="input-group">
                        <label for="preco">Preço Unitário:</label>
                        <input type="number" id="preco" name="preco" required step="0.01">
                    </div>

                    <div class="input-group">
                        <label for="data_venda">Data da Venda:</label>
                        <input type="date" id="data_venda" name="data_venda" required>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-save">Salvar Venda</button>
                        <button type="button" class="btn-save" onclick="closeModal()">Cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('saleModal').style.display = "block";
        }

        function closeModal() {
            document.getElementById('saleModal').style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById("saleModal")) {
                closeModal();
            }
        }

        function editSale(id) {
            fetch(`./movimentacao_vendas.php?edit_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('venda_id').value = data.ID_Venda;
                    document.getElementById('cliente_id').value = data.ID_Cliente;
                    document.getElementById('produto_id').value = data.ID_Produto;
                    document.getElementById('funcionario_id').value = data.ID_Funcionario;
                    document.getElementById('quantidade').value = data.quantidade;
                    document.getElementById('preco').value = data.preco;
                    document.getElementById('data_venda').value = data.data_venda;
                    openModal();
                })
                .catch(error => console.error('Erro ao carregar dados: ', error));
        }


        function deleteSale(id) {
            if (confirm('Tem certeza que deseja excluir esta venda?')) {
                fetch('./movimentacao_vendas.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `delete_id=${id}`
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
