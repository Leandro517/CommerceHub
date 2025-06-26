<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../config/config.php';

// Inicializar variáveis de pesquisa
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';

// Processar o cadastro ou atualização do fornecedor se o método for POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fornecedor_id = isset($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : '';
    $nome = $_POST['nome'];
    $cep = preg_replace('/\D/', '', $_POST['cep']); // limpa qualquer traço
    $endereco = $_POST['endereco'];
    $numero = $_POST['numero'];   // NOVO
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = strtoupper($_POST['estado']);
    $telefone = $_POST['telefone'];
    $condicoes_pagamento = $_POST['condicoes_pagamento'];

    try {
        if ($fornecedor_id) {
            // Atualização
            $stmt = $conn->prepare("UPDATE fornecedor SET 
                nome = :nome, cep = :cep, endereco = :endereco, numero = :numero, bairro = :bairro, 
                cidade = :cidade, estado = :estado, telefone = :telefone, 
                condicoes_pagamento = :condicoes_pagamento 
                WHERE ID_Fornecedor = :fornecedor_id");
            $stmt->bindParam(':fornecedor_id', $fornecedor_id);
        } else {
            // Inserção
            $stmt = $conn->prepare("INSERT INTO fornecedor 
                (nome, cep, endereco, numero, bairro, cidade, estado, telefone, condicoes_pagamento) 
                VALUES (:nome, :cep, :endereco, :numero, :bairro, :cidade, :estado, :telefone, :condicoes_pagamento)");
        }

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':numero', $numero); // bind do número
        $stmt->bindParam(':bairro', $bairro);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':condicoes_pagamento', $condicoes_pagamento);

        $stmt->execute();
        header("Location: ./cadastro_fornecedores.php");
        exit();
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}

// DELETE permanece igual...

// Consulta com filtro
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
                <th>CEP</th>
                <th>Endereço</th>
                <th>Número</th>
                <th>Telefone</th>
                <th>Condições de Pagamento</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="supplierTable">
            <?php foreach ($fornecedores as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nome']) ?></td>
                    <td><?= htmlspecialchars($row['cep']) ?></td>
                    <td><?= htmlspecialchars($row['endereco']) ?></td>
                    <td><?= htmlspecialchars($row['numero']) ?></td>
                    <td><?= htmlspecialchars($row['telefone']) ?></td>
                    <td><?= htmlspecialchars($row['condicoes_pagamento']) ?></td>
                    <td>
                        <button class='edit' 
                            onclick="editSupplier(
                                <?= $row['ID_Fornecedor'] ?>, 
                                '<?= addslashes($row['nome']) ?>', 
                                '<?= addslashes($row['cep']) ?>', 
                                '<?= addslashes($row['endereco']) ?>',
                                '<?= addslashes($row['numero']) ?>',
                                '<?= addslashes($row['bairro']) ?>',
                                '<?= addslashes($row['cidade']) ?>',
                                '<?= addslashes($row['estado']) ?>',
                                '<?= addslashes($row['telefone']) ?>', 
                                '<?= addslashes($row['condicoes_pagamento']) ?>'
                            )">Editar</button>
                        <button class='delete' onclick="deleteSupplier(<?= $row['ID_Fornecedor'] ?>)">Excluir</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<!-- Modal -->
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
                    <label for="cep">CEP:</label>
                    <input type="text" id="cep" name="cep" maxlength="9" required onblur="buscarCep(this.value)">
                </div>

                <div class="input-group">
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" required>
                </div>

                <div class="input-group">
                    <label for="numero">Número:</label>
                    <input type="text" id="numero" name="numero" required>
                </div>

                <div class="input-group">
                    <label for="bairro">Bairro:</label>
                    <input type="text" id="bairro" name="bairro" required>
                </div>

                <div class="input-group">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" required>
                </div>

                <div class="input-group">
                    <label for="estado">Estado (UF):</label>
                    <input type="text" id="estado" name="estado" maxlength="2" required>
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

        function meu_callback(conteudo) {
            if (!("erro" in conteudo)) {
                document.getElementById('endereco').value = conteudo.logradouro || '';
                document.getElementById('bairro').value = conteudo.bairro || '';
                document.getElementById('cidade').value = conteudo.localidade || '';
                document.getElementById('estado').value = conteudo.uf || '';
            } else {
                limpar_formulario_cep();
                alert("CEP não encontrado.");
            }
        }

        function editSupplier(id, nome, cep, endereco, numero, bairro, cidade, estado, telefone, condicoes_pagamento) {
            document.getElementById('fornecedor_id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('cep').value = cep;
            document.getElementById('endereco').value = endereco;
            document.getElementById('numero').value = numero;
            document.getElementById('bairro').value = bairro;
            document.getElementById('cidade').value = cidade;
            document.getElementById('estado').value = estado;
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

        function limpar_formulario_cep() {
            document.getElementById('endereco').value = "";
            document.getElementById('bairro').value = "";
            document.getElementById('cidade').value = "";
            document.getElementById('estado').value = "";
        }

        //Busca o CEP
        function buscarCep(valor) {
            var cep = valor.replace(/\D/g, '');

            if (cep != "") {
                var validacep = /^[0-9]{8}$/;

                if(validacep.test(cep)) {
                    document.getElementById('endereco').value = "...";

                    var script = document.createElement('script');
                    script.src = 'https://viacep.com.br/ws/' + cep + '/json/?callback=meu_callback';
                    document.body.appendChild(script);

                } else {
                    limpar_formulario_cep();
                    alert("Formato de CEP inválido.");
                }
            } else {
                limpar_formulario_cep();
            }
        }

        //Formata o telefone em tempo real
        document.getElementById('telefone').addEventListener('input', function (e) {
        let input = e.target;
        let value = input.value.replace(/\D/g, ''); // Remove tudo que não é número

        if (value.length > 11) value = value.slice(0, 11); // Limita a 11 dígitos

        if (value.length <= 10) {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            value = value.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }

        input.value = value;
    });
    </script>
    
   <!-- Rodapé -->
   <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
</body>
</html>
