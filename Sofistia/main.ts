function cardapio() {
    // Redirecionar para a próxima tela (exemplo: cardapio.html)
    window.location.href = "cardapio.html";
  }
  
  // Futuramente você pode carregar os pratos dinamicamente aqui.
console.log("Tela de cardápio carregada");

const botao = document.getElementById('meuBotao') as HTMLButtonElement;
botao.addEventListener('click', cardapio);

// cardapio.js
document.querySelector('.call-waiter-btn').addEventListener('click', function () {
    alert('O garçom foi chamado!');
  });
  