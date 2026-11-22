
<?php
require_once __DIR__ . '/../config/conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = str_replace(',', '.', $_POST['valor'] ?? '0');
    $quantidade = filter_var($_POST['quantidade'] ?? 0, FILTER_VALIDATE_INT);

    if ($codigo === '' || $nome === '' || $categoria === '') {
        $erro = 'Preencha o código, o nome e a categoria.';
    } elseif (!is_numeric($valor) || (float)$valor < 0 || $quantidade === false || $quantidade < 0) {
        $erro = 'Informe um valor e uma quantidade válidos.';
    } else {
        try {
            $sql = "INSERT INTO produtos
                    (codigo, nome, categoria, descricao, valor, quantidade)
                    VALUES
                    (:codigo, :nome, :categoria, :descricao, :valor, :quantidade)";

            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                ':codigo' => $codigo,
                ':nome' => $nome,
                ':categoria' => $categoria,
                ':descricao' => $descricao,
                ':valor' => $valor,
                ':quantidade' => $quantidade
            ]);

            $mensagem = 'Produto cadastrado com sucesso!';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Este código de produto já está cadastrado.';
            } else {
                $erro = 'Não foi possível cadastrar o produto.';
            }
        }
    }
}

$stmt = $conexao->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTA BIKERS | Produtos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/menu.php'; ?>

<main class="conteudo">

    <header class="topo">
        <div>
            <h1>Produtos</h1>
            <p>Cadastro e gerenciamento de equipamentos e acessórios.</p>
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
        <h2>Novo produto</h2>
        <br>

        <form method="POST">

            <div class="campo">
                <label for="codigo">Código do produto</label>
                <input type="text" id="codigo" name="codigo"
                       maxlength="30" required>
            </div>

            <div class="campo">
                <label for="nome">Nome do produto</label>
                <input type="text" id="nome" name="nome"
                       maxlength="120" required>
            </div>

            <div class="campo">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione uma categoria</option>
                    <option value="Capacetes">Capacetes</option>
                    <option value="Roupas">Roupas</option>
                    <option value="Proteção">Proteção</option>
                    <option value="Luvas">Luvas</option>
                    <option value="Calçados">Calçados</option>
                    <option value="Acessórios">Acessórios</option>
                </select>
            </div>

            <div class="campo">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao"
                          rows="3"></textarea>
            </div>

            <div class="campo">
                <label for="valor">Valor (R$)</label>
                <input type="number" id="valor" name="valor"
                       min="0" step="0.01" required>
            </div>

            <div class="campo">
                <label for="quantidade">Quantidade em estoque</label>
                <input type="number" id="quantidade" name="quantidade"
                       min="0" step="1" required>
            </div>

            <button type="submit" class="botao">
                Cadastrar produto
            </button>

        </form>
    </section>

    <br>

    <section class="tabela">
        <h2>Produtos cadastrados</h2>

        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Quantidade</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?= htmlspecialchars($produto['codigo']) ?></td>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td><?= htmlspecialchars($produto['categoria']) ?></td>
                            <td>
                                R$ <?= number_format((float)$produto['valor'], 2, ',', '.') ?>
                            </td>
                            <td><?= (int)$produto['quantidade'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Nenhum produto cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

</main>

</body>
</html>
