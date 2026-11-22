
<?php
require_once __DIR__ . '/../config/conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificacao = trim($_POST['identificacao'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $complemento = trim($_POST['complemento'] ?? '');

    if (
        $identificacao === '' ||
        $cep === '' ||
        $logradouro === '' ||
        $numero === '' ||
        $cidade === '' ||
        $estado === ''
    ) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (strlen($estado) !== 2) {
        $erro = 'Informe a sigla do estado com duas letras.';
    } else {
        try {
            $sql = "INSERT INTO enderecos
                    (identificacao, cep, logradouro, numero,
                     cidade, estado, complemento)
                    VALUES
                    (:identificacao, :cep, :logradouro, :numero,
                     :cidade, :estado, :complemento)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ':identificacao' => $identificacao,
                ':cep' => $cep,
                ':logradouro' => $logradouro,
                ':numero' => $numero,
                ':cidade' => $cidade,
                ':estado' => $estado,
                ':complemento' => $complemento !== '' ? $complemento : null
            ]);

            $mensagem = 'Endereço cadastrado com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Não foi possível cadastrar o endereço.';
        }
    }
}

$stmt = $conexao->query(
    "SELECT * FROM enderecos ORDER BY id DESC"
);

$enderecos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTA BIKERS | Endereços</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/menu.php'; ?>

<main class="conteudo">

    <header class="topo">
        <div>
            <h1>Endereços</h1>
            <p>Cadastro e gerenciamento de endereços de origem e destino.</p>
        </div>
    </header>

    <?php if ($mensagem): ?>
        <p style="background:#d1fae5; padding:12px; margin-bottom:18px;">
            <?= htmlspecialchars($mensagem) ?>
        </p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p style="background:#fee2e2; padding:12px; margin-bottom:18px;">
            <?= htmlspecialchars($erro) ?>
        </p>
    <?php endif; ?>

    <section class="formulario">
        <h2>Novo endereço</h2>
        <br>

        <form method="POST">

            <div class="campo">
                <label for="identificacao">Identificação do endereço</label>
                <input type="text" id="identificacao"
                       name="identificacao" maxlength="120"
                       placeholder="Ex.: Depósito principal ou Cliente"
                       required>
            </div>

            <div class="campo">
                <label for="cep">CEP</label>
                <input type="text" id="cep" name="cep"
                       maxlength="10" placeholder="00000-000"
                       required>
            </div>

            <div class="campo">
                <label for="logradouro">Rua / Logradouro</label>
                <input type="text" id="logradouro"
                       name="logradouro" maxlength="150" required>
            </div>

            <div class="campo">
                <label for="numero">Número</label>
                <input type="text" id="numero" name="numero"
                       maxlength="20" required>
            </div>

            <div class="campo">
                <label for="cidade">Cidade</label>
                <input type="text" id="cidade" name="cidade"
                       maxlength="100" required>
            </div>

            <div class="campo">
                <label for="estado">Estado (UF)</label>
                <input type="text" id="estado" name="estado"
                       maxlength="2" placeholder="PR" required>
            </div>

            <div class="campo">
                <label for="complemento">Complemento</label>
                <input type="text" id="complemento"
                       name="complemento" maxlength="100">
            </div>

            <button type="submit" class="botao">
                Cadastrar endereço
            </button>

        </form>
    </section>

    <br>

    <section class="tabela">
        <h2>Endereços cadastrados</h2>

        <table>
            <thead>
                <tr>
                    <th>Identificação</th>
                    <th>CEP</th>
                    <th>Logradouro</th>
                    <th>Número</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($enderecos) > 0): ?>
                    <?php foreach ($enderecos as $endereco): ?>
                        <tr>
                            <td><?= htmlspecialchars($endereco['identificacao']) ?></td>
                            <td><?= htmlspecialchars($endereco['cep']) ?></td>
                            <td><?= htmlspecialchars($endereco['logradouro']) ?></td>
                            <td><?= htmlspecialchars($endereco['numero']) ?></td>
                            <td><?= htmlspecialchars($endereco['cidade']) ?></td>
                            <td><?= htmlspecialchars($endereco['estado']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Nenhum endereço cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

</body>
</html>
