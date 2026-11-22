
<?php
require_once __DIR__ . '/../config/conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo_transporte = trim($_POST['tipo_transporte'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (
        $nome === '' ||
        $documento === '' ||
        $telefone === '' ||
        $tipo_transporte === ''
    ) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um endereço de e-mail válido.';
    } else {
        try {
            $sql = "INSERT INTO transportadores
                    (nome, documento, telefone, tipo_transporte, email)
                    VALUES
                    (:nome, :documento, :telefone, :tipo_transporte, :email)";

            $stmt = $conexao->prepare($sql);

            $stmt->execute([
                ':nome' => $nome,
                ':documento' => $documento,
                ':telefone' => $telefone,
                ':tipo_transporte' => $tipo_transporte,
                ':email' => $email !== '' ? $email : null
            ]);

            $mensagem = 'Transportador cadastrado com sucesso!';

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Não foi possível cadastrar. Verifique se os dados já existem.';
            } else {
                $erro = 'Não foi possível cadastrar o transportador.';
            }
        }
    }
}

$stmt = $conexao->query(
    "SELECT * FROM transportadores ORDER BY id DESC"
);

$transportadores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTA BIKERS | Transportadores</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/menu.php'; ?>

<main class="conteudo">

    <header class="topo">
        <div>
            <h1>Transportadores</h1>
            <p>Cadastro e gerenciamento dos responsáveis pelos transportes.</p>
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
        <h2>Novo transportador</h2>
        <br>

        <form method="POST">

            <div class="campo">
                <label for="nome">Nome do transportador</label>
                <input type="text" id="nome" name="nome"
                       maxlength="150" required>
            </div>

            <div class="campo">
                <label for="documento">CPF ou CNPJ</label>
                <input type="text" id="documento" name="documento"
                       maxlength="20" required>
            </div>

            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone"
                       maxlength="20" required>
            </div>

            <div class="campo">
                <label for="tipo_transporte">Tipo de transporte</label>
                <select id="tipo_transporte" name="tipo_transporte" required>
                    <option value="">Selecione o tipo de transporte</option>
                    <option value="Motocicleta">Motocicleta</option>
                    <option value="Carro ou utilitário">Carro ou utilitário</option>
                    <option value="Caminhão">Caminhão</option>
                    <option value="Transportadora">Transportadora</option>
                </select>
            </div>

            <div class="campo">
                <label for="email">E-mail (opcional)</label>
                <input type="email" id="email" name="email"
                       maxlength="120">
            </div>

            <button type="submit" class="botao">
                Cadastrar transportador
            </button>

        </form>
    </section>

    <br>

    <section class="tabela">
        <h2>Transportadores cadastrados</h2>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Documento</th>
                    <th>Telefone</th>
                    <th>Tipo de transporte</th>
                    <th>E-mail</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($transportadores) > 0): ?>
                    <?php foreach ($transportadores as $transportador): ?>
                        <tr>
                            <td><?= htmlspecialchars($transportador['nome']) ?></td>
                            <td><?= htmlspecialchars($transportador['documento']) ?></td>
                            <td><?= htmlspecialchars($transportador['telefone']) ?></td>
                            <td><?= htmlspecialchars($transportador['tipo_transporte']) ?></td>
                            <td><?= htmlspecialchars($transportador['email'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Nenhum transportador cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

</body>
</html>
