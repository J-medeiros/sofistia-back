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

    if (!empty($filtro) && empty($id)) {
        $sql .= " WHERE nome LIKE ?";
        $stmt = $conn->prepare($sql);
        $filtro = "%$filtro%";
        $stmt->bind_param("s", $filtro);
    } elseif (empty($filtro) && !empty($id)) {
        $sql .= " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare($sql);
    }

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Erro ao preparar consulta.",
            "error" => $conn->error
        ]);
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }

    echo json_encode([
        "success" => true,
        "message" => "Consulta realizada com sucesso.",
        "data" => $produtos,
        "totalCount" => count($produtos)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt->close();
    $conn->close();
}

// Encerra o buffer e envia resposta
ob_end_flush();
