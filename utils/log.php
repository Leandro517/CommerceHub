<?php
function registrarLog($conn, $usuario_id, $acao, $detalhes = null) {
    $stmt = $conn->prepare("INSERT INTO logs (usuario_id, acao, detalhes, data_hora) VALUES (:usuario_id, :acao, :detalhes, NOW())");
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':acao', $acao);
    $stmt->bindParam(':detalhes', $detalhes);
    $stmt->execute();
}
?>
 