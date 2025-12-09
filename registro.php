<?php
session_start();
if (!isset($_SESSION["servername"])) {
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Panel Empleado</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { primary: "#3B82F6", "background-light": "#F3F4F6", "background-dark": "#1F2937" },
          fontFamily: { display: ["Roboto", "sans-serif"] },
          borderRadius: { DEFAULT: "0.5rem" },
        },
      },
    };
</script>
</head>
<body class="h-full font-display bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100 flex flex-col items-center justify-center p-4">
<div class="w-full max-w-4xl mx-auto">
<header class="flex justify-between items-center w-full mb-8">
    <div class="flex items-center space-x-4">
        <a href="cerrar_sesion.php" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
            <span class="material-icons-outlined">arrow_back</span>
        </a>
        <h1 class="text-2xl font-bold">Panel Principal Empleado</h1>
    </div>
    <a href="cerrar_sesion.php" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
        <span class="material-icons-outlined">logout</span>
    </a>
</header>
<main class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="flex flex-col space-y-6">
        <button class="flex items-center justify-center w-full bg-green-500 text-white font-bold py-6 px-6 rounded-lg text-2xl shadow-md hover:bg-green-600 transition-colors">
            <span class="material-icons-outlined mr-3">play_circle</span> Iniciar Jornada
        </button>
        <button class="flex items-center justify-center w-full bg-red-500 text-white font-bold py-6 px-6 rounded-lg text-2xl shadow-md hover:bg-red-600 transition-colors">
            <span class="material-icons-outlined mr-3">stop_circle</span> Finalizar Jornada
        </button>
    </div>
    <div class="flex flex-col space-y-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-lg font-medium mb-4 text-gray-800 dark:text-gray-200">Código de Confirmación</h2>
            
            <form action="registrar.php" method="POST" class="flex flex-col space-y-4">
                
                <input type="hidden" name="origen" value="registro.php">
                
                <input type="hidden" id="info_dispositivo" name="info_dispositivo" value="">

                <div class="flex items-center space-x-4">
                    <input id="codigo" name="codigo" class="flex-grow block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-primary focus:border-primary" placeholder="Ingrese código..." type="text" required autofocus />
                    <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-md shadow hover:bg-blue-700 transition-colors">
                        Enviar
                    </button>
                </div>
            </form>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <div class="flex items-center mb-4">
                <span class="material-icons-outlined text-gray-600 dark:text-gray-400 mr-3">history</span>
                <h2 class="text-lg font-medium text-gray-800 dark:text-gray-200">Historial</h2>
            </div>
            <a href="datosregistrados.html" class="block w-full text-center py-3 px-6 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                Ver Historial Completo
            </a>
        </div>
    </div>
</main>
</div>

<script>
// Detectar modelo del dispositivo y guardarlo en el input hidden
window.onload = function() {
    var ua = navigator.userAgent;
    var modelo = "";
    if (/Android/i.test(ua)) {
        var match = ua.match(/Android.*?; (.*?)\)/);
        modelo = match ? match[1] : "Android Genérico";
    } else if (/iPhone|iPad/i.test(ua)) {
        modelo = "Apple iOS";
    }
    
    // Si detectamos un modelo móvil, lo ponemos. Si no, dejamos vacío (registrar.php usará el nombre de PC)
    if(modelo !== "") {
        document.getElementById('info_dispositivo').value = modelo;
    }
    
    document.getElementById('codigo').focus();
    
    // Timeout seguridad
    setTimeout(function(){ window.location.href = "cerrar_sesion.php"; }, 300000); 
};
</script>
</body>
</html>