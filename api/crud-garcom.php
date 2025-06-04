<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

// Cabeçalhos HTTP
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT * FROM chamado_garcom ORDER BY criado_em DESC";
    $result = $conn->query($sql);

    $chamados = [];
    while ($row = $result->fetch_assoc()) {
        $chamados[] = $row;
    }

    echo json_encode(["success" => true, "data" => $chamados]);
    $conn->close();
    exit;

} else if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $mesa = $data['mesa'] ?? null;
    $status = $data['status'] ?? 'pendente';

    if ($mesa) {
        $stmt = $conn->prepare("INSERT INTO chamado_garcom (mesa, status, criado_em) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $mesa, $status);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Garçom chamado.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar chamada.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Mesa não informada.']);
    }

    $conn->close();
    exit;

} else if ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'] ?? null;
    $status = $input['status'] ?? null;

    if (!$id || !$status) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID e status são obrigatórios."]);
        exit;
    }

    $sql = "UPDATE chamado_garcom SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Status atualizado."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao atualizar status."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}