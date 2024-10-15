<?php
    session_start();

    // Destrói a sessão e redireciona para a página de login
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
?>