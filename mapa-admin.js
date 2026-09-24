document.addEventListener('DOMContentLoaded', function () {
    const contenedor = document.getElementById('mapa-admin-contenedor');
    if (!contenedor) return;

    const map = L.map('mapa-admin-contenedor', {
        center: [MapaAdmin.centro.lat, MapaAdmin.centro.lng],
        zoom: MapaAdmin.zoom,
        minZoom: 12,
        maxZoom: 18,
    });

    // Mismos tiles que el mapa público: CARTO pasó a pedir API key.
    L.tileLayer('https://{s}.tile.openstreetmap.de/tiles/osmde/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxNativeZoom: 18,
        maxZoom: 18,
    }).addTo(map);

    const iconoOtro = L.divIcon({
        html: '<div class="marcador-admin marcador-otro"></div>',
        className: '',
        iconSize: [16, 16],
        iconAnchor: [8, 8],
    });

    const iconoActual = L.divIcon({
        html: '<div class="marcador-admin marcador-actual"></div>',
        className: '',
        iconSize: [22, 22],
        iconAnchor: [11, 11],
    });

    // Otros puntos como referencia
    MapaAdmin.puntos.forEach(function (p) {
        L.marker([p.lat, p.lng], { icon: iconoOtro })
            .bindTooltip(p.titulo, { direction: 'top', offset: [0, -8] })
            .addTo(map);
    });

    function actualizarCampos(lat, lng) {
        const inputLat = document.querySelector('.acf-field[data-name="latitud"] input');
        const inputLng = document.querySelector('.acf-field[data-name="longitud"] input');
        if (inputLat) inputLat.value = lat.toFixed(6);
        if (inputLng) inputLng.value = lng.toFixed(6);
    }

    let marcadorActual = null;

    function colocarMarcador(latlng) {
        if (marcadorActual) {
            marcadorActual.setLatLng(latlng);
        } else {
            marcadorActual = L.marker(latlng, {
                icon: iconoActual,
                draggable: true,
            }).addTo(map);

            marcadorActual.on('dragend', function () {
                const pos = marcadorActual.getLatLng();
                actualizarCampos(pos.lat, pos.lng);
            });
        }
        actualizarCampos(latlng.lat, latlng.lng);
    }

    // Si ya tiene coordenadas guardadas, mostrar el marcador
    if (MapaAdmin.actual.lat && MapaAdmin.actual.lng) {
        colocarMarcador(L.latLng(MapaAdmin.actual.lat, MapaAdmin.actual.lng));
        map.setView([MapaAdmin.actual.lat, MapaAdmin.actual.lng], MapaAdmin.zoom);
    }

    map.on('click', function (e) {
        colocarMarcador(e.latlng);
    });
});
