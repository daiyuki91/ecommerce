<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;

//rota tela - categorias
$app->get("/admin/categories", function() {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	//criando a lista de categorias
	$categories = Category::listAll();
	
	$page = new PageAdmin();
	
	$page->setTpl("categories", array(
		"categories"=>$categories
	));
	
});

//rota tela - categorias (abrindo a página para criar uma nova categoria)
$app->get("/admin/categories/create", function() {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$page = new PageAdmin();
	
	$page->setTpl("categories-create");
	
});

//rota tela - categorias (criando uma nova categoria)
$app->post("/admin/categories/create", function() {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	$category->setData($_POST); //class Model
	$category->save();
	
	header("Location: /admin/categories");
	exit;
	
});

//rota tela - categorias (apagando uma categoria)
$app->get("/admin/categories/:idcategory/delete", function($idcategory) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	$category->delete();
	
	header("Location: /admin/categories");
	exit;
	
});

//rota tela - categorias (editando uma categoria)
$app->get("/admin/categories/:idcategory", function($idcategory) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$page = new PageAdmin();
	
	$page->setTpl("categories-update", array(
		"category"=>$category->getValues()		//converter o objeto em dados em forma de array
	));
	
});

//rota tela - categorias (editando uma categoria)
$app->post("/admin/categories/:idcategory", function($idcategory) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$category->setData($_POST); //carrega os dados que vieram através do método post (dados dos formulários)
	
	$category->save();
	
	header("Location: /admin/categories");
	exit;
	
});


//rota tela - categorias (listar produtos de uma determinada categoria)
$app->get("/admin/categories/:idcategory/products", function($idcategory) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$page = new PageAdmin();
	$page->setTpl("categories-products", array(
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(true),	//por padrão, recebe o valor "true" e lista os produtos relacionados a esta categoria
		'productsNotRelated'=>$category->getProducts(false)
	));
	
});

//rota tela - categorias (adicionar produtos em uma determinada categoria)
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$category->addProduct($product);
	
	header("Location: /admin/categories/".$idcategory."/products");
	exit;
	
});

//rota tela - categorias (remover produtos de uma determinada categoria)
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$product = new Product();
	
	$product->get((int)$idproduct);
	
	$category->removeProduct($product);
	
	header("Location: /admin/categories/".$idcategory."/products");
	exit;
	
});

?>