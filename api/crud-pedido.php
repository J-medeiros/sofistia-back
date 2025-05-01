<?php
define("BASE_PATH", __DIR__ . '/../conection.php');
require_once(BASE_PATH);


header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : null;
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    $sql = "SELECT 
    p.id,
    p.idMesa,
    m.numero ,
    m.responsavel ,
    p.status,
    pr.nome AS produto_nome,
    pr.valor
FROM sofistia.pedido p
INNER JOIN sofistia.produtos pr ON p.idProduto = pr.id
inner join sofistia.mesa m on p.idMesa = m.id";

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

    $sql = "INSERT INTO sofistia.pedido(idMesa, idProduto, status) VALUES 
    ('{$data['idMesa']}', '{$data['idProduto']}', '{$data['status']}');";

    if ($conn->query($sql) === TRUE) {
        $message = "Inserido com sucesso!!";
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }

    // Metodo de editar dados
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
    // Verifica se foi passado um ID via query string
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("DELETE FROM pedido WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Serviço excluído com sucesso."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir Serviço."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "ID do Serviço não fornecido."]);
    }
} else {
    echo json_encode(["error" => "Método inválido"]);
}
