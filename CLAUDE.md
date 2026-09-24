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
- `mapa-circuitos.js` — Vista de circuito en el mapa público (feed de puntos,
  navegación, caché en memoria). Depende de `window.MapaSonoroPanel`, que
  expone `mapa-sonoro.js`
- `acf-json/` — Field groups de ACF (Local JSON). Los CPT NO viven acá: se
  crean con CPT UI y se guardan en la base de datos de cada entorno

## CPT: `punto-sonoro`

Campos ACF:
- `latitud`, `longitud` — Number (coordenadas)
- `audio` — File (URL de audio)
- `video` — File (video subido)
- `video_embed` — URL (link de YouTube/Vimeo). Un punto puede tener archivo,
  link embebido, ambos o ninguno; si hay ambos, el archivo subido gana
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

`GET /wp-json/mapa-sonoro/v1/punto/{id}` → `{ titulo, audio, video, imagen, descripcion, categorias, url }`
donde `video` es `{ archivo, embed }` (ambos `null` si no hay nada cargado).

`GET /wp-json/mapa-sonoro/v1/circuito/{id}` → `{ id, titulo, descripcion, color, trazo, puntos }`
donde `puntos` respeta el orden de `puntos_del_circuito` y cada uno trae
`{ id, titulo, lat, lng }`.

El orden se preserva porque se lee directo del array de ACF. Si alguna vez se
pasa por `WP_Query`, hay que incluir `'orderby' => 'post__in'` o el orden se pierde.

Nota: `color` viene `null` — el campo `color_circuito` todavía no existe en ACF.

`GET /wp-json/mapa-sonoro/v1/circuito/{id}` también trae, por cada punto,
su detalle completo (`descripcion`, `audio`, `video`, `imagen`) — una sola
llamada trae todo el circuito. Sin galería de imágenes: `punto-sonoro` solo
tiene `imagen_punto`, una imagen.

### Vista de circuito en el front (`mapa-circuitos.js`)

Al tocar un marcador con `circuito_id`, se abre un feed vertical con todos
los puntos del circuito numerados y renderizados completos (nada se
expande/colapsa). El punto tocado queda destacado y el panel scrollea
hasta él. Navegar a otro punto del mismo circuito (otro marcador, el bloque
en el panel, o el reproductor de audio) nunca cierra el panel; sí lo hace
tocar un punto de otro circuito o uno suelto.

- `mapa-sonoro.js` expone `window.MapaSonoroPanel` (mapa, panel, marcadores,
  `registrarAudio`) para que `mapa-circuitos.js` no duplique esa lógica.
- La respuesta de cada circuito se cachea en memoria (`const cache = {}`
  dentro de `mapa-circuitos.js`) — se resetea con cada recarga de página,
  no persiste entre visitas.
- Solo un audio suena a la vez en todo el panel (`registrarAudio` pausa
  cualquier otro al arrancar uno nuevo, y activar un video también lo pausa).
- Audio con `preload="none"`, imágenes con `loading="lazy"` — el archivo no
  se descarga hasta que se necesita.
- Video: placeholder con botón de play, nada de `<video>`/`<iframe>` hasta
  que se activa (`crearBloqueVideo` / `activarVideo`, en `mapa-sonoro.js`).
  YouTube muestra una miniatura real (URL pública predecible); Vimeo no
  (implicaría una llamada a su API por video) — placeholder genérico.
  Si hay archivo subido y link embebido a la vez, gana el archivo.

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
- Marcadores con `L.divIcon` (`.marcador-sonoro`): 18px, relleno casi blanco
  con stroke oscuro. El tamaño está en dos lados y tiene que coincidir — el
  CSS y el `iconSize`/`iconAnchor` del `divIcon`, o el punto queda corrido
  respecto a su coordenada
- Click en marcador: pan con offset (desktop: horizontal, mobile: vertical) + abre panel
- Desktop (>720px): side panel derecho de 450px con animación ancho + fade
- Mobile (≤720px): bottom sheet desliza desde abajo
- Marcador activo: animación ripple con `::before`/`::after` (keyframe `onda`)
- Click en fondo del mapa (desktop) o tecla Esc: cierra panel
- Al seleccionar un punto arranca solo su medio (`reproducirMedio`): si tiene
  audio gana el audio, si no se activa el video. Suena un solo medio a la vez
  en todo el panel — arrancar un audio corta cualquier video y viceversa
- Recorridos como `L.polyline`, toggle via botón `#toggle-recorridos`

## Mapa admin — comportamiento

- Usa los mismos tiles de OpenStreetMap.de que el mapa público. Antes usaba
  CARTO, que pasó a exigir API key (Leaflet nunca pide key: la pide el
  proveedor de tiles)
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
- Galería de imágenes en `punto-sonoro` (hoy solo `imagen_punto`, una imagen)
- Trazo del circuito dibujado en el mapa mientras la vista está abierta
- Compartir circuito por URL
- Filtros por etiquetas
- Sacar de `functions.php` el bloque "TEMPORAL: migrar videos mal cargados
  en el campo Audio" una vez que se corra en producción (Herramientas →
  "Migrar videos (temporal)") y se confirme que salió bien. No debe quedar
  una herramienta que reescribe contenido colgada permanentemente
