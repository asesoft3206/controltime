<?php
session_start(); // Iniciar la sesión

$servername = $_SESSION["servername"];
$username = $_SESSION["username"];
$password = $_SESSION["password"];
$dbname = $_SESSION["dbname"];

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8mb4");
// Verificar la conexión
if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

// Mostrar listado de personas con entrada pero sin salida
$sql = "SELECT DATE_FORMAT(t.fecha, '%d/%m/%Y') AS fecha, t.idt,t.trabajador, t.entrada_real, t.centro 
        FROM ticados t
        WHERE t.salida_real IS NULL order by t.fecha desc";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  echo "<h2>Listado de personas con turno abierto:</h2>";

 echo "<table><tr><th>Fecha</th><th>   </th><th>Id T</th><th>Trabajador</th><th>Hora Entrada</th><th>Localización</th></tr>";
  while($row = $result->fetch_assoc()) {
    echo "<tr><td>".$row["fecha"]."</td><td>"."  "."</td><td>".$row["idt"]."</td><td>".$row["trabajador"]."</td><td>".$row["entrada_real"]."</td><td>".$row["centro"]."</td></tr>";
  }
  echo "</table>";
} else {
  echo "No hay personas con entrada sin salida.";
}

$conn->close();

?>