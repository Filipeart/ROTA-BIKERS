
<?php
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <div class="logo">
        ROTA <span>BIKERS</span>
    </div>

    <nav class="menu">

        <a href="/rota-bikers/index.php"
           class="<?= $pagina_atual == 'index.php' ? 'ativo' : '' ?>">
            Dashboard
        </a>

        <a href="/rota-bikers/pages/produtos.php"
           class="<?= $pagina_atual == 'produtos.php' ? 'ativo' : '' ?>">
            Produtos
        </a>

        <a href="/rota-bikers/pages/enderecos.php"
           class="<?= $pagina_atual == 'enderecos.php' ? 'ativo' : '' ?>">
            Endereços
        </a>

        <a href="/rota-bikers/pages/transportadores.php"
           class="<?= $pagina_atual == 'transportadores.php' ? 'ativo' : '' ?>">
            Transportadores
        </a>

        <a href="/rota-bikers/pages/fretes.php"
           class="<?= $pagina_atual == 'fretes.php' ? 'ativo' : '' ?>">
            Fretes
        </a>

        <a href="/rota-bikers/pages/relatorios.php"
           class="<?= $pagina_atual == 'relatorios.php' ? 'ativo' : '' ?>">
            Relatórios
        </a>

    </nav>

</aside>
