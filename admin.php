<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

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

//////////////////////////////////////////////////////////////

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

?>