<?php
    include '../config/config.php';

    // Definindo os parâmetros de pesquisa
    $search_value_cliente = isset($_GET['search_value_cliente']) ? $_GET['search_value_cliente'] : '';

    // Construir a consulta de pesquisa dinamicamente
    $sql = "SELECT c.nome AS cliente, COUNT(v.ID_Venda) AS frequencia
            FROM cliente c
            LEFT JOIN venda v ON c.ID_Cliente = v.ID_Cliente";

    // Se houver parâmetros de pesquisa, adicione ao WHERE
    if ($search_value_cliente) {
        // Adicionar WHERE após o JOIN se a pesquisa for aplicada
        $sql .= " WHERE c.nome LIKE :search_value_cliente";
    }

    $sql .= " GROUP BY c.ID_Cliente ORDER BY frequencia DESC";
    
    $stmt = $conn->prepare($sql);

    if ($search_value_cliente) {
        $stmt->bindValue(':search_value_cliente', '%' . $search_value_cliente . '%');
    }

    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Frequência de Clientes</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
    <header>
        <h1><a href="../index.php" style="color: white; text-decoration: none;">Relatório de Frequência de Clientes</a></h1>
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
            <table id="clientesTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Frequência de Compras</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($clientes)): ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= htmlspecialchars($cliente['cliente']) ?></td>
                                <td><?= htmlspecialchars($cliente['frequencia']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">Nenhum resultado encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal de Filtro -->
    <div id="searchModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Filtrar Vendas</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="filterForm" action="" method="GET">
                <div class="modal-body">
                    <!-- Cliente -->
                    <div class="input-group">
                        <label for="search_value_cliente">Cliente:</label>
                        <input type="text" id="search_value_cliente" name="search_value_cliente" value="<?= htmlspecialchars($search_value_cliente ?? '') ?>">
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
            document.getElementById('search_value_cliente').value = '';
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
    doc.text('Relatório de Vendas', 70, 30);

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
        if ("<?= htmlspecialchars($search_value_cliente) ?>" !== "") {
            addHorizontalRectangularFilter("Cliente: " + "<?= htmlspecialchars($search_value_cliente) ?>");
        }

        // Desenhar linha separando os filtros do relatório
        doc.setDrawColor(0, 0, 0); // Cor da linha (preto)
        doc.setLineWidth(0.5); // Espessura da linha
        doc.line(20, yOffset + 15, 200, yOffset + 15); // Desenha a linha

         yOffset += 20; // Ajuste após a linha
         
         <?php foreach ($clientes as $cliente): ?>
            checkPageLimit();
            doc.setFont('helvetica', 'normal');
            doc.text('Cliente: <?= htmlspecialchars($cliente['cliente']) ?>', 20, yOffset);
            yOffset += 6;
            doc.text('Frequência de Compras: <?= htmlspecialchars($cliente['frequencia']) ?>', 20, yOffset);
            yOffset += 10;
        <?php endforeach; ?>
        // Salvar o PDF
            doc.save('relatorio_frequencia_clientes.pdf');
        }
    </script>
</body>
</html>
