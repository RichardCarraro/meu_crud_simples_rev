<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = json_decode(@file_get_contents('users.json'), true) ?? [];
    $username = trim($_POST['username'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($username === '' || $password_raw === '') {
        $msg = "Preencha todos os campos.";
    } elseif ($password_raw !== $confirm) {
        $msg = "As senhas não coincidem.";
    } elseif (isset($users[$username])) {
        $msg = "Usuário já existe.";
    } else {
        $users[$username] = ['password' => password_hash($password_raw, PASSWORD_DEFAULT)];
        file_put_contents('users.json', json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        header('Location: login.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Registrar</title>
<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script>
</head>
<body>
<header class="topbar">
  <h1>Meu CRUD Simples</h1>
  <button id="toggle-dark" type="button">🌙</button>
</header>
<main class="card">
  <h2>Criar conta</h2>
  <?php if (!empty($msg)) echo "<p class='error'>$msg</p>"; ?>
  <form method="POST" autocomplete="off">
      <input type="text" name="username" placeholder="Usuário" required>
      <input type="password" name="password" placeholder="Senha" required>
      <input type="password" name="confirm" placeholder="Confirmar senha" required>
      <button type="submit">Registrar</button>
  </form>
  <p>Já tem conta? <a href="login.php">Entrar</a></p>
</main>
</body>
</html>
