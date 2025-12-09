<?php
session_start();
// Solo iniciamos sesión para poder mostrar el nombre del usuario si quieres
// Si no hay variable de sesión, mostrará "Admin" por defecto
$nombre_usuario = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <style>
        /* Estilos básicos para la estructura */
        body { font-family: sans-serif; margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Menú lateral */
        #sidebar { 
            width: 250px; 
            background-color: #333; 
            color: #fff; 
            display: flex; 
            flex-direction: column; 
            padding-top: 20px;
        }
        
        #sidebar h3 { text-align: center; margin-bottom: 30px; }
        
        /* Botones del menú */
        .menu-btn {
            background: none;
            border: none;
            color: #fff;
            padding: 15px 20px;
            text-align: left;
            cursor: pointer;
            font-size: 16px;
            border-left: 4px solid transparent;
            transition: 0.3s;
        }
        
        .menu-btn:hover { background-color: #444; border-left: 4px solid #3498db; }

        /* Contenedor principal */
        #contenido-principal {
            flex-grow: 1;
            padding: 20px;
            background-color: #f4f4f4;
            overflow-y: auto; /* Scroll si el contenido es muy largo */
        }
        
        /* Estilos del Modal (Ventana emergente) */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;}
        .modal-content { background: white; margin: 5% auto; padding: 20px; width: 60%; border-radius: 8px; position: relative; }
        .close-btn { position: absolute; top: 10px; right: 15px; font-size: 20px; cursor: pointer; }
    </style>
</head>
<body>

    <div id="sidebar">
        <h3>Panel Admin</h3>
        
        <button class="menu-btn" onclick="cargarSeccion('control_horas')">
            ⏱️ Control de Horas
        </button>
        
        <button class="menu-btn" onclick="cargarSeccion('gestion_empleados')">
            👥 Gestión Empleados
        </button>
        
        <button class="menu-btn" onclick="cargarSeccion('calendario')">
            📅 Calendario Laboral
        </button>

        <div style="margin-top: auto;">
             <a href="logout.php" class="menu-btn" style="display:block; text-decoration:none;">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <div id="contenido-principal">
        <h1>Bienvenido, <?php echo $nombre_usuario; ?></h1>
        <p>Selecciona una opción del menú de la izquierda para comenzar a trabajar.</p>
        
        <div id="area-de-carga"></div>
    </div>

    <div id="modal-admin" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="cerrarModal()">&times;</span>
            <div id="contenido-modal"></div>
        </div>
    </div>

    <script src="js/admin_logica.js"></script>

</body>
</html>