<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

//rota tela - alterar senha de um usuário
$app->get('/admin/users/:iduser/password', function($iduser) {
	
	User::verifyLogin();
	
	$user = new User();
	
	$user->get((int)$iduser);
	
	$page = new PageAdmin();
	
	$page->setTpl("users-password", [
		"user"=>$user->getValues(),
		"msgError"=>User::getError(),
		"msgSuccess"=>User::getSuccess()
	]);
	
});

//rota tela - alterar senha de um usuário (gravação dos novos dados)
$app->post('/admin/users/:iduser/password', function($iduser) {
	
	User::verifyLogin();
	
	if (!isset($_POST['despassword']) || $_POST['despassword'] === "" ) {
		
		User::setError("Preencha a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
		
	}
	
	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === "" ) {
		
		User::setError("Confirme a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
		
	}
	
	if ( $_POST['despassword'] !== $_POST['despassword-confirm'] ) {
		
		User::setError("Confirme corretamente as senhas.");
		header("Location: /admin/users/$iduser/password");
		exit;
		
	}
	
	$user = new User();
	
	$user->get((int)$iduser);
	
	$newPasswordHash = User::getPasswordHash($_POST['despassword']);
	$user->setPassword($newPasswordHash);
	
	User::setSuccess("Senha alterada com sucesso.");
	header("Location: /admin/users/$iduser/password");
	exit;
	
});

//rota tela - listar todos os usuários
$app->get('/admin/users', function() {
    
	User::verifyLogin(); //verificar se está logado no admin
	
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	//filtro a partir do search
	if ($search != '') {
		
		$pagination = User::getPageSearch($search, $page);
		
	} else {
		
		$pagination = User::getPage($page);
		
	}
	
	$pages = [];
	
	for ($x = 0; $x < $pagination['pages']; $x++) {
		
		array_push($pages, [
			'href'=>'/admin/users?'.http_build_query([
				'page'=>$x+1,
				'search'=>$search
			]),
			'text'=>$x+1
		]);
		
	}
	
	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
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