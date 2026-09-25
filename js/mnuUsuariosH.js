const TAB_CONFIG = {
    'div_informacion_usr': {
        divs: ['div_informacion_usr'],
        rows: ['tr_informacion_usr']
    },
    'div_permisos_desp': {
        divs: ['div_permisos_desp'],
        rows: ['tr_permisos_usr']
    },
    'div_backup': {
        divs: ['div_backup'],
        rows: ['tr_backup']
    },
    'div_recorrido': {
        divs: ['div_recorrido'],
        rows: []
    },
    'div_informacion_ext': {
        divs: ['div_informacion_ext'],
        rows: []
    },
    'div_historico_ext': {
        divs: ['div_historico_ext'],
        rows: []
    }
};

const TAB_CONFIG_EXTERNAL = {
    'div_informacion_ext': {
        divs: ['div_informacion_ext'],
        rows: []
    },
    'div_historico_ext': {
        divs: ['div_historico_ext'],
        rows: []
    }
};


function mostrar_div_usr(tabId) {
    // Validar que la pestaña existe
    if (!TAB_CONFIG[tabId]) {
        console.error(`Tab "${tabId}" no encontrada en configuración`);
        return;
    }

    // Ocultar todas las pestañas
    Object.keys(TAB_CONFIG).forEach(key => {
        const config = TAB_CONFIG[key];

        // Ocultar divs
        config.divs.forEach(divId => {
            const element = document.getElementById(divId);
            if (element) {
                element.style.display = 'none';
            }
        });

        // Ocultar filas
        config.rows.forEach(rowId => {
            const element = document.getElementById(rowId);
            if (element) {
                element.style.display = 'none';
            }
        });
    });

    // Mostrar la pestaña seleccionada
    const selectedConfig = TAB_CONFIG[tabId];
    selectedConfig.divs.forEach(divId => {
        const element = document.getElementById(divId);
        if (element) {
            element.style.display = '';
        }
    });

    selectedConfig.rows.forEach(rowId => {
        const element = document.getElementById(rowId);
        if (element) {
            element.style.display = '';
        }
    });
}

function mostrar_div_ext_usr(tabId) {
// Validar que la pestaña existe
    if (!TAB_CONFIG_EXTERNAL[tabId]) {
        console.error(`Tab "${tabId}" no encontrada en configuración`);
        return;
    }

    // Ocultar todas las pestañas
    Object.keys(TAB_CONFIG_EXTERNAL).forEach(key => {
        const config = TAB_CONFIG_EXTERNAL[key];

        // Ocultar divs
        config.divs.forEach(divId => {
            const element = document.getElementById(divId);
            if (element) {
                element.style.display = 'none';
            }
        });

        // Ocultar filas
        config.rows.forEach(rowId => {
            const element = document.getElementById(rowId);
            if (element) {
                element.style.display = 'none';
            }
        });
    });

    // Mostrar la pestaña seleccionada
    const selectedConfig = TAB_CONFIG_EXTERNAL[tabId];
    selectedConfig.divs.forEach(divId => {
        const element = document.getElementById(divId);
        if (element) {
            element.style.display = '';
        }
    });

    selectedConfig.rows.forEach(rowId => {
        const element = document.getElementById(rowId);
        if (element) {
            element.style.display = '';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            mostrar_div_usr(tabId);

            // Opcional: Marcar botón como activo
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button-ext');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            mostrar_div_ext_usr(tabId);

            // Opcional: Marcar botón como activo
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
});