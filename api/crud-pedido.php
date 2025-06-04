<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);


header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");


if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : null;
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    $sql = "select 
p.id,
s.status ,
p2.nome ,
p2.descricao ,
m.numero as numero_mesa
from pedido p 
inner join produtos p2 on p.idProduto = p2.id 
inner join mesa m on m.id = p.idMesa 
inner join status s on p.id_status = s.id";

    // Verifica qual parâmetro está presente e ajusta a condição WHERE
    if (!empty($filtro) && empty($id)) {
        $sql .= " WHERE nome LIKE ?";
        $stmt = $conn->prepare($sql);
        $filtro = "%$filtro%";
        $stmt->bind_param("s", $filtro);
    } else if (empty($filtro) && !empty($id)) {
        $sql .= " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id); // Assumindo que o campo id é um número inteiro
    } else {
        // Caso ambos os parâmetros estejam vazios ou ambos presentes, trata conforme necessário
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode(
        ["data" => $result->fetch_all(MYSQLI_ASSOC), "totalCount" => $result->num_rows, "summary" => null, "groupCount" => null, 'success' => true]
    );

    $stmt->close();
    $conn->close();

    // Metodo de inserir dados 
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validação básica
    if (!isset($data['numeroMesa'], $data['idProduto'])) {
        echo json_encode(["success" => false, "message" => "Dados incompletos."]);
        exit;
    }

    $numeroMesa = mysqli_real_escape_string($conn, $data['numeroMesa']);
    $idProduto = mysqli_real_escape_string($conn, $data['idProduto']);
    $idStatus = 2;

    // Busca o ID da mesa a partir do número
    $result = mysqli_query($conn, "SELECT id FROM mesa WHERE numero = '$numeroMesa' LIMIT 1");

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $idMesa = $row['id'];

        // Inserção do pedido
        $sql = "INSERT INTO sofistia.pedido (idMesa, idProduto, idStatus)
                VALUES ('$idMesa', '$idProduto', '$idStatus')";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["success" => true, "message" => "Pedido inserido com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao inserir pedido: " . $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Mesa com número $numeroMesa não encontrada."]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = isset($_GET['id']) && !empty($_GET['id']) ? $_GET['id'] : null;
    $data = json_decode(file_get_contents('php://input'), true);

    $query = "UPDATE sofistia.pedido SET";
    $comma = " ";
    foreach ($data as $key => $val) {
        if (!empty($val)) {
            $query .= $comma . $key . "= '" . mysqli_real_escape_string($conn, trim($val)) . "' ";
            $comma = ", ";
        }
    }
    $query .= "WHERE id = $id";
    // Execute a consulta no banco de dados (use a conexão $conn adequada)
    $result = mysqli_query($conn, $query);
    if ($result) {
        // Operação bem-sucedida
        echo json_encode(array('message' => 'Atualização bem-sucedida.'));
    } else {
        // Erro na consulta
        echo json_encode(array('error' => 'Erro na atualização.'));
    }
} else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['mesa'])) {
        $mesa = intval($data['mesa']);

        // Deleta da tabela pedido
        $stmtPedido = $conn->prepare("DELETE FROM pedido WHERE idMesa = ?");
        $stmtPedido->bind_param("i", $mesa);
        $successPedido = $stmtPedido->execute();

        // Deleta da tabela carrinho
        $stmtCarrinho = $conn->prepare("DELETE FROM carrinho WHERE mesa = ?");
        $stmtCarrinho->bind_param("i", $mesa);
        $successCarrinho = $stmtCarrinho->execute();

        if ($successPedido && $successCarrinho) {
            echo json_encode(["success" => true, "message" => "Carrinho e pedido limpos com sucesso."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao limpar carrinho e pedido."]);
        }
    } else if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // Lógica para deletar por ID, se necessário
    } else {
        echo json_encode(["success" => false, "message" => "Parâmetro 'mesa' ou 'id' não fornecido."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Parâmetro 'mesa' ou 'id' não fornecido."]);
}
