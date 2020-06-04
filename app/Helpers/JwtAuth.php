<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth{
	public $key;

	public function __construct(){
		$this->key = 'Esta_es_la_Key_para_la_Rendición_de_Cuentas_Vigencia_2019';
	}

	public function signup($alias, $password, $getToken=null){
		// Buscar si existe el usuario con sus credenciales.
		$user = User::where([
			'alias'			=> $alias,
			'password'		=> $password
		])->first();

		// Comprobar si son correctas, es decir, si devuelve un objeto
		$signup = false;
		if(is_object($user) && $user != null){
			$signup = true;
		}

		// Generar el token del usuario identificado
		if($signup){
			$token = array(
				'sub'		=> $user->id,
				'email'		=> $user->email,
				'alias'		=> $user->alias,
				'name'		=> $user->name,
				'surname'	=> $user->surname,
				'role'		=> $user->role,
				'iat'		=> time(),
				'exp'		=> time() + (24*60*60)
			);

			$jwt = JWT::encode($token, $this->key, 'HS256');

			// Devolver los datos decodificados o el token en función de un parametro
			if(is_null($getToken)){
				$data = $jwt;
			} else{
				$decoded = JWT::decode($jwt, $this->key, ['HS256']);
				$data = $decoded;
			}
			return $data;
		}
		return false;		
	}

	public function checkToken($jwt, $getIdentity=false){
		$auth = false;

		try{
			$jwt = str_replace('"', '', $jwt);
			$decoded = JWT::decode($jwt, $this->key, ['HS256']);
		} catch(\UnexpectedValueException $e){
			$auth = false;
		} catch(\DomainException $e){
			$auth = false;
		}

		if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
			$auth = true;
		} else{
			$auth = false;
		}

		if($getIdentity){
			return $decoded;
		}

		return $auth;
	}
}
?>