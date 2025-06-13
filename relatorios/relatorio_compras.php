<?php
    include '../config/config.php';

    // Definindo os parâmetros de pesquisa
    $search_field = isset($_GET['search_field']) ? $_GET['search_field'] : '';
    $search_value = isset($_GET['search_value']) ? $_GET['search_value'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    $search_value_fornecedor = isset($_GET['search_value_fornecedor']) ? $_GET['search_value_fornecedor'] : '';
    $search_value_produto = isset($_GET['search_value_produto']) ? $_GET['search_value_produto'] : '';

    // Construir a consulta de pesquisa dinamicamente
    $sql = "SELECT c.ID_Compra, c.data_compra, c.valor_total, f.nome AS fornecedor, p.nome AS produto, cp.quantidade, cp.preco,
            (cp.quantidade * cp.preco) AS valor_total
            FROM compra c
            JOIN fornecedor f ON c.ID_Fornecedor = f.ID_Fornecedor
            JOIN compra_produto cp ON c.ID_Compra = cp.ID_Compra
            JOIN produtos p ON cp.ID_Produto = p.ID_Produto";

    // Adicionando filtros à consulta SQL
    $conditions = [];
    if ($start_date) {
        $conditions[] = "c.data_compra >= :start_date";
    }
    if ($end_date) {
        $conditions[] = "c.data_compra <= :end_date";
    }
    if ($search_value_fornecedor) {
        $conditions[] = "f.nome LIKE :search_value_fornecedor";
    }
    if ($search_value_produto) {
        $conditions[] = "p.nome LIKE :search_value_produto";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY c.ID_Compra";
    $stmt = $conn->prepare($sql);

    // Vincular os parâmetros de pesquisa
    if ($start_date) {
        $stmt->bindValue(':start_date', $start_date);
    }
    if ($end_date) {
        $stmt->bindValue(':end_date', $end_date);
    }
    if ($search_value_fornecedor) {
        $stmt->bindValue(':search_value_fornecedor', '%' . $search_value_fornecedor . '%');
    }
    if ($search_value_produto) {
        $stmt->bindValue(':search_value_produto', '%' . $search_value_produto . '%');
    }

    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Compras</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

</head>
<body>
    <header>
        <h1><a href="../index.php" style="color: white; text-decoration: none;">Relátorio de Compras</a></h1>
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
        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fornecedor</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Data da Compra</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($produtos)): ?>
                        <?php foreach ($produtos as $compra): ?>
                            <tr>
                                <td><?= htmlspecialchars($compra['ID_Compra']) ?></td>
                                <td><?= htmlspecialchars($compra['fornecedor']) ?></td>
                                <td><?= htmlspecialchars($compra['produto']) ?></td>
                                <td><?= htmlspecialchars($compra['quantidade']) ?></td>
                                <td><?= number_format($compra['preco'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($compra['data_compra']) ?></td>
                                <td><?= number_format($compra['valor_total'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Nenhum resultado encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal de Filtro -->
    <div id="searchModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Filtrar Compras</h2>
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
                        <label for="search_value_fornecedor">Fornecedor:</label>
                        <input type="text" id="search_value_fornecedor" name="search_value_fornecedor" value="<?= htmlspecialchars($search_value_fornecedor ?? '') ?>">
                    </div>

                    <div class="input-group">
                        <label for="search_value_produto">Produto:</label>
                        <input type="text" id="search_value_produto" name="search_value_produto" value="<?= htmlspecialchars($search_value_produto ?? '') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-save" onclick="clearFilters()">Limpar Filtros</button>
                    <button type="submit" class="btn-save">Aplicar Filtro</button>
                </div>
            </form>
        </div>
    </div>

   <!-- Rodapé -->
   <footer>
        <p>&copy; 2024 CommerceHub - Todos os direitos reservados.</p>
    </footer>
    <script>
    // Função para abrir o modal
    function openSearchModal() {
        document.getElementById("searchModal").style.display = "block";
    }

    // Função para fechar o modal
    function closeModal() {
        document.getElementById("searchModal").style.display = "none";
    }

    // Limpar os filtros
    function clearFilters() {
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
        document.getElementById('search_value_fornecedor').value = '';
        document.getElementById('search_value_produto').value = '';
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

    // Subtítulo "Relatório de Compras"
    doc.setFontSize(16);
    doc.text('Relatório de Compras', 70, 30);

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
    if ("<?= htmlspecialchars($start_date) ?>" !== "") {
        addHorizontalRectangularFilter("Data de: " + "<?= htmlspecialchars($start_date) ?>");
    }

    if ("<?= htmlspecialchars($end_date) ?>" !== "") {
        addHorizontalRectangularFilter("Até: " + "<?= htmlspecialchars($end_date) ?>");
    }

    if ("<?= htmlspecialchars($search_value_fornecedor) ?>" !== "") {
        addHorizontalRectangularFilter("Fornecedor: " + "<?= htmlspecialchars($search_value_fornecedor) ?>");
    }

    if ("<?= htmlspecialchars($search_value_produto) ?>" !== "") {
        addHorizontalRectangularFilter("Produto: " + "<?= htmlspecialchars($search_value_produto) ?>");
    }

    // Desenhar linha separando os filtros do relatório
    doc.setDrawColor(0, 0, 0); // Cor da linha (preto)
    doc.setLineWidth(0.5); // Espessura da linha
    doc.line(20, yOffset + 15, 200, yOffset + 15); // Desenha a linha

    yOffset += 20; // Ajuste após a linha

    // Adicionar informações das compras
    <?php foreach ($produtos as $compra): ?>
        checkPageLimit();
        doc.setFont('helvetica', 'bold');
        doc.text(`ID Compra: <?= htmlspecialchars($compra['ID_Compra']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.setFont('helvetica', 'normal');
        doc.text(`Fornecedor: <?= htmlspecialchars($compra['fornecedor']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Produto: <?= htmlspecialchars($compra['produto']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Quantidade: <?= htmlspecialchars($compra['quantidade']) ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Preço: <?= number_format($compra['preco'], 2, ',', '.') ?>`, 20, yOffset);
        yOffset += 6;
        doc.text(`Total: <?= number_format($compra['valor_total'], 2, ',', '.') ?>`, 20, yOffset);
        yOffset += 10;
    <?php endforeach; ?>

    // Salvar o PDF
    doc.save('relatorio_compras.pdf');
}
</script>
</body>
</html>
