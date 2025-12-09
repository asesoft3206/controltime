<?php
include '../../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nif = $_POST['nif'];
    $nombre = $_POST['nombre'];
    $horas = $_POST['horas_contrato'];
    $estado = $_POST['estado'];

    // Usar Prepared Statements para seguridad
    $stmt = $conn->prepare("UPDATE empleados SET nif=?, nombre=?, horas_contrato=?, estado=? WHERE id=?");
    $stmt->bind_param("ssdsi", $nif, $nombre, $horas, $estado, $id);

    if ($stmt->execute()) {
        echo "Empleado actualizado correctamente.";
    } else {
        echo "Error al actualizar: " . $conn->error;
    }
    $stmt->close();
}
?>