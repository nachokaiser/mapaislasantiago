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
        'cy'        => 677,
        'recorrido' => 160,   // cuánto se desplazan las piezas al entrar, en px del lienzo
        'ondas'     => [ 321, 438, 545, 690, 829, 990, 1170, 1360, 1560, 1770, 1990 ],
        'piezas'    => [
            [ 'n' => 'isla-verde',               'x' =>  544, 'y' =>  467, 'w' => 814, 'h' => 448, 'fade' => true ],
            [ 'n' => 'libreta',                  'x' =>  169, 'y' =>  266, 'w' => 435, 'h' => 335 ],
            [ 'n' => 'titulo',                   'x' =>  691, 'y' =>  374, 'w' => 535, 'h' => 633, 'titulo' => true ],
            [ 'n' => 'nene-camara',              'x' =>  204, 'y' =>  586, 'w' => 337, 'h' => 400 ],
            [ 'n' => 'punto-azul',               'x' => 1456, 'y' =>  949, 'w' =>  12, 'h' =>  12 ],
            [ 'n' => 'nene-celular',             'x' =>  134, 'y' =>  957, 'w' => 536, 'h' => 281 ],
            [ 'n' => 'garabato-amarillo-arcos',  'x' =>  414, 'y' =>  900, 'w' => 146, 'h' => 139 ],
            [ 'n' => 'garabato-naranja-rayas',   'x' =>  423, 'y' =>  622, 'w' => 102, 'h' =>  87 ],
            [ 'n' => 'mano-grabador',            'x' => 1305, 'y' =>  212, 'w' => 353, 'h' => 352 ],
            [ 'n' => 'garabato-naranja-ondas-1', 'x' => 1209, 'y' =>  281, 'w' => 118, 'h' => 121 ],
            [ 'n' => 'garabato-naranja-ondas-2', 'x' => 1382, 'y' =>  141, 'w' => 104, 'h' =>  97 ],
            [ 'n' => 'garabato-azul-barra',      'x' => 1180, 'y' => 1061, 'w' => 523, 'h' => 156 ],
            [ 'n' => 'muelle',                   'x' => 1389, 'y' =>  466, 'w' => 538, 'h' => 313 ],
            [ 'n' => 'garabato-amarillo-zigzag', 'x' => 1761, 'y' =>  495, 'w' => 138, 'h' =>  89 ],
            [ 'n' => 'velero',                   'x' => 1242, 'y' =>  624, 'w' => 503, 'h' => 581 ],
            [ 'n' => 'garabato-naranja-ovalos',  'x' =>  274, 'y' =>  218, 'w' =>  93, 'h' =>  71 ],
            [ 'n' => 'garabato-azul-onda',       'x' =>   59, 'y' =>  893, 'w' => 146, 'h' =>  86 ],
        ],
    ],
    'mobile'  => [
        'w'         => 784,
        'h'         => 1371,
        'cx'        => 390,
        'cy'        => 666,
        'recorrido' => 100,
        'ondas'     => [ 238, 323, 402, 507, 609, 728, 860, 1000 ],
        'piezas'    => [
            [ 'n' => 'isla-verde',               'x' =>   75, 'y' =>  544, 'w' => 604, 'h' => 333, 'fade' => true ],
            [ 'n' => 'titulo',                   'x' =>  226, 'y' =>  498, 'w' => 330, 'h' => 390, 'titulo' => true ],
            [ 'n' => 'nene-camara',              'x' =>   21, 'y' =>  931, 'w' => 218, 'h' => 265, 'f' => 'nene-camara-m' ],
            [ 'n' => 'garabato-naranja-rayas',   'x' =>  154, 'y' =>  947, 'w' =>  64, 'h' =>  60, 'f' => 'garabato-naranja-rayas-m' ],
            [ 'n' => 'mano-grabador',            'x' =>  473, 'y' =>  243, 'w' => 250, 'h' => 250 ],
            [ 'n' => 'garabato-naranja-ondas-1', 'x' =>  405, 'y' =>  292, 'w' =>  83, 'h' =>  86 ],
            [ 'n' => 'garabato-naranja-ondas-2', 'x' =>  528, 'y' =>  193, 'w' =>  73, 'h' =>  68 ],
            [ 'n' => 'muelle',                   'x' =>    0, 'y' =>  193, 'w' => 386, 'h' => 242, 'f' => 'muelle-m' ],
            [ 'n' => 'garabato-amarillo-zigzag', 'x' =>  257, 'y' =>  215, 'w' => 106, 'h' =>  69 ],
            [ 'n' => 'garabato-azul-barra',      'x' =>  506, 'y' => 1118, 'w' => 347, 'h' => 104 ],
            [ 'n' => 'velero',                   'x' =>  548, 'y' =>  829, 'w' => 236, 'h' => 385, 'f' => 'velero-m' ],
            [ 'n' => 'garabato-azul-onda',       'x' =>   78, 'y' =>  435, 'w' => 146, 'h' =>  86 ],
        ],
    ],
];
?>
<div id="portada" class="portada" role="dialog" aria-modal="true" aria-label="El mapa sonoro de la Isla Santiago">
    <img class="portada__papel" src="<?php echo esc_url( $portada_url . 'papel.webp' ); ?>" alt="" width="1920" height="1280" aria-hidden="true">

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

            <?php foreach ( $esc['piezas'] as $pieza ) :
                $es_fade   = ! empty( $pieza['fade'] );
                $es_titulo = ! empty( $pieza['titulo'] );
                $archivo   = isset( $pieza['f'] ) ? $pieza['f'] : $pieza['n'];

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
                if ( ! $es_fade && ! $es_titulo ) {
                    $estilo .= sprintf( '--dx:%d;--dy:%d;', $dx, $dy );
                }

                // lazy: la composición que no se ve (display:none) no se descarga.
                $img = sprintf(
                    '<img src="%s" alt="%s" width="%d" height="%d" loading="lazy"%s>',
                    esc_url( $portada_url . $archivo . '.webp' ),
                    $es_titulo ? 'El mapa sonoro de la Isla Santiago' : '',
                    $pieza['w'],
                    $pieza['h'],
                    $es_titulo ? '' : ' aria-hidden="true"'
                );
                ?>
                <?php if ( $es_titulo ) : ?>
                    <h1 class="<?php echo esc_attr( $clases ); ?>" style="<?php echo esc_attr( $estilo ); ?>"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
                <?php else : ?>
                    <div class="<?php echo esc_attr( $clases ); ?>" style="<?php echo esc_attr( $estilo ); ?>"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <button type="button" class="portada__comenzar">Comenzar</button>
</div>
