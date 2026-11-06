<?php
include("conexion.php");

if (isset($_POST['nombre'])) {
    $nombre = $_POST['nombre'];

    $sql = "INSERT INTO integrantes (nom) VALUES ('$nombre')";

    if ($conexion->query($sql) === TRUE) {
        echo "Nuevo registro creado exitosamente";
    } else {
        echo "Error: " . $sql . "<br>" . $conexion->error;
    }
} else {
    echo "No se recibio el nombre.";
}

$conexion->close();
?>