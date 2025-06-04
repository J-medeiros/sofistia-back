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

// Conexão
$conn = $conn ?? null;
if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro na conexão com o banco."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mesa = isset($_GET['mesa']) ? intval($_GET['mesa']) : null;
    $sql = "SELECT 

  c.id_produto,
  p.nome,
  p.descricao,
  p.image,
  c.quantidade,
  c.totalValor,
  c.mesa
FROM sofistia.carrinho AS c
INNER JOIN sofistia.produtos AS p ON c.id_produto = p.id";
    $stmt = null;

    if (!is_null($mesa)) {
        $sql .= " WHERE c.mesa = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $mesa);
    } else {
        $stmt = $conn->prepare($sql);
    }

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao preparar consulta.", "error" => $conn->error]);
        exit;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $itens = [];
    while ($row = $result->fetch_assoc()) {
        $itens[] = $row;
    }

    echo json_encode([
        "success" => true,
        "message" => "Consulta realizada com sucesso.",
        "data" => $itens,
        "totalCount" => count($itens)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt->close();
    $conn->close();
    ob_end_flush();
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id_produto'], $data['quantidade'], $data['mesa'], $data['totalValor'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Dados incompletos para inserir no carrinho."]);
        exit;
    }

    // Verifica se o item já existe para a mesa
    $sqlCheck = "SELECT id, quantidade FROM carrinho WHERE id_produto = ? AND mesa = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $data['id_produto'], $data['mesa']);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows > 0) {
        // Atualiza quantidade e total
        $row = $result->fetch_assoc();
        $novaQuantidade = $row['quantidade'] + $data['quantidade'];
        $sqlUpdate = "UPDATE carrinho SET quantidade = ?, totalValor = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("idi", $novaQuantidade, $data['totalValor'], $row['id']);

        if ($stmtUpdate->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Quantidade atualizada com sucesso no carrinho."
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao atualizar o carrinho."]);
        }
        $stmtUpdate->close();
    } else {
        // Insere novo item
        $sqlInsert = "INSERT INTO carrinho (id_produto, quantidade, totalValor, mesa) VALUES (?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("iidi", $data['id_produto'], $data['quantidade'], $data['totalValor'], $data['mesa']);

        if ($stmtInsert->execute()) {
            echo json_encode(["success" => true, "message" => "Produto adicionado ao carrinho."]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Erro ao adicionar produto ao carrinho."]);
        }
        $stmtInsert->close();
    }

    $stmtCheck->close();
    $conn->close();
    ob_end_flush();
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['id_produto'], $input['mesa'], $input['quantidade'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Dados insuficientes para atualizar o carrinho."]);
        exit;
    }

    $id_produto = intval($input['id_produto']);
    $mesa = intval($input['mesa']);
    $quantidade = intval($input['quantidade']);

    // Buscar o preço unitário do produto
    $sqlPreco = "SELECT valor FROM sofistia.produtos p WHERE id = ?";
    $stmtPreco = $conn->prepare($sqlPreco);
    $stmtPreco->bind_param("i", $id_produto);
    $stmtPreco->execute();
    $resultPreco = $stmtPreco->get_result();

    if ($resultPreco->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Produto não encontrado."]);
        exit;
    }

    $produto = $resultPreco->fetch_assoc();
    $preco_unitario = floatval($produto['valor']);
    $totalValor = $quantidade * $preco_unitario;

    $sqlUpdate = "UPDATE carrinho SET quantidade = ?, totalValor = ? WHERE id_produto = ? AND mesa = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("idii", $quantidade, $totalValor, $id_produto, $mesa);

    if ($stmtUpdate->execute()) {
        echo json_encode(["success" => true, "message" => "Produto atualizado com sucesso no carrinho."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao atualizar o carrinho.", "erro" => $conn->error]);
    }

    $stmtPreco->close();
    $stmtUpdate->close();
    $conn->close();
    ob_end_flush();
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);

    $id_produto = $data['id_produto'] ?? $_GET['id_produto'] ?? null;
    $id_mesa = $data['id_mesa'] ?? $_GET['id_mesa'] ?? null;

    if ($id_produto && $id_mesa) {
        $stmt = $conn->prepare("DELETE FROM carrinho WHERE id_produto = ? AND mesa = ?");
        $stmt->execute([$id_produto, $id_mesa]);

        echo json_encode(["success" => true, "message" => "Item removido com sucesso"]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Parâmetros id_produto e id_mesa são obrigatórios"]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
