<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {
	
	const SESSION = "User";
	const SECRET = "HcodePhp7_Secret"; //chave com 16 caracteres
	const SECRET_IV = "HcodePhp7_Secret_IV"; //chave com 16 caracteres IV
	
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
		
		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
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
		
		if (User::checkLogin($inadmin)) {
			
			header("Location: /admin/login");
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
		
		$this->setData($results[0]);
		
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
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>$this->getdespassword(),
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
		
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
			array(
				":iduser"=>$this->getiduser(),
				":desperson"=>$this->getdesperson(),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>$this->getdespassword(),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
				)
		); //chamar uma procedure para inserir um novo usuário (só com uma chamada, requisição)
		
		$this->setData($results[0]);
		
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
	
	public static function getForgot($email)
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
				
				$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
				
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
	
}

?>