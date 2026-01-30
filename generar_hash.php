<?php

$password = 'sudoapt';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Hash generado para la contrasenna: <strong>$password</strong></h2>";
echo "<p>Hash: <code>$hash</code></p>";
echo "<hr>";
echo "<h3>Query SQL para insertar el usuario:</h3>";
echo "<pre>";
echo "INSERT INTO usuarios (usuario, password, nombre) VALUES \n";
echo "('pablo', '$hash', 'Pablo');";
echo "</pre>";
echo "<hr>";
echo "<h3>O ejecuta este UPDATE si el usuario ya existe:</h3>";
echo "<pre>";
echo "UPDATE usuarios SET password = '$hash' WHERE usuario = 'pablo';";
echo "</pre>";
?>
