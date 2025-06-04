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
        m.numero as numero_mesa
    FROM pedido p
    INNER JOIN produtos p2 ON p.idProduto = p2.id
    INNER JOIN mesa m ON m.id = p.idMesa
    INNER JOIN status s ON p.id_status = s.id";

    if (!empty($filtro) && empty($id)) {
        $sql .= " WHERE p2.nome ILIKE $1";
        $result = pg_query_params($conn, $sql, ["%$filtro%"]);
    } else if (empty($filtro) && !empty($id)) {
        $sql .= " WHERE p.id = $1";
        $result = pg_query_params($conn, $sql, [$id]);
    } else {
        $result = pg_query($conn, $sql);
    }

    $data = [];
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode([
        "data" => $data,
        "totalCount" => count($data),
        "summary" => null,
        "groupCount" => null,
        'success' => true
    ]);

    pg_close($conn);

} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['numeroMesa'], $data['idProduto'])) {
        echo json_encode(["success" => false, "message" => "Dados incompletos."]);
        exit;
    }

    $numeroMesa = $data['numeroMesa'];
    $idProduto = $data['idProduto'];
    $idStatus = 2;

    $resMesa = pg_query_params($conn, "SELECT id FROM mesa WHERE numero = $1 LIMIT 1", [$numeroMesa]);

    if ($resMesa && pg_num_rows($resMesa) > 0) {
        $row = pg_fetch_assoc($resMesa);
        $idMesa = $row['id'];

        $insert = "INSERT INTO pedido (idMesa, idProduto, id_status) VALUES ($1, $2, $3)";
        $resInsert = pg_query_params($conn, $insert, [$idMesa, $idProduto, $idStatus]);

        if ($resInsert) {
            echo json_encode(["success" => true, "message" => "Pedido inserido com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao inserir pedido."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Mesa com número $numeroMesa não encontrada."]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$id || empty($data)) {
        echo json_encode(["success" => false, "message" => "ID ou dados de atualização ausentes."]);
        exit;
    }

    $fields = [];
    $values = [];
    $i = 1;

    foreach ($data as $key => $val) {
        if (!empty($val)) {
            $fields[] = "$key = $$i";
            $values[] = trim($val);
            $i++;
        }
    }

    $values[] = $id;
    $sql = "UPDATE pedido SET " . implode(', ', $fields) . " WHERE id = $$i";

    $res = pg_query_params($conn, $sql, $values);

    if ($res) {
        echo json_encode(['message' => 'Atualização bem-sucedida.']);
    } else {
        echo json_encode(['error' => 'Erro na atualização.']);
    }

} else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['mesa'])) {
        $mesa = intval($data['mesa']);

        $res1 = pg_query_params($conn, "DELETE FROM pedido WHERE idMesa = $1", [$mesa]);
        $res2 = pg_query_params($conn, "DELETE FROM carrinho WHERE mesa = $1", [$mesa]);

        if ($res1 && $res2) {
            echo json_encode(["success" => true, "message" => "Carrinho e pedido limpos com sucesso."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao limpar carrinho e pedido."]);
        }
    } else if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // Aqui pode-se implementar DELETE por id do pedido, se necessário
        echo json_encode(["success" => false, "message" => "Implementar DELETE por id se necessário."]);
    } else {
        echo json_encode(["success" => false, "message" => "Parâmetro 'mesa' ou 'id' não fornecido."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método HTTP não suportado."]);
}
