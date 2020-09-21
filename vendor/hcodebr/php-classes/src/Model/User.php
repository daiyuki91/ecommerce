<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {
	
	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret"; //chave com 16 caracteres
	const SECRET_IV = "HcodePhp7_Secret_IV"; //chave com 16 caracteres IV
	const ERROR = "UserError";
	const ERROR_REGISTER = "UserErrorRegister";
	const SUCCESS = "UserSucess";
	
	const ITEMS_PER_PAGE = 10;
	
	//método para retornar a seção do usuário
	public static function getFromSession()
	{
		
		$user = new User();
		
		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {
			
			$user->setData($_SESSION[User::SESSION]);
			
		}
		
		return $user;
		
	}
	
	//verificar se o usuário está logado (retorna = true/false)
	public static function checkLogin($inadmin = true)
	{
		
		if (
			!isset($_SESSION[User::SESSION])						//esta session não foi definida?
			||
			!$_SESSION[User::SESSION]								//sessão foi definida mas possui valor vazio
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0			//verifica se realmente é um usuário
		) {
			
			//Não está logado ===========
			return false;
			
		} else {
			
			//Está logado ================
			
			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) { 
				
				//rota do adminstrador (precisa verificar se o usuário tem permissão, inadmin = true)
				return true;
				
			} else if ($inadmin === false) { 
				
				//rota que não é do administrador (parte do site que pode ter acesso livre, por exemplo na parte do carrinho)
				return true;
				
			} else {
				
				return false;
				
			}
			
		}
		
	}
	
	public static function login($login, $password) {
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b ON a.idperson = b.idperson WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));
		
		if (count($results) === 0)
		{
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}
		
		$data = $results[0];
		
		if (password_verify($password, $data["despassword"]))
		{
			$user = new User();
			
			$data['desperson'] = utf8_encode($data['desperson']);
			
			$user->setData($data);
			
			/*
			// verificacao
			var_dump($user);
			exit;
			*/
			
			$_SESSION[User::SESSION] = $user->getValues();
			
			return $user;
			
		}
		else{
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}
		
	}
	
	public static function verifyLogin($inadmin = true)
	{
		
		if (!User::checkLogin($inadmin)) {
			
			if ($inadmin) {
				header("Location: /admin/login");
			} else {
				header("Location: /login");
			}
			
			exit;
			
		}
		
	}
	
	public static function logout()
	{
		
		$_SESSION[User::SESSION] = NULL;
		
	}
	
	public static function listAll()
	{
		
		$sql = new Sql();
		
		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson;");
		
	}
	
	public function get($iduser) {
		
		$sql = new Sql(); //criando uma instância Sql para acessar o banco de dados
		
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser;", array(
			":iduser"=>$iduser
		));
		
		$data = $results[0];
		
		$data['desperson'] = utf8_encode($data['desperson']);
		
		$this->setData($data);
		
	}
	
	public function save()
	{
		
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_user_save
			pdesperson VARCHAR(64), 
			pdeslogin VARCHAR(64), 
			pdespassword VARCHAR(256), 
			pdesemail VARCHAR(128), 
			pnrphone BIGINT, 
			pinadmin TINYINT
		*/
		
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
			array(
				":desperson"=>$this->getdesperson(),
				":deslogin"=>utf8_decode($this->getdeslogin()),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
				)
		); //chamar uma procedure para inserir um novo usuário (só com uma chamada, requisição)
		
		$this->setData($results[0]);
		
	}
	
	public function update()
	{
		
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_userupdate_save
			piduser INT,
			pdesperson VARCHAR(64), 
			pdeslogin VARCHAR(64), 
			pdespassword VARCHAR(256), 
			pdesemail VARCHAR(128), 
			pnrphone BIGINT, 
			pinadmin TINYINT
		*/
		
		if ($this->getinadmin() !== NULL) {
			$auxNrphone = $this->getinadmin();
		} else {
			$auxNrphone = "";
		}
		
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
			array(
				":iduser"=>$this->getiduser(),
				":desperson"=>$this->getdesperson(),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>$this->getdespassword(),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$auxNrphone,
				":inadmin"=>$this->getinadmin()
				)
		); //chamar uma procedure para inserir um novo usuário (só com uma chamada, requisição)
		
		$this->setData($results[0]);
		$_SESSION[User::SESSION] = $this->getValues(); //gravar as alterações na sessão
		
	}
	
	public function delete()
	{
		
		$sql = new Sql();
		
		/*
			//Ordem de variáriveis de entrada da procedure sp_users_delete
			piduser INT
		*/
		
		$sql->query("CALL sp_users_delete(:iduser)",
			array(
				":iduser"=>$this->getiduser()
			)
		);
		
	}
	
	public static function getForgot($email, $inadmin = true)
	{
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email;",
			array(
					":email"=>$email
			)		
		);
		
		//verificar se o email está cadastrado no banco de dados
		if(count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{
			//criar um novo registro na tabela de recuperação de senhas
			
			
			$data = $results[0];
			
			/*
				//Ordem de variáriveis de entrada da procedure sp_userspasswordsrecoveries_create
				piduser INT,
				pdesip VARCHAR(45)
			*/
			
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)",
				array(
					":iduser"=>$data["iduser"],
					":desip"=>$_SERVER["REMOTE_ADDR"]
				)
			);
			
			if (count($results2) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha.");
			}
			else
			{
				$dataRecovery = $results2[0];
				
				//gerar um código criptografado
				//1- criar os dados em base64
				
				$code = openssl_encrypt($dataRecovery["idrecovery"], 'AES-128-CBC', pack("a16",User::SECRET), 0, pack("a16",User::SECRET_IV));
				$code = base64_encode($code);
				
				if ($inadmin === true) {
					
						$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
						
				} else {
					
					$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
					
				}
				
				$mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir senha da Hcode Store", "forgot",
					array(
						"name"=>$data["desperson"],
						"link"=>$link
					)
				);
				
				$mailer->send();
				
				return $data;
				
			}
		}
		
	}
	
	public static function validForgotDecrypt($code)
	{
		
		$idrecovery = openssl_decrypt(base64_decode($code), 'AES-128-CBC', pack("a16",User::SECRET), 0, pack("a16",User::SECRET_IV));
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT * 
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR ) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));
		
		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{
			return $results[0];
		}
		
	}
	
	public static function setForgotUsed($idrecovery)
	{
		
		$sql = new Sql();
		
		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",
			array(
				":idrecovery"=>$idrecovery
			)
		);
		
	}
	
	public function setPassword($password)
	{
		
		$sql = new Sql();
		
		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser",
			array(
				":password"=>$password,
				":iduser"=>$this->getiduser()
			)
		);
		
	}
	
	public static function setError($msg)
	{
		
		$_SESSION[User::ERROR] = $msg;
		
	}
	
	public static function getError()
	{
		
		$msg = (isset($_SESSION[User::ERROR])) && $_SESSION[User::ERROR] ? $_SESSION[User::ERROR] : "";
		
		User::clearError();
		
		return $msg;
		
	}
	
	public static function clearError()
	{
		
		$_SESSION[User::ERROR] = NULL;
		
	}
	
	public static function setSuccess($msg)
	{
		
		$_SESSION[User::SUCCESS] = $msg;
		
	}
	
	public static function getSuccess()
	{
		
		$msg = (isset($_SESSION[User::SUCCESS])) && $_SESSION[User::SUCCESS] ? $_SESSION[User::SUCCESS] : "";
		
		User::clearSuccess();
		
		return $msg;
		
	}
	
	public static function clearSuccess()
	{
		
		$_SESSION[User::SUCCESS] = NULL;
		
	}
	
	public static function setErrorRegister($msg)
	{
		
		$_SESSION[User::ERROR_REGISTER] = $msg;
		
	}
	
	public static function getErrorRegister()
	{
		
		$msg = (isset($_SESSION[User::ERROR_REGISTER])) && $_SESSION[User::ERROR_REGISTER] ? $_SESSION[User::ERROR_REGISTER] : "";
		
		User::clearErrorRegister();
		
		return $msg;
		
	}
	
	public static function clearErrorRegister()
	{
		
		$_SESSION[User::ERROR_REGISTER] = NULL;
		
	}
	
	public static function checkLoginExist($login)
	{
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin",
			array(
				":deslogin"=>$login
			)
		);
		
		return (count($results) > 0);
		
	}
	
	public static function getPasswordHash($password)
	{
		
		return password_hash($password, PASSWORD_DEFAULT, [
			'cost'=>12
		]);
		
	}
	
	public function getOrders()
	{
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.iduser = :iduser
		", [
			":iduser"=>$this->getiduser()
		]);
		
		return $results;
		
	}
	
	public static function getPage($page = 1, $itemsPerPage = User::ITEMS_PER_PAGE)
	{
		
		$start = ($page-1) * $itemsPerPage;
		
		$sql = new Sql();
		
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			ORDER BY b.desperson
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
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
			ORDER BY b.desperson
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