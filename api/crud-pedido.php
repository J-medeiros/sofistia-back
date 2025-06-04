<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $filtro = $_GET['filtro'] ?? null;
    $id = $_GET['id'] ?? null;

    $sql = "SELECT 
                p.id,
                s.status,
                p2.nome,
                p2.descricao,
                m.numero AS numero_mesa
            FROM pedido p
            INNER JOIN produtos p2 ON p.idproduto = p2.id
            INNER JOIN mesa m ON m.id = p.idMesa
            INNER JOIN status s ON p.id_status = s.id";

    if (!empty($filtro) && empty($id)) {
        $sql .= " WHERE p2.nome ILIKE :filtro";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':filtro', "%$filtro%", PDO::PARAM_STR);
    } elseif (!empty($id)) {
        $sql .= " WHERE p.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    } else {
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data" => $result,
        "totalCount" => count($result),
        "summary" => null,
        "groupCount" => null,
        "success" => true
    ]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['numeroMesa'], $data['idproduto'])) {
        echo json_encode(["success" => false, "message" => "Dados incompletos."]);
        exit;
    }

    $numeroMesa = $data['numeroMesa'];
    $idproduto = $data['idproduto'];
    $idStatus = 2;

    $stmtMesa = $conn->prepare("SELECT id FROM mesa WHERE numero = :numero LIMIT 1");
    $stmtMesa->bindValue(':numero', $numeroMesa, PDO::PARAM_STR);
    $stmtMesa->execute();
    $mesa = $stmtMesa->fetch(PDO::FETCH_ASSOC);

    if ($mesa) {
        $idMesa = $mesa['id'];

        $stmtInsert = $conn->prepare("INSERT INTO pedido (idMesa, idproduto, id_status) VALUES (:idMesa, :idproduto, :idStatus)");
        $stmtInsert->bindValue(':idMesa', $idMesa, PDO::PARAM_INT);
        $stmtInsert->bindValue(':idproduto', $idproduto, PDO::PARAM_INT);
        $stmtInsert->bindValue(':idStatus', $idStatus, PDO::PARAM_INT);

        if ($stmtInsert->execute()) {
            echo json_encode(["success" => true, "message" => "Pedido inserido com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao inserir pedido."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Mesa com número $numeroMesa não encontrada."]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str($_SERVER['QUERY_STRING'], $query);
    $id = $query['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$id || empty($data)) {
        echo json_encode(["success" => false, "message" => "ID ou dados não fornecidos."]);
        exit;
    }

    $updates = [];
    foreach ($data as $key => $value) {
        $updates[] = "$key = :$key";
    }
    $sql = "UPDATE pedido SET " . implode(", ", $updates) . " WHERE id = :id";
    $stmt = $conn->prepare($sql);

    foreach ($data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Atualização bem-sucedida."]);
    } else {
        echo json_encode(["success" => false, "message" => "Erro na atualização."]);
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $data = json_decode(file_get_contents("php://input"), true);
    $mesa = $data['mesa'] ?? null;
    $id = $_GET['id'] ?? null;

    if ($mesa) {
        $stmt1 = $conn->prepare("DELETE FROM pedido WHERE idMesa = :mesa");
        $stmt1->bindValue(':mesa', $mesa, PDO::PARAM_INT);
        $success1 = $stmt1->execute();

        $stmt2 = $conn->prepare("DELETE FROM carrinho WHERE mesa = :mesa");
        $stmt2->bindValue(':mesa', $mesa, PDO::PARAM_INT);
        $success2 = $stmt2->execute();

        if ($success1 && $success2) {
            echo json_encode(["success" => true, "message" => "Carrinho e pedido limpos com sucesso."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao limpar carrinho e pedido."]);
        }
    } elseif ($id) {
        $stmt = $conn->prepare("DELETE FROM pedido WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Pedido deletado com sucesso."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao deletar pedido."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Parâmetro 'mesa' ou 'id' não fornecido."]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Método não suportado."]);
}
