<?php
include '../config/config.php';

// Inicializando as variáveis para evitar warnings
$search_value_produto = isset($_GET['search_value_produto']) ? $_GET['search_value_produto'] : '';
$search_value_categoria = isset($_GET['search_value_categoria']) ? $_GET['search_value_categoria'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$tipo_movimento = isset($_GET['tipo_movimento']) ? $_GET['tipo_movimento'] : '';

// Construir a consulta de pesquisa dinamicamente
$sql = "SELECT 
    p.nome AS produto, 
    c.nome AS categoria, 
    e.tipo_movimento, 
    e.data_movimento, 
    e.quantidade
FROM 
    produtos p
JOIN 
    categoria c ON p.ID_Categoria = c.ID_Categoria
JOIN 
    estoque e ON p.ID_Produto = e.ID_Produto
LEFT JOIN 
    venda_produto vp ON p.ID_Produto = vp.ID_Produto
LEFT JOIN 
    venda v ON vp.ID_Venda = v.ID_Venda
WHERE 
    1=1
GROUP BY 
    p.ID_Produto, e.tipo_movimento, e.data_movimento, e.quantidade, c.nome
";

// Verificar se cada valor de filtro está presente e adicionar a condição SQL correspondente
if ($search_value_produto != '') {
    $sql .= " AND p.nome LIKE :search_value_produto";
}

if ($search_value_categoria != '') {
    $sql .= " AND c.nome LIKE :search_value_categoria";
}

if ($start_date != '') {
    $sql .= " AND e.data_movimento >= :start_date";
}

if ($end_date != '') {
    $sql .= " AND e.data_movimento <= :end_date";
}

if ($tipo_movimento != '') {
    $sql .= " AND e.tipo_movimento = :tipo_movimento";
}

// Ordenar pela data do movimento
$sql .= " ORDER BY e.data_movimento DESC";

// Preparar a consulta
$stmt = $conn->prepare($sql);

// Vincular os valores de pesquisa
if ($search_value_produto != '') {
    $stmt->bindValue(':search_value_produto', '%' . $search_value_produto . '%');
}

if ($search_value_categoria != '') {
    $stmt->bindValue(':search_value_categoria', '%' . $search_value_categoria . '%');
}

if ($start_date != '') {
    $stmt->bindValue(':start_date', $start_date);
}

if ($end_date != '') {
    $stmt->bindValue(':end_date', $end_date);
}

if ($tipo_movimento != '') {
    $stmt->bindValue(':tipo_movimento', $tipo_movimento);
}

// Executar a consulta
$stmt->execute();

// Obter os resultados
$movimentacoes = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Estoque</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/modal.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

</head>
<body>
<header>
    <h1><a href="../index.php" style="color: white; text-decoration: none;">Relatório de Estoque</a></h1>
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
        <!-- Botão para abrir o modal de filtro -->
        <button type="button" onclick="openSearchModal()" class="button-pdf">Filtrar</button>
        <button type="button" onclick="generatePDF()" class="button-pdf">Gerar PDF</button>
    </section>

    <!-- Exibição da Tabela de Movimentações -->
    <section class="table-section">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Tipo de Movimento</th>
                    <th>Quantidade</th>
                    <th>Data do Movimento</th>
                </tr>
            </thead>
            <tbody>
    <?php if (empty($movimentacoes)): ?>
        <tr>
            <td colspan="5">Nenhuma movimentação encontrada.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($movimentacoes as $movimentacao): ?>
            <tr>
                <td><?= htmlspecialchars($movimentacao['produto']) ?></td>
                <td><?= htmlspecialchars($movimentacao['categoria'] ?: 'Sem Categoria') ?></td>
                <td><?= htmlspecialchars($movimentacao['tipo_movimento']) ?></td>
                <td><?= htmlspecialchars($movimentacao['quantidade']) ?></td>
                <td><?= htmlspecialchars($movimentacao['data_movimento']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>

        </table>
    </section>
</div>

<!-- Modal -->
<div id="searchModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Filtrar Movimentações</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="filterForm" action="" method="GET">
            <div class="modal-body">
                <div class="input-group">
                    <label for="start_date">Data de:</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>

                <div class="input-group">
                    <label for="end_date">Até:</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>

                <div class="input-group">
                    <label for="search_value_produto">Produto:</label>
                    <input type="text" id="search_value_produto" name="search_value_produto" value="<?= $search_value_produto ?>">
                </div>

                <div class="input-group">
                    <label for="search_value_categoria">Categoria:</label>
                    <input type="text" id="search_value_categoria" name="search_value_categoria" value="<?= $search_value_categoria ?>">
                </div>

                <div class="input-group">
                    <label for="tipo_movimento">Tipo de Movimento:</label>
                    <select id="tipo_movimento" name="tipo_movimento">
                        <option value="">Todos</option>
                        <option value="entrada" <?= $tipo_movimento == 'entrada' ? 'selected' : '' ?>>Entrada</option>
                        <option value="saida" <?= $tipo_movimento == 'saida' ? 'selected' : '' ?>>Saída</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-save" onclick="clearFilters()">Limpar Filtros</button>
                <button type="submit" class="btn-save">Aplicar Filtro</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Função para abrir o modal de filtro
    function openSearchModal() {
        document.getElementById('searchModal').style.display = 'block';
    }

    // Função para fechar o modal
    function closeModal() {
        document.getElementById('searchModal').style.display = 'none';
    }

    // Fechar o modal ao clicar fora da área modal
    window.onclick = function(event) {
        if (event.target == document.getElementById('searchModal')) {
            closeModal();
        }
    }

    // Função para limpar os filtros
    function clearFilters() {
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('search_value_produto').value = '';
    document.getElementById('search_value_categoria').value = '';
    document.getElementById('search_value_funcionario').value = '';
    document.getElementById('tipo_movimento').value = '';
    window.location.href = window.location.pathname;
}

function generatePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const pageHeight = doc.internal.pageSize.height; // Altura da página
    const marginBottom = 20; // Margem inferior para evitar corte do texto

    // Função para verificar se precisa de nova página
    function checkPageLimit() {
        if (yOffset > pageHeight - marginBottom) {
            doc.addPage();
            yOffset = 20; // Reinicia o yOffset na nova página
        }
    }

    // Adicionar imagem no canto superior esquerdo
    const img = '../logo.jpg'; // Substitua pela URL da sua imagem
    doc.addImage(img, 'PNG', 20, 10, 40, 40); // Posição e tamanho da imagem

    // Título "CommerceHub"
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.text('CommerceHub', 70, 20); // Posição ao lado da imagem

    // Subtítulo "Relatório de Estoque"
    doc.setFontSize(16);
    doc.text('Relatório de Estoque', 70, 30);

    // Título "Filtros Utilizados"
    doc.setFontSize(14);
    doc.text('Filtros Utilizados', 20, 60);

    let yOffset = 70; // Posição inicial para os filtros
    let xOffset = 20; // Posição inicial na horizontal
    const fontSize = 12; // Tamanho da fonte
    const fontHeight = fontSize * 0.5;
    const rectHeight = fontHeight + 1;
    const rectPadding = 1;
    const maxXOffset = 190;

    // Função para adicionar cada filtro como retângulo alinhado horizontalmente
    function addHorizontalRectangularFilter(text) {
        const textWidth = doc.getTextWidth(text);
        const rectWidth = textWidth + rectPadding * 2;

        if (xOffset + rectWidth > maxXOffset) {
            xOffset = 20;
            yOffset += rectHeight + 5;
            checkPageLimit();
        }

        doc.setFillColor(200, 220, 255);
        doc.rect(xOffset, yOffset, rectWidth, rectHeight, 'F');

        const textX = xOffset + rectWidth / 2 - textWidth / 2;
        const textY = yOffset + rectHeight / 2 + fontHeight / 4;
        doc.setFontSize(fontSize);
        doc.setTextColor(0, 0, 0);
        doc.text(text, textX, textY);

        xOffset += rectWidth + 5;
    }

    // Adiciona os filtros
    if (document.getElementById('search_value_produto').value) {
        addHorizontalRectangularFilter('Produto: ' + document.getElementById('search_value_produto').value);
    }
    if (document.getElementById('search_value_categoria').value) {
        addHorizontalRectangularFilter('Categoria: ' + document.getElementById('search_value_categoria').value);
    }

    if (document.getElementById('start_date').value) {
        addHorizontalRectangularFilter('Data de: ' + document.getElementById('start_date').value);
    }
    if (document.getElementById('end_date').value) {
        addHorizontalRectangularFilter('Até: ' + document.getElementById('end_date').value);
    }
    if (document.getElementById('tipo_movimento').value) {
        addHorizontalRectangularFilter('Movimento: ' + document.getElementById('tipo_movimento').value);
    }

   // Desenhar linha separando os filtros do relatório
   doc.setDrawColor(0, 0, 0); // Cor da linha (preto)
    doc.setLineWidth(0.5); // Espessura da linha
    doc.line(20, yOffset + rectHeight + 5, 200, yOffset + rectHeight + 5); // Desenha a linha

    yOffset += rectHeight + 10; // Ajuste o yOffset após a linha
    
    <?php foreach ($movimentacoes as $movimentacao): ?>
        checkPageLimit();
        doc.setFont('helvetica', 'bold');
        doc.text(`Produto: <?= htmlspecialchars($movimentacao['produto']) ?>`, 20, yOffset);
        doc.setFont('helvetica', 'normal');
        yOffset += 6;
        doc.text(`Categoria: <?= htmlspecialchars($movimentacao['categoria'] ?: 'Sem Categoria') ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Movimento: <?= htmlspecialchars($movimentacao['tipo_movimento']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Quantidade: <?= htmlspecialchars($movimentacao['quantidade']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Data: <?= htmlspecialchars($movimentacao['data_movimento']) ?>`, 20, yOffset);
        yOffset += 10;
    <?php endforeach; ?>

    doc.save('relatorio_estoque.pdf');
}
</script>

<!-- Rodapé -->
<footer>
    <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
</footer>
</body>
</html>
