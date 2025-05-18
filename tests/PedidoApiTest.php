<?php

use PHPUnit\Framework\TestCase;

class PedidoApiTest extends TestCase
{
    private $baseUrl = "http://localhost/Sofistia/Desenvolvimento.Sofistia/desenvolvimento-back-end/api/crud-pedido.php";

    public function testGetPedidosRetornaStatus200()
    {
        $response = file_get_contents($this->baseUrl);
        $this->assertNotFalse($response, "A resposta GET falhou.");
        $data = json_decode($response, true);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function testPostCriaPedido()
    {
        $payload = json_encode([
            "idMesa" => 1,       // Ajuste para um ID válido no seu BD
            "idProduto" => 1,    // Ajuste para um ID válido no seu BD
            "status" => "Aberto"
        ]);

        $options = ['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload
        ]];

        $context  = stream_context_create($options);
        $result = file_get_contents($this->baseUrl, false, $context);

        $this->assertNotFalse($result, "POST retornou falso.");

        $data = json_decode($result, true);
        $this->assertEquals('Inserido com sucesso!!', $data['message']);
    }

    public function testPutAtualizaPedido()
    {
        $pedidoId = 1; // Ajuste para um ID válido no seu BD

        $payload = json_encode([
            "status" => "Fechado"
        ]);

        $options = ['http' => [
            'method'  => 'PUT',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload
        ]];

        $context  = stream_context_create($options);
        $url = $this->baseUrl . "?id=$pedidoId";
        $result = file_get_contents($url, false, $context);

        $this->assertNotFalse($result, "PUT retornou falso.");

        $data = json_decode($result, true);
        $this->assertEquals('Atualização bem-sucedida.', $data['message']);
    }

    public function testDeleteRemovePedido()
    {
        $pedidoId = 1; // Ajuste para um ID válido no seu BD

        $options = ['http' => ['method' => 'DELETE']];
        $context = stream_context_create($options);
        $url = $this->baseUrl . "?id=$pedidoId";
        $result = file_get_contents($url, false, $context);

        $this->assertNotFalse($result, "DELETE falhou.");

        $data = json_decode($result, true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Serviço excluído com sucesso.', $data['message']);
    }
}
