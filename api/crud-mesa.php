<?php
ob_start();
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

// Cabeçalhos HTTP
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");

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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $numero = isset($_GET['numero']) ? intval($_GET['numero']) : null;
    $onlyDisponiveis = isset($_GET['disponiveis']) && $_GET['disponiveis'] === '1';

    $sql = "SELECT id, numero, responsavel FROM mesa";
    $params = [];

    if ($numero !== null) {
        $sql .= " WHERE numero = :numero";
        $params[':numero'] = $numero;
    } elseif ($onlyDisponiveis) {
        $sql .= " WHERE responsavel IS NULL OR responsavel = ''";
    }

    $sql .= " ORDER BY numero ASC";

    try {
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }

        $stmt->execute();
        $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respostaJson(true, "Mesas listadas com sucesso.", $mesas);
    } catch (PDOException $e) {
        respostaJson(false, "Erro ao executar consulta: " . $e->getMessage());
    }
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['numero']) || !isset($input['responsavel'])) {
        respostaJson(false, "Dados incompletos. É necessário 'numero' e 'responsavel'.");
    }

    $numero = intval($input['numero']);
    $responsavel = trim($input['responsavel']);

    if ($numero <= 0 || $responsavel === "") {
        respostaJson(false, "Número da mesa inválido ou responsável vazio.");
    }

    try {
        // Verifica se a mesa existe
        $stmt = $conn->prepare("SELECT id FROM mesa WHERE numero = :numero");
        $stmt->bindValue(':numero', $numero, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            respostaJson(false, "Mesa não encontrada.");
        }

        // Atualiza responsável
        $stmt = $conn->prepare("UPDATE mesa SET responsavel = :responsavel WHERE numero = :numero");
        $stmt->bindValue(':responsavel', $responsavel, PDO::PARAM_STR);
        $stmt->bindValue(':numero', $numero, PDO::PARAM_INT);
        $stmt->execute();

        respostaJson(true, "Mesa atualizada com sucesso.");
    } catch (PDOException $e) {
        respostaJson(false, "Erro ao atualizar mesa: " . $e->getMessage());
    }
}

if ($method === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit;
}

http_response_code(405);
respostaJson(false, "Método HTTP não permitido.");

ob_end_flush();
