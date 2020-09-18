<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model {
	
	const SESSION_ERROR = "AddressError";
	
	public static function getCEP($nrcep)
	{
		
		$nrcep = str_replace("-","",$nrcep);
		
		//http://viacep.com.br/ws/01001000/json/
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$data = json_decode(curl_exec($ch), true); //retornando um array
		
		curl_close($ch);
		
		return $data;
		
	}
	
	public function loadFromCEP($nrcep)
	{
		
		$data = Address::getCEP($nrcep);
		
		if (isset($data['logradouro']) && $data['logradouro']) {
			
			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);
			
		}
		
	}
	
	public function save()
	{
		
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_addresses_save
			pidaddress int(11), 
			pidperson int(11),
			pdesaddress varchar(128),
			pdescomplement varchar(32),
			pdescity varchar(32),
			pdesstate varchar(32),
			pdescountry varchar(32),
			pdeszipcode char(8),
			pdesdistrict varchar(32)
		*/
		
		$results = $sql->select("CALL sp_addresses_save (:idaddress, :idperson, :desaddress, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)",
			array(
				":idaddress"=>$this->getidaddress(),
				":idperson"=>utf8_decode($this->getidperson()),
				":desaddress"=>utf8_decode($this->getdesaddress()),
				":descomplement"=>utf8_decode($this->getdescomplement()),
				":descity"=>utf8_decode($this->getdescity()),
				":desstate"=>utf8_decode($this->getdesstate()),
				":descountry"=>utf8_decode($this->getdescountry()),
				":deszipcode"=>$this->getdeszipcode(),
				":desdistrict"=>utf8_decode($this->getdesdistrict())
			)
		);
		
		if (count($results) > 0) {
			
			$this->setData($results[0]);
			
		}
		
	}
	
	public static function setMsgError($msg)
	{
		
		$_SESSION[Address::SESSION_ERROR] = $msg;
		
	}
	
	public static function getMsgError()
	{
		
		$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";
		
		Address::clearMsgError();
		
		return $msg;
		
	}
	
	public static function clearMsgError()
	{
		
		$_SESSION[Address::SESSION_ERROR] = NULL;
		
	}
	
}

?>