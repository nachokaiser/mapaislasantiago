<?php
/* Template Name: Mapa Sonoro */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        // La portada se muestra una vez por sesión. Va acá, antes del primer
        // dibujado, para que no parpadee si ya se vio.
        try { if (sessionStorage.getItem('portadaVista')) document.documentElement.classList.add('sin-portada'); } catch (e) {}
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'mapa-page' ); ?>>
<?php wp_body_open(); ?>

<?php get_template_part( 'portada' ); ?>

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

    <nav class="mapa-header-nav" id="mapa-nav" aria-label="Navegación principal">
        <?php wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'mapa-nav-list',
            'fallback_cb'    => false,
        ]); ?>

        <?php // Un link por circuito, con su color principal. Al tocarlo, mapa-nav.js abre el panel del circuito. ?>
        <ul class="mapa-nav-circuitos">
            <?php foreach ( mapa_sonoro_circuitos_ordenados() as $circuito_nav ) :
                $colores_nav = mapa_sonoro_colores_circuito( $circuito_nav ); ?>
                <li>
                    <a href="#circuito-<?php echo esc_attr( $circuito_nav->post_name ); ?>"
                       class="mapa-nav-circuito"
                       data-circuito-id="<?php echo (int) $circuito_nav->ID; ?>"
                       <?php if ( $colores_nav['principal'] ) : ?>style="--c-principal:<?php echo esc_attr( $colores_nav['principal'] ); ?>;--c-texto:<?php echo esc_attr( $colores_nav['texto'] ); ?>"<?php endif; ?>>
                        <?php echo esc_html( get_the_title( $circuito_nav ) ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <button type="button" class="mapa-nav-toggle" aria-expanded="false" aria-controls="mapa-nav" aria-label="Abrir menú de circuitos">
        <span class="mapa-nav-toggle-barra"></span>
        <span class="mapa-nav-toggle-barra"></span>
        <span class="mapa-nav-toggle-barra"></span>
    </button>
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
    <p>Hecho en comunidad y coordinado por el Club Isleños Unidos en Isla Santiago, Argentina</p>
</footer>

<div id="lightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Imagen ampliada" hidden>
    <button type="button" class="lightbox-cerrar" aria-label="Cerrar imagen ampliada">✕</button>
    <img class="lightbox-imagen" src="" alt="">
</div>

<?php wp_footer(); ?>
</body>
</html>
