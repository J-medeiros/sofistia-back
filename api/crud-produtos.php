<?php
ob_start();
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

// Cabeçalhos HTTP
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// Apenas requisições GET
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : null;
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    $sql = "SELECT * FROM produtos";
    $stmt = null;

    try {
        if (!empty($filtro) && empty($id)) {
            $sql .= " WHERE nome ILIKE :filtro"; // ILIKE é case-insensitive no PostgreSQL
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':filtro', "%$filtro%", PDO::PARAM_STR);
        } elseif (empty($filtro) && !empty($id)) {
            $sql .= " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $stmt = $conn->prepare($sql);
        }

        $stmt->execute();
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "message" => "Consulta realizada com sucesso.",
            "data" => $produtos,
            "totalCount" => count($produtos)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Erro ao executar consulta.",
            "error" => $e->getMessage()
        ]);
    }

    $conn = null; // Encerra conexão
}

ob_end_flush();
