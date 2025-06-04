<?php

$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$dbname = getenv("DB_NAME");
$user = getenv("DB_USER");
$password = getenv("DB_PASSWORD");

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Erro ao conectar ao banco de dados",
        "error" => $e->getMessage()
    ]);
    exit;
}


// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "Sofistia";

// // Cria a conexão
// $conn = new mysqli($servername, $username, $password, $dbname);

// // Verifica se ocorreu erro na conexão
// if ($conn->connect_error) {
//     // Encerra a execução com erro HTTP e mensagem JSON (usado se for um endpoint direto de teste)
//     http_response_code(500);
//     die(json_encode([
//         "success" => false,
//         "message" => "Erro ao conectar ao banco de dados",
//         "error" => $conn->connect_error
//     ]));
// }