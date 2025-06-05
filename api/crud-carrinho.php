<?php
ob_start();
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

if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro na conexão com o banco."]);
    exit;
}
if ($method === 'GET') {
    $mesa = isset($_GET['mesa']) ? intval($_GET['mesa']) : null;
    $sql = "SELECT 
                c.id_produto,
                p.nome,
                p.descricao,
                p.image,
                c.quantidade,
                c.totalvalor ,
                c.mesa
            FROM carrinho AS c
            INNER JOIN produtos AS p ON c.id_produto = p.id";
    if (!is_null($mesa)) {
        $sql .= " WHERE c.mesa = :mesa";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':mesa', $mesa, PDO::PARAM_INT);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        "success" => true,
        "message" => "Consulta realizada com sucesso.",
        "data" => $itens,
        "totalCount" => count($itens)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
} else if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id_produto'], $data['quantidade'], $data['mesa'], $data['totalvalor'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Dados incompletos para inserir no carrinho."]);
        exit;
    }
    // Verifica se o item já existe para a mesa
    $sqlCheck = "SELECT id, quantidade FROM carrinho WHERE id_produto = :id_produto AND mesa = :mesa";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([
        ':id_produto' => $data['id_produto'],
        ':mesa' => $data['mesa']
    ]);
    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $novaQuantidade = $row['quantidade'] + $data['quantidade'];
        $sqlUpdate = "UPDATE carrinho SET quantidade = :quantidade, \"totalvalor\" = :totalvalor WHERE id = :id";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':quantidade' => $novaQuantidade,
            ':totalvalor' => $data['totalvalor'],
            ':id' => $row['id']
        ]);
        echo json_encode(["success" => true, "message" => "Quantidade atualizada com sucesso no carrinho."]);
    } else {
        $sqlInsert = "INSERT INTO carrinho (id_produto, quantidade, \"totalvalor\", mesa) VALUES (:id_produto, :quantidade, :totalvalor, :mesa)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([
            ':id_produto' => $data['id_produto'],
            ':quantidade' => $data['quantidade'],
            ':totalvalor' => $data['totalvalor'],
            ':mesa' => $data['mesa']
        ]);
        echo json_encode(["success" => true, "message" => "Produto adicionado ao carrinho."]);
    }
    ob_end_flush();
    exit;
} else if ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!isset($input['id_produto'], $input['mesa'], $input['quantidade'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Dados insuficientes para atualizar o carrinho."]);
        exit;
    }
    $id_produto = intval($input['id_produto']);
    $mesa = intval($input['mesa']);
    $quantidade = intval($input['quantidade']);
    // Buscar o preço unitário
    $stmtPreco = $conn->prepare("SELECT valor FROM produtos WHERE id = :id");
    $stmtPreco->execute([':id' => $id_produto]);
    $produto = $stmtPreco->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Produto não encontrado."]);
        exit;
    }
    $preco_unitario = floatval($produto['valor']);
    $totalvalor = $quantidade * $preco_unitario;
    $sqlUpdate = "UPDATE carrinho SET quantidade = :quantidade, \"totalvalor\" = :totalvalor WHERE id_produto = :id_produto AND mesa = :mesa";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':quantidade' => $quantidade,
        ':totalvalor' => $totalvalor,
        ':id_produto' => $id_produto,
        ':mesa' => $mesa
    ]);
    echo json_encode(["success" => true, "message" => "Produto atualizado com sucesso no carrinho."]);
    ob_end_flush();
    exit;
} else if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);

    $id_produto = $data['id_produto'] ?? $_GET['id_produto'] ?? null;
    $id_mesa = $data['id_mesa'] ?? $_GET['id_mesa'] ?? null;

    if ($id_produto && $id_mesa) {
        try {
            $stmt = $conn->prepare("DELETE FROM carrinho WHERE id_produto = :id_produto AND mesa = :mesa");
            $stmt->execute([
                ':id_produto' => $id_produto,
                ':mesa' => $id_mesa
            ]);

            echo json_encode(["success" => true, "message" => "Item removido do carrinho com sucesso."]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Erro ao remover item do carrinho.",
                "error" => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Parâmetros id_produto e id_mesa são obrigatórios."
        ]);
    }
}
