<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

//rota tela - excluir pedidos (admin)
$app->get("/admin/orders/:idorder/delete", function($idorder){
	
	User::verifyLogin();
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	$order->delete($idorder);
	
	header("Location: /admin/orders");
	exit;
	
});

//rota tela - detalhe do status de um pedido (admin)
$app->get("/admin/orders/:idorder/status", function($idorder){
	
	User::verifyLogin();
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	$page = new PageAdmin();
	
	$page->setTpl("order-status", [
		"order"=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgSuccess'=>Order::getSuccess(),
		'msgError'=>Order::getError()
	]);
	
});

//rota tela - mudando o status de um pedido (admin)
$app->post("/admin/orders/:idorder/status", function($idorder){
	
	User::verifyLogin();
	
	if(!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
		
		Order::setError("Informe o status atual.");
		header("Location: /admin/orders/".$idorder."/status");
		exit;
		
	}
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	$order->setidstatus((int)$_POST['idstatus']);
	
	$order->save();
	
	Order::setSuccess("Status atualizado.");
	
	header("Location: /admin/orders/".$idorder."/status");
	exit;
	
});

//rota tela - detalhes de um pedido (admin)
$app->get("/admin/orders/:idorder", function($idorder){
	
	User::verifyLogin();
	
	$order = new Order();
	
	$order->get((int)$idorder);
	
	$cart = $order->getCart();
	
	$page = new PageAdmin();
	
	$page->setTpl("order", [
		"order"=>$order->getValues(),
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts()
	]);
	
});

//rota tela - lista de pedidos (admin)
$app->get("/admin/orders", function(){
	
	User::verifyLogin();
	
	$page = new PageAdmin();
	
	$page->setTpl("orders", [
		"orders"=>Order::listAll()
	]);
	
});

?>