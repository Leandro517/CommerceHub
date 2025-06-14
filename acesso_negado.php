<?php
echo '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Acesso Negado</title>
    <style>
        body {
            background: #fff5f5;
            color: #b00020;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            border: 2px solid #b00020;
            background: #fff0f0;
            padding: 40px 60px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(176, 0, 32, 0.2);
            max-width: 400px;
            width: 100%;
        }
        h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        h1 span {
            font-size: 3.5rem;
            line-height: 1;
        }
        p {
            font-size: 1.15rem;
            margin-top: 0;
        }
        @media (max-width: 420px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 2rem;
            }
            h1 span {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span>🚫</span>Acesso Negado</h1>
        <p>Você não tem permissão para acessar esta página.</p>
    </div>
</body>
</html>
';
?>
