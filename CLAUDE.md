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
- `mapa-lightbox.js` — Lightbox de fotos (click en `.punto-imagen` la abre
  en pantalla completa). Independiente de los otros dos: usa delegación de
  eventos en `document`, no depende de cuándo/dónde se insertan las fotos
- `mapa-nav.js` — Nav de circuitos del header (links + menú hamburguesa).
  Depende de `mapa-circuitos.js`: al tocar un link llama a
  `MapaCircuitos.abrir(id)`
- `mapa-toast.js` — Toast de bienvenida (una vez por sesión, ver "Header, toast
  de bienvenida y footer")
- `portada.php` / `portada.css` / `portada.js` — Pantalla de portada con
  collage animado (en cada carga, ver "Portada")
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
MapaSonoro.circuitos       // array: { id, titulo, color, color_claro, color_texto }
                           // (colores de la paleta en código, ver "Colores por circuito")
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

Nota: `color` de este endpoint viene `null` — el color de cada circuito NO
sale de ACF sino de la paleta en código (`mapa_sonoro_paleta_circuitos()`,
ver "Colores por circuito"); el JS lo toma de `MapaSonoro.circuitos`.

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
  gris, solo cuando el panel muestra un circuito, no en un punto suelto).
  El título en sí va a **24px** y con el **color de texto del circuito**
  (`--c-texto`, que `mapa-circuitos.js` pone sobre `#panel-titulo-sticky` y
  `mapa-sonoro.js` saca al mostrar un punto suelto — ahí vuelve a negro).
  Vale para el side panel y el bottom sheet (es el mismo elemento).
- **Scroll al abrir desde el nav:** abrir un circuito sin elegir punto
  (`abrir(id)` sin segundo parámetro) deja siempre `#panel-contenido` en
  `scrollTop = 0`, sea un circuito nuevo, el mismo ya abierto, o con un
  punto seleccionado. Es un salto directo, sin `smooth` (el scroll suave
  no se pudo probar en el navegador de pruebas).
- Las tarjetas y la línea toman el color del circuito vía variables CSS
  que `renderizar()` pone en `.circuito-detalle` (`--c-principal`,
  `--c-claro`, `--c-texto`). Ojo: se pidió que sea **sutil** — el color vive
  solo en el borde de la tarjeta activa, el bullet seleccionado y el título.
  El fondo de la tarjeta/hover sigue siendo `#f8f8f8`, la línea y los
  bullets en reposo siguen grises (la primera versión los teñía y se
  revirtió por exceso de color).
- Los puntos ya no muestran su número en el front (`data-orden` lo guarda
  igual por si hace falta después) — el orden real sigue viniendo del
  backend en `data.puntos`. Cada punto es un bullet (`.punto-circuito-numero`,
  10px gris `#B0B0B0` / 16px del color principal del circuito cuando está
  seleccionado, vía `transform: scale()` para no correr el centro entre
  estados) + título (color de texto del circuito cuando seleccionado, gris
  `#4b5563` si no).
- Una línea (`.circuito-linea`, `#B0B0B0`) atraviesa todos los puntos del
  feed, del primero al último. La dibuja `dibujarLineaCircuito()` en JS
  porque el alto entre un punto y el siguiente depende del contenido real
  (variable) — no hay forma de calcularlo en CSS puro. El `left` SÍ queda
  fijo en CSS (no medido): está derivado del border + padding + ancho del
  bullet de `.punto-circuito-item`, ver el comentario en `.circuito-linea`
  si esos valores cambian. Se recalcula cuando el panel termina de
  animarse (si estaba cerrado), igual que el scroll.
- Punto seleccionado (`.activo`): además del bullet/título en acento, la
  card entera lleva el fondo del hover de forma persistente + un borde de
  2px del color principal del circuito alrededor. El borde vive siempre (transparente cuando no
  está seleccionado) para que activar/desactivar un punto no reacomode el
  layout.
- Capas del feed (de atrás para adelante): fondo del hover en un punto sin
  seleccionar (`::before`, z-index 0) → línea (z-index 1) → fondo del
  punto SELECCIONADO (z-index 2, por eso la línea queda oculta detrás de
  la card activa) → bullet y contenido (z-index 3, siempre visibles).
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

### Lightbox de fotos (`mapa-lightbox.js`)

Click en cualquier `.punto-imagen` (punto suelto o feed de circuito) la
abre en pantalla completa. Markup fijo en `template-mapa.php`
(`#lightbox`, `hidden` por defecto), reutilizado para cualquier foto —
delegación de eventos en `document`, no hace falta cablear cada imagen.

- Zoom: click en la imagen alterna `.ampliada` (tamaño real en vez de
  ajustada a pantalla); si no entra, el `.lightbox` de afuera tiene
  `overflow: auto` para recorrerla.
- Cierre: botón ✕, Escape, o click en el fondo. El Escape hace
  `stopPropagation()` — si no, también dispara el Escape que cierra
  `#mapa-panel` por detrás.
- Accesible: `role="dialog"` + `aria-modal`, foco al botón cerrar al
  abrir, foco de vuelta a la foto disparadora al cerrar, y un trap de Tab
  simple (el único control interno es el botón cerrar, así que Tab
  siempre vuelve ahí).

## Colores por circuito

La paleta vive **en código**, no en la base de datos: `mapa_sonoro_paleta_circuitos()`
en `functions.php`, indexada por slug del circuito. El orden de esa lista es
también el orden del nav. Cada circuito tiene tres colores:
- `principal`: puntos, ondas, borde y bullet de la tarjeta activa
- `claro`: relleno de los puntos en reposo
- `texto`: versión más oscura del principal, para letra sobre blanco (nav y
  título del panel) — el principal solo no llega a 4,5:1 de contraste en
  todos los casos

| Circuito (slug) | principal | claro | texto |
| --- | --- | --- | --- |
| `del-barco-al-barquito` | `#8A3FE0` | `#E8D8FB` | `#8A3FE0` |
| `por-los-pasillos` | `#F2540A` | `#FFDCC6` | `#D44700` |
| `el-porton-misterioso` | `#2F6BFF` | `#CFE0FF` | `#2159E0` |
| `en-el-rio` | `#F0428A` | `#FFD3E6` | `#D81B72` |
| `camino-a-la-playita` | `#0F9E82` | `#C4F2E6` | `#0A7F68` |

- "En el río" (rosa) y "El portón misterioso" (azul) se **intercambiaron a
  propósito**: el azul de En el río se mimetizaba con el agua del mapa.
- Naranja y verde están ajustados para que el borde pase 3:1 de contraste.
- **Amarillo**: reservado para un sexto circuito, pero NO sobre blanco con el
  tono vivo (`#F5B800` da ~1,7:1). Versión usable anotada en un comentario de
  la paleta: `#B07C00` / `#FFEFB3` / `#8A6100`.
- Un circuito cuyo slug no esté en la paleta no tiene color (`null`): sus
  puntos quedan blancos con borde oscuro y su link de nav sin color. Si se
  suma un circuito, hay que agregarlo a la paleta (en código).
- Si algún día se quiere editar desde el admin, el camino es un campo ACF
  `color_circuito` (no existe hoy).

## Nav de circuitos (header)

`template-mapa.php` renderiza, dentro de `#mapa-nav`, un link por circuito
(`.mapa-nav-circuito`, con `data-circuito-id` y `--c-principal`/`--c-texto`
inline), en el orden de la paleta. `mapa-nav.js`:
- click → `preventDefault()` + cierra el menú + `MapaCircuitos.abrir(id)`:
  abre el panel del circuito **sin seleccionar ningún punto**, encuadra el
  circuito en el mapa y corta el audio/video del circuito anterior
- escucha el evento `mapa:circuito` (lo dispara `mapa-circuitos.js` al
  abrir/cerrar) para marcar el link activo con `aria-current="true"` (subrayado)
- menú hamburguesa (`.mapa-nav-toggle`, a la derecha): se abre/cierra con el
  botón, Esc o un toque afuera; `aria-expanded` y `aria-label` se actualizan

**Breakpoints**: la hamburguesa aparece por debajo de **1279px** (el nav no
entra al lado del título más abajo; a 1280px entra con ~116px de aire). El
resto del layout (side panel vs. bottom sheet) sigue cortando en 720px
(`esMobile = innerWidth <= 720`). Son dos breakpoints distintos a propósito.

**Encuadre del mapa** (`encuadrarPuntos()` en `mapa-sonoro.js`): al abrir un
circuito sin punto, `fitBounds` con padding que esquiva el panel (desktop:
derecha 450px + margen; mobile: abajo ~60% del alto). Para que `maxBounds`
no lo corte, mientras el circuito está abierto se amplía con
`setMaxBounds(limitesBase.pad(1.5))`; `restaurarLimites()` lo devuelve al
cerrar (`salir()`).

**Hoja compacta en mobile** (`.sin-punto`): al abrir un circuito desde el nav (sin
punto elegido), la hoja de abajo mide **42dvh** en vez de 60dvh, para dejar más
mapa a la vista: "En el río" mide ~3,8 km × 1,7 km y con la hoja normal quedaba un
tramo de ~150px de mapa y el circuito no entraba. `abrir()` pone/saca la clase;
`seleccionarPunto()` y `abrirPanel()` la sacan (la hoja vuelve a 60dvh, con
transición de `max-height`). `encuadrarPuntos()` usa `HOJA_COMPACTA` (0,42) para el
padding de abajo; si se cambia el 42 del CSS hay que cambiarlo en las dos partes.
Las hojas usan **`dvh`** (con `vh` de respaldo): en iPhone `vh` es el alto sin las
barras de Safari, y `60vh` tapaba ~70% de lo visible.

**Los links del nav NO se agrandaron** con el resto del texto: 16px en
desktop, 18px en el menú hamburguesa (excepción explícita a la regla de +2px).

## Tipografía

**Alegreya Sans** en todo el mapa (Google Fonts, pesos 400/500/700/800 y
cursivas, encolada en `functions.php` con el handle `mapa-fuentes`). Se aplica
en `body.mapa-page` y se repite en `button`, `input`, `select`, `textarea` y
`.leaflet-container` porque esos no heredan la fuente. No tiene peso 600.
Se ve más chica que la fuente anterior, por eso: base de `body.mapa-page`
en **19px** (GeneratePress da 17px) y todos los `font-size` explícitos de
`mapa-sonoro.css`/`portada.css` subieron +2px, salvo los links del nav (ver
arriba) y los íconos ✕ (`#panel-cerrar`, `.lightbox-cerrar`, 20px). El
título del circuito en el panel es 24px. El `<h1>` de la portada no se tocó.

## Portada

Pantalla de entrada (`portada.php` + `portada.css` + `portada.js`) que tapa el
mapa hasta tocar COMENZAR. **Se muestra en cada carga de la página** — antes se
mostraba una vez por sesión (`sessionStorage`), pero se sacó a pedido: no
molesta y no había forma de volver a verla. Mientras está arriba, header, mapa y
footer están `inert`.

- **Composiciones:** una de escritorio (`collage-mapa-sonoro.psd`, lienzo
  1920×1371) y una de pantallas verticales (`mobile-collage-mapa-sonoro.psd`,
  784×1371), en `~/Documents/Mapa sonoro Isla santiago/`. El CSS elige por
  proporción (`max-aspect-ratio: 1/1` = mobile). Cada una es un "escenario" con
  el tamaño del lienzo, escalado con el ancho de pantalla
  (`--portada-escala`, 0 = entra entero, 1 = cubre), y las piezas van en % con
  los valores del PSD (array `$portada_escenarios` en `portada.php`, en el
  orden de capas del PSD de abajo hacia arriba).
- **Línea de tiempo** (empieza cuando JS agrega `.is-playing`): isla 0 s →
  ondas desde 0,4 s → piezas de alrededor 1,25 s → wordmark "Canto del Río"
  1,95 s → subtítulo 2,1 s → **subrayado de "Isla Santiago" 2,5 s (se traza de
  izquierda a derecha con `clip-path`)** → botón 3,0 s.
- **Carga:** `portada.js` arranca la animación apenas están decodificados el
  papel y la isla (tope 1,5 s); las demás piezas esperan en pausa a tener su
  imagen lista (`.is-lista`; red de seguridad a los 4 s). Esperar todas las
  imágenes hacía arrancar la isla más de 1 s tarde. Papel e isla llevan
  `fetchpriority="high"`; el resto `loading="lazy"` (la composición que no se
  ve no se descarga).
- **Centro de las ondas** (`cx`/`cy` de cada escenario): el de la capa visible
  "Ondas copy" del PSD, no el del grupo oculto "Ondas" (ese está corrido y las
  ondas salían descentradas de la isla). Si se mueve el collage en el PSD,
  releer ese centro.
- **Botón:** de tamaño **estándar** (16px, padding 16px 40px, Alegreya Sans 500,
  azul `#1F42AE`), no escala con el collage — antes copiaba el tamaño del PSD y
  se veía muy grande. Solo su **posición** sigue al escenario (centro a 430px /
  364px bajo el centro del lienzo, escritorio / mobile), y en escritorio está
  subido 30px más (`- 30px` en el `top`). Va último en el DOM, así queda por
  encima de cualquier pieza.
- **Foco del botón:** recibe el foco por código al terminar la animación (así
  Enter/Espacio andan de una y los lectores de pantalla lo anuncian), pero ese
  foco **no se dibuja**. El anillo aparece solo cuando se toca Tab o una flecha
  (`portada.js` agrega `.por-teclado` a `.portada`). No usar `:focus-visible`
  a secas: el navegador lo muestra en un foco puesto por código.
- **Esquinas de abajo en mobile** (`'grupo' => 'bl' | 'br'`): grabador+ondas
  y velero+barra azul van en contenedores `.portada__grupo` anclados a la
  esquina real de la pantalla. Solo se mueven hacia afuera (si sobra papel al
  costado/abajo se pegan a la esquina; si el escenario ya se pasa, quedan
  donde están), nunca hacia el botón.
- **Accesibilidad:** el wordmark es el `<h1>` ("Canto del Río"), el subtítulo
  un `<p>` con su texto, el resto de las imágenes `aria-hidden`.
  `prefers-reduced-motion`: aparece ya armada, sin animar.
- **Destello del papel al entrar las piezas** (corregido, PR #13): las piezas
  arrancaban en `opacity: 0` y el navegador no pinta lo transparente, así que
  ~15 imágenes se pintaban todas juntas en el primer cuadro de su entrada
  (1,25 s): la pantalla se trababa, el papel de fondo se perdía un instante
  (gris plano) y las fotos aparecían de a poco (se vio en una grabación de la
  pantalla a 2880px). Ahora las piezas arrancan en `opacity: 0.01` con
  `will-change: transform, opacity` (ya pintadas de antes), entran escalonadas
  30ms (`--k`, orden de la pieza) y el papel y las ondas tienen capa propia
  (`will-change: transform`). Si vuelve a pasar, lo siguiente es simplificar las
  ondas (se redibujan cada cuadro). Los keyframes `portada-entra`/`portada-fade`
  parten de 0.01, no de 0: no volver a 0.
- **Imágenes** (`assets/portada/*.webp`, exportadas de los PSD con
  `psd-tools`): al reemplazar una, **cambiar el nombre** del archivo (los
  `<img>` no se versionan y el navegador cachea el viejo; por eso existen
  `-m2`). Varias piezas se reutilizan entre escritorio y mobile (misma imagen,
  otro tamaño); las `-m` son recortes propios del mobile.
- Probar siempre con la portada **recién cargada**: el navegador de pruebas del
  editor frena las animaciones y puede tardar 20 s en mostrar el estado final.

## Header, toast de bienvenida y footer

Diseño de Figma `388:202`.

- **Título del sitio** (`.mapa-site-name`): Alegreya Sans **ExtraBold (800)**,
  24px en escritorio, tracking `0.02em`, color `#313740`. En ≤720px va en **19px
  fijos** (pedido explícito) y puede partirse en dos líneas parejas
  (`text-wrap: balance`): a 24px mide ~343px y no entra al lado del botón del
  menú.
- **Footer** (`#mapa-footer`): fondo `#2b333f`, texto blanco, sin borde de
  arriba. El tamaño de letra sigue en 13px (la regla de +2px de la tipografía;
  Figma trae 11px). Texto del Figma: "Hecho en comunidad. Coordinado por el Club
  Isleños Unidos de Isla Santiago, Argentina." (antes decía "Hecho en comunidad
  y coordinado por el Club Isleños Unidos en Isla Santiago, Argentina").
- **Toast de bienvenida** (`#mapa-toast`, `mapa-toast.js`, ícono
  `assets/icon-cerrar.svg` bajado de Figma): "Seleccioná un circuito en el menú de
  arriba o cualquier punto en el mapa para comenzar." (sin saludo ni emoji, a
  pedido del Figma). Fondo `#ececec`, borde 1px `#bebebe`, radio 5px, padding
  10px, Alegreya Sans 500 14px, `position: fixed`, centrado,
  52px debajo del header (en ≤720px, 84px: si no tapa los botones de zoom). El
  `<br>` del texto es solo de escritorio.
  - **Una vez por sesión:** `sessionStorage['toastBienvenidaVisto']`, que se
    marca al mostrarse. "Sesión" = la vida de la pestaña: sobrevive a recargas,
    se pierde al cerrar la pestaña, y una pestaña nueva es una sesión nueva. Si
    el navegador bloquea el storage, el toast sale en cada carga.
  - **Línea de tiempo:** aparece 0,5 s después de que se ve el mapa (la portada
    avisa con el evento `portada:cerrada` al sacarse; si no hay portada, desde
    la carga) con fade de 0,4 s, se queda **10 s** y sale con fade de 0,6 s.
  - **Salida rápida** (fade de 0,2 s): tocar un punto o un circuito — se detecta
    porque el panel (`#mapa-panel`) gana la clase `activo` (MutationObserver), así
    no hay que cablear cada punto ni cada link — o tocar la X.
  - Invisible no recibe clics (`visibility: hidden` + `pointer-events: none`);
    `role="status"` + `aria-live="polite"`; sin transición con
    `prefers-reduced-motion`.
  - `portada.js` ahora saca la portada con `terminarSalida()`: por
    `transitionend`, con un respaldo a los 700 ms (con "reducir movimiento" no hay
    transición y la portada nunca se sacaba).

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
    ahora — todo del color principal de su circuito (relleno y borde) +
    `scale(1.2)` + animación ripple en ese mismo color (`::before`/`::after`,
    keyframe `onda`, con `color-mix()` y fallback rgba). Es el único estado
    con la onda. Sin circuito, el fallback es gris oscuro `#333` (el rojo
    `#FE4734` ya no se usa en ningún lado)
  - **Con circuito** (`.con-circuito`, reposo): relleno claro + borde 1px del
    color principal. Cada marcador lleva `--c-principal`/`--c-claro` inline
    (los pone `crearIcono()` desde `MapaSonoro.circuitos`)
  - **Circuito abierto** (`.circuito-abierto` en el contenedor de Leaflet,
    la agrega `marcarMismoCircuito()`): los puntos que NO son del circuito
    abierto pasan a blanco con borde oscuro (siguen siendo interactivos).
    Los del circuito (`.mismo-circuito`) mantienen su color. Se
    marca/limpia con `marcarMismoCircuito()` / `limpiarMismoCircuito()`
    (expuestas en `window.MapaSonoroPanel`), llamadas desde
    `mapa-circuitos.js` al abrir/salir de un circuito — no al navegar
    dentro del mismo. `.selected` le gana a `.mismo-circuito` vía
    `:not(.selected)`, porque el punto activo también pertenece a su
    propio circuito y tiene ambas clases a la vez
  - Puntos sin circuito (6 en local): quedan blancos con borde oscuro siempre
- Click en marcador: acomoda la vista con offset (desktop: horizontal, mobile:
  vertical) + abre panel. **Siempre al mismo zoom** (`ZOOM_PUNTO`, hoy 16 en
  mobile y desktop, constantes separadas en `mapa-sonoro.js`), sea por toque,
  nav o autoplay y no importa el zoom que dejó el usuario: así el recorrido en
  autoplay tiene una escala reconocible. `centrarConOffset()` usa `setView`
  (0,8 s) y calcula el corrimiento en píxeles al zoom de destino. Abrir un
  circuito desde el nav NO usa esto: encuadra el circuito entero
  (`encuadrarPuntos`, `fitBounds` con `maxZoom` 16)
- Conocido, aceptado: en desktop el punto seleccionado queda a ~60px del borde
  del panel, no centrado en el espacio libre, porque Leaflet no recalcula su
  tamaño cuando el panel se abre (no hay `invalidateSize`). Ya pasaba antes y se
  decidió dejarlo así (se ve bien); en mobile no ocurre
- Desktop (>720px): side panel derecho de 450px con animación ancho + fade
- Mobile (≤720px): bottom sheet desliza desde abajo
- Fondo de `#panel-contenido` (no de `#mapa-panel`): textura de papel
  tileable (`assets/paper-texture.png`, CC0, Rice Paper 2 de Subtle
  Patterns) con `background-attachment: local` — sin ese valor el fondo
  queda pegado al contenedor visible en vez de moverse con el scroll
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

## Entorno local — clon de producción (Mac)

- Sitio de Local: `Mapa Isla Santiago` (`~/Local Sites/mapa-isla-santiago`, URL `http://mapa-isla-santiago.local`), PHP 8.3.36, nginx, MySQL 8.4.
- Es un **clon de producción** hecho el 01/11/2026: base `mapaisla_datos` (prefijo `wp_`, es la que dice `DB_NAME` en el `wp-config.php` de prod) exportada desde phpMyAdmin + `wp-content` comprimido desde el Administrador de archivos de cPanel. URLs reemplazadas de `https://mapaislasantiago.com` a la local con `wp search-replace`. Se loguea con el mismo usuario/contraseña que prod.
- En prod existe además una base `mapaisla_wp_twxwo` (prefijo `i2XQY_`, blogname "Future Investments"): es un resto de instalador, **no se usa y no se toca**.
- Este tema vive en `wp-content/themes/generatepress-child` (clon de git de `nachokaiser/mapaislasantiago`, rama `main`).

### Regla innegociable: la base de producción no se toca
- Se despliega **solo código**, por git (cPanel → Git Version Control → pull). Un pull cambia archivos del tema y nada más.
- La base de datos local **jamás** se sube a prod, ni se importa nada en prod. Si hace falta volver a clonar, siempre en la dirección prod → local.
- Lo único del tema que escribe en la base es `mapa_sonoro_sync_circuito_puntos()` y solo al guardar un circuito en el admin. Nada corre al activar/actualizar el tema.
- Cualquier cambio que viva en la base (CPT de CPT UI, contenido, opciones) hay que replicarlo a mano en prod desde el admin — avisar explícitamente antes de hacerlo.

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
- Campo `color_circuito` (color picker) en el field group de circuitos, si se
  quiere editar la paleta desde el admin (hoy vive en código)
- Galería de imágenes en `punto-sonoro` (hoy solo `imagen_punto`, una imagen)
- Trazo del circuito dibujado en el mapa mientras la vista está abierta
- Compartir circuito por URL
- Filtros por etiquetas
- Tiles del mapa público sin íconos de POI (restaurantes, comercios, etc.):
  hoy vienen "horneados" en la imagen del tile de OSM.de, no son una capa
  aparte que se pueda ocultar. Se probó Esri World Light Gray Canvas (sin
  key, sin POIs) pero el gris monocromático perdía demasiado color frente
  al agua/vegetación del estilo actual — se descartó. No hay ninguna
  opción gratis-sin-key que combine ambas cosas; requeriría tiles propios
  con estilo custom (Mapbox Studio / MapTiler con key, o similar)
