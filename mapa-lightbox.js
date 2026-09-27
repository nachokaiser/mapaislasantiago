document.addEventListener('DOMContentLoaded', function () {

    const lightbox = document.getElementById('lightbox');
    if (!lightbox) return;

    const imagen    = lightbox.querySelector('.lightbox-imagen');
    const btnCerrar = lightbox.querySelector('.lightbox-cerrar');

    // Elemento que abrió el lightbox, para devolverle el foco al cerrar.
    let disparador = null;

    function abrir(img) {
        disparador = img;
        imagen.src = img.src;
        imagen.alt = img.alt || '';
        imagen.classList.remove('ampliada');
        lightbox.hidden = false;
        btnCerrar.focus();
    }

    function cerrar() {
        if (lightbox.hidden) return;
        lightbox.hidden = true;
        imagen.src = '';
        if (disparador) {
            disparador.focus();
            disparador = null;
        }
    }

    // Delegación en document: las fotos se insertan dinámicamente (punto
    // suelto y feed de circuito), así que no hay que cablear cada una.
    document.addEventListener('click', function (e) {
        const img = e.target.closest('.punto-imagen');
        if (img) {
            abrir(img);
            return;
        }
        // Click en el fondo (no en la imagen ampliada ni en el botón) cierra.
        if (!lightbox.hidden && e.target === lightbox) {
            cerrar();
        }
    });

    btnCerrar.addEventListener('click', cerrar);

    // Zoom simple: un click alterna entre "ajustada a pantalla" y tamaño
    // real (con scroll para recorrer la imagen si no entra completa).
    imagen.addEventListener('click', function (e) {
        e.stopPropagation();
        imagen.classList.toggle('ampliada');
    });

    lightbox.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            // Sin esto, el Escape sigue de largo y también cierra el panel
            // de fondo (que tiene su propio listener de Escape).
            e.stopPropagation();
            cerrar();
            return;
        }

        // Único elemento con foco propio adentro es el botón de cerrar —
        // el trap es simplemente no dejar que Tab se escape del lightbox.
        if (e.key === 'Tab') {
            e.preventDefault();
            btnCerrar.focus();
        }
    });
});
