
<?php

require_once __DIR__ . '/../config/conexao.php';

$data = $_GET['data'] ?? '';
$fretes = [];

if ($data !== '') {

    $sql = "SELECT
                f.codigo,
                f.data_frete,
                f.status,
                t.nome AS transportador,
                o.identificacao AS origem,
                d.identificacao AS destino,
                GROUP_CONCAT(
                    CONCAT(p.nome, ' (Qtd: ', fp.quantidade, ')')
                    SEPARATOR ', '
                ) AS produtos,
                COALESCE(
                    SUM(fp.quantidade * fp.valor_unitario), 0
                ) AS valor_total
            FROM fretes f
            INNER JOIN transportadores t
                ON f.transportador_id = t.id
            INNER JOIN enderecos o
                ON f.origem_id = o.id
            INNER JOIN enderecos d
                ON f.destino_id = d.id
            LEFT JOIN frete_produtos fp
                ON f.id = fp.frete_id
            LEFT JOIN produtos p
                ON fp.produto_id = p.id
            WHERE f.data_frete = ?
            GROUP BY
                f.id, f.codigo, f.data_frete, f.status,
                t.nome, o.identificacao, d.identificacao
            ORDER BY f.id DESC";

    $consulta = $conexao->prepare($sql);
    $consulta->execute([$data]);
    $fretes = $consulta->fetchAll();
}

// Exportação do relatório em CSV.
if (isset($_GET['baixar']) && $_GET['baixar'] === '1' && $data !== '') {

    $arquivo = 'relatorio-fretes-' . $data . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $arquivo . '"');

    // BOM para facilitar a leitura de acentos no Excel.
    echo "\xEF\xBB\xBF";

    $saida = fopen('php://output', 'w');

    fputcsv($saida, [
        'Código',
        'Data',
        'Transportador',
        'Origem',
        'Destino',
        'Produtos',
        'Valor total',
        'Status'
    ], ';');

    foreach ($fretes as $frete) {

        fputcsv($saida, [
            $frete['codigo'],
            date('d/m/Y', strtotime($frete['data_frete'])),
            $frete['transportador'],
            $frete['origem'],
            $frete['destino'],
            $frete['produtos'],
            number_format($frete['valor_total'], 2, ',', '.'),
            $frete['status']
        ], ';');
    }

    fclose($saida);
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - ROTA BIKERS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="layout">

    <?php include __DIR__ . '/../includes/menu.php'; ?>

    <main class="conteudo">

        <div class="topbar">
            <h1>Relatórios</h1>
            <p>ROTA BIKERS</p>
        </div>

        <section class="card">

            <h2>Relatório de Fretes por Data</h2>

            <p>Selecione uma data para consultar os fretes cadastrados.</p>

            <form method="GET">

                <div class="form-group">

                    <label for="data">Data do frete</label>

                    <input
                        type="date"
                        id="data"
                        name="data"
                        value="<?= htmlspecialchars($data) ?>"
                        required
                    >

                </div>

                <button type="submit" class="btn-principal">
                    Consultar Fretes
                </button>

            </form>

        </section>

        <?php if ($data !== ''): ?>

            <section class="card">

                <h2>Resultado da Consulta</h2>

                <p>
                    Data consultada:
                    <strong>
                        <?= date('d/m/Y', strtotime($data)) ?>
                    </strong>
                </p>

                <?php if (count($fretes) > 0): ?>

                    <p>
                        Total de fretes encontrados:
                        <strong><?= count($fretes) ?></strong>
                    </p>

                    <div class="table-responsive">

                        <table>

                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Transportador</th>
                                    <th>Origem</th>
                                    <th>Destino</th>
                                    <th>Produtos</th>
                                    <th>Valor total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($fretes as $frete): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars($frete['codigo']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($frete['transportador']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($frete['origem']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($frete['destino']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($frete['produtos'] ?? '') ?>
                                        </td>

                                        <td>
                                            R$ <?= number_format($frete['valor_total'], 2, ',', '.') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($frete['status']) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                    <br>

                    <a
                        href="relatorios.php?data=<?= urlencode($data) ?>&baixar=1"
                        class="btn-principal"
                    >
                        Baixar Relatório CSV
                    </a>

                <?php else: ?>

                    <p>Nenhum frete encontrado para essa data.</p>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </main>

</div>

</body>
</html>
