<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
	public function __construct(){
		//$this->middleware('api-auth');
	}

	public function index(Request $request){
		// Buscar los usuarios en la base de datos
		$users = User::all();

		if(is_object($users) && $users != null){
			$data = array(
				'status'		=> 'success',
				'code'			=> '200',
				'users'			=> $users
			);
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> '404',
				'message'		=> 'En el momento, no existen usuarios en la base de datos'
			);			
		}

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	public function show(Request $request, $id){
		// Buscar el usuario en la base de datos
		$user = User::find($id);

		// Comprobar la respuesta
		if(is_object($user) && $user != null){
			$data = array(
				'status'		=> 'success',
				'code'			=> '200',
				'user'			=> $user
			);
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> '404',
				'message'		=> 'No se ha encontrado ningun usuario que tenga el id '.$id
			);
		}

		// Devolver la respuesta
		return response()->json($data, $data['code']);
	}

	public function store(Request $request){
		// Recoger el json del request
		$json = $request->input('json', null);
		$params = json_decode($json);
		$params_array = json_decode($json, true);

		if(is_object($params) && $params != null){
			// Validar los datos
			$validate = \Validator::make($params_array, [
				'name'			=> 'required|regex:/^[\pL\s\-]+$/u',
				'surname'		=> 'required|regex:/^[\pL\s\-]+$/u',
				'email'			=> 'required|email|unique:users',
				'alias'			=> 'required|unique:users',
				'password'		=> 'required',
				'role'			=> 'required'
			]);
			if($validate->fails()){
				$data = array(
					'status'	=> 'error',
					'code'		=> 400,
					'message'	=> 'La validación de datos ha fallado. Comuniquese con el administrador de la plataforma',
					'errors'	=> $validate->errors()
				);
			} else{
				// Cifrar la contraseña
				$hash_password = hash('sha256', $params->password);
				// Guardar el usuario en la base de datos
				$user = new User();
				$user->name 	= $params->name;
				$user->surname	= $params->surname;
				$user->email 	= $params->email;
				$user->alias 	= $params->alias;
				$user->password = $hash_password;
				$user->role 	= $params->role;

				$user->save();

				$data = array(
					'status'	=> 'success',
					'code'		=> 201,
					'message'	=> 'Se ha registrado correctamente el nuevo usuario',
					'user'		=> $user
				);
			}
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 400,
				'message'		=> 'Se han ingresado los datos al servidor de manera incorrecta. Error en el servicio'
			);
		}

		// Devolver la respuesta
		return response()->json($data, $data['code']);
			
	}

	public function update(Request $request, $id){
		$user = User::find($id);

		if(is_object($user) && $user != null){
			// Recoger los datos del usuario del token
			$jwtAuth = new \JwtAuth();
			$token = $request->header('Authorization', null);
			$user = $jwtAuth->checkToken($token, true);

			if($user->role == 'admin'){
				// Recoger los datos json del request
				$json = $request->input('json', null);
				$params = json_decode($json);
				$params_array = json_decode($json, true);

				if(is_object($params) && $params != null){
					// Validar los datos ingresados
					$validate = \Validator::make($params_array, [
						'name'			=> 'required|regex:/^[\pL\s\-]+$/u',
						'surname'		=> 'required|regex:/^[\pL\s\-]+$/u',
						'email'			=> 'required|email|unique:users,email,'.$id,
						'alias'			=> 'required|unique:users,alias,'.$id,
						'role'			=> 'required'
					]);
					if($validate->fails()){
						$data = array(
							'status'	=> 'error',
							'code'		=> 400,
							'message'	=> 'La validación de datos ha fallado. Comuniquese con el administrador de la plataforma',
							'errors'	=> $validate->errors()
						);
					} else {
						// Eliminar los datos que no se desean actualizar
						unset($params_array['id']);
						unset($params_array['password']);
						unset($params_array['created_at']);
						unset($params_array['updated_at']);

						// Actualizar los datos
						$user = User::where('id', $id)
									->update($params_array);
						if($user != 0){
							$data = array(
								'status'	=> 'success',
								'code'		=> 201,
								'message'	=> 'Se ha actualizado el usuario '.$id.' correctamente',
								'changes'	=> $params_array
							);
						} else{
							$data = array(
								'status'	=> 'error',
								'code'		=> 404,
								'message'	=> 'No se ha podido actualizar el usuario solicitado '.$id
							);					
						}
					}
				} else{
					$data = array(
						'status'		=> 'error',
						'code'			=> 400,
						'message'		=> 'Se han ingresado los datos al servidor de manera incorrecta. Error en el servicio'
					);
				}
			} else{
				$data = array(
					'status'		=> 'error',
					'code'			=> 401,
					'message'		=> 'El usuario autenticado no tiene permisos para acceder a esta sección'
				);
			}					
		} else {
			$data = array(
				'status'		=> 'error',
				'code'			=> 404,
				'message'		=> 'El usuario que está intentando actualizar no existe en la base de datos'
			);
		} 			

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	// FUNCIONES PARA ELIMINAR USUARIOS
	public function destroy($id, Request $request){
		// Comprobar si el usuario está autorizado
		$jwtAuth = new \JwtAuth();
		$token = $request->header('Authorization', null);
		$user = $jwtAuth->checkToken($token, true);

		if($user->role == 'admin'){
			// Comprobar si el usuario existe
			$user = User::find($id);

			if(is_object($user) && $user != null){
				$user_alias = $user->alias;
				$user->delete();
				$data = array(
					'status'		=> 'success',
					'code'			=> 200,
					'message'		=> 'El usuario '.$user_alias.' se ha eliminado correctamente'
				);
			} else{
				$data = array(
					'status'		=> 'error',
					'code'			=> 404,
					'message'		=> 'El usuario que está intentando eliminar no existe en la base de datos'
				);
			}

		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 401,
				'message'		=> 'El usuario autenticado no tiene permisos para acceder a esta sección'
			);
		}

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	public function login(Request $request){
		// Definir la variable jwtAuth
		$jwtAuth = new \JwtAuth();
		// Recibir los datos por POST
		$json = $request->input('json', true);
		$params = json_decode($json);
		$params_array = json_decode($json, true);

		if(is_object($params) && $params != null){
			// Validar los datos
			$validate = \Validator::make($params_array, [
				'alias'			=> 'required',
				'password'		=> 'required'
			]);
			if($validate->fails()){
				$data = array(
					'status'	=> 'error',
					'code'		=> 400,
					'message'	=> 'La validación de datos ha fallado. Comuniquese con el administrador de la plataforma',
					'errors'	=> $validate->errors()
				);
			} else{
				// Cifrar la contraseña
				$hash_password = hash('SHA256', $params->password);

				// Devolver el token o los datos
				$signup = $jwtAuth->signup($params->alias, $hash_password);
				if(isset($params->gettoken)){
					$signup = $jwtAuth->signup($params->alias, $hash_password, true);
				}
				if($signup){
					$data = array(
						'status'	=> 'success',
						'code'		=> 200,
						'signup'	=> $signup
					);
				} else{
					$data = array(
						'status'	=> 'error',
						'code'		=> 401,
						'message'	=> 'Los datos ingresados son incorrectos. Login incorrecto'
					);
				}					
			}
				
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 400,
				'message'		=> 'Se han ingresado los datos al servidor de manera incorrecta. Error en el servicio'
			);
		}

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}
}
