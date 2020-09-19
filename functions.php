<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;


//funções criadas no escopo global para serem usadas em todo o sistema

function formatPrice($vlprice)
{
	
	if (!$vlprice > 0) $vlprice = 0;
	
	return number_format($vlprice, 2, ",", ".");
	
}

function checkLogin($inadmin = true)
{
	
	return User::checkLogin($inadmin);
	
}

function getUserName()
{
	
	$user = User::getFromSession(); //carregar o usuário logado na sessão
	
	return $user->getdesperson();
	
}

function getCartNrQtd()
{
	
	$cart = Cart::getFromSession();
	
	$totals = $cart->getProductsTotals();
	
	return $totals['nrqtd'];
	
}

function getCartVlSubTotal()
{
	
	$cart = Cart::getFromSession();
	
	$totals = $cart->getProductsTotals();
	
	return formatPrice($totals['vlprice']); //total do carrinho sem o custo do frete
	
}

function analisarParam($param)
{
	
	var_dump($param); echo "<br>"; echo "<br>";
	
}

?>