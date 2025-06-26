<?php
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

session_start();

global $decoded_data;

$chave_secreta = "sua_chave_secreta";

if (!isset($_SESSION['token'])) {
    header("Location: ../login.php");
    exit;
}

try {
    $decoded = JWT::decode($_SESSION['token'], new Key($chave_secreta, 'HS256'));
    $decoded_data = $decoded->data;
    $_SESSION['user_tipo'] = $decoded_data->tipo;
} catch (Exception $e) {
    header("Location: ../login.php");
    exit;
}

// Função para checar acesso por tipo de usuário
function verificarAcesso(array $tiposPermitidos) {
    global $decoded_data;
    if (!isset($decoded_data) || !in_array($decoded_data->tipo, $tiposPermitidos)) {
        header("Location: ../acesso_negado.php");
        exit;
    }
}
?>
