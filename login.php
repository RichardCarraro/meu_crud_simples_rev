<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = json_decode(@file_get_contents('users.json'), true) ?? [];
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['username'] = $username;
        header('Location: dashboard.php'); exit;
    } else {
        $msg = "Credenciais inválidas";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script>
</head>
<body>
<header class="topbar">
  <h1>Meu CRUD Simples</h1>
  <button id="toggle-dark" type="button">🌙</button>
</header>
<main class="card">
  <h2>Entrar</h2>
  <?php if (!empty($msg)) echo "<p class='error'>$msg</p>"; ?>
  <form method="POST" autocomplete="off">
      <input type="text" name="username" placeholder="Usuário" required>
      <input type="password" name="password" placeholder="Senha" required>
      <button type="submit">Entrar</button>
  </form>
  <p>Não tem conta? <a href="register.php">Registrar</a></p>
</main>
</body>
</html>
