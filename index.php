<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;

$app = new Slim();

$app->config('debug', true);

//rota principal
$app->get('/', function() {
    
	/*
	//teste inicial
	$sql = new Hcode\DB\Sql();
	$results = $sql->select("SELECT * FROM tb_users");
	echo json_encode($results);
	*/
	
	$page = new Page();
	$page->setTpl("index");
	
});

//rota admin
$app->get('/admin', function() {
    
	$page = new PageAdmin();
	$page->setTpl("index");
	
});

$app->run(); //faz rodar tudo que está na memória

 ?>