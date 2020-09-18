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
	
	$cart = Cart::getFromSession();
	
	$address = new Address();
	
	$page = new Page();
	
	$page->setTpl("checkout", [
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues()
	]);
	
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

?>