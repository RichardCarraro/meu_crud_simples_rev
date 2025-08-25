<?php
session_start();
if (empty($_SESSION['username'])) { header('Location: login.php'); exit; }
$user = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script>
</head>
<body>
<header class="topbar">
  <h1>Olá, <?php echo htmlspecialchars($user); ?></h1>
  <nav>
    <a href="notas.php">Minhas notas</a>
    <a href="logout.php" class="danger">Sair</a>
    <button id="toggle-dark" type="button">🌙</button>
  </nav>
</header>
<main class="card">
  <h2>Bem-vindo ao painel</h2>
  <p>Use o menu acima para criar e gerenciar suas notas.</p>
</main>
</body>
</html>
