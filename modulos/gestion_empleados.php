<?php
include '../../db_connection.php'; // Ajusta la ruta

$sql = "SELECT * FROM empleados WHERE estado = 'activo'";
$result = $conn->query($sql);
?>

<h3>Gestión de Empleados</h3>
<table border="1" cellpadding="10" style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>
            <th>ID</th>
            <th>NIF</th>
            <th>Nombre</th>
            <th>Horas Contrato</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nif']; ?></td>
            <td><?php echo $row['nombre']; ?></td>
            <td><?php echo $row['horas_contrato']; ?></td>
            <td><?php echo $row['estado']; ?></td>
            <td>
                <button onclick="abrirModalEditar(
                    '<?php echo $row['id']; ?>', 
                    '<?php echo $row['nif']; ?>', 
                    '<?php echo $row['nombre']; ?>', 
                    '<?php echo $row['estado']; ?>', 
                    '<?php echo $row['horas_contrato']; ?>'
                )">Editar</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>