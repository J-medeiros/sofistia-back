<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (isset($_GET['nome'])) {
        $nome = $_GET['nome'];

        // Evita SQL Injection
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE nome LIKE ?");
        $nome_like = "%" . $nome . "%";
        $stmt->bind_param("s", $nome_like);
        $stmt->execute();

        $result = $stmt->get_result();
        $dados = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode($dados);
    } else {
        echo json_encode(["error" => "Parâmetro 'nome' não fornecido."]);
    }
} else {
    echo json_encode(["error" => "Método inválido"]);
}
