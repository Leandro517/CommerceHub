<?php
session_start();

function verificarAcesso($tiposPermitidos = []) {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: ../login.php");
        exit;
    }

    if (!in_array($_SESSION['user_tipo'], $tiposPermitidos)) {
        return false;
    }

    return true;
}
?>
