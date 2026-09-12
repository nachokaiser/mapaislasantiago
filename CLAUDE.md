# Mapa Sonoro — Isla Santiago

Mapa sonoro interactivo de la Isla Santiago Oeste (La Plata, Argentina). WordPress + Leaflet.js.

## Stack

- WordPress en Local by Flywheel (staging local) / cPanel en producción (mapaislasantiago.com)
- Child theme basado en GeneratePress
- Leaflet.js con tiles OpenStreetMap.de
- Advanced Custom Fields (ACF)
- Custom Post Type UI

## Archivos del child theme

- `functions.php` — Encola Leaflet y el mapa admin, pasa datos al JS via `wp_localize_script`
- `mapa-sonoro.js` — Lógica principal del mapa público
- `mapa-sonoro.css` — Estilos del mapa y sus componentes
- `mapa-admin.js` — Mapa de selección de coordenadas en el panel de WordPress
- `mapa-admin.css` — Estilos del mapa admin
- `template-mapa.php` — Template de página personalizado para el mapa
- `style.css` — Declaración del child theme

## CPT: `punto-sonoro`

Campos ACF:
- `latitud`, `longitud` — Number (coordenadas)
- `audio` — File (URL de audio)
- `imagen_punto` — Image
- `descripcion-punto` — Textarea
- Taxonomías propias del CPT (fauna / flora / humano / etc.)

## Datos pasados al JS público (`MapaSonoro`)

```js
MapaSonoro.puntos  // array: { id, titulo, lat, lng }
MapaSonoro.restUrl // base URL del endpoint REST
```

El detalle completo de cada punto se carga on-demand vía REST:
`GET /wp-json/mapa-sonoro/v1/punto/{id}` → `{ titulo, audio, imagen, descripcion, categorias, url }`

## Datos pasados al JS admin (`MapaAdmin`)

```js
MapaAdmin.centro  // { lat, lng } centro del mapa
MapaAdmin.zoom    // zoom inicial
MapaAdmin.actual  // { lat, lng } coordenadas del punto que se está editando
MapaAdmin.puntos  // array de otros puntos (referencia visual)
```

## Mapa público — comportamiento

- Centro: `-34.834911, -57.901543`, zoom 14, minZoom 12/13, maxZoom 18
- `maxBounds` limitado a la isla
- Tile: `https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png`
- Marcadores con `L.divIcon` (`.marcador-sonoro`)
- Click en marcador: pan con offset (desktop: horizontal, mobile: vertical) + abre panel
- Desktop (>720px): side panel derecho con animación ancho + fade
- Mobile (≤720px): bottom sheet desliza desde abajo
- Marcador activo: animación ripple con `::before`/`::after` (keyframe `onda`)
- Click en fondo del mapa (desktop): cierra panel
- Recorridos como `L.polyline`, toggle via botón `#toggle-recorridos`

## Mapa admin — comportamiento

- Click en el mapa coloca/mueve el marcador del punto actual
- Marcador es draggable
- Al soltar o clickear, actualiza los campos ACF `latitud` y `longitud`
- Muestra otros puntos existentes como referencia (sin interacción)

## Workflow de desarrollo

- Desarrollar en Local by Flywheel (staging local)
- Hacer push a GitHub (solo este repositorio — el child theme)
- Desde cPanel → Git Version Control → hacer pull para deployar a producción
- La base de datos y uploads NO se versionan — viven solo en cada entorno

## Pendiente / próximas features

- Loader mientras carga el mapa
- Circuitos sonoros: nuevo CPT con múltiples `punto-sonoro` dentro, panel propio con lista de puntos y reproducción individual con resaltado en mapa
