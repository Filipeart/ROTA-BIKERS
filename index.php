
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROTA BIKERS | Dashboard</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <?php include 'includes/menu.php'; ?>

    <main class="conteudo">

        <header class="topo">
            <div>
                <h1>Dashboard</h1>
                <p>Bem-vindo ao sistema ROTA BIKERS</p>
            </div>
            <strong>Painel administrativo</strong>
        </header>

        <section class="cards">

            <div class="card">
                <h3>PRODUTOS</h3>
                <p>Cadastro</p>
                <small>Gerencie os produtos da empresa</small>
            </div>

            <div class="card">
                <h3>ENDEREÇOS</h3>
                <p>Localização</p>
                <small>Organize origens e destinos</small>
            </div>

            <div class="card">
                <h3>TRANSPORTADORES</h3>
                <p>Equipe</p>
                <small>Gerencie os responsáveis pelo transporte</small>
            </div>

            <div class="card">
                <h3>FRETES</h3>
                <p>Logística</p>
                <small>Organize e acompanhe os fretes</small>
            </div>

        </section>

        <section class="tabela">
            <h2>Central de operações</h2>

            <p>
                Utilize o menu lateral para acessar os cadastros,
                organizar os transportes e consultar os relatórios.
            </p>

            <br>

            <a class="botao" href="pages/fretes.php">
                Acessar gerenciamento de fretes
            </a>
        </section>

    </main>

</body>
</html>
