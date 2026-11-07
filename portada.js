(function () {
    const portada = document.getElementById('portada');
    if (!portada) return;

    // Ya la vio en esta sesión: el CSS la oculta. Sin sacarla acá, el código de
    // abajo igual dejaría el mapa bloqueado (inert) esperando un clic en un
    // botón que nunca se ve.
    if (document.documentElement.classList.contains('sin-portada')) {
        portada.remove();
        return;
    }

    const boton = portada.querySelector('.portada__comenzar');

    // Mientras la portada está arriba, el mapa de atrás no recibe foco ni clicks.
    const detras = ['mapa-header', 'mapa-wrapper', 'mapa-footer']
        .map(function (id) { return document.getElementById(id); })
        .filter(Boolean);

    detras.forEach(function (el) { el.inert = true; });

    // Si las imágenes todavía no cargaron, la animación arrancaría con las
    // piezas apareciendo de golpe: se espera a que estén listas (con tope por
    // si alguna falla o la conexión es muy lenta).
    // Solo las de la composición que se ve: la otra tiene display:none y su
    // carga es lazy, no se descarga (y esperarla trabaría la animación). Las
    // piezas que quedan fuera de pantalla tampoco se esperan.
    const imagenes = Array.from(portada.querySelectorAll('img')).filter(function (img) {
        const r = img.getBoundingClientRect();
        return r.width > 0 && r.right > 0 && r.bottom > 0 &&
            r.left < window.innerWidth && r.top < window.innerHeight;
    });
    const listas = Promise.all(imagenes.map(function (img) {
        return img.decode ? img.decode().catch(function () {}) : Promise.resolve();
    }));
    const tope = new Promise(function (resolve) { setTimeout(resolve, 4000); });

    Promise.race([listas, tope]).then(function () {
        portada.classList.add('is-playing');
    });

    // El foco va al botón recién cuando aparece, no antes (invisible).
    boton.addEventListener('animationend', function () {
        boton.focus({ preventScroll: true });
    }, { once: true });

    // Sin animación (reducir movimiento) no hay animationend: foco directo.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        boton.focus({ preventScroll: true });
    }

    boton.addEventListener('click', function () {
        try { sessionStorage.setItem('portadaVista', '1'); } catch (e) {}

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
