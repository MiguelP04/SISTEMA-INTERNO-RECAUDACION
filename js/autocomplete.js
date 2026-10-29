document.addEventListener('DOMContentLoaded', function() {
    // --- Referencias para elementos de AGENTE ---
    const tipoAgenteSelect = document.getElementById('tipo_agente_filter');
    const rifAgenteInput = document.getElementById('rif_agente_numero');
    const rifAgenteHidden = document.getElementById('rif_agente');
    const nombreAgenteDisplay = document.getElementById('nombre_agente_display');
    const suggestionsAgenteDiv = document.getElementById('suggestions_agente');

    // --- Referencias para elementos de CONSIGNATARIO ---
    const tipoConsignatarioSelect = document.getElementById('tipo_consignatario_filter');
    const rifConsignatarioInput = document.getElementById('rif_consignatario_numero');
    const rifConsignatarioHidden = document.getElementById('rif_consignatario');
    const nombreConsignatarioDisplay = document.getElementById('nombre_consignatario_display');
    const suggestionsConsignatarioDiv = document.getElementById('suggestions_consignatario');

    // --- Carga de datos ---
    const agentsData = window.ALL_AGENTS_DATA || [];
    const consignatariosData = window.ALL_CONSIGNATARIOS_DATA || [];

    let debounceTimer;

    // --- Funciones de Ayuda ---

    /**
     * Mapea la letra de un RIF al ID de tipo correspondiente del menú desplegable.
     * @param {string} letter - La primera letra del RIF (V, E, J, G).
     * @returns {string} El ID correspondiente ('1', '2', '3', '4').
     */
    function mapRifLetterToTypeId(letter) {
        switch (letter.toUpperCase()) {
            case 'G': return '1';
            case 'J': return '2';
            case 'V': return '3';
            case 'E': return '4';
            default: return '';
        }
    }

    /**
     * Mapea un ID de tipo del menú desplegable a su letra de RIF correspondiente.
     * @param {string} typeId - El ID del elemento <select>.
     * @returns {string} La letra de RIF correspondiente.
     */
    function mapTypeIdToRifLetter(typeId) {
        switch (String(typeId)) {
            case '1': return 'G';
            case '2': return 'J';
            case '3': return 'V';
            case '4': return 'E';
            default: return '';
        }
    }

    /**
     * Actualiza el campo oculto del RIF completo.
     * @param {HTMLInputElement} hiddenEl - El campo de RIF oculto.
     * @param {string} letter - La letra del RIF.
     * @param {string} number - El número del RIF.
     */
    function updateHiddenRif(hiddenEl, letter, number) {
        hiddenEl.value = (letter + number.trim()).toUpperCase();
    }

    /**
     * Función principal para filtrar datos y mostrar sugerencias.
     * @param {object} config - Objeto de configuración.
     * @param {HTMLInputElement} config.inputEl - El campo de entrada para el número de RIF.
     * @param {HTMLSelectElement} config.typeSelectEl - El campo de selección para el tipo de RIF.
     * @param {Array} config.data - El array de datos a buscar.
     * @param {HTMLDivElement} config.suggestionsEl - El contenedor para las sugerencias.
     * @param {HTMLInputElement} config.hiddenEl - El campo oculto para el RIF completo.
     * @param {HTMLInputElement} config.nameDisplayEl - El campo de solo lectura para el nombre.
     * @param {string} config.rifKey - La clave del RIF en el objeto de datos.
     * @param {string} config.nameKey - La clave del nombre en el objeto de datos.
     */
    function filterAndDisplay(config) {
        const searchTerm = config.inputEl.value.trim().toLowerCase();
        const selectedType = config.typeSelectEl.value;
        const selectedLetter = mapTypeIdToRifLetter(selectedType).toLowerCase();

        // Limpiar sugerencias anteriores
        config.suggestionsEl.innerHTML = '';
        config.suggestionsEl.style.display = 'none';
        
        // Actualizar RIF oculto mientras se escribe
        updateHiddenRif(config.hiddenEl, selectedLetter, searchTerm);

        if (searchTerm.length < 1 && !selectedLetter) {
            config.nameDisplayEl.value = "";
            return;
        }

        const filteredData = config.data.filter(item => {
            const itemRif = String(item[config.rifKey] || '').toLowerCase();
            const itemName = String(item[config.nameKey] || '').toLowerCase();

            // 1. Filtrar por tipo (letra del RIF) si se selecciona uno
            const typeMatch = !selectedLetter || itemRif.startsWith(selectedLetter);

            // 2. Filtrar por término de búsqueda (en RIF o nombre)
            const searchMatch = searchTerm === '' || itemRif.includes(searchTerm) || itemName.includes(searchTerm);
            
            return typeMatch && searchMatch;
        });

        if (filteredData.length > 0) {
            filteredData.forEach(item => {
                const suggestionItem = document.createElement('div');
                suggestionItem.className = 'suggestion-item';
                suggestionItem.textContent = `${item[config.rifKey]} - ${item[config.nameKey]}`;

                suggestionItem.addEventListener('click', () => {
                    const selectedRif = item[config.rifKey];
                    const rifLetter = selectedRif.charAt(0).toUpperCase();
                    const rifNumber = selectedRif.substring(1);

                    // Rellenar campos con la selección
                    config.inputEl.value = rifNumber;
                    config.nameDisplayEl.value = item[config.nameKey];
                    config.hiddenEl.value = selectedRif;
                    config.typeSelectEl.value = mapRifLetterToTypeId(rifLetter);

                    // Ocultar sugerencias
                    config.suggestionsEl.innerHTML = '';
                    config.suggestionsEl.style.display = 'none';
                });

                config.suggestionsEl.appendChild(suggestionItem);
            });
            config.suggestionsEl.style.display = 'block';
        } else {
            config.nameDisplayEl.value = "";
            config.nameDisplayEl.placeholder = "No se encontraron coincidencias";
        }
    }

    /**
     * Configura los event listeners para una sección (Agente o Consignatario).
     * @param {object} config - El objeto de configuración para la sección.
     */
    function setupEventListeners(config) {
        const runFilter = () => filterAndDisplay(config);

        config.inputEl.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runFilter, 300);
        });

        config.typeSelectEl.addEventListener('change', runFilter);
        
        config.inputEl.addEventListener('keyup', (event) => {
            if (event.key === 'Backspace' || event.key === 'Delete') {
                if (config.inputEl.value.trim() === "") {
                    config.nameDisplayEl.value = "";
                    config.nameDisplayEl.placeholder = config.nameKey.includes('agente') ? "Nombre del Agente" : "Nombre del Consignatario";
                }
            }
        });
    }

    // --- Inicialización ---

    const agentConfig = {
        inputEl: rifAgenteInput,
        typeSelectEl: tipoAgenteSelect,
        data: agentsData,
        suggestionsEl: suggestionsAgenteDiv,
        hiddenEl: rifAgenteHidden,
        nameDisplayEl: nombreAgenteDisplay,
        rifKey: 'rif_agentes',
        nameKey: 'nombre_agente'
    };

    const consigneeConfig = {
        inputEl: rifConsignatarioInput,
        typeSelectEl: tipoConsignatarioSelect,
        data: consignatariosData,
        suggestionsEl: suggestionsConsignatarioDiv,
        hiddenEl: rifConsignatarioHidden,
        nameDisplayEl: nombreConsignatarioDisplay,
        rifKey: 'rif_consignatario',
        nameKey: 'nombre_consignatario'
    };

    if (rifAgenteInput) setupEventListeners(agentConfig);
    if (rifConsignatarioInput) setupEventListeners(consigneeConfig);

    // Listener global para cerrar las sugerencias al hacer clic fuera
    document.addEventListener('click', (event) => {
        const isClickInsideAgent = suggestionsAgenteDiv.contains(event.target) || rifAgenteInput.contains(event.target);
        const isClickInsideConsignee = suggestionsConsignatarioDiv.contains(event.target) || rifConsignatarioInput.contains(event.target);
        
        if (!isClickInsideAgent) {
            suggestionsAgenteDiv.style.display = 'none';
        }
        if (!isClickInsideConsignee) {
            suggestionsConsignatarioDiv.style.display = 'none';
        }
    });

    // Inicializar valores de los campos ocultos
    updateHiddenRif(rifAgenteHidden, mapTypeIdToRifLetter(tipoAgenteSelect.value), rifAgenteInput.value);
    updateHiddenRif(rifConsignatarioHidden, mapTypeIdToRifLetter(tipoConsignatarioSelect.value), rifConsignatarioInput.value);
});