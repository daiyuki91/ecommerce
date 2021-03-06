<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model {
	
	const ITEMS_PER_PAGE = 10;
	
	public static function listAll()
	{
		
		$sql = new Sql();
		
		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct;");
		
	}
	
	public static function checkList($list)
	{
		
		foreach ($list as &$row) {
			
			$p = new Product();
			$p->setData($row);
			$row = $p->getValues();
			
		}
		
		return $list;
		
	}
	
	public function save()
	{
		
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_products_save
			pidproduct int(11),
			pdesproduct varchar(64),
			pvlprice decimal(10,2),
			pvlwidth decimal(10,2),
			pvlheight decimal(10,2),
			pvllength decimal(10,2),
			pvlweight decimal(10,2),
			pdesurl varchar(128)
		*/
		
		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)",
			array(
				":idproduct"=>$this->getidproduct(),
				":desproduct"=>$this->getdesproduct(),
				":vlprice"=>$this->getvlprice(),
				":vlwidth"=>$this->getvlwidth(),
				":vlheight"=>$this->getvlheight(),
				":vllength"=>$this->getvllength(),
				":vlweight"=>$this->getvlweight(),
				":desurl"=>$this->getdesurl()
				)
		); //chamar uma procedure para inserir um novo usuário (só com uma chamada, requisição)
		
		$this->setData($results[0]);
		
	}
	
	public function get($idproduct)
	{
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct",
			array(
				":idproduct"=>$idproduct
			)
		);
		
		$this->setData($results[0]); //inserir o resultado no objeto
		
	}
	
	public function delete()
	{
		
		$sql = new Sql();
		
		$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct",
			array(
				":idproduct"=>$this->getidproduct()
			)
		);
		
	}
	
	public function checkPhoto()
	{
		
		if (file_exists(
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg"
		)) {
			
			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
			
		} else {
			
			$url = "/res/site/img/product.jpg";
			
		}
		
		return $this->setdesphoto($url);
		
	}
	
	public function getValues()
	{
		
		$this->checkPhoto(); //método para verificar se tem a foto deste produto
		
		$values = parent::getValues(); //faz o que a classe pai já faz
		
		return $values;
		
	}
	
	public function setPhoto($file)
	{
		
		$extension = explode('.', $file['name']);
		$extension = end($extension);
		
		switch ($extension) {
			
			case "jpg":
			case "jpeg":
			$image = imagecreatefromjpeg($file["tmp_name"]); //tmp_name é o nome do arquivo temporário criado no servidor
			break;
			
			case "gif":
			$image = imagecreatefromgif($file["tmp_name"]); //tmp_name é o nome do arquivo temporário criado no servidor
			break;
			
			case "png":
			$image = imagecreatefrompng($file["tmp_name"]); //tmp_name é o nome do arquivo temporário criado no servidor
			break;
			
		}
		
		//diretório destino
		$dest = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 
			"res" . DIRECTORY_SEPARATOR . 
			"site" . DIRECTORY_SEPARATOR . 
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR . 
			$this->getidproduct() . ".jpg";
		
		imagejpeg($image, $dest);
		
		imagedestroy($image);
		
		$this->checkPhoto();
		
	}
	
	public function getFromURL($desurl)
	{
		$sql = new Sql();
		
		$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1;",
			array(
				":desurl"=>$desurl
			)
		);
		
		$this->setData($rows[0]);
		
	}
	
	public function getCategories()
	{
		
		$sql = new Sql();
		
		return $sql->select("
			SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct
		", array(
			":idproduct"=>$this->getidproduct()
		));
		
	}
	
	public static function getPage($page = 1, $itemsPerPage = User::ITEMS_PER_PAGE)
	{
		
		$start = ($page-1) * $itemsPerPage;
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products
			ORDER BY desproduct
			LIMIT $start, $itemsPerPage;
		");
		
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		
		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) //função do PHP para arredondar para cima
		];
	}
	
	public static function getPageSearch($search, $page = 1, $itemsPerPage = User::ITEMS_PER_PAGE)
	{
		
		$start = ($page-1) * $itemsPerPage;
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products
			WHERE desproduct LIKE :search 
			ORDER BY desproduct
			LIMIT $start, $itemsPerPage;
		", [
			":search"=>'%'.$search.'%'
		]);
		
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");
		
		return [
			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) //função do PHP para arredondar para cima
		];
		
	}
	
}

?>