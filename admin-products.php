<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;

//rota tela - produtos
$app->get("/admin/products", function() {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	//filtro a partir do search
	if ($search != '') {
		
		$pagination = Product::getPageSearch($search, $page);
		
	} else {
		
		$pagination = Product::getPage($page);
		
	}
	
	$pages = [];
	
	for ($x = 0; $x < $pagination['pages']; $x++) {
		
		array_push($pages, [
			'href'=>'/admin/products?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
		
	}
	
	$page = new PageAdmin();
	
	$page->setTpl("products", array(
		"products"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	));
	
});

//rota tela - produtos (abrindo a página para criar um novo produto)
$app->get("/admin/products/create", function() {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$page = new PageAdmin();
	
	$page->setTpl("products-create");
	
});

//rota tela - produtos (criando uma nova produto)
$app->post("/admin/products/create", function() {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$product = new Product();
	$product->setData($_POST); //class Model
	
	$product->save();
	
	header("Location: /admin/products");
	exit;
	
});

//rota tela - produtos (editando um produto)
$app->get("/admin/products/:idproduct", function($idproduct) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$page = new PageAdmin();
	
	$page->setTpl("products-update", array(
		"product"=>$product->getValues()		//converter o objeto em dados em forma de array
	));
	
});

//rota tela - produtos (editando um produto)
$app->post("/admin/products/:idproduct", function($idproduct) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$product->setData($_POST); //carrega os dados que vieram através do método post (dados dos formulários)
	
	$product->save();
	
	$product->setPhoto($_FILES["file"]); //receber as entradas de arquivos
	
	header("Location: /admin/products");
	exit;
	
});

//rota tela - produtos (apagando um produto)
$app->get("/admin/products/:idproduct/delete", function($idproduct) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	$product->delete();
	
	header("Location: /admin/products");
	exit;
	
});


/*



//rota tela - produtos (mostrando o produto)
$app->get("/products/:idcategory", function($idcategory) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$page = new Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>[]
	]);
	
});
*/

?>