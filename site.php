<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

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
	
	User::verifyLogin(false);
	
	$user = user::getFromSession();
	
	$address = new Address();
	
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();
	
	$address->setData($_POST);
	
	$address->save();
	
	header("Location: /order");
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

?>