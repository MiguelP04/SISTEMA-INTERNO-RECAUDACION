document.addEventListener('DOMContentLoaded', function() {
    // Mapeo de valores para mostrar (base de datos -> interfaz)
    const dbToDisplayMap = {
        '19': '80',
        '20': '81',
        '21': '86'
    };

    // Mapeo de valores para almacenar (interfaz -> base de datos)
    const displayToDbMap = {
        '80': '19',
        '81': '20',
        '86': '21'
    };

    // Función para inicializar los selects de cod_forma
    function initCodFormaSelects() {
        document.querySelectorAll('.cod-forma-select').forEach(select => {
            const isForDisplay = select.dataset.mapping === 'display';
            const isForStorage = select.dataset.mapping === 'storage';
            const realValueInput = select.closest('form')?.querySelector('input[name="cod_de_forma_real"]');

            // Configurar para mostrar valores (DB -> Display)
            if (isForDisplay) {
                Array.from(select.options).forEach(option => {
                    if (option.value && option.value !== '') {
                        option.textContent = dbToDisplayMap[option.value] || option.value;
                    }
                });
            }

            // Configurar para entrada de datos (Display -> DB)
            if (isForStorage) {
                // Crear opciones con valores de visualización
                select.innerHTML = `
                    <option value="">Seleccionar...</option>
                    <option value="80">80</option>
                    <option value="81">81</option>
                    <option value="86">86</option>
                `;

                // Si hay un valor real, establecer la selección correspondiente
                if (realValueInput && realValueInput.value) {
                    const displayValue = dbToDisplayMap[realValueInput.value];
                    if (displayValue) {
                        select.value = displayValue;
                    }
                }

                // Manejar cambios para actualizar el valor real
                select.addEventListener('change', function() {
                    if (realValueInput) {
                        realValueInput.value = displayToDbMap[this.value] || this.value;
                    }
                });
            }
        });
    }

    initCodFormaSelects();
});