<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Função para registrar logs
function registrarLog($conn, $usuario_id, $acao, $detalhes = null) {
    $stmt = $conn->prepare("INSERT INTO logs (usuario_id, acao, detalhes) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $acao, $detalhes]);
}

// Capturar usuário logado (exemplo, adapte conforme seu sistema)
session_start();
$usuario_id = $_SESSION['usuario_id'] ?? null; // ou ajuste conforme seu sistema

// Buscar clientes e funcionários para o formulário (se necessário)
$clientes = $conn->query("SELECT ID_Cliente, nome FROM cliente ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$funcionarios = $conn->query("SELECT ID_Funcionario, nome FROM funcionario ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar vendas para exibir (exemplo simplificado)
$query = "SELECT v.ID_Venda, c.nome AS cliente, f.nome AS funcionario, p.nome AS produto, vp.quantidade, vp.preco, v.data_venda,
          (vp.quantidade * vp.preco) AS valor_total
          FROM venda v
          JOIN venda_produto vp ON v.ID_Venda = vp.ID_Venda
          JOIN produtos p ON vp.ID_Produto = p.ID_Produto
          LEFT JOIN cliente c ON v.ID_Cliente = c.ID_Cliente
          LEFT JOIN funcionario f ON v.ID_Funcionario = f.ID_Funcionario
          ORDER BY v.ID_Venda ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do formulário
    $venda_id = $_POST['venda_id'] ?? null;
    $cliente_id = $_POST['cliente_id'] ?? null;
    $funcionario_id = $_POST['funcionario_id'] ?? null;
    $produto_id = $_POST['produto_id'] ?? null;
    $quantidade = isset($_POST['quantidade']) ? (int) $_POST['quantidade'] : 0;
    $preco = isset($_POST['preco']) ? (float) $_POST['preco'] : 0;
    $data_venda = $_POST['data_venda'] ?? date('Y-m-d');

    try {
        $conn->beginTransaction();

        // 1. Verificar estoque atual pelo campo produtos.quantidade
        $stmtEstoque = $conn->prepare("SELECT quantidade FROM produtos WHERE ID_Produto = :produto_id FOR UPDATE");
        $stmtEstoque->execute(['produto_id' => $produto_id]);
        $estoqueAtual = (int) $stmtEstoque->fetchColumn();

        if ($estoqueAtual < $quantidade) {
            throw new Exception("Estoque insuficiente para o produto selecionado. Estoque atual: $estoqueAtual");
        }

        if (empty($venda_id)) {
            // Inserir venda
            $stmt = $conn->prepare("INSERT INTO venda (ID_Cliente, ID_Funcionario, data_venda) VALUES (:cliente_id, :funcionario_id, :data_venda)");
            $stmt->execute([
                'cliente_id' => $cliente_id,
                'funcionario_id' => $funcionario_id,
                'data_venda' => $data_venda
            ]);
            $venda_id = $conn->lastInsertId();

            // Inserir produto da venda
            $stmt = $conn->prepare("INSERT INTO venda_produto (ID_Venda, ID_Produto, quantidade, preco) VALUES (:venda_id, :produto_id, :quantidade, :preco)");
            $stmt->execute([
                'venda_id' => $venda_id,
                'produto_id' => $produto_id,
                'quantidade' => $quantidade,
                'preco' => $preco
            ]);

            // Registrar saída no estoque
            $stmt = $conn->prepare("INSERT INTO estoque (ID_Produto, tipo_movimento, quantidade) VALUES (:produto_id, 'saida', :quantidade)");
            $stmt->execute([
                'produto_id' => $produto_id,
                'quantidade' => $quantidade
            ]);

            // Atualizar quantidade em produtos
            $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :quantidade WHERE ID_Produto = :produto_id");
            $stmt->execute([
                'quantidade' => $quantidade,
                'produto_id' => $produto_id
            ]);

            // Registrar log
            registrarLog($conn, $usuario_id, "Nova venda registrada", "Venda ID: $venda_id, Produto ID: $produto_id, Quantidade: $quantidade");

        } else {
            // Atualizar venda existente
            // Pegar quantidade antiga para ajustar estoque
            $stmtAntiga = $conn->prepare("SELECT quantidade FROM venda_produto WHERE ID_Venda = :venda_id AND ID_Produto = :produto_id");
            $stmtAntiga->execute([
                'venda_id' => $venda_id,
                'produto_id' => $produto_id
            ]);
            $quantidadeAntiga = (int) $stmtAntiga->fetchColumn();

            $diferenca = $quantidade - $quantidadeAntiga;

            if ($diferenca > 0 && $estoqueAtual < $diferenca) {
                throw new Exception("Estoque insuficiente para aumentar a quantidade da venda. Estoque atual: $estoqueAtual");
            }

            // Atualizar venda
            $stmt = $conn->prepare("UPDATE venda SET ID_Cliente = :cliente_id, ID_Funcionario = :funcionario_id, data_venda = :data_venda WHERE ID_Venda = :venda_id");
            $stmt->execute([
                'cliente_id' => $cliente_id,
                'funcionario_id' => $funcionario_id,
                'data_venda' => $data_venda,
                'venda_id' => $venda_id
            ]);

            // Atualizar venda_produto
            $stmt = $conn->prepare("UPDATE venda_produto SET quantidade = :quantidade, preco = :preco WHERE ID_Venda = :venda_id AND ID_Produto = :produto_id");
            $stmt->execute([
                'quantidade' => $quantidade,
                'preco' => $preco,
                'venda_id' => $venda_id,
                'produto_id' => $produto_id
            ]);

            // Registrar movimento no estoque (apenas diferença)
            if ($diferenca != 0) {
                $tipoMovimento = $diferenca > 0 ? 'saida' : 'entrada';
                $stmt = $conn->prepare("INSERT INTO estoque (ID_Produto, tipo_movimento, quantidade) VALUES (:produto_id, :tipo, :quantidade)");
                $stmt->execute([
                    'produto_id' => $produto_id,
                    'tipo' => $tipoMovimento,
                    'quantidade' => abs($diferenca)
                ]);

                // Atualizar quantidade em produtos
                $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :diferenca WHERE ID_Produto = :produto_id");
                // Atenção: para 'entrada', diferenca é negativa, então subtrair diferenca negativa = somar
                $stmt->execute([
                    'diferenca' => $diferenca,
                    'produto_id' => $produto_id
                ]);
            }

            // Registrar log
            registrarLog($conn, $usuario_id, "Venda atualizada", "Venda ID: $venda_id, Produto ID: $produto_id, Quantidade antiga: $quantidadeAntiga, nova: $quantidade");
        }

        $conn->commit();

        // Redirecionar para evitar reenvio
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Erro: " . $e->getMessage() . "'); window.history.back();</script>";
        exit;
    }
}

// Opcional: editar venda via GET para preencher formulário (AJAX)
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $query = "SELECT v.ID_Venda, v.ID_Cliente, v.ID_Funcionario, vp.ID_Produto, vp.quantidade, vp.preco, v.data_venda
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
                            <td><?= isset($row['data_venda']) ? htmlspecialchars($row['data_venda']) : '' ?></td>
                            <td><?= isset($row['valor_total']) && $row['valor_total'] > 0 ? 'R$ ' . number_format($row['valor_total'], 2, ',', '.') : 'R$ 0,00' ?></td>
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
