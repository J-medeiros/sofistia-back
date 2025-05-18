<?php

use PHPUnit\Framework\TestCase;

class CozinhaApiTest extends TestCase
{
    private string $baseUrl = "http://localhost/Sofistia/Desenvolvimento.Sofistia/desenvolvimento-back-end/api/crud-cozinha.php";

    public function testGetPedidosPendentes()
    {
        $response = file_get_contents($this->baseUrl);
        $data = json_decode($response, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey("data", $data);
        $this->assertArrayHasKey("totalCount", $data);
        $this->assertTrue($data["success"]);

        // Verifica se todos os pedidos retornados têm status = 0
        foreach ($data["data"] as $pedido) {
            $this->assertEquals(0, $pedido["status"]);
        }
    }

    public function testPutAtualizaStatusParaTrue()
    {
        // Pré-requisito: deve existir pelo menos um pedido com status = 0
        $getResponse = file_get_contents($this->baseUrl);
        $getData = json_decode($getResponse, true);

        if (empty($getData["data"])) {
            $this->markTestSkipped("Nenhum pedido com status = false disponível para testar.");
        }

        $pedidoId = $getData["data"][0]["id"];

        $opts = [
            "http" => [
                "method" => "PUT",
                "header" => "Content-Type: application/json"
            ]
        ];

        $context = stream_context_create($opts);
        $putResponse = file_get_contents("{$this->baseUrl}?id={$pedidoId}", false, $context);
        $putData = json_decode($putResponse, true);

        $this->assertIsArray($putData);
        $this->assertTrue($putData["success"]);
        $this->assertEquals("Pedido atualizado para preparado (status true).", $putData["message"]);
    }
}
