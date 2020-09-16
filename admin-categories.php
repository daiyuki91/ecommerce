<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

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

//rota tela - categorias (mostrando a categoria)
$app->get("/categories/:idcategory", function($idcategory) {
	
	User::verifyLogin(); //verificar se está logado no admin
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$page = new Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>[]
	]);
	
});

?>