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
        wp_enqueue_style('mapa-sonoro-css', get_stylesheet_directory_uri() . '/mapa-sonoro.css');

        // Pasar solo los datos necesarios para los marcadores
        $puntos = get_posts(['post_type' => 'punto-sonoro', 'numberposts' => -1]);
        $data = [];
        foreach ($puntos as $punto) {
            $data[] = [
                'id'     => $punto->ID,
                'titulo' => get_the_title($punto->ID),
                'lat'    => (float) get_field('latitud', $punto->ID),
                'lng'    => (float) get_field('longitud', $punto->ID),
            ];
        }
        wp_localize_script('mapa-sonoro-js', 'MapaSonoro', [
            'puntos'  => $data,
            'restUrl' => rest_url('mapa-sonoro/v1/punto/'),
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