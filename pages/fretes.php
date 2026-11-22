
<?php

require_once __DIR__ . '/../config/conexao.php';

$mensagem = '';
$tipo_mensagem = '';

$transportadores = $conexao->query(
    "SELECT id, nome FROM transportadores ORDER BY nome"
)->fetchAll();

$enderecos = $conexao->query(
    "SELECT id, identificacao, cidade, estado FROM enderecos ORDER BY identificacao"
)->fetchAll();

$produtos = $conexao->query(
    "SELECT id, codigo, nome, valor FROM produtos ORDER BY nome"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $data_frete = $_POST['data_frete'] ?? '';
        $transportador_id = filter_var(
            $_POST['transportador_id'] ?? '',
            FILTER_VALIDATE_INT
        );
        $origem_id = filter_var(
            $_POST['origem_id'] ?? '',
            FILTER_VALIDATE_INT
        );
        $destino_id = filter_var(
            $_POST['destino_id'] ?? '',
            FILTER_VALIDATE_INT
        );

        $produto_ids = $_POST['produto_id'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];

        if (!$data_frete || !$transportador_id || !$origem_id || !$destino_id) {
            throw new Exception('Preencha todos os campos obrigatórios.');
        }

        if ($origem_id === $destino_id) {
            throw new Exception('A origem e o destino devem ser diferentes.');
        }

        if (!is_array($produto_ids) || !is_array($quantidades) || count($produto_ids) === 0) {
            throw new Exception('Adicione pelo menos um produto ao frete.');
        }

        // Confere se o transportador existe.
        $consulta = $conexao->prepare(
            "SELECT id FROM transportadores WHERE id = ?"
        );
        $consulta->execute([$transportador_id]);

        if (!$consulta->fetch()) {
            throw new Exception('Transportador inválido.');
        }

        // Confere se os endereços existem.
        $consulta = $conexao->prepare(
            "SELECT id FROM enderecos WHERE id IN (?, ?)"
        );
        $consulta->execute([$origem_id, $destino_id]);

        if ($consulta->rowCount() < 2) {
            throw new Exception('Selecione endereços válidos para origem e destino.');
        }

        // Prepara os produtos e suas quantidades.
        $itens = [];
        $produtos_utilizados = [];

        foreach ($produto_ids as $indice => $produto_id) {

            $produto_id = filter_var($produto_id, FILTER_VALIDATE_INT);
            $quantidade = filter_var(
                $quantidades[$indice] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$produto_id || !$quantidade || $quantidade < 1) {
                throw new Exception('Selecione produtos e informe quantidades válidas.');
            }

            if (in_array($produto_id, $produtos_utilizados)) {
                throw new Exception('Não selecione o mesmo produto mais de uma vez.');
            }

            $produtos_utilizados[] = $produto_id;

            $consulta = $conexao->prepare(
                "SELECT id, valor FROM produtos WHERE id = ?"
            );
            $consulta->execute([$produto_id]);

            $produto = $consulta->fetch();

            if (!$produto) {
                throw new Exception('Um dos produtos selecionados não existe.');
            }

            $itens[] = [
                'produto_id' => $produto['id'],
                'quantidade' => $quantidade,
                'valor_unitario' => $produto['valor']
            ];
        }

        $conexao->beginTransaction();

        // Gera um código único para o frete.
        do {
            $codigo = 'RB-' . date('YmdHis') . '-' . random_int(100, 999);

            $consulta = $conexao->prepare(
                "SELECT id FROM fretes WHERE codigo = ?"
            );
            $consulta->execute([$codigo]);

        } while ($consulta->fetch());

        // Cadastra o frete.
        $sql = "INSERT INTO fretes
                (codigo, data_frete, transportador_id, origem_id, destino_id, status)
                VALUES (?, ?, ?, ?, ?, ?)";

        $consulta = $conexao->prepare($sql);

        $consulta->execute([
            $codigo,
            $data_frete,
            $transportador_id,
            $origem_id,
            $destino_id,
            'Em preparação'
        ]);

        $frete_id = $conexao->lastInsertId();

        // Cadastra os produtos vinculados ao frete.
        $sql = "INSERT INTO frete_produtos
                (frete_id, produto_id, quantidade, valor_unitario)
                VALUES (?, ?, ?, ?)";

        $consulta = $conexao->prepare($sql);

        foreach ($itens as $item) {
            $consulta->execute([
                $frete_id,
                $item['produto_id'],
                $item['quantidade'],
                $item['valor_unitario']
            ]);
        }

        $conexao->commit();

        header('Location: fretes.php?sucesso=1');
        exit;

    } catch (Exception $erro) {

        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }

        $mensagem = $erro->getMessage();
        $tipo_mensagem = 'erro';

    } catch (PDOException $erro) {

        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }

        $mensagem = 'Erro ao salvar o frete no banco de dados.';
        $tipo_mensagem = 'erro';
    }
}

if (isset($_GET['sucesso'])) {
    $mensagem = 'Frete cadastrado com sucesso!';
    $tipo_mensagem = 'sucesso';
}

// Lista os fretes cadastrados.
$sql = "SELECT
            f.id,
            f.codigo,
            f.data_frete,
            f.status,
            t.nome AS transportador,
            o.identificacao AS origem,
            d.identificacao AS destino,
            COUNT(DISTINCT fp.produto_id) AS total_produtos,
            COALESCE(SUM(fp.quantidade * fp.valor_unitario), 0) AS valor_total
        FROM fretes f
        INNER JOIN transportadores t ON f.transportador_id = t.id
        INNER JOIN enderecos o ON f.origem_id = o.id
        INNER JOIN enderecos d ON f.destino_id = d.id
        LEFT JOIN frete_produtos fp ON f.id = fp.frete_id
        GROUP BY
            f.id, f.codigo, f.data_frete, f.status,
            t.nome, o.identificacao, d.identificacao
        ORDER BY f.id DESC";

$fretes = $conexao->query($sql)->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fretes - ROTA BIKERS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="layout">

    <?php include __DIR__ . '/../includes/menu.php'; ?>

    <main class="conteudo">

        <div class="topbar">
            <h1>Gerenciamento de Fretes</h1>
            <p>ROTA BIKERS</p>
        </div>

        <section class="card">

            <h2>Novo Frete</h2>

            <?php if ($mensagem): ?>
                <div class="mensagem <?= htmlspecialchars($tipo_mensagem) ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if (count($transportadores) === 0 || count($enderecos) < 2 || count($produtos) === 0): ?>

                <p>Para cadastrar um frete, é necessário ter pelo menos um produto, um transportador e dois endereços cadastrados.</p>

            <?php else: ?>

                <form method="POST">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Data do frete</label>
                            <input
                                type="date"
                                name="data_frete"
                                value="<?= date('Y-m-d') ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label>Transportador</label>
                            <select name="transportador_id" required>
                                <option value="">Selecione o transportador</option>

                                <?php foreach ($transportadores as $transportador): ?>
                                    <option value="<?= $transportador['id'] ?>">
                                        <?= htmlspecialchars($transportador['nome']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Endereço de origem</label>
                            <select name="origem_id" required>
                                <option value="">Selecione a origem</option>

                                <?php foreach ($enderecos as $endereco): ?>
                                    <option value="<?= $endereco['id'] ?>">
                                        <?= htmlspecialchars($endereco['identificacao'] . ' - ' . $endereco['cidade'] . '/' . $endereco['estado']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Endereço de destino</label>
                            <select name="destino_id" required>
                                <option value="">Selecione o destino</option>

                                <?php foreach ($enderecos as $endereco): ?>
                                    <option value="<?= $endereco['id'] ?>">
                                        <?= htmlspecialchars($endereco['identificacao'] . ' - ' . $endereco['cidade'] . '/' . $endereco['estado']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                    </div>

                    <h3>Produtos do Frete</h3>

                    <div id="lista-produtos">

                        <div class="produto-linha">

                            <div class="form-group">
                                <label>Produto</label>

                                <select name="produto_id[]" required>
                                    <option value="">Selecione um produto</option>

                                    <?php foreach ($produtos as $produto): ?>
                                        <option value="<?= $produto['id'] ?>">
                                            <?= htmlspecialchars($produto['codigo'] . ' - ' . $produto['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="form-group">
                                <label>Quantidade</label>
                                <input
                                    type="number"
                                    name="quantidade[]"
                                    min="1"
                                    value="1"
                                    required
                                >
                            </div>

                            <button
                                type="button"
                                class="btn-remover"
                                onclick="removerProduto(this)"
                            >
                                Remover
                            </button>

                        </div>

                    </div>

                    <button type="button" class="btn-secundario" onclick="adicionarProduto()">
                        + Adicionar outro produto
                    </button>

                    <br><br>

                    <button type="submit" class="btn-principal">
                        Cadastrar Frete
                    </button>

                </form>

                <template id="modelo-produto">
                    <div class="produto-linha">

                        <div class="form-group">
                            <label>Produto</label>

                            <select name="produto_id[]" required>
                                <option value="">Selecione um produto</option>

                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto['id'] ?>">
                                        <?= htmlspecialchars($produto['codigo'] . ' - ' . $produto['nome']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="form-group">
                            <label>Quantidade</label>
                            <input
                                type="number"
                                name="quantidade[]"
                                min="1"
                                value="1"
                                required
                            >
                        </div>

                        <button
                            type="button"
                            class="btn-remover"
                            onclick="removerProduto(this)"
                        >
                            Remover
                        </button>

                    </div>
                </template>

            <?php endif; ?>

        </section>

        <section class="card">

            <h2>Fretes Cadastrados</h2>

            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Data</th>
                            <th>Transportador</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Produtos</th>
                            <th>Valor total</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($fretes) > 0): ?>

                            <?php foreach ($fretes as $frete): ?>

                                <tr>
                                    <td><?= htmlspecialchars($frete['codigo']) ?></td>

                                    <td>
                                        <?= date('d/m/Y', strtotime($frete['data_frete'])) ?>
                                    </td>

                                    <td><?= htmlspecialchars($frete['transportador']) ?></td>

                                    <td><?= htmlspecialchars($frete['origem']) ?></td>

                                    <td><?= htmlspecialchars($frete['destino']) ?></td>

                                    <td><?= $frete['total_produtos'] ?></td>

                                    <td>
                                        R$ <?= number_format($frete['valor_total'], 2, ',', '.') ?>
                                    </td>

                                    <td><?= htmlspecialchars($frete['status']) ?></td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8">Nenhum frete cadastrado.</td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<script>

function adicionarProduto() {

    const modelo = document.getElementById('modelo-produto');
    const lista = document.getElementById('lista-produtos');

    const novoProduto = modelo.content.cloneNode(true);

    lista.appendChild(novoProduto);
}

function removerProduto(botao) {

    const lista = document.getElementById('lista-produtos');

    if (lista.children.length > 1) {
        botao.closest('.produto-linha').remove();
    } else {
        alert('O frete precisa ter pelo menos um produto.');
    }
}

</script>

</body>
</html>
