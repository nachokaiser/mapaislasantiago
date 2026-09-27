<?php
/* Template Name: Mapa Sonoro */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'mapa-page' ); ?>>
<?php wp_body_open(); ?>

<header id="mapa-header">
    <div class="mapa-header-logo">
        <?php if ( has_custom_logo() ) : ?>
            <?php the_custom_logo(); ?>
        <?php else : ?>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mapa-site-name">
                <?php bloginfo( 'name' ); ?>
            </a>
        <?php endif; ?>
    </div>

    <nav class="mapa-header-nav" aria-label="Navegación principal">
        <?php wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'mapa-nav-list',
            'fallback_cb'    => false,
        ]); ?>
    </nav>
</header>

<div id="mapa-wrapper">
    <div id="mapa-contenedor"></div>

    <div id="mapa-panel">
        <div id="panel-header">
            <span id="panel-titulo-sticky"></span>
            <button id="panel-cerrar" aria-label="Cerrar panel">✕</button>
        </div>
        <div id="panel-contenido"></div>
    </div>
</div>

<footer id="mapa-footer">
    <p>Hecho por lxs pibxs y el Club Isleños Unidos de Isla Santiago, Argentina</p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
