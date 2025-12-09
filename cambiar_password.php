<?php
session_start();
// Seguridad: Si no está logueado, fuera.
if (!isset($_SESSION["id_usuario_db"])) {
    header("Location: index.html");
    exit();
}

// Procesar el formulario
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];
    
    // 1. Validar que coinciden
    if ($pass1 !== $pass2) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "red";
    } else {
        // 2. Validar política: Min 8 chars, 1 Mayúscula, 1 Número Y 1 SÍMBOLO
        // (?=.*[\W_]) asegura al menos un carácter especial
        if (!preg_match('/(?=.*\d)(?=.*[A-Z])(?=.*[\W_]).{8,}/', $pass1)) {
            $mensaje = "La contraseña debe tener: Min. 8 caracteres, 1 Mayúscula, 1 Número y 1 Símbolo especial (.,@#$%).";
            $tipo_mensaje = "red";
        } else {
            // 3. Actualizar en BD central
            $conn = new mysqli("85.215.144.168", "asesoft", "Pantera1", "asesoft");
            
            if ($conn->connect_error) {
                 $mensaje = "Error de conexión.";
                 $tipo_mensaje = "red";
            } else {
                $id_user = $_SESSION["id_usuario_db"];
                $new_pass = mysqli_real_escape_string($conn, $pass1);
                
                // Actualizamos pass y ponemos cambiarpass a 0
                $sql = "UPDATE usuarios SET userpass = '$new_pass', cambiarpass = 0 WHERE id = $id_user";
                
                if ($conn->query($sql) === TRUE) {
                    session_destroy();
                    echo "<script>
                            alert('Contraseña actualizada correctamente. Inicie sesión de nuevo.');
                            window.location.href = 'index.html';
                          </script>";
                    exit();
                } else {
                    $mensaje = "Error al actualizar: " . $conn->error;
                    $tipo_mensaje = "red";
                }
                $conn->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Contraseña</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Cambio de Contraseña</h2>
        
        <?php if($mensaje != ""): ?>
            <div class="mb-4 p-3 bg-<?= $tipo_mensaje ?>-100 text-<?= $tipo_mensaje ?>-700 rounded border border-<?= $tipo_mensaje ?>-400">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <div class="bg-blue-50 p-4 rounded mb-6 text-sm text-blue-800 border border-blue-200">
            <strong>Requisitos de seguridad:</strong>
            <ul class="list-disc ml-5 mt-1 space-y-1">
                <li>Mínimo 8 caracteres</li>
                <li>Al menos 1 Mayúscula (A-Z)</li>
                <li>Al menos 1 Número (0-9)</li>
                <li>Al menos 1 Símbolo (@, #, $, %, etc.)</li>
            </ul>
        </div>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nueva Contraseña</label>
                <input type="password" name="pass1" required class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Confirmar Contraseña</label>
                <input type="password" name="pass2" required class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                Actualizar y Salir
            </button>
        </form>
    </div>
</body>
</html>