<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;

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
		"products"=>$cart->getProducts()
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

?>