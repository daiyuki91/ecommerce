<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Category extends Model {
	
	
	public static function listAll()
	{
		
		$sql = new Sql();
		
		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory;");
		
	}
	
	public function save()
	{
		
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_user_save
			pidcategory INT,
			pdescategory VARCHAR(64)
		*/
		
		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)",
			array(
				":idcategory"=>$this->getidcategory(),
				":descategory"=>$this->getdescategory()
				)
		); //chamar uma procedure para inserir um novo usuário (só com uma chamada, requisição)
		
		$this->setData($results[0]);
		
		Category::updateFile(); //atualizar o arquivo html com a lista de categorias
		
	}
	
	public function get($idcategory)
	{
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory",
			array(
				":idcategory"=>$idcategory
			)
		);
		
		$this->setData($results[0]); //inserir o resultado no objeto
		
	}
	
	public function delete()
	{
		
		$sql = new Sql();
		
		$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory",
			array(
				":idcategory"=>$this->getidcategory()
			)
		);
		
		Category::updateFile(); //atualizar o arquivo html com a lista de categorias
		
	}
	
	//método para ATUALIZAR a lista de categorias do arquivo "categories-menu.html"
	public static function updateFile()
	{
		
		$categories = Category::listAll(); //trazer todas as categorias que estão no banco de dados
		
		$html = [];

		foreach ($categories as $row) {
			array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
		}
		
		//função implode para um array se tornar uma string
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode('', $html));
		
	}
	
	//método para TRAZER todos os produtos de uma categoria
	public function getProducts($related = true)
	{
		
		$sql = new Sql();
		
		if ($related === true) {
			
			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct IN (
					SELECT a.idproduct 
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				":idcategory"=>$this->getidcategory()
			]);
			
		} else {
			
			return $sql->select("
				SELECT * FROM tb_products WHERE idproduct NOT IN (
					SELECT a.idproduct 
					FROM tb_products a
					INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory
				);
			", [
				":idcategory"=>$this->getidcategory()
			]);
			
		}
		
	}
	
	public function getProductPage($page = 1, $itemsPerPage = 3)
	{
		
		$start = ($page-1) * $itemsPerPage;
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itemsPerPage;
		", array(
			":idcategory"=>$this->getidcategory()
		));
		
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		
		return [
			'data'=>Product::checkList($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) //função do PHP para arredondar para cima
		];
	}
	
	//método para ADICIONAR um produto em uma categoria
	public function addProduct(Product $product)
	{
		
		$sql = new Sql();
		
		$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES(:idcategory, :idproduct)",
			array(
				":idcategory"=>$this->getidcategory(),
				":idproduct"=>$product->getidproduct()
			)
		);
		
	}
	
	//método para REMOVER um produto em uma categoria
	public function removeProduct(Product $product)
	{
		
		$sql = new Sql();
		
		$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct",
			array(
				":idcategory"=>$this->getidcategory(),
				":idproduct"=>$product->getidproduct()
			)
		);
		
	}
	
}

?>