<?php

use \Hcode\Model\User;


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

?>