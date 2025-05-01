<?php

$servername ="localhost";
$username ="root";
$password ="";
$dbname ="Sofistia";

$conn  = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao conectar ao banco de dados",
        "error" => $conn->connect_error
    ]);
} else {
     echo json_encode([
     "success" => true,
     "message" => "Connection already established",
     ]);
    return;
}
?>