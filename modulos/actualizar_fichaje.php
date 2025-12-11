<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["servername"])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $entrada = $_POST['entrada'];
    $salida = !empty($_POST['salida']) ? $_POST['salida'] : null;

    $conn = new mysqli($_SESSION["servername"], $_SESSION["username"], $_SESSION["password"], $_SESSION["dbname"]);
    
    // Usar Prepared Statements para seguridad
    $stmt = $conn->prepare("UPDATE ticados SET entrada_real = ?, salida_real = ? WHERE id = ?");
    $stmt->bind_param("ssi", $entrada, $salida, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
}
?>