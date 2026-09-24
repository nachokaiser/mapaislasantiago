document.addEventListener("DOMContentLoaded", function () {

    const esMobile = window.innerWidth <= 720;

    const mapConfig = esMobile ? {
        center:    [-34.8345, -57.903],
        zoom:      13,
        minZoom:   12,
        maxBounds: [ [-34.808, -57.965], [-34.862, -57.845] ]
    } : {
        center:    [-34.839, -57.899],
        zoom:      14.5,
        minZoom:   13,
        maxBounds: [ [-34.818, -57.955], [-34.852, -57.858] ]
    };

    const map = L.map('mapa-contenedor', {
        center:              mapConfig.center,
        zoom:                mapConfig.zoom,
        minZoom:             mapConfig.minZoom,
        maxZoom:             18,
        maxBounds:           mapConfig.maxBounds,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxNativeZoom: 18,
        maxZoom: 18
    }).addTo(map);


    // Recorrido
    const recorridos = {
        recorrido_principal: {
            color: 'rgba(253, 195, 22, 0.9)',
            coordenadas: [
                [-34.8358481, -57.8819484],
                [-34.8335409, -57.8810016],
                [-34.8326909, -57.8838421],
                [-34.835071,  -57.8852328],
                [-34.8344881, -57.8878957],
                [-34.8322537, -57.8860612],
                [-34.8310637, -57.882481 ],
                [-34.8301408, -57.8826586],
                [-34.830612,  -57.8842151],
                [-34.8340581, -57.8890764],
                [-34.8353795, -57.8887607],
                [-34.8359755, -57.8873402],
                [-34.8380741, -57.8871193],
                [-34.83981,   -57.8875927],
                [-34.8408981, -57.8904022]
            ]
        }
    };

    const lineasActivas = {};

    Object.keys(recorridos).forEach(function(id) {
        const r = recorridos[id];
        const linea = L.polyline(r.coordenadas, {
            color: r.color,
            weight: 4,
            opacity: 0.8
        });
        lineasActivas[id] = { linea: linea, visible: false };
    });

    // Toggle único para todos los recorridos
    const btnRecorridos = document.getElementById('toggle-recorridos');
    let recorridosVisibles = false;

    if (btnRecorridos) {
        btnRecorridos.addEventListener('click', function () {
            recorridosVisibles = !recorridosVisibles;
            Object.values(lineasActivas).forEach(function (item) {
                if (recorridosVisibles) {
                    item.linea.addTo(map);
                } else {
                    map.removeLayer(item.linea);
                }
                item.visible = recorridosVisibles;
            });
            btnRecorridos.classList.toggle('activo', recorridosVisibles);
            btnRecorridos.setAttribute('aria-pressed', recorridosVisibles);
        });
    }

    const panel = document.getElementById('mapa-panel');
    const panelContenido = document.getElementById('panel-contenido');
    const cerrarBtn = document.getElementById('panel-cerrar');

    const panelTituloSticky = document.getElementById('panel-titulo-sticky');

    function cerrarPanel() {
        panel.classList.remove('activo');
        if (panelTituloSticky) panelTituloSticky.textContent = '';
        if (window.audioActual) {
            window.audioActual.pause();
        }
        if (marcadorActivo) {
            marcadorActivo.getElement()
                .querySelector('.marcador-sonoro')
                .classList.remove('selected');
            marcadorActivo = null;
        }
        if (window.MapaCircuitos) {
            window.MapaCircuitos.salir();
        }
    }

    cerrarBtn.addEventListener('click', cerrarPanel);

    function centrarConOffset(latlng) {
        const esDesktop = window.innerWidth > 720;

        if (esDesktop) {
            const punto = map.project(latlng, map.getZoom());
            const mapAncho = document.getElementById('mapa-contenedor').offsetWidth;
            const offsetX = mapAncho * 0.10;
            const puntoCentrado = punto.subtract([offsetX, 0]);
            map.panTo(map.unproject(puntoCentrado, map.getZoom()), {
                animate: true,
                duration: 0.6
            });
        } else {
            const punto = map.project(latlng, map.getZoom());
            const mapAlto = document.getElementById('mapa-contenedor').offsetHeight;
            const offsetY = mapAlto * 0.32;
            const puntoCentrado = punto.subtract([0, -offsetY]);
            map.panTo(map.unproject(puntoCentrado, map.getZoom()), {
                animate: true,
                duration: 0.6
            });
        }
    }

    // Un solo audio sonando a la vez en todo el panel — incluye el feed de
    // un circuito, donde conviven varios <audio> al mismo tiempo.
    function registrarAudio(audioEl) {
        audioEl.addEventListener('play', function () {
            if (window.audioActual && window.audioActual !== audioEl) {
                window.audioActual.pause();
            }
            window.audioActual = audioEl;
        });
    }

    // Detecta YouTube/Vimeo en una URL y arma el src de embed. Para YouTube
    // también devuelve una miniatura real (URL pública y predecible). Para
    // Vimeo no: conseguirla implica una llamada a su API por cada video, y
    // por ahora no vale la pena — queda con el placeholder genérico.
    function parsearVideoEmbed(url) {
        if (!url) return null;

        const youtube = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/);
        if (youtube) {
            return {
                embedSrc:  'https://www.youtube.com/embed/' + youtube[1] + '?autoplay=1',
                thumbnail: 'https://img.youtube.com/vi/' + youtube[1] + '/hqdefault.jpg'
            };
        }

        const vimeo = url.match(/vimeo\.com\/(\d+)/);
        if (vimeo) {
            return {
                embedSrc:  'https://player.vimeo.com/video/' + vimeo[1] + '?autoplay=1',
                thumbnail: null
            };
        }

        return null;
    }

    // Bloque de video con placeholder: nada de <video> ni <iframe> hasta que
    // se toca play. `puntoId` es opcional — solo hace falta cuando conviven
    // varios puntos en un mismo contenedor (el feed de un circuito).
    function crearBloqueVideo(video, alt, puntoId) {
        if (!video || (!video.archivo && !video.embed)) return '';

        const embed     = video.embed ? parsearVideoEmbed(video.embed) : null;
        const thumbnail = embed && embed.thumbnail
            ? ' style="background-image:url(\'' + embed.thumbnail + '\')"'
            : '';
        const atributoPunto = puntoId ? ' data-punto-id="' + puntoId + '"' : '';

        let html = '<div class="punto-video"' + atributoPunto + '>';
        html += '<div class="video-placeholder"' + thumbnail + '>';
        html += '<button type="button" class="video-play" aria-label="Reproducir video de ' + alt + '">';
        html += '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
        html += '</button></div></div>';
        return html;
    }

    // Cablea el click de play de un bloque de video ya insertado en el DOM.
    // El archivo subido tiene prioridad sobre el link embebido si hay ambos.
    function activarVideo(el, video) {
        if (!video) return;

        const boton = el.querySelector('.video-play');
        if (!boton) return;

        boton.addEventListener('click', function () {
            if (window.audioActual) window.audioActual.pause();

            let media = null;

            if (video.archivo) {
                media = document.createElement('video');
                media.controls  = true;
                media.autoplay  = true;
                media.src       = video.archivo;
            } else if (video.embed) {
                const embed = parsearVideoEmbed(video.embed);
                media = document.createElement('iframe');
                media.src = embed ? embed.embedSrc : video.embed;
                media.setAttribute('allow', 'autoplay; fullscreen');
                media.setAttribute('allowfullscreen', '');
            }

            if (media) {
                media.className = 'punto-video-player';
                el.innerHTML = '';
                el.appendChild(media);
            }
        });
    }

    async function abrirPanel(id) {
        window.audioActual = null;
        panelContenido.innerHTML = '<div class="panel-cargando">Cargando…</div>';
        panel.classList.add('activo');

        try {
            const res  = await fetch(MapaSonoro.restUrl + id);
            const data = await res.json();
            renderizarPanel(data);
        } catch (e) {
            panelContenido.innerHTML = '';
        }
    }

    function renderizarPanel(data) {
        if (panelTituloSticky) panelTituloSticky.textContent = data.titulo;

        let html = '<div class="punto-detalle">';

        if (data.audio) {
            html += '<audio id="audio-player" class="punto-audio" controls src="' + data.audio + '"></audio>';
        }

        html += crearBloqueVideo(data.video, data.titulo);

        if (data.imagen) {
            html += '<img class="punto-imagen" src="' + data.imagen + '" alt="' + data.titulo + '">';
        }

        if (data.descripcion) {
            html += '<p class="punto-descripcion">' + data.descripcion + '</p>';
        }

        if (data.categorias && data.categorias.length) {
            html += '<div class="punto-etiquetas">';
            data.categorias.forEach(function (cat) {
                html += '<span class="etiqueta">' + cat + '</span>';
            });
            html += '</div>';
        }

        if (data.url) {
            html += '<button class="btn-compartir">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';
            html += 'Compartir</button>';
        }

        html += '</div>';

        panelContenido.innerHTML = html;

        const audioEl = panelContenido.querySelector('#audio-player');
        if (audioEl) registrarAudio(audioEl);

        const videoEl = panelContenido.querySelector('.punto-video');
        if (videoEl) activarVideo(videoEl, data.video);

        const btnCompartir = panelContenido.querySelector('.btn-compartir');
        if (btnCompartir) {
            btnCompartir.addEventListener('click', function () {
                if (navigator.share) {
                    navigator.share({ title: data.titulo, url: data.url });
                } else {
                    navigator.clipboard.writeText(data.url);
                }
            });
        }
    }

    // Ícono custom
    function crearIcono() {
        return L.divIcon({
            html: '<div class="marcador-sonoro"></div>',
            className: '',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
    }

    let marcadorActivo = null;
    const markersById = {};

    function seleccionarMarcador(puntoId) {
        const marker = markersById[puntoId];

        if (marcadorActivo && marcadorActivo !== marker) {
            const elPrevio = marcadorActivo.getElement();
            if (elPrevio) {
                elPrevio.querySelector('.marcador-sonoro').classList.remove('selected');
            }
        }

        if (marker) {
            const el = marker.getElement();
            if (el) el.querySelector('.marcador-sonoro').classList.add('selected');
            marcadorActivo = marker;
        }
    }

    MapaSonoro.puntos.forEach(function (punto) {
        if (!punto.lat || !punto.lng) return;

        const marker = L.marker([punto.lat, punto.lng], {
            icon: crearIcono()
        }).addTo(map);

        markersById[punto.id] = marker;

        marker.on('click', function () {
            // Punto que pertenece a un circuito: la vista de circuito se
            // encarga de todo (destacado, centrado, cierre/apertura).
            if (punto.circuito_id && window.MapaCircuitos) {
                window.MapaCircuitos.abrir(punto.circuito_id, punto.id);
                return;
            }

            if (window.MapaCircuitos) window.MapaCircuitos.salir();
            seleccionarMarcador(punto.id);
            centrarConOffset({ lat: punto.lat, lng: punto.lng });
            abrirPanel(punto.id);
        });
    });

    map.on('click', function (e) {
        if (window.innerWidth > 720) {
            cerrarPanel();
        }
    });

    // API compartida con mapa-circuitos.js: mapa, panel, marcadores, audio y video.
    window.MapaSonoroPanel = {
        panel:               panel,
        panelContenido:      panelContenido,
        panelTituloSticky:   panelTituloSticky,
        centrarConOffset:    centrarConOffset,
        seleccionarMarcador: seleccionarMarcador,
        registrarAudio:      registrarAudio,
        crearBloqueVideo:    crearBloqueVideo,
        activarVideo:        activarVideo
    };
});