<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

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

?>