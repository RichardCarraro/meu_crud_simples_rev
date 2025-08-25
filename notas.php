<?php
session_start();
if (empty($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['username'];
$path = 'notas.json';
$all = json_decode(@file_get_contents($path), true) ?? [];

function save_all($path, $data){
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// Criar nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $imageName = '';

    if (!is_dir('uploads')) { @mkdir('uploads', 0777, true); }

    if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $safe = preg_replace('/[^a-zA-Z0-9_-]/','', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
        $imageName = $safe . '_' . uniqid() . ($ext ? ('.' . $ext) : '');
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName);
    }

    if ($title !== '' && $content !== '') {
        array_unshift($all, [
            'user' => $user,
            'title' => $title,
            'content' => $content,
            'image' => $imageName,
            'date' => date('Y-m-d H:i')
        ]);
        save_all($path, $all);
    }

    header('Location: notas.php');
    exit;
}

// Excluir nota
if (isset($_GET['delete'])) {
    $idx = (int)$_GET['delete'];
    if (isset($all[$idx]) && $all[$idx]['user'] === $user) {
        if (!empty($all[$idx]['image']) && file_exists('uploads/' . $all[$idx]['image'])) {
            @unlink('uploads/' . $all[$idx]['image']);
        }
        array_splice($all, $idx, 1);
        save_all($path, $all);
    }
    header('Location: notas.php');
    exit;
}

// Notas do usuário atual
$userNotes = array_values(array_filter($all, fn($n) => $n['user'] === $user));
$notesWithImages = array_map(function($n){
    if(!empty($n['image']) && file_exists('uploads/'.$n['image'])){
        $imgData = base64_encode(file_get_contents('uploads/'.$n['image']));
        $ext = pathinfo($n['image'], PATHINFO_EXTENSION);
        $n['imageBase64'] = 'data:image/' . ($ext==='png'?'png':'jpeg') . ';base64,' . $imgData;
    } else {
        $n['imageBase64'] = null;
    }
    return $n;
}, $userNotes);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Minhas Notas</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
<header class="topbar">
  <h1>Notas de <?php echo htmlspecialchars($user); ?></h1>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php" class="danger">Sair</a>
    <button id="toggle-dark" type="button">🌙</button>
  </nav>
</header>

<main class="card">
  <h2>Nova nota</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">
    <input type="text" name="title" placeholder="Título" required>
    <textarea name="content" placeholder="Conteúdo" required></textarea>
    <input type="file" name="image" accept="image/*">
    <button type="submit">Salvar</button>
  </form>
</main>

<main class="card">
  <h2>Minhas notas</h2>
  <?php if (empty($userNotes)): ?>
    <p>Nenhuma nota ainda.</p>
  <?php else: ?>
    <ul class="notes">
      <?php foreach ($all as $i => $n): if ($n['user'] !== $user) continue; ?>
        <li class="note">
          <div class="note-head">
            <strong><?php echo htmlspecialchars($n['title']); ?></strong>
            <span class="muted"><?php echo htmlspecialchars($n['date']); ?></span>
          </div>
          <div class="note-body">
            <p><?php echo nl2br(htmlspecialchars($n['content'])); ?></p>
            <?php if (!empty($n['image'])): ?>
              <img src="uploads/<?php echo htmlspecialchars($n['image']); ?>" alt="Imagem da nota" class="thumb">
            <?php endif; ?>
          </div>
          <div class="note-actions">
            <a class="danger" href="notas.php?delete=<?php echo $i; ?>" onclick="return confirm('Excluir esta nota?')">Excluir</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="export-bar">
      <button id="export-txt" type="button">Exportar TXT</button>
      <button id="export-pdf" type="button">Exportar PDF</button>
    </div>
  <?php endif; ?>
</main>

<script>
const USER_NOTES = <?php echo json_encode($notesWithImages, JSON_UNESCAPED_UNICODE); ?>;

document.getElementById('export-txt')?.addEventListener('click', () => {
  if (!USER_NOTES.length) return alert('Sem notas para exportar.');
  const txt = USER_NOTES.map(n => `${n.date} - ${n.title}\n${n.content}`).join('\n\n');
  const blob = new Blob([txt], {type:'text/plain'});
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'notas.txt';
  link.click();
});

// Exportar PDF usando UMD jsPDF
document.getElementById('export-pdf')?.addEventListener('click', () => {
  if (!USER_NOTES.length) return alert('Sem notas para exportar.');
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  const margin = 20;
  const pageWidth = doc.internal.pageSize.getWidth() - margin*2;
  let y = margin;

  USER_NOTES.forEach((n, idx) => {
    if (y > doc.internal.pageSize.getHeight() - margin) { doc.addPage(); y = margin; }

    // Título
    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);
    doc.text(`${n.title} (${n.date})`, margin, y);
    y += 18;

    // Conteúdo
    doc.setFont("helvetica", "normal");
    doc.setFontSize(12);
    const lines = doc.splitTextToSize(n.content, pageWidth);
    lines.forEach(line => {
      if (y > doc.internal.pageSize.getHeight() - margin) { doc.addPage(); y = margin; }
      doc.text(line, margin, y);
      y += 16;
    });

    // Imagem
    if (n.imageBase64) {
      const imgProps = doc.getImageProperties(n.imageBase64);
      const maxWidth = pageWidth;
      const scale = Math.min(maxWidth / imgProps.width, 200 / imgProps.height, 1);
      const imgWidth = imgProps.width * scale;
      const imgHeight = imgProps.height * scale;
      if (y + imgHeight > doc.internal.pageSize.getHeight() - margin) { doc.addPage(); y = margin; }
      doc.addImage(n.imageBase64, 'JPEG', margin, y, imgWidth, imgHeight);
      y += imgHeight + 10;
    } else {
      y += 10;
    }

    // Linha separadora
    if (idx < USER_NOTES.length - 1) {
      if (y > doc.internal.pageSize.getHeight() - margin) { doc.addPage(); y = margin; }
      doc.setDrawColor(150);
      doc.line(margin, y, pageWidth + margin, y);
      y += 20;
    }
  });

  doc.save("notas.pdf");
});
</script>
<script>
// Dark mode toggle
const toggleBtn = document.getElementById('toggle-dark');
const body = document.body;

// Verifica preferência salva
if(localStorage.getItem('dark-mode') === 'on'){                   
    body.classList.add('dark');
}

toggleBtn.addEventListener('click', () => {
    body.classList.toggle('dark');
    // Salva a preferência
    if(body.classList.contains('dark')){
        localStorage.setItem('dark-mode', 'on');
    } else {
        localStorage.setItem('dark-mode', 'off');
    }
});
</script>


</body>
</html>
