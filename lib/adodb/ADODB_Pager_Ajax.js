

function paginador_reload_obtener_dato(objeto) {
    switch (document.getElementById(objeto).type.toLowerCase()) {
        case 'radio':
            var i;
            var elementos = document.getElementsByName(objeto);
            for (i=0 ; i<elementos.length ; i++) {
                if (elementos[i].checked)
                    return elementos[i].value;
            }
            break;
        case 'span':
        case 'div':
            return document.getElementById(objeto).innerHTML;
            break;
        default:
            return document.getElementById(objeto).value;
            break;
    }
}