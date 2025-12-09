<?php
// --- BLOQUE DE DEPURACIÓN (Eliminar al pasar a producción) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// -------------------------------------------------------------

ob_start(); // Iniciar búfer de salida para evitar errores de headers
session_start();

// Si no hay ID de usuario en sesión, echar fuera
if (!isset($_SESSION["id_usuario_db"])) {
    header("Location: index.html");
    exit();
}

$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar que recibimos el dato
    if (!isset($_POST['firma'])) {
        $mensaje = "No se ha recibido el código de firma.";
        $tipo_mensaje = "red";
    } else {
        $firma = $_POST['firma'];
        
        // 1. Validar: 5 dígitos numéricos exactos
        if (!preg_match('/^\d{5}$/', $firma)) {
            $mensaje = "La firma debe consistir exactamente en 5 números.";
            $tipo_mensaje = "red";
        } else {
            // 2. Actualizar en BD central con manejo de errores (Try-Catch)
            $conn = null;
            try {
                // NOTA: Verifica que estos datos de conexión sean correctos para tu servidor central
                $conn = new mysqli("85.215.144.168", "asesoft", "Pantera1", "asesoft");
                
                if ($conn->connect_error) {
                    throw new Exception("Fallo de conexión: " . $conn->connect_error);
                }

                $id_user = (int)$_SESSION["id_usuario_db"]; // Aseguramos que es entero 
                $nueva_firma = $conn->real_escape_string($firma); // Sanitizar entrada
                
                // Actualizamos firma y ponemos cambiarfirma a 0 (según tabla usuarios )
                $sql = "UPDATE usuarios SET firma = '$nueva_firma', cambiarfirma = 0 WHERE id = $id_user";
                
                if ($conn->query($sql) === TRUE) {
                    // Limpiar sesión y redirigir
                    session_destroy();
                    echo "<script>
                            alert('Código de firma actualizado correctamente. Por favor, inicie sesión de nuevo.');
                            window.location.href = 'index.html';
                          </script>";
                    exit();
                } else {
                    throw new Exception("Error al ejecutar UPDATE: " . $conn->error);
                }

            } catch (Exception $e) {
                $mensaje = "Error del sistema: " . $e->getMessage();
                $tipo_mensaje = "red";
            } finally {
                if ($conn) {
                    $conn->close();
                }
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
    <title>Actualizar Firma Digital</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Configurar Firma Digital</h2>
        
        <?php if($mensaje != ""): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-400">
                <strong>Error:</strong> <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <p class="text-sm text-gray-600 mb-4">
            El sistema requiere que configure un nuevo código de firma.
            <br><strong>Requisito:</strong> Exactamente 5 números (Ej: 12345).
        </p>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nuevo Código (5 dígitos)</label>
                <input type="text" name="firma" pattern="\d{5}" maxlength="5" placeholder="00000" required 
                       class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 text-center text-2xl tracking-widest">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                Guardar Firma
            </button>
        </form>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>