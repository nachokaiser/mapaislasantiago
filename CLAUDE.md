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
- `acf-json/` — Field groups de ACF (Local JSON). Los CPT NO viven acá: se
  crean con CPT UI y se guardan en la base de datos de cada entorno

## CPT: `punto-sonoro`

Campos ACF:
- `latitud`, `longitud` — Number (coordenadas)
- `audio` — File (URL de audio)
- `imagen_punto` — Image
- `descripcion-punto` — Textarea
- Taxonomías propias del CPT (fauna / flora / humano / etc.)
- `circuito` — Post object hacia `circuito-sonoro`. Campo DERIVADO y de
  sistema: se registra por código en `functions.php` (no en `acf-json/`),
  se muestra deshabilitado en el editor y lo escribe solo la sincronización

## CPT: `circuito-sonoro`

Un circuito es un conjunto ORDENADO de puntos sonoros. Un punto pertenece a
un solo circuito, o a ninguno — nunca a más de uno.

Campos ACF:
- `descripcion-circuito` — Textarea
- `trazo_circuito` — Textarea (JSON de coordenadas formato Leaflet `[[lat,lng],...]`)
- `puntos_del_circuito` — Relationship hacia `punto-sonoro`, ordenable arrastrando

### Fuente de verdad y sincronización

`puntos_del_circuito` (lado del circuito) es la ÚNICA fuente de verdad sobre
qué puntos integran el circuito y en qué orden. El campo `circuito` de cada
punto es derivado: existe para que el front sepa a qué circuito pertenece un
punto sin recorrer todos los circuitos.

Al guardar un circuito (`acf/save_post`), `mapa_sonoro_sync_circuito_puntos()`:
- Escribe el ID del circuito en el campo `circuito` de cada punto listado
- Limpia el campo `circuito` de los puntos que fueron quitados
- Si un punto agregado ya pertenecía a otro circuito, lo saca del
  `puntos_del_circuito` de ese otro circuito (gana el último)

El sync NO es retroactivo: cada circuito tiene que guardarse al menos una vez
para que sus puntos queden sincronizados.

Las escrituras usan field keys (constantes `MAPA_SONORO_KEY_*`), no nombres:
ACF resuelve las escrituras programáticas de forma más confiable por key.

## Datos pasados al JS público (`MapaSonoro`)

```js
MapaSonoro.puntos          // array: { id, titulo, lat, lng, circuito_id }
MapaSonoro.circuitos       // array: { id, titulo, color }
MapaSonoro.restUrl         // base URL del endpoint REST de punto
MapaSonoro.restUrlCircuito // base URL del endpoint REST de circuito
```

El manifest se mantiene liviano: solo lo necesario para dibujar marcadores y
saber a qué circuito pertenece cada uno. El detalle se carga on-demand vía REST:

`GET /wp-json/mapa-sonoro/v1/punto/{id}` → `{ titulo, audio, imagen, descripcion, categorias, url }`

`GET /wp-json/mapa-sonoro/v1/circuito/{id}` → `{ id, titulo, descripcion, color, trazo, puntos }`
donde `puntos` respeta el orden de `puntos_del_circuito` y cada uno trae
`{ id, titulo, lat, lng }`.

El orden se preserva porque se lee directo del array de ACF. Si alguna vez se
pasa por `WP_Query`, hay que incluir `'orderby' => 'post__in'` o el orden se pierde.

Nota: `color` viene `null` — el campo `color_circuito` todavía no existe en ACF.

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
- Los CPT (CPT UI) tampoco viajan por git: viven en `wp_options` de cada
  entorno. Si se crea o cambia un CPT en local, hay que replicarlo en
  producción con CPT UI → Herramientas (los slugs tienen que coincidir
  exactamente, porque el código los chequea por nombre)

## Pendiente / próximas features

- Loader mientras carga el mapa
- Campo `color_circuito` (color picker) en el field group de circuitos
- Circuitos en el front (`mapa-sonoro.js`, todavía sin tocar): al tocar un punto
  que pertenece a un circuito se abre la vista del circuito con su listado
  numerado y el punto tocado seleccionado; se navega entre puntos del mismo
  circuito sin cerrar el panel (solo se cierra al tocar un punto de otro
  circuito o uno suelto); mientras está abierto se dibuja el trazo en el mapa
