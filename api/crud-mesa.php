<?php
ob_start();
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

// Cabeçalhos HTTP
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");


// Função para resposta JSON padronizada
function respostaJson($success, $message, $data = null, $extra = []) {
    $resp = [
        "success" => $success,
        "message" => $message,
    ];
    if ($data !== null) {
        $resp["data"] = $data;
        if (is_array($data)) {
            $resp["totalCount"] = count($data);
        }
    }
    $resp = array_merge($resp, $extra);
    echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Conexão ($conn) já está disponível via conection.php

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Podemos receber um filtro para buscar mesa por número ou listar todas
    $numero = isset($_GET['numero']) ? intval($_GET['numero']) : null;
    $onlyDisponiveis = isset($_GET['disponiveis']) && $_GET['disponiveis'] === '1';

    $sql = "SELECT id, numero, responsavel FROM mesa";
    $params = [];
    $types = "";

    if ($numero !== null) {
        $sql .= " WHERE numero = ?";
        $types .= "i";
        $params[] = $numero;
    } elseif ($onlyDisponiveis) {
        $sql .= " WHERE responsavel IS NULL OR responsavel = ''";
    }

    $sql .= " ORDER BY numero ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        respostaJson(false, "Erro ao preparar consulta: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        respostaJson(false, "Erro ao executar consulta: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $mesas = [];
    while ($row = $result->fetch_assoc()) {
        $mesas[] = $row;
    }
    $stmt->close();

    respostaJson(true, "Mesas listadas com sucesso.", $mesas);
}

if ($method === 'PUT') {
    // Recebe JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['numero']) || !isset($input['responsavel'])) {
        respostaJson(false, "Dados incompletos. É necessário 'numero' e 'responsavel'.");
    }

    $numero = intval($input['numero']);
    $responsavel = trim($input['responsavel']);

    if ($numero <= 0 || $responsavel === "") {
        respostaJson(false, "Número da mesa inválido ou responsável vazio.");
    }

    // Verifica se a mesa existe
    $stmt = $conn->prepare("SELECT id FROM mesa WHERE numero = ?");
    if ($stmt === false) {
        respostaJson(false, "Erro ao preparar consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $numero);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        respostaJson(false, "Mesa não encontrada.");
    }
    $stmt->close();

    // Atualiza responsável (não permite mesa duplicada com mesmo responsável pois número é único)
    $stmt = $conn->prepare("UPDATE mesa SET responsavel = ? WHERE numero = ?");
    if ($stmt === false) {
        respostaJson(false, "Erro ao preparar atualização: " . $conn->error);
    }
    $stmt->bind_param("si", $responsavel, $numero);
    if (!$stmt->execute()) {
        respostaJson(false, "Erro ao atualizar mesa: " . $stmt->error);
    }
    $stmt->close();

    respostaJson(true, "Mesa atualizada com sucesso.");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

// Se método não suportado
http_response_code(405);
respostaJson(false, "Método HTTP não permitido.");

ob_end_flush();
