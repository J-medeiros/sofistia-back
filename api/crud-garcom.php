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
    try {
        $sql = "SELECT * FROM chamado_garcom ORDER BY criado_em DESC";
        $stmt = $conn->query($sql);
        $chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $chamados]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro ao buscar chamados: " . $e->getMessage()]);
    }
    exit;

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $mesa = $data['mesa'] ?? null;
    $status = $data['status'] ?? 'pendente';

    if ($mesa !== null) {
        try {
            $sql = "INSERT INTO chamado_garcom (mesa, status, criado_em) VALUES (:mesa, :status, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':mesa', $mesa, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Garçom chamado.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao registrar chamada: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Mesa não informada.']);
    }
    exit;

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input['id'] ?? null;
    $status = $input['status'] ?? null;

    if (!$id || !$status) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID e status são obrigatórios."]);
        exit;
    }

    try {
        $sql = "UPDATE chamado_garcom SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(["success" => true, "message" => "Status atualizado."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao atualizar status: " . $e->getMessage()]);
    }
    exit;
}
