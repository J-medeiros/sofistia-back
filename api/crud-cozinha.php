<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Origin, X-Requested-With, Accept");

// Permite requisições OPTIONS para CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    try {
        $sql = "SELECT 
            p.id,
            p.idmesa,
            m.numero,
            m.responsavel,
            p.id_status,
            s.status,
            pr.nome AS produto_nome,
            pr.valor
        FROM pedido p
        INNER JOIN produtos pr ON p.idproduto = pr.id
        INNER JOIN mesa m ON p.idmesa = m.id
        INNER JOIN status s ON p.id_status = s.id";

        $stmt = $conn->query($sql);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "data" => $dados,
            "totalCount" => count($dados),
            "success" => true
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao buscar pedidos: " . $e->getMessage()]);
    }
} elseif ($method === "PUT") {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$id) {
        echo json_encode(["success" => false, "message" => "ID do pedido não fornecido."]);
        exit;
    }

    try {
        $sql = "UPDATE pedido SET status = true WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Pedido atualizado para preparado (status true)."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao atualizar pedido: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método inválido. Apenas GET e PUT são permitidos."]);
}
