document.addEventListener('DOMContentLoaded', function () {

    const panelApi = window.MapaSonoroPanel;
    if (!panelApi) return;

    const cache = {};
    let circuitoActual = null;

    function renderizarPuntoCircuito(punto, numero) {
        let html = '<div class="punto-circuito-item" data-punto-id="' + punto.id + '">';
        html += '<div class="punto-circuito-numero">' + numero + '</div>';
        html += '<div class="punto-circuito-contenido">';
        html += '<h3 class="punto-circuito-titulo">' + punto.titulo + '</h3>';

        if (punto.audio) {
            html += '<audio class="punto-audio" controls preload="none" src="' + punto.audio + '" data-punto-id="' + punto.id + '"></audio>';
        }

        html += panelApi.crearBloqueVideo(punto.video, punto.titulo, punto.id);

        if (punto.imagen) {
            html += '<img class="punto-imagen" src="' + punto.imagen + '" alt="' + punto.titulo + '" loading="lazy">';
        }

        if (punto.descripcion) {
            html += '<p class="punto-descripcion">' + punto.descripcion + '</p>';
        }

        html += '</div></div>';
        return html;
    }

    function renderizar(data) {
        let html = '<div class="circuito-detalle">';

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
        });

        panelApi.panelContenido.querySelectorAll('.punto-video[data-punto-id]').forEach(function (videoEl) {
            const puntoId = Number(videoEl.dataset.puntoId);
            const punto   = data.puntos.find(function (p) { return p.id === puntoId; });
            if (!punto) return;

            panelApi.activarVideo(videoEl, punto.video);

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

        const itemActivo = panelApi.panelContenido.querySelector('.punto-circuito-item[data-punto-id="' + puntoId + '"]');
        if (itemActivo) {
            itemActivo.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

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

    function abrir(circuitoId, puntoSeleccionadoId) {
        // ¿Ya está abierto este mismo circuito? Solo navegar, sin recargar.
        const yaAbierto = circuitoActual === circuitoId && cache[circuitoId];
        circuitoActual = circuitoId;

        if (yaAbierto) {
            seleccionarPunto(puntoSeleccionadoId);
            return;
        }

        if (panelApi.panelTituloSticky) panelApi.panelTituloSticky.textContent = '';
        panelApi.panelContenido.innerHTML = '<div class="panel-cargando">Cargando…</div>';
        panelApi.panel.classList.add('activo');

        const alTerminar = function (data) {
            // El usuario pudo haber navegado a otro punto/circuito mientras
            // esta respuesta estaba en vuelo: no pisar lo que se ve ahora.
            if (circuitoActual !== circuitoId) return;

            cache[circuitoId] = data;
            if (panelApi.panelTituloSticky) panelApi.panelTituloSticky.textContent = data.titulo;
            renderizar(data);
            seleccionarPunto(puntoSeleccionadoId);
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
    }

    window.MapaCircuitos = {
        abrir: abrir,
        salir: salir
    };
});
