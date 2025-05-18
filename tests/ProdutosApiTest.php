<?php

use PHPUnit\Framework\TestCase;

class ProdutosApiTest extends TestCase
{
    private $baseUrl = "http://localhost/Sofistia/Desenvolvimento.Sofistia/desenvolvimento-back-end/api/crud-produtos.php";

    public function testGetProdutosRetornaStatus200()
    {
        $response = file_get_contents($this->baseUrl);
        $this->assertNotFalse($response, "A resposta GET falhou.");
        $data = json_decode($response, true);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function testPostCriaProduto()
    {
        $payload = json_encode([
            "nome" => "Produto Teste",
            "descricao" => "Descrição teste",
            "valor" => 9.99,
            "image" => "https://imagem.com/produto.jpg"
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
        $this->assertEquals('Inserido com sucesso', $data['message']);
    }

    public function testPutAtualizaProduto()
    {
        $produtoId = 1; // Altere conforme o ID que você tem no banco

        $payload = json_encode([
            "nome" => "Produto Atualizado",
            "descricao" => "Descrição atualizada"
        ]);

        $options = ['http' => [
            'method'  => 'PUT',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $payload
        ]];

        $context  = stream_context_create($options);
        $url = $this->baseUrl . "?id=$produtoId";
        $result = file_get_contents($url, false, $context);

        $this->assertNotFalse($result, "PUT retornou falso.");

        $data = json_decode($result, true);
        $this->assertEquals('Atualização bem-sucedida.', $data['message']);
    }

    public function testDeleteRemoveProduto()
    {
        $produtoId = 1; // Altere conforme o ID que você quer remover

        $options = ['http' => ['method' => 'DELETE']];
        $context = stream_context_create($options);
        $url = $this->baseUrl . "?id=$produtoId";
        $result = file_get_contents($url, false, $context);

        $this->assertNotFalse($result, "DELETE falhou.");

        $data = json_decode($result, true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Produto excluído com sucesso.', $data['message']);
    }
}
