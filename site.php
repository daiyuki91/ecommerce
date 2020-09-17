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

?>