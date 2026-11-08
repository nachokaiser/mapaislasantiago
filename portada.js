(function () {
    const portada = document.getElementById('portada');
    if (!portada) return;

    const boton = portada.querySelector('.portada__comenzar');

    // Mientras la portada está arriba, el mapa de atrás no recibe foco ni clicks.
    const detras = ['mapa-header', 'mapa-wrapper', 'mapa-footer']
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);

    detras.forEach(function (el) { el.inert = true; });

    // La animación arranca apenas están listos el papel y la isla (lo primero
    // que se ve): esperar todas las imágenes la hacía arrancar tarde. Las demás
    // piezas entran más adelante en la línea de tiempo y cada una espera, en
    // pausa, a tener su imagen decodificada (.is-lista, ver el CSS), así nunca
    // aparecen de golpe a medio cargar.
    // Solo las de la composición que se ve: la otra tiene display:none y su
    // carga es lazy, no se descarga (y esperarla trabaría la animación). Las
    // piezas que quedan fuera de pantalla tampoco se esperan.
    const imagenes = Array.from(portada.querySelectorAll('img')).filter(function (img) {
        const r = img.getBoundingClientRect();
        return r.width > 0 && r.right > 0 && r.bottom > 0 &&
            r.left < window.innerWidth && r.top < window.innerHeight;
    });

    // Ya se sabe que se van a mostrar: que no esperen a ser "lazy".
    imagenes.forEach(function (img) { img.loading = 'eager'; });

    function decodificar(img) {
        return img.decode ? img.decode().catch(function () {}) : Promise.resolve();
    }

    const criticas = imagenes.filter(function (img) {
        return img.classList.contains('portada__papel') ||
            (img.closest('.portada__pieza--isla-verde') !== null);
    });
    const tope = new Promise(function (resolve) { setTimeout(resolve, 1500); });

    Promise.race([Promise.all(criticas.map(decodificar)), tope]).then(function () {
        portada.classList.add('is-playing');
    });

    imagenes.forEach(function (img) {
        const pieza = img.closest('.portada__pieza');
        if (!pieza) return;
        decodificar(img).then(function () { pieza.classList.add('is-lista'); });
    });

    // Red de seguridad: si alguna imagen se cuelga, las piezas igual entran.
    setTimeout(function () {
        portada.querySelectorAll('.portada__pieza').forEach(function (p) { p.classList.add('is-lista'); });
    }, 4000);

    // El foco va al botón recién cuando aparece, no antes (invisible).
    boton.addEventListener('animationend', function () {
        boton.focus({ preventScroll: true });
    }, { once: true });

    // Sin animación (reducir movimiento) no hay animationend: foco directo.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        boton.focus({ preventScroll: true });
    }

    boton.addEventListener('click', function () {
        detras.forEach(function (el) { el.inert = false; });
        portada.classList.add('is-saliendo');

        // Leaflet midió su contenedor mientras estaba tapado: por las dudas
        // se le avisa que recalcule.
        window.dispatchEvent(new Event('resize'));

        portada.addEventListener('transitionend', function () {
            portada.remove();
        }, { once: true });
    });
})();
