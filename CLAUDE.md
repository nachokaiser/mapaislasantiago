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

La imagen viaja con sus medidas (`imagen_ancho` / `imagen_alto`) y el front
las escribe como atributos `width`/`height` del `<img>`. No es cosmético: sin
eso, una imagen lazy ocupa alto ~0 hasta que carga, el feed se reacomoda a
medida que las imágenes van entrando, y el scroll al punto activo queda
desfasado al abrir un circuito.

### Vista de circuito en el front (`mapa-circuitos.js`)

Al tocar un marcador con `circuito_id`, se abre un feed vertical con todos
los puntos del circuito renderizados completos (nada se expande/colapsa;
sin números — ver abajo). El punto tocado queda destacado y el panel
scrollea hasta él. Navegar a otro punto del mismo circuito (otro marcador,
el bloque en el panel, o el reproductor de audio) nunca cierra el panel;
sí lo hace tocar un punto de otro circuito o uno suelto.

- El título del panel lleva un eyebrow "Circuito" arriba (`.panel-eyebrow`,
  solo cuando el panel muestra un circuito, no en un punto suelto).
- Los puntos ya no muestran su número en el front (`data-orden` lo guarda
  igual por si hace falta después) — el orden real sigue viniendo del
  backend en `data.puntos`. Cada punto es un bullet (`.punto-circuito-numero`,
  10px gris `#B0B0B0` / 16px `#FE4734` cuando está seleccionado, vía
  `transform: scale()` para no correr el centro entre estados) + título
  (mismo acento cuando seleccionado, gris `#4b5563` si no).
- Una línea (`.circuito-linea`, mismo gris que los bullets) atraviesa todos
  los puntos del feed, del primero al último. La dibuja
  `dibujarLineaCircuito()` en JS porque el alto entre un punto y el
  siguiente depende del contenido real (variable) — no hay forma de
  calcularlo en CSS puro. El `left` SÍ queda fijo en CSS (no medido): está
  derivado del padding y ancho del bullet, ver el comentario en
  `.circuito-linea` si esos valores cambian. Se recalcula cuando el panel
  termina de animarse (si estaba cerrado), igual que el scroll.
- Capas del feed (de atrás para adelante): fondo del hover (`::before`,
  z-index 0) → línea (z-index 1) → bullet y contenido (z-index 2). Así el
  hover no tapa la línea, y la línea pasa "por detrás" de los bullets.
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
- Fotos (`.punto-imagen`) con efecto polaroid: sin `aspect-ratio` forzado
  (se adaptan a la proporción real de cada foto, a diferencia del video que
  sí fuerza 16/9), `border-radius: 2px`, borde blanco sólido de 8px,
  sombra, y rotadas `-1.416deg`. La rotación es solo visual — no reduce el
  espacio que la imagen ocupa en el layout — por eso lleva 10px de margen
  vertical extra, para que las esquinas giradas no se solapen con el
  contenido de arriba/abajo. El video NO lleva nada de este efecto
  (`.punto-video` sigue en `border-radius: 8px`, sin borde ni rotación)

## Datos pasados al JS admin (`MapaAdmin`)

```js
MapaAdmin.centro  // { lat, lng } centro del mapa
MapaAdmin.zoom    // zoom inicial
MapaAdmin.actual  // { lat, lng } coordenadas del punto que se está editando
MapaAdmin.puntos  // array de otros puntos (referencia visual)
```

## Mapa público — comportamiento

- Layout de página fijo al viewport (`body.mapa-page` en flex-column,
  `height: 100dvh`, `overflow: hidden`): el único scroll de toda la página
  es el de `#panel-contenido`. Header y footer quedan siempre visibles.
  `#mapa-wrapper` (mapa + panel) usa `flex: 1; min-height: 0` para ocupar
  lo que sobra — si se le vuelve a poner una altura fija en vez de dejarlo
  crecer por flexbox, el body puede volver a superar el viewport
- Centro: `-34.834911, -57.901543`, zoom 14, minZoom 12/13, maxZoom 18
- `maxBounds` limitado a la isla
- Tile: `https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png`
- Marcadores con `L.divIcon` (`.marcador-sonoro`): 18px, relleno casi blanco
  con stroke oscuro 1px. El tamaño está en dos lados y tiene que coincidir —
  el CSS y el `iconSize`/`iconAnchor` del `divIcon`, o el punto queda
  corrido respecto a su coordenada
- 4 estados de marcador (todos con `box-sizing: border-box`, así que un
  borde más grueso no cambia el tamaño total del punto):
  - **Idle**: colores base
  - **Hover** (`:hover`): mismos colores de idle, solo `scale(1.2)` más grande
  - **Seleccionado** (`.selected`): el punto que se muestra en el panel
    ahora — todo `#FE4734` (relleno y borde) + `scale(1.2)` + animación
    ripple (`::before`/`::after`, keyframe `onda`). Es el único estado con
    la onda
  - **Mismo circuito** (`.mismo-circuito`): los demás puntos del circuito
    que está abierto en el panel — relleno `#FFD7D3`, borde 2px `#FE4734`,
    tamaño idle. Su variante hover solo agranda, no cambia color. Se
    marca/limpia con `marcarMismoCircuito()` / `limpiarMismoCircuito()`
    (expuestas en `window.MapaSonoroPanel`), llamadas desde
    `mapa-circuitos.js` al abrir/salir de un circuito — no al navegar
    dentro del mismo. `.selected` le gana a `.mismo-circuito` vía
    `:not(.selected)`, porque el punto activo también pertenece a su
    propio circuito y tiene ambas clases a la vez
- Click en marcador: pan con offset (desktop: horizontal, mobile: vertical) + abre panel
- Desktop (>720px): side panel derecho de 450px con animación ancho + fade
- Mobile (≤720px): bottom sheet desliza desde abajo
- Fondo de `#panel-contenido` (no de `#mapa-panel`): textura de papel
  tileable (`assets/paper-texture.png`, CC0, Rice Paper 2 de Subtle
  Patterns) con `background-attachment: local` — sin ese valor el fondo
  queda pegado al contenedor visible en vez de moverse con el scroll
- Marcador activo: animación ripple con `::before`/`::after` (keyframe `onda`)
- Click en fondo del mapa (desktop) o tecla Esc: cierra panel
- Al seleccionar un punto arranca solo su medio (`reproducirMedio`): si tiene
  audio gana el audio, si no se activa el video. Suena un solo medio a la vez
  en todo el panel — arrancar un audio corta cualquier video y viceversa

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
- Accesos directos a circuitos en el header — ahí vivía el toggle de
  "Mostrar recorridos" (junto con los `L.polyline` hardcodeados que
  dibujaba), que se sacó entero por quedar sin uso
- Tiles del mapa público sin íconos de POI (restaurantes, comercios, etc.):
  hoy vienen "horneados" en la imagen del tile de OSM.de, no son una capa
  aparte que se pueda ocultar. Se probó Esri World Light Gray Canvas (sin
  key, sin POIs) pero el gris monocromático perdía demasiado color frente
  al agua/vegetación del estilo actual — se descartó. No hay ninguna
  opción gratis-sin-key que combine ambas cosas; requeriría tiles propios
  con estilo custom (Mapbox Studio / MapTiler con key, o similar)
