<?php
require_once 'conexao.php';

$produto_id = isset($_GET['produto_id'])
    ? intval($_GET['produto_id'])
    : 0;

$stmtProd = $pdo->prepare(
    "SELECT * FROM produtos WHERE id = ?"
);
$stmtProd->execute([$produto_id]);
$produto = $stmtProd->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    die("Produto não encontrado. <a href='index.php'>Voltar</a>");
}

$perguntas = $pdo->query(
    "SELECT * FROM perguntas ORDER BY ordem ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['notas']) && is_array($_POST['notas'])) {
        try {
            $stmtIns = $pdo->prepare(
                "INSERT INTO avaliacoes
                (produto_id, pergunta_id, nota)
                VALUES (?, ?, ?)"
            );

            foreach ($_POST['notas'] as $pergunta_id => $nota) {
                $pergunta_id = intval($pergunta_id);
                $nota = intval($nota);

                // Validar pergunta e nota
                if (
                    $nota < 1 ||
                    $nota > 5 ||
                    !in_array(
                        $pergunta_id,
                        array_column($perguntas, 'id')
                    )
                ) {
                    continue;
                }

                $stmtIns->execute([
                    $produto_id,
                    $pergunta_id,
                    $nota
                ]);
            }

            echo "<script>
                alert('Avaliação enviada com sucesso!');
                window.location.href = 'index.php';
            </script>";
            exit;

        } catch (PDOException $e) {
            die("Erro ao salvar a avaliação. Tente novamente.");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Avaliar: <?= htmlspecialchars($produto['nome']) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f3f6fb;
            color: #243047;
            font-family: Arial, sans-serif;
        }

        .pagina {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .voltar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .card-avaliacao {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 28px rgba(28, 48, 85, 0.09);
        }

        .cabecalho {
            background: linear-gradient(135deg, #1769e0, #5046e5);
            color: white;
            padding: 24px;
        }

        .cabecalho h1 {
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .conteudo {
            padding: 28px;
        }

        .pergunta {
            padding: 0 0 24px;
            margin-bottom: 24px;
            border-bottom: 1px solid #e8edf4;
        }

        .enunciado {
            display: block;
            font-size: 1.05rem;
            font-weight: 600;
            line-height: 1.6;
            margin-bottom: 16px;
            overflow-wrap: anywhere;
        }

        .opcoes-nota {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .opcao {
            position: relative;
        }

        .opcao input {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
        }

        .opcao label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            width: 100%;
            min-height: 70px;
            padding: 10px 4px;
            border: 2px solid #e0e7f1;
            border-radius: 13px;
            background: #fff;
            color: #56647a;
            cursor: pointer;
            transition: .2s ease;
        }

        .opcao label .estrela {
            font-size: 1.35rem;
            color: #f4b400;
        }

        .opcao label .numero {
            font-size: .85rem;
            font-weight: 700;
        }

        .opcao input:checked + label {
            background: #eaf2ff;
            border-color: #2877ee;
            color: #1459bf;
        }

        .opcao input:focus-visible + label {
            outline: 3px solid #91baff;
            outline-offset: 3px;
        }

        .btn-enviar {
            width: 100%;
            min-height: 52px;
            border: none;
            border-radius: 12px;
            background: #16865b;
            color: white;
            font-size: 1rem;
            font-weight: 700;
            transition: .2s;
        }

        .btn-enviar:hover {
            background: #106b48;
            color: white;
        }

        .aviso {
            border-radius: 12px;
            padding: 16px;
            line-height: 1.6;
        }

        @media (max-width: 576px) {
            .pagina {
                padding: 16px 12px 28px;
            }

            .voltar {
                margin-bottom: 14px;
            }

            .cabecalho {
                padding: 20px 16px;
            }

            .cabecalho h1 {
                font-size: 1.2rem;
            }

            .conteudo {
                padding: 20px 15px;
            }

            .enunciado {
                font-size: .98rem;
            }

            .opcoes-nota {
                gap: 6px;
            }

            .opcao label {
                min-height: 64px;
                padding: 8px 2px;
                border-radius: 10px;
            }

            .opcao label .estrela {
                font-size: 1.2rem;
            }

            .opcao label .numero {
                font-size: .8rem;
            }

            .btn-enviar {
                min-height: 54px;
            }
        }

        @media (max-width: 360px) {
            .conteudo {
                padding: 18px 11px;
            }

            .opcoes-nota {
                gap: 4px;
            }

            .opcao label {
                min-height: 58px;
            }

            .opcao label .estrela {
                font-size: 1.05rem;
            }
        }
    </style>
</head>

<body>
    <main class="pagina">

        <a href="index.php" class="btn btn-outline-secondary voltar">
            ← Voltar ao início
        </a>

        <section class="card card-avaliacao">

            <header class="cabecalho">
                <h1>
                    Avaliação do produto:
                    <?= htmlspecialchars($produto['nome']) ?>
                </h1>
            </header>

            <div class="conteudo">

                <?php if (empty($perguntas)): ?>

                    <div class="alert alert-warning aviso">
                        Nenhuma pergunta cadastrada no sistema.
                        Cadastre as questões primeiro em
                        <a href="gerenciar_perguntas.php">
                            Gerenciar Perguntas
                        </a>.
                    </div>

                <?php else: ?>

                    <p class="text-secondary mb-4">
                        Responda às perguntas abaixo selecionando
                        uma nota de 1 a 5 estrelas.
                    </p>

                    <form
                        action="avaliacao.php?produto_id=<?= $produto_id ?>"
                        method="POST"
                    >

                        <?php foreach ($perguntas as $index => $p): ?>

                            <fieldset class="pergunta">

                                <legend class="enunciado">
                                    Q<?= $index + 1 ?>.
                                    <?= htmlspecialchars($p['enunciado']) ?>
                                </legend>

                                <div class="opcoes-nota">

                                    <?php for ($i = 1; $i <= 5; $i++): ?>

                                        <div class="opcao">

                                            <input
                                                type="radio"
                                                name="notas[<?= $p['id'] ?>]"
                                                id="q<?= $p['id'] ?>_<?= $i ?>"
                                                value="<?= $i ?>"
                                                required
                                            >

                                            <label for="q<?= $p['id'] ?>_<?= $i ?>">
                                                <span class="estrela"
                                                    aria-hidden="true">★</span>
                                                <span class="numero">
                                                    <?= $i ?>
                                                </span>
                                            </label>

                                        </div>

                                    <?php endfor; ?>

                                </div>

                            </fieldset>

                        <?php endforeach; ?>

                        <button
                            type="submit"
                            class="btn btn-enviar"
                        >
                            Enviar avaliação 🚀
                        </button>

                    </form>

                <?php endif; ?>

            </div>
        </section>

    </main>
</body>
</html>