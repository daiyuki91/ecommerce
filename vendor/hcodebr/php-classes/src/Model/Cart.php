<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;
use \Hcode\Model\Product;

class Cart extends Model {
	
	const SESSION = "Cart";
	
	//método para VERIFICAR se o carrinho ainda existe nesta sessão
	public static function getFromSession()
	{
		
		$cart = new Cart();
		
		if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
			
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
			
		} else {
			
			$cart->getFromSessionID();
			
			if (!(int)$cart->getidcart() > 0) {
				
				$data = [
					'dessessionid'=>session_id() //função do PHP
				];
				
				if (User::checkLogin(false) === true ) {
				
					$user = User::getFromSession();
					
					$data['iduser'] = $user->getiduser();
					
				}
				
				$cart->setData($data);
				
				$cart->save();
				
				$cart->setToSession();
				
			}
			
		}
		
		return $cart;
		
	}
	
	
	public function setToSession()
	{
		
		$_SESSION[Cart::SESSION] = $this->getValues();
		
	}
	
	
	public function getFromSessionID()
	{
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid",
			array(
				":dessessionid"=>session_id() //funcção do PHP
			)
		);
		
		if (count($results) > 0 ){
		
			$this->setData($results[0]);
			
		}
		
	}
	
	public function get(int $idcart)
	{
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart",
			array(
				":idcart"=>$idcart
			)
		);
		
		if (count($results[0]) > 0 ){
		
			$this->setData($results[0]);
			
		}
	}
	
	public function save()
	{
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_cart_save
			pidcart INT,
			pdessessionid VARCHAR(64),
			piduser INT,
			pdeszipcode CHAR(8),
			pvlfreight DECIMAL(10,2),
			pnrdays INT
		*/
		
		$results = $sql->select("CALL sp_carts_save (:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",
			array(
				":idcart"=>$this->getidcart(),
				":dessessionid"=>$this->getdessessionid(),
				":iduser"=>$this->getiduser(),
				":deszipcode"=>$this->getdeszipcode(),
				":vlfreight"=>$this->getfreight(),
				":nrdays"=>$this->getnrdays()
			)
		);
		
		$this->setData($results[0]); //inserir o resultado (a primeira linha) no objeto
		
		//updateFile
		
	}
	
	//método para ADICIONAR um novo produto no carrinho
	public function addProduct(Product $product) //recebe uma instância da classe Product
	{
		
		$sql = new Sql();
		
		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", 
			array(
				":idcart"=>$this->getidcart(),
				":idproduct"=>$product->getidproduct()
			)
		);
		
	}
	
	//método para REMOVER todos os itens de um produto
	public function removeProduct(Product $product, $all = false)
	{
		
		$sql = new Sql();
		
		//remove todos os itens deste produto
		if ($all) {
			
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL",
				array(
					":idcart"=>$this->getidcart(),
					":idproduct"=>$product->getidproduct()
				)
			);
			
		} else {
			
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1",
				array(
					":idcart"=>$this->getidcart(),
					":idproduct"=>$product->getidproduct()
				)
			);
			
		}
		
	}
	
	//método para OBTER todos os produtos de um carrinho
	public function getProducts()
	{
		
		$sql = new Sql();
		
		$rows = $sql->select("
			SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, vllength, b.vlweight, b.desurl
			ORDER BY b.desproduct
			",
			array(
				":idcart"=>$this->getidcart()
			)
		);
		
		return Product::checkList($rows);
		
	}
	
}

?>