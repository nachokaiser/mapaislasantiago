<?php
/**
 * Portada: pantalla de entrada que tapa el mapa hasta que se toca COMENZAR.
 *
 * Hay dos composiciones, cada una con su propio PSD (collage-mapa-sonoro.psd y
 * mobile-collage-mapa-sonoro.psd): la de escritorio y la de pantallas verticales
 * (celular). El CSS muestra una u otra según la proporción de la pantalla.
 *
 * Cada composición vive en un "escenario" con el tamaño del lienzo del PSD, que
 * se escala con el ancho de la pantalla. Las piezas van posicionadas en % de ese
 * escenario con los valores del PSD (x, y, ancho, alto, en px del lienzo), así
 * siguen alineadas a cualquier tamaño de pantalla.
 *
 * Solo el papel está a la vista desde el principio. 'fade' => true: aparece en
 * fade, sin moverse (la isla, que es lo primero en entrar). 'f' => nombre de
 * archivo, si no es el mismo que 'n' (el escritorio y el móvil comparten el
 * archivo cuando el dibujo es el mismo).
 *
 * Las piezas van en el orden de capas del PSD, de abajo hacia arriba (si en el
 * PSD se cambia qué tapa a qué, hay que reordenar acá también).
 *
 * Las ondas son los radios (en px del lienzo) de los círculos, de la más chica
 * a la más grande. Los primeros 6 salen del PSD; el resto sigue el mismo ritmo
 * hasta que la última queda fuera de cualquier pantalla (la mitad de la
 * diagonal de la pantalla más exigente ronda 1800px del lienzo de escritorio y
 * 850px del móvil).
 */

$portada_url = get_stylesheet_directory_uri() . '/assets/portada/';

$portada_escenarios = [
    'desktop' => [
        'w'         => 1920,
        'h'         => 1371,
        'cx'        => 960,   // centro de las ondas
        'cy'        => 526,
        'recorrido' => 160,   // cuánto se desplazan las piezas al entrar, en px del lienzo
        'ondas'     => [ 321, 438, 545, 690, 829, 990, 1170, 1360, 1560, 1770, 1990 ],
        'piezas'    => [
            [ 'n' => 'isla-verde',               'x' =>  544, 'y' =>  312, 'w' => 814, 'h' => 449, 'fade' => true ],
            [ 'n' => 'libreta',                  'x' =>  149, 'y' =>  236, 'w' => 435, 'h' => 335 ],
            [ 'n' => 'titulo',                   'x' =>  710, 'y' =>  293, 'w' => 492, 'h' => 470, 'titulo' => true, 'f' => 'canto-del-rio' ],
            [ 'n' => 'nene-camara',              'x' =>  252, 'y' =>  596, 'w' => 289, 'h' => 343 ],
            [ 'n' => 'nene-celular',             'x' =>  200, 'y' =>  927, 'w' => 440, 'h' => 230 ],
            [ 'n' => 'garabato-amarillo-arcos',  'x' =>  430, 'y' =>  879, 'w' => 120, 'h' => 115 ],
            [ 'n' => 'garabato-naranja-rayas',   'x' =>  440, 'y' =>  627, 'w' =>  87, 'h' =>  74 ],
            [ 'n' => 'mano-grabador',            'x' => 1455, 'y' =>  206, 'w' => 281, 'h' => 281 ],
            [ 'n' => 'ondas-naranjas',           'x' =>  969, 'y' =>  149, 'w' => 630, 'h' => 612 ],
            [ 'n' => 'garabato-azul-barra',      'x' => 1378, 'y' =>  952, 'w' => 416, 'h' => 124 ],
            [ 'n' => 'muelle',                   'x' => 1412, 'y' =>  416, 'w' => 469, 'h' => 272 ],
            [ 'n' => 'garabato-amarillo-zigzag', 'x' => 1736, 'y' =>  502, 'w' => 120, 'h' =>  78 ],
            [ 'n' => 'velero',                   'x' => 1427, 'y' =>  605, 'w' => 400, 'h' => 461 ],
            [ 'n' => 'garabato-naranja-ovalos',  'x' =>  254, 'y' =>  188, 'w' =>  93, 'h' =>  71 ],
            [ 'n' => 'garabato-azul-onda',       'x' =>  169, 'y' =>  578, 'w' => 146, 'h' =>  86 ],
            [ 'n' => 'subtitulo',                'x' =>  714, 'y' =>  817, 'w' => 492, 'h' => 142, 'texto' => true ],
            [ 'n' => 'subrayado',                'x' =>  848, 'y' =>  967, 'w' => 354, 'h' =>  16, 'subrayado' => true ],
        ],
    ],
    'mobile'  => [
        'w'         => 784,
        'h'         => 1371,
        'cx'        => 392,   // centro de las ondas = centro de "Ondas copy" del PSD
        'cy'        => 606,
        'recorrido' => 100,
        'ondas'     => [ 238, 323, 402, 507, 609, 728, 860, 1000 ],
        // 'grupo' => 'bl' / 'br': piezas de las esquinas de abajo. Se anclan a la
        // esquina de la pantalla (ver .portada__grupo en portada.css) para que,
        // cuando la pantalla no tiene la proporción del PSD, queden contra la
        // esquina y no floten hacia el botón.
        'piezas'    => [
            [ 'n' => 'isla-verde',               'x' =>   60, 'y' =>  428, 'w' => 633, 'h' => 348, 'fade' => true, 'f' => 'isla-verde' ],
            [ 'n' => 'titulo',                   'x' =>  207, 'y' =>  419, 'w' => 370, 'h' => 353, 'titulo' => true, 'f' => 'canto-del-rio' ],
            [ 'n' => 'nene-camara',              'x' =>  511, 'y' =>   51, 'w' => 259, 'h' => 316, 'f' => 'nene-camara-m2' ],
            [ 'n' => 'garabato-naranja-rayas',   'x' =>  693, 'y' =>   87, 'w' =>  82, 'h' =>  70, 'f' => 'garabato-naranja-rayas-m2' ],
            [ 'n' => 'ondas-naranjas',           'x' =>   22, 'y' => 1103, 'w' => 704, 'h' => 326, 'f' => 'ondas-naranjas-m', 'grupo' => 'bl' ],
            [ 'n' => 'mano-grabador',            'x' =>  -26, 'y' => 1134, 'w' => 188, 'h' => 285, 'f' => 'mano-grabador-m', 'grupo' => 'bl' ],
            [ 'n' => 'garabato-azul-onda',       'x' =>  483, 'y' =>  297, 'w' => 118, 'h' =>  70 ],
            [ 'n' => 'garabato-azul-barra',      'x' =>  480, 'y' => 1291, 'w' => 354, 'h' => 106, 'grupo' => 'br' ],
            [ 'n' => 'velero',                   'x' =>  522, 'y' =>  995, 'w' => 341, 'h' => 394, 'grupo' => 'br' ],
            [ 'n' => 'muelle',                   'x' =>  -21, 'y' =>  138, 'w' => 470, 'h' => 247, 'f' => 'muelle-m2' ],
            [ 'n' => 'garabato-amarillo-arcos',  'x' =>  224, 'y' =>   87, 'w' => 129, 'h' => 123 ],
            [ 'n' => 'subtitulo',                'x' =>  209, 'y' =>  802, 'w' => 366, 'h' => 104, 'texto' => true, 'f' => 'subtitulo-m' ],
            [ 'n' => 'subrayado',                'x' =>  313, 'y' =>  916, 'w' => 273, 'h' =>  12, 'subrayado' => true, 'f' => 'subrayado-m' ],
        ],
    ],
];
?>
<div id="portada" class="portada" role="dialog" aria-modal="true" aria-label="Canto del Río — Mapa Sonoro de la Isla Santiago">
    <img class="portada__papel" src="<?php echo esc_url( $portada_url . 'papel.webp' ); ?>" alt="" width="1920" height="1280" fetchpriority="high" aria-hidden="true">

    <?php // El grano de las ondas va en un SVG aparte, siempre visible: un patrón dentro de un SVG con display:none no se dibuja. ?>
    <svg class="portada__defs" width="0" height="0" aria-hidden="true" focusable="false">
        <defs>
            <pattern id="portada-grano" patternUnits="userSpaceOnUse" width="256" height="256">
                <image href="<?php echo esc_url( $portada_url . 'grano-onda.webp' ); ?>" width="256" height="256"></image>
            </pattern>
        </defs>
    </svg>

    <?php foreach ( $portada_escenarios as $nombre => $esc ) : ?>
        <div class="portada__escenario portada__escenario--<?php echo esc_attr( $nombre ); ?>">
            <svg class="portada__ondas" viewBox="0 0 <?php echo (int) $esc['w']; ?> <?php echo (int) $esc['h']; ?>" aria-hidden="true" focusable="false">
                <?php foreach ( $esc['ondas'] as $i => $radio ) : ?>
                    <circle class="portada__onda" style="--i:<?php echo (int) $i; ?>" cx="<?php echo (int) $esc['cx']; ?>" cy="<?php echo (int) $esc['cy']; ?>" r="<?php echo (int) $radio; ?>"></circle>
                <?php endforeach; ?>
            </svg>

            <?php
            $grupo_abierto = null;
            foreach ( $esc['piezas'] as $pieza ) :
                $es_fade      = ! empty( $pieza['fade'] );
                $es_titulo    = ! empty( $pieza['titulo'] );
                $es_texto     = ! empty( $pieza['texto'] );
                $es_subrayado = ! empty( $pieza['subrayado'] );
                $archivo      = isset( $pieza['f'] ) ? $pieza['f'] : $pieza['n'];
                $grupo        = isset( $pieza['grupo'] ) ? $pieza['grupo'] : null;

                // Las piezas de una esquina van juntas en un contenedor propio
                // (las del mismo 'grupo' tienen que ser consecutivas).
                if ( $grupo !== $grupo_abierto ) {
                    if ( $grupo_abierto ) {
                        echo '</div>';
                    }
                    if ( $grupo ) {
                        echo '<div class="portada__grupo portada__grupo--' . esc_attr( $grupo ) . '">';
                    }
                    $grupo_abierto = $grupo;
                }

                // Vector unitario del centro de las ondas hacia la pieza: de ahí
                // arranca, y se mueve hacia el centro hasta quedar en su lugar.
                $vx  = $pieza['x'] + $pieza['w'] / 2 - $esc['cx'];
                $vy  = $pieza['y'] + $pieza['h'] / 2 - $esc['cy'];
                $mod = max( 1, sqrt( $vx * $vx + $vy * $vy ) );
                $dx  = round( $vx / $mod * $esc['recorrido'] );
                $dy  = round( $vy / $mod * $esc['recorrido'] );

                $clases = 'portada__pieza portada__pieza--' . $pieza['n'];
                if ( $es_titulo ) {
                    $clases .= ' portada__pieza--titulo';
                } elseif ( $es_texto ) {
                    $clases .= ' portada__pieza--texto';
                } elseif ( $es_subrayado ) {
                    $clases .= ' portada__pieza--subrayado';
                } elseif ( $es_fade ) {
                    $clases .= ' portada__pieza--fade';
                } else {
                    $clases .= ' portada__pieza--entra';
                }

                $estilo = sprintf(
                    'left:%s%%;top:%s%%;width:%s%%;',
                    round( $pieza['x'] / $esc['w'] * 100, 3 ),
                    round( $pieza['y'] / $esc['h'] * 100, 3 ),
                    round( $pieza['w'] / $esc['w'] * 100, 3 )
                );
                if ( ! ( $es_fade || $es_titulo || $es_texto || $es_subrayado ) ) {
                    $estilo .= sprintf( '--dx:%d;--dy:%d;', $dx, $dy );
                }

                // El título y el subtítulo son texto para lectores de pantalla; el resto es decoración.
                $alt = '';
                if ( $es_titulo ) {
                    $alt = 'Canto del Río';
                } elseif ( $es_texto ) {
                    $alt = 'Mapa Sonoro de la Isla Santiago';
                }

                // lazy: la composición que no se ve (display:none) no se descarga.
                // La isla es lo primero que aparece y es el mismo archivo en las
                // dos composiciones: se pide ya, con prioridad, sin esperar a que
                // el JS la marque.
                $carga = $es_fade ? 'fetchpriority="high"' : 'loading="lazy"';
                $img = sprintf(
                    '<img src="%s" alt="%s" width="%d" height="%d" %s%s>',
                    esc_url( $portada_url . $archivo . '.webp' ),
                    esc_attr( $alt ),
                    $pieza['w'],
                    $pieza['h'],
                    $carga,
                    $alt ? '' : ' aria-hidden="true"'
                );
                $etiqueta = $es_titulo ? 'h1' : ( $es_texto ? 'p' : 'div' );
                ?>
                <<?php echo $etiqueta; ?> class="<?php echo esc_attr( $clases ); ?>" style="<?php echo esc_attr( $estilo ); ?>"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></<?php echo $etiqueta; ?>>
            <?php endforeach; ?>
            <?php if ( $grupo_abierto ) { echo '</div>'; } ?>
        </div>
    <?php endforeach; ?>

    <button type="button" class="portada__comenzar">Comenzar</button>
</div>
