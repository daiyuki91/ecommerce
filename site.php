<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

//rota principal
$app->get('/', function() {
    
	$products = Product::listAll();
	
	$page = new Page();
	$page->setTpl("index", array(
		'products'=>Product::checkList($products)
	));
	
});


//rota tela - categorias (MOSTRAR a categoria)
$app->get("/categories/:idcategory", function($idcategory) {
	
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$pagination = $category->getProductPage($page);
	
	$pages = [];
	
	for ($i=1; $i <= $pagination['pages']; $i++) {
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}
	
	$page = new Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);
	
});

//rota tela - produtos
$app->get("/products/:desurl", function($desurl){
	
	$product = new Product();
	
	$product->getFromURL($desurl); //na função getFromURL, a instrução "$this->setData" carrega os dados para o próprio objeto
	
	$page = new Page();
	$page->setTpl("product-detail", array(
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	));
	
});

//rota tela - cart
$app->get("/cart", function(){
	
	
	$cart = Cart::getFromSession();
		
	$page = new Page();
	
	$page->setTpl("cart", [
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>Cart::getMsgError()
	]);
	
});

//rota tela - cart (ADICIONAR produtos no carrinho)
$app->get("/cart/:idproduct/add", function($idproduct){
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$cart = Cart::getFromSession(); //retoma o carrinho da sessão
	
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
	
	for ($i = 0; $i < $qtd; $i++) {
		
		$cart->addProduct($product);
		
	}
	
	header("Location: /cart");
	exit;
	
});

//rota tela - cart (REMOVER UM ITEM do produto no carrinho)
$app->get("/cart/:idproduct/minus", function($idproduct){
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$cart = Cart::getFromSession(); //retoma o carrinho da sessão
	
	$cart->removeProduct($product); //por padrão, só remove um item
	
	header("Location: /cart");
	exit;
	
});

//rota tela - cart (REMOVER TODOS ITENS de um produto no carrinho)
$app->get("/cart/:idproduct/remove", function($idproduct){
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$cart = Cart::getFromSession(); //retoma o carrinho da sessão
	
	$cart->removeProduct($product, true); //remove todos os itens de um produto
	
	header("Location: /cart");
	exit;
	
});

//rota tela - cart (CALCULAR FRETE)
$app->post("/cart/freight", function(){
	
	$cart = Cart::getFromSession(); //retoma o carrinho da sessão
	
	$cart->setFreight($_POST['zipcode']);
	
	header("Location: /cart");
	exit;
	
});

//rota tela - checkout (?)
$app->get("/checkout", function(){
	
	User::verifyLogin(false);
	
	$address = new Address();
	$cart = Cart::getFromSession();
	
	if (isset($_GET['zipcode'])) {
		
		$_GET['zipcode'] = $cart->getdeszipcode();
		
	}
	
	if (isset($_GET['zipcode'])) {
		
		$address->loadFromCEP($_GET['zipcode']); //objeto carregado com o endereço completo
		
		$cart->setdeszipcode($_GET['zipcode']);
		
		$cart->save();
		
		$cart->getCalculateTotal();
		
	}
	
	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');
	
	$page = new Page();
	
	$page->setTpl("checkout", [
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>Address::getMsgError()
	]);
	
});

//rota tela - 
$app->post("/checkout", function(){
	
	User::verifyLogin(false);
	
	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === "") {
		Address::setMsgError("Informe o CEP.");
		header("Location: /checkout");
		exit;
	}
	
	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === "") {
		Address::setMsgError("Informe o endereço.");
		header("Location: /checkout");
		exit;
	}
	
	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === "") {
		Address::setMsgError("Informe o bairro.");
		header("Location: /checkout");
		exit;
	}
	
	if (!isset($_POST['descity']) || $_POST['descity'] === "") {
		Address::setMsgError("Informe a cidade.");
		header("Location: /checkout");
		exit;
	}
	
	if (!isset($_POST['desstate']) || $_POST['desstate'] === "") {
		Address::setMsgError("Informe o estado.");
		header("Location: /checkout");
		exit;
	}
	
	if (!isset($_POST['descountry']) || $_POST['descountry'] === "") {
		Address::setMsgError("Informe o país.");
		header("Location: /checkout");
		exit;
	}
	
	$user = user::getFromSession();
	
	$address = new Address();
	
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();
	
	$address->setData($_POST);
	
	$address->save();
	
	$cart = Cart::getFromSession();
	
	$cart->getCalculateTotal();
	
	$order = new Order();
	
	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO, //constante
		'vltotal'=>$cart->getvltotal()
	]);
	
	$order->save();
	
	header("Location: /order/".$order->getidorder());
	exit;
	
});


//rota tela - login (cliente)
$app->get("/login", function(){
	
	$page = new Page();
	
	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'','email'=>'','phone'=>'']
	]);
	
});

//rota tela - login (cliente) - recebendo os dados do formulário pelo método post
$app->post("/login", function(){
	
	try {
		
		User::login($_POST['login'], $_POST['password']);
		
	} catch(Exception $e) {
		
		User::setError($e->getMessage());
		
	}
	
	header("Location: /checkout");
	exit;
	
});

//rota tela - logout (cliente)
$app->get("/logout", function(){
	
	user::logout();
	
	header("Location: /login");
	exit;
	
});

//rota - login (cliente) - novo cadastro
$app->post("/register", function(){
	
	$_SESSION['registerValues'] = $_POST;
	
	if (!isset($_POST['name']) || $_POST['name'] == '') {
		
		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;
		
	}
	
	if (!isset($_POST['email']) || $_POST['email'] == '') {
		
		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;
		
	}
	
	if (!isset($_POST['password']) || $_POST['password'] == '') {
		
		User::setErrorRegister("Preencha a sua senha.");
		header("Location: /login");
		exit;
		
	}
	
	if (User::checkLoginExist($_POST['email']) === true) {
		
		User::setErrorRegister("Este endereço de e-mail já está sendo utilizado por outro usuário.");
		header("Location: /login");
		exit;
		
	}
	
	$user = new User();
	
	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nphone'=>$_POST['phone']
	]);
	
	$user->save();
	
	User::login($_POST['email'], $_POST['password']);
	
	header("Location: /checkout");
	exit;
});

//////////////////////////////////////////////////////////////////////////////////////////////////////

//rota tela - forgot (solicitando a recuperação de senha)
$app->get("/forgot", function() {

	$page = new Page();
	
	$page->setTpl("forgot");

});

//rota tela - forgot-sent (coletando o email de recuperação)
$app->post("/forgot", function() {
	
	$user = User::getForgot($_POST["email"], false);
	
	header("Location: /forgot/sent");
	exit;
	
});

//rota tela - forgot-sent (confirmação do envio do email de redefinição de senha)
$app->get("/forgot/sent", function() {
	
	$page = new Page();
	
	$page->setTpl("forgot-sent");
	
});

//rota tela - forgot-reset (redefinição de senha)
$app->get("/forgot/reset", function() {
	
	$user = User::validForgotDecrypt($_GET["code"]);
	
	
	$page = new Page();
	
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
	
});

//rota tela - forgot-reset (informar a nova senha)
$app->post("/forgot/reset", function() {
	
	$forgot = User::validForgotDecrypt($_POST["code"]);
	
	//registrar a solicitação da mudança de senha para verificar se o link de recuperação foi usado dentro de 1 hora
	User::setForgotUsed($forgot["idrecovery"]);
	
	//carregando os dados do usuário
	$user = new User();
	$user->get((int)$forgot["iduser"]);
	
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);
	
	//este método é usado para criar um hash para a senha
	$user->setPassword($password);
	
	//confirmação visual da alteração da senha
	$page = new Page();
	
	$page->setTpl("forgot-reset-success");
	
});

//////////////////////////////////////////////////////////////////////////////////////////////////////

//rota - profile (dados cadastrais do cliente)
$app->get("/profile", function(){
	
	User::verifyLogin(false);
	
	$user = User::getFromSession();
	
	$page = new Page();
	
	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);
	
});

//rota - profile (dados cadastrais do cliente)
$app->post("/profile", function(){
	
	User::verifyLogin(false);
	
	
	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setError("Preencha o seu nome.");
		header("Location: /profile");
		exit;
	}
	
	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setError("Preencha o seu e-mail.");
		header("Location: /profile");
		exit;
	}
	
	$user = User::getFromSession();
	
	if ($_POST['desemail'] !== $user->getdesemail()) {
		
		if (User::checkLoginExist($_POST['desemail']) === true) {
			User::setError("Este e-mail já está cadastrado.");
			header("Location: /profile");
			exit;
		}
		
	}
	
	//proteger o sistema (evitar ataque do tipo injection)
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];
	//----------------------------------------------------
	
	$user->setData($_POST);
	
	$user->update();
	
	User::setSuccess("Dados alterados com sucesso!");
	
	header("Location: /profile");
	exit;
	
});

//rota tela - order (cliente)
$app->get("/order/:idorder", function($idorder){
	
	User::verifyLogin(false);
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	$page = new Page();
	
	$page->setTpl("payment", [
		"order"=>$order->getValues()
	]);
	
});

//rota tela - boleto (cliente)
$app->get("/boleto/:idorder", function($idorder){
	
	User::verifyLogin(false);
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
	$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " ". $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - ". $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";
	
	
	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
	
});

//rota tela - my orders (cliente) - meus pedidos
$app->get("/profile/orders", function(){
	
	User::verifyLogin(false);
	
	$user = User::getFromSession();
	
	$page = new Page();
	
	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);
	
});

//rota tela - profile-details
$app->get("/profile/orders/:idorder", function($idorder){
	
	User::verifyLogin(false);
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	$cart = new Cart();
	
	$cart->get((int)$order->getidcart());
	
	$page = new Page();
	
	$page->setTpl("profile-orders-detail", [
		'order'=>$order->getValues(),
		'products'=>$cart->getProducts(),
		'cart'=>$cart->getValues()
	]);
	
});

//rota tela - alterar senha (cliente)
$app->get("/profile/change-password", function(){
	
	User::verifyLogin(false);
	
	$page = new page();
	
	$page->setTpl("profile-change-password", [
		"changePassError"=>User::getError(),
		"changePassSuccess"=>User::getSuccess()
	]);
	
});

//rota tela - alterar senha (cliente)
$app->post("/profile/change-password", function(){
	
	User::verifyLogin(false);
	
	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === "" ) {
		
		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;
		
	}
	
	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === "" ) {
		
		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;
		
	}
	
	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === "" ) {
		
		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;
		
	}
	
	if ( $_POST['current_pass'] === $_POST['new_pass'] ) {
		
		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;
		
	}
	
	if ( $_POST['new_pass'] !== $_POST['new_pass_confirm'] ) {
		
		User::setError("A confirmação da senha deve ser igual a nova senha.");
		header("Location: /profile/change-password");
		exit;
		
	}
	
	$user = User::getFromSession();
	
	if (!password_verify($_POST['current_pass'], $user->getdespassword())) {
		
		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;
		
	}
	
	$newPasswordHash = User::getPasswordHash($_POST['new_pass']);
	$user->setdespassword($newPasswordHash);
	$_SESSION[User::SESSION] = $user->getValues(); //gravar as alterações na sessão
	
	User::setSuccess("Senha alterada com sucesso.");
	header("Location: /profile/change-password");
	exit;
	
});

?>