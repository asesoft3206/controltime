<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Desactivar errores visibles para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Verificar si hay sesión
    if (!isset($_SESSION["id_usuario_db"])) {
        throw new Exception('Sesión no iniciada.');
    }

    // Verificar si la firma se cargó correctamente en el login
    if (!isset($_SESSION["firma_usuario"])) {
        throw new Exception('Error de seguridad: Firma no cargada en sesión. Por favor, cierre sesión e ingrese nuevamente.');
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $firma_input = $_POST['firma'] ?? '';
        
        // La firma real que viene de la BD (guardada en login)
        $firma_real = $_SESSION["firma_usuario"];

        // Comparación simple
        if ($firma_input === $firma_real) {
            $_SESSION['admin_access_verified'] = true; // Flag de acceso concedido
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Firma incorrecta']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>