// --- FUNCIONES GLOBALES PARA EL PANEL DE ADMINISTRACIÓN ---

// Cargar módulo simple
window.cargarModulo = function(modulo) {
    const contenedor = document.getElementById('contenido-dinamico');
    contenedor.innerHTML = '<div class="flex justify-center items-center h-64"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div></div>';

    if (typeof toggleSidebar === 'function' && window.innerWidth < 768) {
        toggleSidebar(); 
    }

    fetch(`modulos/${modulo}.php`)
        .then(response => response.text())
        .then(html => {
            contenedor.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            contenedor.innerHTML = '<p class="text-red-500 text-center p-4">Error al cargar el módulo.</p>';
        });
}

// Cargar módulo con filtros
window.cargarModuloConFiltros = function(modulo, event) {
    if(event) event.preventDefault();
    
    const form = event.target;
    const contenedor = document.getElementById('contenido-dinamico');
    const formData = new FormData(form);
    
    contenedor.style.opacity = '0.6';
    contenedor.style.cursor = 'wait';

    fetch(`modulos/${modulo}.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        contenedor.innerHTML = html;
        contenedor.style.opacity = '1';
        contenedor.style.cursor = 'default';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al filtrar los datos.');
        contenedor.style.opacity = '1';
        contenedor.style.cursor = 'default';
    });
    
    return false;
}

// --- NUEVA FUNCIÓN EXPORTAR EXCEL (.xlsx) ---
window.exportarExcel = function() {
    let tabla = document.getElementById("tabla-horas");
    if (!tabla) {
        alert("No hay datos para exportar.");
        return;
    }

    let tablaClon = tabla.cloneNode(true);
    let filas = tablaClon.rows;
    for (let i = 0; i < filas.length; i++) {
        if (filas[i].cells.length > 0) {
            filas[i].deleteCell(-1); // Borrar última celda
        }
    }

    let wb = XLSX.utils.table_to_book(tablaClon, {sheet: "Control Horas"});
    let fecha = new Date().toLocaleDateString().replace(/\//g, '-');
    XLSX.writeFile(wb, `Reporte_Horas_${fecha}.xlsx`);
}

// --- GESTIÓN DE MODAL Y DETALLES DE FICHAJES ---

window.cerrarModal = function() {
    const modal = document.getElementById('modal-edicion');
    if(modal) modal.classList.add('hidden');
}

// Función llamada desde el botón "Ver Detalle"
window.verDetalleFichajes = function(id_empleado, fecha, nombre) {
    const modal = document.getElementById('modal-edicion');
    const body = document.getElementById('form-modal-body');
    const titulo = document.getElementById('modal-titulo');

    titulo.innerText = `Fichajes: ${nombre} (${fecha})`;
    body.innerHTML = '<div class="text-center p-4">Cargando datos...</div>';
    modal.classList.remove('hidden');

    fetch(`modulos/obtener_fichajes_dia.php?id=${id_empleado}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // Cabecera con botón PDF
            let html = `
                <div class="flex justify-end mb-3">
                    <a href="modulos/obtener_fichajes_dia.php?id=${id_empleado}&fecha=${fecha}&accion=pdf" target="_blank" 
                       class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded shadow text-sm flex items-center transition-colors">
                        <span class="material-icons-outlined text-sm mr-1">picture_as_pdf</span> Descargar PDF
                    </a>
                </div>
            `;

            if(data.length === 0) {
                html += '<p class="text-gray-500 text-center">No hay fichajes registrados para este día.</p>';
                body.innerHTML = html;
            } else {
                html += `
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Entrada</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Salida</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">`;
                
                data.forEach(f => {
                    let entrada = f.entrada_real ? f.entrada_real : '';
                    let salida = f.salida_real ? f.salida_real : '';
                    
                    // Generar iconos de mapa por separado para entrada y salida
                    let iconoEntrada = '';
                    if (f.loc_entrada) {
                        iconoEntrada = `
                            <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(f.loc_entrada)}" 
                               target="_blank" 
                               class="text-blue-500 hover:text-blue-700" 
                               title="Ver Ubicación Entrada">
                                <span class="material-icons-outlined text-lg">location_on</span>
                            </a>
                        `;
                    }

                    let iconoSalida = '';
                    if (f.loc_salida) {
                        iconoSalida = `
                            <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(f.loc_salida)}" 
                               target="_blank" 
                               class="text-blue-500 hover:text-blue-700" 
                               title="Ver Ubicación Salida">
                                <span class="material-icons-outlined text-lg">location_on</span>
                            </a>
                        `;
                    }
                    
                    html += `
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-500 align-middle">#${f.id}</td>
                            <td class="px-4 py-2 align-middle">
                                <div class="flex items-center gap-2">
                                    <input type="time" step="1" id="entrada_${f.id}" value="${entrada}" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-32">
                                    ${iconoEntrada}
                                </div>
                            </td>
                            <td class="px-4 py-2 align-middle">
                                <div class="flex items-center gap-2">
                                    <input type="time" step="1" id="salida_${f.id}" value="${salida}" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 w-32">
                                    ${iconoSalida}
                                </div>
                            </td>
                            <td class="px-4 py-2 text-center align-middle">
                                <button onclick="guardarFichaje(${f.id})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">Guardar</button>
                            </td>
                        </tr>
                    `;
                });
                
                html += `</tbody></table></div>`;
                html += `<div class="mt-4 text-xs text-gray-500 text-right">* Formato hora: HH:MM:SS</div>`;
                body.innerHTML = html;
            }
        })
        .catch(err => {
            console.error(err);
            body.innerHTML = '<p class="text-red-500 text-center">Error al cargar datos.</p>';
        });
}

// Guardar cambios de un fichaje específico
window.guardarFichaje = function(id_ticado) {
    const entrada = document.getElementById(`entrada_${id_ticado}`).value;
    const salida = document.getElementById(`salida_${id_ticado}`).value;

    if(!entrada) {
        alert("La hora de entrada no puede estar vacía.");
        return;
    }

    const formData = new FormData();
    formData.append('id', id_ticado);
    formData.append('entrada', entrada);
    formData.append('salida', salida);

    fetch('modulos/actualizar_fichaje.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('Actualizado correctamente.');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('Error de conexión');
    });
}