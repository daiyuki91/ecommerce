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
		
	}
	
}

?>