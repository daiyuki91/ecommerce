<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;

//rota principal
$app->get('/', function() {
    
	$products = Product::listAll();
	
	$page = new Page();
	$page->setTpl("index", array(
		'products'=>Product::checkList($products)
	));
	
});


//rota tela - categorias (mostrando a categoria)
$app->get("/categories/:idcategory", function($idcategory) {
	
	$category = new Category();
	
	$category->get((int)$idcategory);
	
	$page = new Page();
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>Product::checkList($category->getProducts()) //por padrão, recebe o valor "true" e lista os produtos relacionados a esta categoria
	]);
	
});

?>