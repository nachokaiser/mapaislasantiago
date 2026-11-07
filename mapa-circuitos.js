document.addEventListener('DOMContentLoaded', function () {

    const panelApi = window.MapaSonoroPanel;
    if (!panelApi) return;

    const cache = {};
    let circuitoActual = null;

    function renderizarPuntoCircuito(punto, numero) {
        // El número ya no se muestra (solo el punto/bullet) — el orden real
        // sigue viniendo del backend en `data.puntos`, `numero` queda como
        // dato por si hace falta más adelante (ej. accesibilidad).
        let html = '<div class="punto-circuito-item" data-punto-id="' + punto.id + '" data-orden="' + numero + '">';
        html += '<div class="punto-circuito-numero"></div>';
        html += '<div class="punto-circuito-contenido">';
        html += '<h3 class="punto-circuito-titulo">' + punto.titulo + '</h3>';

        if (punto.audio) {
            html += '<audio class="punto-audio" controls preload="none" src="' + punto.audio + '" data-punto-id="' + punto.id + '"></audio>';
        }

        html += panelApi.crearBloqueVideo(punto.video, punto.titulo, punto.id);

        if (punto.imagen) {
            // width/height declarados para que el navegador reserve el alto
            // antes de cargar la imagen. Sin esto el feed se reacomoda al ir
            // cargando y el scroll al punto activo queda desfasado.
            const medidas = punto.imagen_ancho && punto.imagen_alto
                ? ' width="' + punto.imagen_ancho + '" height="' + punto.imagen_alto + '"'
                : '';
            html += '<img class="punto-imagen" src="' + punto.imagen + '" alt="' + punto.titulo + '"' + medidas + ' loading="lazy">';
        }

        if (punto.descripcion) {
            html += '<p class="punto-descripcion">' + punto.descripcion + '</p>';
        }

        html += '</div></div>';
        return html;
    }

    function renderizar(data) {
        // Las tarjetas y la línea toman el color del circuito (variables CSS).
        const meta = (MapaSonoro.circuitos || []).find(function (c) { return c.id === circuitoActual; });
        const estilo = meta && meta.color
            ? ' style="--c-principal:' + meta.color + ';--c-claro:' + meta.color_claro + ';--c-texto:' + meta.color_texto + '"'
            : '';
        let html = '<div class="circuito-detalle"' + estilo + '>';

        // El título ya se muestra en la barra sticky del panel (arriba de
        // todo); acá solo va la descripción, si hay.
        if (data.descripcion) {
            html += '<div class="circuito-header">';
            html += '<p class="circuito-descripcion">' + data.descripcion + '</p>';
            html += '</div>';
        }

        html += '<div class="circuito-puntos">';
        data.puntos.forEach(function (punto, i) {
            html += renderizarPuntoCircuito(punto, i + 1);
        });
        html += '</div></div>';

        panelApi.panelContenido.innerHTML = html;

        panelApi.panelContenido.querySelectorAll('audio[data-punto-id]').forEach(function (audioEl) {
            panelApi.registrarAudio(audioEl);
            audioEl.addEventListener('play', function () {
                seleccionarPunto(Number(audioEl.dataset.puntoId), false);
            });
            audioEl.addEventListener('ended', function () {
                avanzarAlSiguiente(Number(audioEl.dataset.puntoId));
            });
        });

        panelApi.panelContenido.querySelectorAll('.punto-video[data-punto-id]').forEach(function (videoEl) {
            const puntoId = Number(videoEl.dataset.puntoId);
            const punto   = data.puntos.find(function (p) { return p.id === puntoId; });
            if (!punto) return;

            panelApi.activarVideo(videoEl, punto.video, function () {
                avanzarAlSiguiente(puntoId);
            });

            const boton = videoEl.querySelector('.video-play');
            if (boton) {
                boton.addEventListener('click', function () {
                    seleccionarPunto(puntoId, false);
                });
            }
        });

        panelApi.panelContenido.querySelectorAll('.punto-circuito-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                // Los controles del audio y del video ya tienen su propio
                // manejo; sin esto, el click burbujea hasta acá y, por
                // ejemplo, pausar un audio lo volvería a arrancar.
                if (e.target.closest('audio, .punto-video')) return;
                seleccionarPunto(Number(item.dataset.puntoId));
            });
        });

        dibujarLineaCircuito();
    }

    // La línea va del centro del primer punto al centro del último. Su alto
    // depende del contenido real de cada punto (audio/imagen/descripción
    // varían), así que no hay forma de calcularlo en CSS puro — se mide acá.
    // El `left` NO se mide (queda fijo en CSS, ver .circuito-linea): medirlo
    // con getBoundingClientRect() quedaba sujeto a redondeo de subpíxeles
    // y no coincidía siempre con el centro real del bullet.
    function dibujarLineaCircuito() {
        const bullets = panelApi.panelContenido.querySelectorAll('.punto-circuito-numero');
        const contenedor = panelApi.panelContenido.querySelector('.circuito-puntos');
        if (!contenedor || bullets.length < 2) return;

        let linea = contenedor.querySelector('.circuito-linea');
        if (!linea) {
            linea = document.createElement('div');
            linea.className = 'circuito-linea';
            contenedor.insertBefore(linea, contenedor.firstChild);
        }

        const contenedorRect = contenedor.getBoundingClientRect();
        const primero = bullets[0].getBoundingClientRect();
        const ultimo = bullets[bullets.length - 1].getBoundingClientRect();

        const y1 = (primero.top + primero.height / 2) - contenedorRect.top;
        const y2 = (ultimo.top + ultimo.height / 2) - contenedorRect.top;

        linea.style.top = y1 + 'px';
        linea.style.height = (y2 - y1) + 'px';
    }

    function scrollAlPunto(puntoId) {
        const item = panelApi.panelContenido.querySelector('.punto-circuito-item[data-punto-id="' + puntoId + '"]');
        if (item) item.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // `reproducir` va en false cuando el usuario ya arrancó un medio a mano
    // (tocó play en el audio o en el video): ahí solo queremos el destacado
    // y el centrado, no volver a decidir qué se reproduce.
    function seleccionarPunto(puntoId, reproducir) {
        const data = cache[circuitoActual];
        if (!data) return;

        const punto = data.puntos.find(function (p) { return p.id === puntoId; });
        if (!punto) return;

        panelApi.panelContenido.querySelectorAll('.punto-circuito-item').forEach(function (el) {
            el.classList.toggle('activo', Number(el.dataset.puntoId) === puntoId);
        });

        scrollAlPunto(puntoId);

        panelApi.seleccionarMarcador(puntoId);

        if (punto.lat && punto.lng) {
            panelApi.centrarConOffset({ lat: punto.lat, lng: punto.lng });
        }

        if (reproducir !== false) {
            panelApi.reproducirMedio(
                panelApi.panelContenido.querySelector('audio[data-punto-id="' + puntoId + '"]'),
                panelApi.panelContenido.querySelector('.punto-video[data-punto-id="' + puntoId + '"]')
            );
        }
    }

    // Cuando termina el audio de un punto, pasa al siguiente del circuito (en
    // el orden del backend) que tenga algo para reproducir: los que no tienen
    // audio ni video se saltean. Si no queda ninguno, se frena ahí. Solo
    // avanza si el punto que terminó sigue siendo el activo — si el usuario
    // ya fue a otro, no le pisamos la navegación.
    function avanzarAlSiguiente(puntoId) {
        const data = cache[circuitoActual];
        if (!data) return;

        const activo = panelApi.panelContenido.querySelector('.punto-circuito-item.activo');
        if (!activo || Number(activo.dataset.puntoId) !== puntoId) return;

        const indice = data.puntos.findIndex(function (p) { return p.id === puntoId; });
        if (indice === -1) return;

        const siguiente = data.puntos.slice(indice + 1).find(function (p) {
            return p.audio || (p.video && (p.video.archivo || p.video.embed));
        });
        if (siguiente) seleccionarPunto(siguiente.id);
    }

    // Avisa qué circuito está abierto (o null si se cerró): el nav lo usa para
    // marcar su link.
    function avisarCircuito(id) {
        document.dispatchEvent(new CustomEvent('mapa:circuito', { detail: { id: id } }));
    }

    // `puntoSeleccionadoId` es opcional: el nav abre el circuito sin elegir
    // ningún punto.
    function abrir(circuitoId, puntoSeleccionadoId) {
        // ¿Ya está abierto este mismo circuito? Solo navegar, sin recargar.
        const yaAbierto = circuitoActual === circuitoId && cache[circuitoId];
        circuitoActual = circuitoId;
        avisarCircuito(circuitoId);

        // Sin punto elegido (viene del nav): el mapa se acomoda para que se vea
        // el circuito entero, no solo detrás del panel.
        if (!puntoSeleccionadoId) {
            panelApi.encuadrarPuntos(MapaSonoro.puntos.filter(function (p) {
                return p.circuito_id === circuitoId && p.lat && p.lng;
            }));
        }

        if (yaAbierto) {
            if (puntoSeleccionadoId) {
                seleccionarPunto(puntoSeleccionadoId);
            } else {
                // Viene del nav: se ve el circuito desde el principio.
                panelApi.panelContenido.scrollTop = 0;
            }
            return;
        }

        // Circuito nuevo: se corta lo que sonaba del anterior (si el usuario
        // viene del nav no hay un punto nuevo que arranque y lo reemplace) y
        // no queda ningún punto seleccionado hasta que se elija uno.
        panelApi.detenerMedios();
        panelApi.seleccionarMarcador(null);

        // No depende de la data del circuito (todavía no llegó): usa el
        // manifest liviano, que ya tiene circuito_id por punto.
        panelApi.marcarMismoCircuito(circuitoId);

        // El panel cerrado entra con una animación de 0.3s. Si el scroll se
        // calcula durante esa animación, mide sobre un panel más angosto
        // (el texto envuelve distinto) y queda desfasado.
        const estabaCerrado = !panelApi.panel.classList.contains('activo');

        if (panelApi.panelTituloSticky) {
            panelApi.panelTituloSticky.textContent = '';
            panelApi.panelTituloSticky.style.removeProperty('--c-texto');
        }
        panelApi.panelContenido.innerHTML = '<div class="panel-cargando">Cargando…</div>';
        // Un circuito nuevo siempre arranca desde arriba (sin esto queda el
        // scroll del circuito anterior).
        panelApi.panelContenido.scrollTop = 0;
        panelApi.panel.classList.add('activo');

        const alTerminar = function (data) {
            // El usuario pudo haber navegado a otro punto/circuito mientras
            // esta respuesta estaba en vuelo: no pisar lo que se ve ahora.
            if (circuitoActual !== circuitoId) return;

            cache[circuitoId] = data;
            if (panelApi.panelTituloSticky) {
                panelApi.panelTituloSticky.innerHTML = '<span class="panel-eyebrow">Circuito</span>' + data.titulo;
                // El título lleva el color del circuito (versión legible sobre blanco).
                const meta = (MapaSonoro.circuitos || []).find(function (c) { return c.id === circuitoId; });
                if (meta && meta.color_texto) panelApi.panelTituloSticky.style.setProperty('--c-texto', meta.color_texto);
            }
            renderizar(data);
            if (!puntoSeleccionadoId) panelApi.panelContenido.scrollTop = 0;
            seleccionarPunto(puntoSeleccionadoId);

            if (estabaCerrado) {
                // Igual que el scroll: si se mide mientras el panel todavía
                // está animando su ancho (0 → 450px), la línea sale mal
                // posicionada. Se recalcula recién cuando termina.
                panelApi.panel.addEventListener('transitionend', function () {
                    if (circuitoActual !== circuitoId) return;
                    scrollAlPunto(puntoSeleccionadoId);
                    dibujarLineaCircuito();
                }, { once: true });
            }
        };

        if (cache[circuitoId]) {
            alTerminar(cache[circuitoId]);
        } else {
            fetch(MapaSonoro.restUrlCircuito + circuitoId)
                .then(function (res) { return res.json(); })
                .then(alTerminar)
                .catch(function () {
                    if (circuitoActual === circuitoId) panelApi.panelContenido.innerHTML = '';
                });
        }
    }

    function salir() {
        circuitoActual = null;
        avisarCircuito(null);
        panelApi.restaurarLimites();
        panelApi.limpiarMismoCircuito();
    }

    window.MapaCircuitos = {
        abrir: abrir,
        salir: salir
    };
});
