<?php
// validar_login.php

$servername = "85.215.144.168";
$username = "asesoft";
$password = "Pantera1";
$dbname = "asesoft";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

// Variables para controlar la redirección y el modal
$mostrar_modal = false;
$mensaje_modal = "";
$url_destino = "index.html"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $login = $_POST["login"];
  $userpass = $_POST["password"];
  
  $lat_login = isset($_POST["latitud"]) ? $_POST["latitud"] : "";
  $lon_login = isset($_POST["longitud"]) ? $_POST["longitud"] : "";

  $login = mysqli_real_escape_string($conn, $login);
  $userpass = mysqli_real_escape_string($conn, $userpass);

  $sql = "SELECT * FROM usuarios WHERE login = '$login' AND userpass = '$userpass'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // --- 1. COMPROBACIÓN DE FECHA DE ACCESO ---
    $fecha_fin = $row['fin_acceso']; 
    $fecha_actual = date('Y-m-d');
    
    if ($fecha_fin < $fecha_actual && $fecha_fin != '0000-00-00') {
        echo "<script>alert('ACCESO DENEGADO: Su cuenta caducó el $fecha_fin.'); window.location.href='index.html';</script>";
        exit();
    }

    // --- SESIÓN ---
    session_start();
    $_SESSION["id_usuario_db"] = $row["id"];
    
    // --- NUEVO: GUARDAMOS LA FIRMA EN SESIÓN ---
    // Esto evita tener que volver a consultarla después en otra BD
    $_SESSION["firma_usuario"] = $row["firma"]; 
    
    $_SESSION["servername"] = $row["servername"];
    $_SESSION["dbname"] = $row["dbname"];
    $_SESSION["username"] = $row["username"];
    $_SESSION["password"] = $row["password"];
    $_SESSION["latitud_sesion"] = $lat_login;
    $_SESSION["longitud_sesion"] = $lon_login;
    $_SESSION["loggedin"] = true;

    // --- DETERMINAR DESTINO FINAL ---
    if ($row["cambiarpass"] == 1) {
        $url_destino = "cambiar_password.php";
    } elseif ($row["cambiarfirma"] == 1) {
        $url_destino = "cambiar_firma.php";
    } elseif ($row["administrador"] == 1) {
        $url_destino = "principal.php"; // Recuerda que cambiamos a .php
    } elseif ($row["usuario"] == 1) {
        $url_destino = "registro.php";
    } else {
        session_destroy();
        echo "<script>alert('Sin rol asignado'); window.location.href='index.html';</script>";
        exit();
    }

    // --- 2. VERIFICAR AVISO DE CADUCIDAD ---
    if ($url_destino != "cambiar_password.php" && $url_destino != "cambiar_firma.php" && $fecha_fin != '0000-00-00') {
        $segundos_restantes = strtotime($fecha_fin) - strtotime($fecha_actual);
        $dias_restantes = floor($segundos_restantes / (60 * 60 * 24));
        
        if ($dias_restantes >= 0 && $dias_restantes <= 30) {
            $mostrar_modal = true;
            $mensaje_modal = "Su suscripción caducará en <strong>$dias_restantes días</strong> ($fecha_fin).<br>Por favor, contacte con administración para renovar.";
        }
    }

    if (!$mostrar_modal) {
        header("Location: $url_destino");
        exit();
    }

  } else {
    echo "<script>alert('Usuario o contraseña incorrectos'); window.location.href='index.html';</script>";
    exit();
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso del Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full m-4">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Aviso de Caducidad</h3>
                    <div class="mt-2"><p class="text-sm text-gray-500"><?= $mensaje_modal ?></p></div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button" onclick="continuar()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Entendido, Continuar</button>
        </div>
    </div>
    <script>function continuar() { window.location.href = "<?= $url_destino ?>"; }</script>
</body>
</html>