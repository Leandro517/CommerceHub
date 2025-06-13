<?php
header('Content-Type: application/json');
require_once 'vendor/autoload.php';
require_once 'config/config.php';

use Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['username'] ?? '';
$senha = $input['password'] ?? '';

if (!$email || !$senha) {
    http_response_code(400);
    echo json_encode(['error' => 'Preencha todos os campos.']);
    exit;
}

// Consulta no banco com PDO
$sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':email', $email, PDO::PARAM_STR);
$stmt->execute();

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'E-mail ou senha inválidos.']);
    exit;
}

// Gerar token JWT
$payload = [
    "iss" => "commercehub",
    "aud" => "commercehub_usuarios",
    "iat" => time(),
    "exp" => time() + 3600,
    "data" => [
        "id" => $usuario['id'],
        "nome" => $usuario['nome'],
        "email" => $usuario['email'],
        "tipo" => $usuario['tipo']
    ]
];

$chave_secreta = 'leandro_commercehub2024_seguro';

$token = JWT::encode($payload, $chave_secreta, 'HS256');

echo json_encode(['token' => $token]);
