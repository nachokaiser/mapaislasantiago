<?php
add_action( 'wp_enqueue_scripts', 'my_child_theme_enqueue_styles' );
function my_child_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}


// Cargar Leaflet y el mapa sonoro
function mapa_sonoro_scripts() {
    if ( is_page('mapa') ) {
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
        wp_enqueue_script('mapa-sonoro-js', get_stylesheet_directory_uri() . '/mapa-sonoro.js', ['leaflet-js'], null, true);
        wp_enqueue_script('mapa-circuitos-js', get_stylesheet_directory_uri() . '/mapa-circuitos.js', ['mapa-sonoro-js'], null, true);
        wp_enqueue_style('mapa-sonoro-css', get_stylesheet_directory_uri() . '/mapa-sonoro.css');

        // Pasar solo los datos necesarios para los marcadores
        $puntos = get_posts(['post_type' => 'punto-sonoro', 'numberposts' => -1]);
        $data = [];
        foreach ($puntos as $punto) {
            $circuito    = get_field('circuito', $punto->ID);
            $circuito_id = $circuito ? ( is_object($circuito) ? $circuito->ID : (int) $circuito ) : null;

            $data[] = [
                'id'          => $punto->ID,
                'titulo'      => get_the_title($punto->ID),
                'lat'         => (float) get_field('latitud', $punto->ID),
                'lng'         => (float) get_field('longitud', $punto->ID),
                'circuito_id' => $circuito_id,
            ];
        }

        // Manifest liviano de circuitos (sin trazo ni puntos, eso se pide on-demand)
        $circuitos_posts = get_posts(['post_type' => 'circuito-sonoro', 'numberposts' => -1]);
        $circuitos_data  = [];
        foreach ($circuitos_posts as $circuito_post) {
            $circuitos_data[] = [
                'id'     => $circuito_post->ID,
                'titulo' => get_the_title($circuito_post->ID),
                'color'  => get_field('color_circuito', $circuito_post->ID) ?: null,
            ];
        }

        wp_localize_script('mapa-sonoro-js', 'MapaSonoro', [
            'puntos'        => $data,
            'circuitos'     => $circuitos_data,
            'restUrl'       => rest_url('mapa-sonoro/v1/punto/'),
            'restUrlCircuito' => rest_url('mapa-sonoro/v1/circuito/'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'mapa_sonoro_scripts');


// Mapa admin: scripts y datos para edición de punto-sonoro
function mapa_admin_scripts( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;

    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'punto-sonoro' ) return;

    wp_enqueue_style( 'leaflet-css-admin', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' );
    wp_enqueue_script( 'leaflet-js-admin', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true );
    wp_enqueue_script( 'mapa-admin-js', get_stylesheet_directory_uri() . '/mapa-admin.js', [ 'leaflet-js-admin' ], null, true );
    wp_enqueue_style( 'mapa-admin-css', get_stylesheet_directory_uri() . '/mapa-admin.css' );

    global $post;
    $post_id    = $post ? $post->ID : ( isset( $_GET['post'] ) ? (int) $_GET['post'] : 0 );
    $actual_lat = $post_id ? (float) get_field( 'latitud', $post_id )  : 0;
    $actual_lng = $post_id ? (float) get_field( 'longitud', $post_id ) : 0;

    $todos   = get_posts( [ 'post_type' => 'punto-sonoro', 'numberposts' => -1 ] );
    $otros   = [];
    foreach ( $todos as $p ) {
        if ( $p->ID === $post_id ) continue;
        $lat = (float) get_field( 'latitud', $p->ID );
        $lng = (float) get_field( 'longitud', $p->ID );
        if ( ! $lat || ! $lng ) continue;
        $otros[] = [
            'id'     => $p->ID,
            'titulo' => get_the_title( $p->ID ),
            'lat'    => $lat,
            'lng'    => $lng,
        ];
    }

    wp_localize_script( 'mapa-admin-js', 'MapaAdmin', [
        'centro' => [ 'lat' => -34.834911, 'lng' => -57.901543 ],
        'zoom'   => 14,
        'actual' => [ 'lat' => $actual_lat, 'lng' => $actual_lng ],
        'puntos' => $otros,
    ] );
}
add_action( 'admin_enqueue_scripts', 'mapa_admin_scripts' );


// Inyectar el contenedor del mapa en la pantalla de edición
function mapa_admin_insertar_contenedor( $post ) {
    if ( $post->post_type !== 'punto-sonoro' ) return;
    echo '<div id="mapa-admin-contenedor"></div>';
}
add_action( 'edit_form_after_title', 'mapa_admin_insertar_contenedor' );


// Endpoint REST para el detalle completo de un punto-sonoro
add_action( 'rest_api_init', function () {
    register_rest_route( 'mapa-sonoro/v1', '/punto/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'mapa_sonoro_rest_punto',
        'permission_callback' => '__return_true',
    ] );
} );

function mapa_sonoro_rest_punto( $request ) {
    $id = (int) $request->get_param( 'id' );

    if ( ! $id || get_post_type( $id ) !== 'punto-sonoro' ) {
        return new WP_Error( 'not_found', 'Punto no encontrado', [ 'status' => 404 ] );
    }

    $audio  = get_field( 'audio', $id );
    $imagen = get_field( 'imagen_punto', $id );

    // Categorías desde todas las taxonomías del CPT
    $categorias = [];
    foreach ( get_object_taxonomies( 'punto-sonoro' ) as $tax ) {
        $terms = get_the_terms( $id, $tax );
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $categorias[] = $term->name;
            }
        }
    }

    return rest_ensure_response( [
        'titulo'      => get_the_title( $id ),
        'audio'       => $audio  ? $audio['url']  : null,
        'imagen'      => $imagen ? $imagen['url'] : null,
        'descripcion' => get_field( 'descripcion-punto', $id ) ?: null,
        'categorias'  => $categorias,
        'url'         => get_permalink( $id ),
    ] );
}


// ============================================================
// Circuitos sonoros
// ============================================================

// Field keys: ACF resuelve escrituras programáticas de forma más confiable
// por key que por nombre.
define( 'MAPA_SONORO_KEY_CIRCUITO', 'field_punto_sonoro_circuito_sistema' );
define( 'MAPA_SONORO_KEY_PUNTOS_CIRCUITO', 'field_6aa6d68b085d0' );

// Campo de sistema `circuito` en punto-sonoro: se completa solo por
// sincronización (ver mapa_sonoro_sync_circuito_puntos). No vive en
// acf-json a propósito — es de código, no de la UI de ACF.
add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    acf_add_local_field_group( [
        'key'    => 'group_punto_sonoro_circuito_sistema',
        'title'  => 'Circuito (sistema)',
        'fields' => [
            [
                'key'           => 'field_punto_sonoro_circuito_sistema',
                'label'         => 'Circuito',
                'name'          => 'circuito',
                'type'          => 'post_object',
                'instructions'  => 'Se asigna automáticamente al agregar este punto a un circuito. No editar a mano.',
                'post_type'     => [ 'circuito-sonoro' ],
                'return_format' => 'object',
                'multiple'      => 0,
                'allow_null'    => 1,
                'disabled'      => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'punto-sonoro',
                ],
            ],
        ],
        'position'  => 'side',
        'menu_order' => 99,
    ] );
} );


// Sincroniza circuito → punto. `puntos_del_circuito` (relationship, del lado
// del circuito) es la única fuente de verdad sobre pertenencia y orden.
// `circuito` (del lado del punto) es derivado y se reescribe acá.
function mapa_sonoro_sync_circuito_puntos( $post_id ) {
    if ( get_post_type( $post_id ) !== 'circuito-sonoro' ) return;

    static $syncing = false;
    if ( $syncing ) return;
    $syncing = true;

    $circuito_id = (int) $post_id;

    $puntos_nuevos = get_field( 'puntos_del_circuito', $circuito_id ) ?: [];
    $ids_nuevos    = array_map( function ( $p ) {
        return is_object( $p ) ? $p->ID : (int) $p;
    }, $puntos_nuevos );

    // Puntos que antes apuntaban a este circuito y ya no están en la lista: limpiar.
    $puntos_previos = get_posts( [
        'post_type'   => 'punto-sonoro',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => [
            [
                'key'   => 'circuito',
                'value' => $circuito_id,
            ],
        ],
    ] );

    foreach ( $puntos_previos as $punto_id ) {
        if ( ! in_array( $punto_id, $ids_nuevos, true ) ) {
            update_field( MAPA_SONORO_KEY_CIRCUITO, null, $punto_id );
        }
    }

    // Asignar este circuito a cada punto de la lista nueva.
    // Si un punto ya pertenecía a otro circuito, se lo saca de ahí primero
    // (el último circuito al que se agrega gana).
    foreach ( $ids_nuevos as $punto_id ) {
        $circuito_anterior    = get_field( 'circuito', $punto_id );
        $circuito_anterior_id = $circuito_anterior ? ( is_object( $circuito_anterior ) ? $circuito_anterior->ID : (int) $circuito_anterior ) : 0;

        if ( $circuito_anterior_id && $circuito_anterior_id !== $circuito_id && get_post_type( $circuito_anterior_id ) === 'circuito-sonoro' ) {
            $otros_puntos = get_field( 'puntos_del_circuito', $circuito_anterior_id ) ?: [];
            $otros_ids    = array_values( array_filter( array_map( function ( $p ) {
                return is_object( $p ) ? $p->ID : (int) $p;
            }, $otros_puntos ), function ( $id ) use ( $punto_id ) {
                return $id !== $punto_id;
            } ) );
            update_field( MAPA_SONORO_KEY_PUNTOS_CIRCUITO, $otros_ids, $circuito_anterior_id );
        }

        update_field( MAPA_SONORO_KEY_CIRCUITO, $circuito_id, $punto_id );
    }

    $syncing = false;
}
add_action( 'acf/save_post', 'mapa_sonoro_sync_circuito_puntos', 20 );


// Endpoint REST para el detalle completo de un circuito-sonoro
add_action( 'rest_api_init', function () {
    register_rest_route( 'mapa-sonoro/v1', '/circuito/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'mapa_sonoro_rest_circuito',
        'permission_callback' => '__return_true',
    ] );
} );

// Detalle completo de un punto sonoro, para el feed del circuito. Sin
// campo de video ni galería: no existen todavía en ACF (solo audio, una
// imagen y descripción). Cuando se agreguen, extender acá.
function mapa_sonoro_punto_detalle_para_circuito( $punto_id ) {
    $audio  = get_field( 'audio', $punto_id );
    $imagen = get_field( 'imagen_punto', $punto_id );

    return [
        'id'          => $punto_id,
        'titulo'      => get_the_title( $punto_id ),
        'lat'         => (float) get_field( 'latitud', $punto_id ),
        'lng'         => (float) get_field( 'longitud', $punto_id ),
        'descripcion' => get_field( 'descripcion-punto', $punto_id ) ?: null,
        'audio'       => $audio  ? $audio['url']  : null,
        'imagen'      => $imagen ? $imagen['url'] : null,
    ];
}

function mapa_sonoro_rest_circuito( $request ) {
    $id = (int) $request->get_param( 'id' );

    if ( ! $id || get_post_type( $id ) !== 'circuito-sonoro' ) {
        return new WP_Error( 'not_found', 'Circuito no encontrado', [ 'status' => 404 ] );
    }

    $trazo_raw = get_field( 'trazo_circuito', $id );
    $trazo     = null;
    if ( $trazo_raw ) {
        $decoded = json_decode( $trazo_raw, true );
        $trazo   = ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : null;
    }

    // El orden de `puntos_del_circuito` ya viene correcto porque lo leemos
    // directo del campo ACF (no via WP_Query, que perdería el orden salvo
    // que se use 'orderby' => 'post__in' explícitamente).
    $puntos_relacion = get_field( 'puntos_del_circuito', $id ) ?: [];
    $puntos = [];
    foreach ( $puntos_relacion as $punto ) {
        $punto_id = is_object( $punto ) ? $punto->ID : (int) $punto;
        $puntos[] = mapa_sonoro_punto_detalle_para_circuito( $punto_id );
    }

    return rest_ensure_response( [
        'id'          => $id,
        'titulo'      => get_the_title( $id ),
        'descripcion' => get_field( 'descripcion-circuito', $id ) ?: null,
        'color'       => get_field( 'color_circuito', $id ) ?: null,
        'trazo'       => $trazo,
        'puntos'      => $puntos,
    ] );
}