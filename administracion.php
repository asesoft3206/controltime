<?php
session_start();

// Verificación de seguridad básica
if (!isset($_SESSION["id_usuario_db"])) {
    header("Location: index.html");
    exit();
}

$nombre_usuario = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    
    <!-- Fuentes e Iconos -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    
    <!-- Librería para Excel real (.xlsx) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#3b82f6",
                        "background-light": "#f3f4f6",
                        "background-dark": "#1f2937",
                    },
                    fontFamily: {
                        display: ["Roboto", "sans-serif"],
                    },
                },
            },
        };
    </script>
    <style>
        /* CORRECCIÓN PARA IMPRESIÓN (PDF) */
        @media print {
            body, html, #wrapper, main {
                height: auto !important;
                overflow: visible !important;
                display: block !important;
            }
            #sidebar, header, #mobile-overlay, .no-print {
                display: none !important;
            }
            #contenido-dinamico {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse;
                font-size: 10pt;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-background-light font-display text-gray-800 h-screen flex overflow-hidden">

    <!-- OVERLAY PARA MÓVIL -->
    <div id="mobile-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden glass transition-opacity duration-300"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="bg-white shadow-xl w-64 z-30 fixed inset-y-0 left-0 transform -translate-x-full transition-transform duration-300 md:relative md:translate-x-0 flex flex-col">
        <div class="h-16 flex items-center justify-center border-b border-gray-200">
            <h1 class="text-xl font-bold text-primary flex items-center">
                <span class="material-icons-outlined mr-2">admin_panel_settings</span>
                Panel de Administración
            </h1>
            <button onclick="toggleSidebar()" class="md:hidden absolute right-4 text-gray-500">
                <span class="material-icons-outlined">close</span>
            </button>
        </div>

        <nav class="flex-grow py-6 px-4 space-y-2 overflow-y-auto">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Módulos</p>
            
            <button onclick="cargarModulo('control_horas'); toggleSidebarSiMovil()" class="w-full flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-colors group">
                <span class="material-icons-outlined mr-3 group-hover:text-primary">timer</span>
                <span class="font-medium">Control de Horas</span>
            </button>

            <button onclick="cargarModulo('gestion_empleados'); toggleSidebarSiMovil()" class="w-full flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-colors group">
                <span class="material-icons-outlined mr-3 group-hover:text-primary">people</span>
                <span class="font-medium">Gestión Empleados</span>
            </button>

            <!-- 
            <button onclick="cargarModulo('calendario'); toggleSidebarSiMovil()" class="w-full flex items-center px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-colors group">
                <span class="material-icons-outlined mr-3 group-hover:text-primary">event</span>
                <span class="font-medium">Calendario Laboral</span>
            </button>
            -->
        </nav>

        <div class="p-4 border-t border-gray-200">
            <a href="cerrar_sesion.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <span class="material-icons-outlined mr-3">logout</span>
                <span class="font-medium">Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER SUPERIOR -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="md:hidden">
                <button onclick="toggleSidebar()" class="text-gray-500 hover:text-primary focus:outline-none p-2 rounded-md hover:bg-gray-100">
                    <span class="material-icons-outlined text-2xl">menu</span>
                </button>
            </div>
            
            <div class="flex items-center justify-end w-full">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white font-bold shadow-sm">
                        <?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?>
                    </div>
                    <span class="text-sm font-medium text-gray-700 hidden sm:block"><?php echo $nombre_usuario; ?></span>
                </div>
            </div>
        </header>

        <!-- ÁREA DE CARGA DINÁMICA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
            <div id="contenido-dinamico" class="bg-white rounded-lg shadow p-6 min-h-[500px]">
                <div class="text-center py-20">
                    <span class="material-icons-outlined text-6xl text-gray-300 mb-4">dashboard</span>
                    <h2 class="text-2xl font-bold text-gray-700">Panel de Administración</h2>
                    <p class="text-gray-500 mt-2">Seleccione una opción del menú.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL DE EDICIÓN -->
    <div id="modal-edicion" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 id="modal-titulo" class="text-xl font-bold text-gray-800">Detalles</h3>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-red-500">
                    <span class="material-icons-outlined">close</span>
                </button>
            </div>
            <div class="mt-4" id="form-modal-body"></div>
        </div>
    </div>

    <script src="js/admin.js"></script> 
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-overlay');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        function toggleSidebarSiMovil() {
            if (window.innerWidth < 768) {
                toggleSidebar();
            }
        }
        window.onclick = function(event) {
            const modal = document.getElementById('modal-edicion');
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>