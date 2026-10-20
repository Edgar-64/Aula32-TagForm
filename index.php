<?php
require_once 'conexao.php';

// Cadastro de Produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['descricao'])) {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);

    // Upload de Imagem Simples
    $imagem = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem = 'uploads/' . uniqid() . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['imagem']['tmp_name'], $imagem);
    }

    $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, imagem) VALUES (?, ?, ?)");
    $stmt->execute([$nome, $descricao, $imagem]);
    header('Location: index.php');
    exit;
}

// Buscar Ranking dos Produtos (Média geral das notas)
$queryRanking = $pdo->query("
    SELECT p.id, p.nome, p.imagem,
           COALESCE(AVG(a.nota), 0) as media_notas,
           COUNT(a.id) as total_avaliacoes
    FROM produtos p
    LEFT JOIN avaliacoes a ON p.id = a.produto_id
    GROUP BY p.id
    ORDER BY media_notas DESC, total_avaliacoes DESC
");
$ranking = $queryRanking->fetchAll(PDO::FETCH_ASSOC);

// Dados para o Gráfico (JSON)
$nomesProdutos = array_column($ranking, 'nome');
$mediasProdutos = array_map(fn($v) => round($v, 2), array_column($ranking, 'media_notas'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Produtos por Avaliação</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🏆 Sistema de Avaliação & Ranking</a>
            <div>
                <a href="gerenciar_perguntas.php" class="btn btn-outline-light btn-sm me-2">Gerenciar Perguntas (10)</a>
                <a href="setup.php" class="btn btn-outline-warning btn-sm">Resetar/Instalar DB</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">

        <!-- Formulário de Cadastro e Gráfico de Classificação -->
        <div class="row g-4 mb-5">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white fw-bold">Cadastrar Novo Produto</div>
                    <div class="card-body">
                        <form action="index.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Nome do Produto</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <textarea name="descricao" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Foto do Produto</label>
                                <input type="file" name="imagem" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Salvar Produto</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white fw-bold">Gráfico de Classificação dos Produtos</div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div style="width: 100%; height: 300px;">
                            <canvas id="rankingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista e Posição no Ranking -->
        <h3 class="mb-3 fw-bold text-secondary">Podium e Classificação Geral</h3>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($ranking as $index => $prod): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 position-relative">
                        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger fs-6 m-2">
                            #<?php echo $index + 1; ?>
                        </span>
                        <?php if (!empty($prod['imagem'])): ?>
                            <img src="<?php echo $prod['imagem']; ?>" class="card-img-top" alt="..." style="height: 180px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 180px;">Sem Imagem</div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($prod['nome']); ?></h5>
                            <p class="text-muted small mb-2">Média: <strong><?php echo number_format($prod['media_notas'], 1, ',', '.'); ?> ★</strong> (<?php echo $prod['total_avaliacoes']; ?> votos)</p>
                            <a href="avaliacao.php?produto_id=<?php echo $prod['id']; ?>" class="btn btn-outline-primary btn-sm mt-auto">Avaliar Produto 📝</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Script Chart.js -->
    <script>
        const ctx = document.getElementById('rankingChart').getContext('2d');
        const rankingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($nomesProdutos); ?>,
                datasets: [{
                    label: 'Média de Avaliação',
                    data: <?php echo json_encode($mediasProdutos); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5
                    }
                }
            }
        });
    </script>
</body>
</html>