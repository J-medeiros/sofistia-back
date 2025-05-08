# 🐘 Backend - Sistema de Sofistia (PHP)

Este é o backend **simples e direto** do sistema de pedidos, desenvolvido em **PHP puro**. Ele é responsável por fornecer e gerenciar os dados dos pedidos (exemplo: buscar, criar, atualizar status).

---

## ✅ Pré-requisitos

- **WampServer / XAMPP / MAMP (ou outro servidor local PHP).**
  - 👉 Baixe em: https://www.wampserver.com/
  - 👉 Ou XAMPP: https://www.apachefriends.org/

---

## 📂 Estrutura dos arquivos

backend/
├── crud-cozinha.php
├── crud-mesa.php
├── crud-pedido.php
├── crud-produtos.php
└── conection.php


## 🚀 Como rodar

### 1️⃣ Coloque o backend no diretório do seu servidor local

- Se estiver usando **WampServer**, copie a pasta `backend` para:  
  `C:\wamp64\www\desenvolvimento-back-end/api/crud-produtos.php`

- Se estiver usando **XAMPP**, copie para:  
  `C:\xampp\htdocs\desenvolvimento-back-end/api/crud-produtos.php`

---

### 2️⃣ Inicie o servidor

- Abra o **Wamp/XAMPP** e inicie o **Apache** (e MySQL, se usar banco depois).

---

### 3️⃣ Teste os endpoints

- ✅ Testar busca de pedidos:
  
  👉 Abra o navegador e acesse:  
  `http://localhost/desenvolvimento-back-end/api/crud-produtos.php`

- ✅ Testar criação (via Postman ou extensão como Thunder Client):

  **POST** `http://localhost/desenvolvimento-back-end/api/crud-produtos.php`  
  Body (form-data ou JSON):

  ```json
  {
    "nome": "Novo Pedido",
    "description": "Pedido teste via API"
  }

❗ Problemas comuns
Erro 404?
👉 Verifique se colocou na pasta correta (www ou htdocs).

Sem resposta?
👉 Veja se o Apache está rodando e não há firewall bloqueando.

CORS bloqueando?
👉 Em todos os arquivos PHP, adicione no topo:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
```

✨ Melhorias futuras
* Conectar com banco de dados (MySQL).

* Validações e autenticação.

* Organização com rotas e controllers.
