<?php
include '../config/config.php';

// Definindo os parâmetros de pesquisa
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search_value_funcionario = isset($_GET['search_value_funcionario']) ? $_GET['search_value_funcionario'] : '';

// Construir a consulta de pesquisa dinamicamente
$sql = "SELECT v.ID_Venda, v.data_venda, 
               SUM(vp.preco * vp.quantidade) AS valor_total, 
               f.nome AS funcionario, c.nome AS cliente, vp.quantidade, p.nome AS produto
        FROM venda v
        JOIN funcionario f ON v.ID_Funcionario = f.ID_Funcionario
        JOIN cliente c ON v.ID_Cliente = c.ID_Cliente
        JOIN venda_produto vp ON v.ID_Venda = vp.ID_Venda
        JOIN produtos p ON vp.ID_Produto = p.ID_Produto";

// Adicionar filtros
$filters = [];
if ($start_date) {
    $filters[] = "v.data_venda >= :start_date";
}
if ($end_date) {
    $filters[] = "v.data_venda <= :end_date";
}
if ($search_value_funcionario) {
    $filters[] = "f.nome LIKE :search_value_funcionario";
}

if (count($filters) > 0) {
    $sql .= " WHERE " . implode(" AND ", $filters);
}

$sql .= " GROUP BY v.ID_Venda ORDER BY v.ID_Venda"; // Agrupar por venda
$stmt = $conn->prepare($sql);

// Bind the values to the query
if ($start_date) {
    $stmt->bindValue(':start_date', $start_date);
}
if ($end_date) {
    $stmt->bindValue(':end_date', $end_date);
}
if ($search_value_funcionario) {
    $stmt->bindValue(':search_value_funcionario', '%' . $search_value_funcionario . '%');
}

$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas por Funcionários</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
    <header>
        <h1><a href="../index.php" style="color: white; text-decoration: none;">Vendas por Funcionários</a></h1>
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
            <button type="button" onclick="openSearchModal()" class="button-pdf">Filtrar</button>
            <button type="button" onclick="generatePDF()" class="button-pdf">Gerar PDF</button>
        </section>
        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Funcionário</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Total</th>
                        <th>Data da Venda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vendas)): ?>
                        <?php foreach ($vendas as $venda): ?>
                            <tr>
                                <td><?= htmlspecialchars($venda['ID_Venda']) ?></td>
                                <td><?= htmlspecialchars($venda['funcionario']) ?></td>
                                <td><?= htmlspecialchars($venda['produto']) ?></td>
                                <td><?= htmlspecialchars($venda['quantidade']) ?></td>
                                <td><?= number_format($venda['valor_total'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($venda['data_venda']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Nenhum resultado encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Modal de Filtro -->
        <div id="searchModal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Filtrar Vendas</h2>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <form id="filterForm" action="" method="GET">
                    <div class="modal-body">
                        <div class="input-group">
                            <label for="start_date">Data de:</label>
                            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>">
                        </div>

                        <div class="input-group">
                            <label for="end_date">Até:</label>
                            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>">
                        </div>

                        <div class="input-group">
                            <label for="search_value_funcionario">Funcionário:</label>
                            <input type="text" id="search_value_funcionario" name="search_value_funcionario" value="<?= htmlspecialchars($search_value_funcionario ?? '') ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-save" onclick="clearFilters()">Limpar Filtros</button>
                        <button type="submit" class="btn-save">Aplicar Filtro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>

    <script>
        function openSearchModal() {
    document.getElementById('searchModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('searchModal').style.display = 'none';
    }

    function clearFilters() {
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
        document.getElementById('search_value_funcionario').value = '';
        window.location.href = window.location.pathname;
    }


    function generatePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const pageHeight = doc.internal.pageSize.height; // Altura da página
    const marginBottom = 20; // Margem inferior para evitar corte do texto
    let yOffset = 20; // Posição inicial para o conteúdo
    let xOffset = 20; // Posição inicial horizontal para os filtros

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

    // Subtítulo "Relatório de Vendas"
    doc.setFontSize(16);
    doc.text('Relatório de Vendas por Funcionário', 70, 30);

    // Título "Filtros Utilizados"
    doc.setFontSize(14);
    doc.text('Filtros Utilizados', 20, 60);

    yOffset = 70; // Posição inicial para os filtros

    // Função para adicionar cada filtro como retângulo
    function addHorizontalRectangularFilter(text) {
        const textWidth = doc.getTextWidth(text);
        const rectWidth = textWidth + 10; // Ajuste do tamanho do retângulo
        if (xOffset + rectWidth > 190) { // Verifica se o filtro ultrapassa a largura da página
            xOffset = 20; // Reinicia o xOffset para a próxima linha
            yOffset += 15; // Aumenta o yOffset para a próxima linha
        }

        if (yOffset > pageHeight - marginBottom) { // Verificar se vai cortar o conteúdo
            checkPageLimit();
        }

        doc.setFillColor(200, 220, 255);
        doc.rect(xOffset, yOffset, rectWidth, 10, 'F'); // Desenha o retângulo

        doc.setTextColor(0, 0, 0); // Cor do texto
        doc.text(text, xOffset + 5, yOffset + 6); // Posição do texto
        xOffset += rectWidth + 5; // Ajuste horizontal para o próximo filtro
    }

    // Adicionando os filtros
    <?php if (!empty($start_date)) : ?>
        addHorizontalRectangularFilter("Data de: <?= htmlspecialchars($start_date) ?>");
    <?php endif; ?>
    <?php if (!empty($end_date)) : ?>
        addHorizontalRectangularFilter("Até: <?= htmlspecialchars($end_date) ?>");
    <?php endif; ?>
    <?php if (!empty($search_value_funcionario)) : ?>
        addHorizontalRectangularFilter("Funcionário: <?= htmlspecialchars($search_value_funcionario) ?>");
    <?php endif; ?>
    <?php if (!empty($search_value_produto)) : ?>
        addHorizontalRectangularFilter("Produto: <?= htmlspecialchars($search_value_produto) ?>");
    <?php endif; ?>

    // Desenhar linha separando os filtros do relatório
    doc.setDrawColor(0, 0, 0); // Cor da linha (preto)
    doc.setLineWidth(0.5); // Espessura da linha
    doc.line(20, yOffset + 15, 200, yOffset + 15); // Desenha a linha

    yOffset += 20; // Ajuste após a linha

    // Adicionar informações das vendas
    <?php foreach ($vendas as $venda): ?>
        checkPageLimit();
        doc.setFont('helvetica', 'bold');
        doc.text(`ID Venda: <?= htmlspecialchars($venda['ID_Venda']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.setFont('helvetica', 'normal');
        doc.text(`Funcionário: <?= htmlspecialchars($venda['funcionario']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Produto: <?= htmlspecialchars($venda['produto']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Quantidade: <?= htmlspecialchars($venda['quantidade']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Valor Total: <?= number_format($venda['valor_total'], 2, ',', '.') ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Data da Venda: <?= htmlspecialchars($venda['data_venda']) ?>`, 20, yOffset);
        yOffset += 10;
    <?php endforeach; ?>

    // Salvar o PDF
    doc.save('relatorio_vendas_por_funcionarios.pdf');
}

    </script>
</body>
</html>