<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Permite requisições OPTIONS para CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Buscar apenas pedidos com status = false (pendentes)
    $sql = "SELECT 
        p.id,
        p.idMesa,
        m.numero,
        m.responsavel,
        p.status,
        pr.nome AS produto_nome,
        pr.valor
    FROM sofistia.pedido p
    INNER JOIN sofistia.produtos pr ON p.idProduto = pr.id
    INNER JOIN sofistia.mesa m ON p.idMesa = m.id
    WHERE p.status = 0";  // status false = 0 no MySQL

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode([
        "data" => $result->fetch_all(MYSQLI_ASSOC),
        "totalCount" => $result->num_rows,
        "success" => true
    ]);

    $stmt->close();
    $conn->close();

} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Atualiza o status do pedido para true (1)
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    if (!$id) {
        echo json_encode(["success" => false, "message" => "ID do pedido não fornecido."]);
        exit;
    }

    $sql = "UPDATE sofistia.pedido SET status = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Pedido atualizado para preparado (status true)."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erro ao atualizar pedido."]);
    }

    $stmt->close();
    $conn->close();

} else {
    // Método inválido
    http_response_code(405);
    echo json_encode(["error" => "Método inválido. Apenas GET e PUT são permitidos."]);
}
