<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

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

//rota logout
$app->get('/admin/logout', function() {
    
	User::logout();
	
	header("Location: /admin/login");
	exit;
	
});

//rota tela - listar todos os usuários
$app->get('/admin/users', function() {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	$users = User::listAll(); //array com lista de usuários
	
	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users"=>$users
	));
	
});

//rota tela - criar usuário
$app->get('/admin/users/create', function() {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	$page = new PageAdmin();
	$page->setTpl("users-create");
	
});

//rota tela - excluir usuário
$app->get('/admin/users/:iduser/delete', function($iduser) {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	$user = new User();
	$user->get((int)$iduser);
	
	//deletando usuário
	$user->delete();
	
	header("Location: /admin/users");
	exit;
	
});

//rota tela - alterar usuário
$app->get('/admin/users/:iduser', function($iduser) {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	$user = new User();
	$user->get((int)$iduser);
	
	$page = new PageAdmin();
	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));
	
});

//rota tela - salvar novo usuário
$app->post('/admin/users/create', function() {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	//var_dump($_POST); //visualizar os dados recebidos pelo método "post" (vindo do formulário HTML)
	
	//insert do usuário
	$user = new User();
	
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	
	$user->setData($_POST);	
	$user->save();
	
	header("Location: /admin/users");
	exit;
	
	//var_dump($user); //visualizar a criação dos objetos com o nome dos atributos da tabela
	
	
});

//rota tela - salvar edição do usuário
$app->post('/admin/users/:iduser', function($iduser) {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	//update do usuário
	$user = new User();
	
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();
	
	header("Location: /admin/users");
	exit;
	
});

//rota tela - forgot (solicitando a recuperação de senha)
$app->get("/admin/forgot", function() {

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	
	$page->setTpl("forgot");

});

//rota tela - forgot-sent (coletando o email de recuperação)
$app->post("/admin/forgot", function() {
	
	$user = User::getForgot($_POST["email"]);
	
	header("Location: /admin/forgot/sent");
	exit;
	
});

//rota tela - forgot-sent (confirmação do envio do email de redefinição de senha)
$app->get("/admin/forgot/sent", function() {
	
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	
	$page->setTpl("forgot-sent");
	
});

//rota tela - forgot-reset (redefinição de senha)
$app->get("/admin/forgot/reset", function() {
	
	$user = User::validForgotDecrypt($_GET["code"]);
	
	
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
	
});

//rota tela - forgot-reset (informar a nova senha)
$app->post("/admin/forgot/reset", function() {
	
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
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	
	$page->setTpl("forgot-reset-success");
	
});

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


$app->run(); //faz rodar tudo que está na memória

 ?>