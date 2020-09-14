<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

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
    
	User::verifyLogin();
	
	$page = new PageAdmin();
	$page->setTpl("index");
	
});

//rota login - get
$app->get('/admin/login', function() {
    
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("login");
	
});

//rota login - post
$app->post('/admin/login', function() {
    
	User::login($_POST["login"],$_POST["password"]);
	
	header("Location: /admin");
	exit;
	
});

//rota login - logout
$app->get('/admin/logout', function() {
    
	User::logout();
	
	header("Location: /admin/login");
	exit;
	
});

$app->run(); //faz rodar tudo que está na memória

 ?>