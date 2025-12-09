function cargarModulo(modulo) {
    const contenedor = document.getElementById('contenido-dinamico');
    contenedor.innerHTML = '<p>Cargando datos...</p>';

    // Hacemos una petición al archivo PHP correspondiente en la carpeta modulos
    fetch(`modulos/${modulo}.php`)
        .then(response => response.text())
        .then(html => {
            contenedor.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            contenedor.innerHTML = '<p>Error al cargar el módulo.</p>';
        });
}

// Funciones para el Modal de Empleados
function abrirModalEditar(id, nif, nombre, estado, horas) {
    const modal = document.getElementById('modal-edicion');
    const body = document.getElementById('form-modal-body');

    // Generamos el formulario dinámicamente con los datos recibidos
    body.innerHTML = `
        <form onsubmit="guardarEmpleado(event)">
            <input type="hidden" name="id" value="${id}">
            
            <label>NIF:</label><br>
            <input type="text" name="nif" value="${nif}" required><br><br>
            
            <label>Nombre:</label><br>
            <input type="text" name="nombre" value="${nombre}" required><br><br>
            
            <label>Horas Contrato Diarias:</label><br>
            <input type="number" step="0.1" name="horas_contrato" value="${horas}" required><br><br>
            
            <label>Estado:</label><br>
            <select name="estado">
                <option value="activo" ${estado === 'activo' ? 'selected' : ''}>Activo</option>
                <option value="inactivo" ${estado === 'inactivo' ? 'selected' : ''}>Inactivo</option>
            </select><br><br>
            
            <button type="submit">Guardar Cambios</button>
        </form>
    `;
    modal.style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modal-edicion').style.display = 'none';
}

function guardarEmpleado(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch('modulos/guardar_empleado.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(data => {
        alert(data);
        cerrarModal();
        cargarModulo('gestion_empleados'); // Recargamos la tabla
    });
}