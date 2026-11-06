<?php
$servidor = "localhost";
$usuario = "root";
$password = "";
$basedatos = "registrados";

$conexion = new mysqli($servidor,$usuario,$password,$basedatos);
if ($conexion->connect_error){
    die("error en la conexion:".$conexion->connect_error);
}
$conexion->set_charset("utf8mb4");
?>