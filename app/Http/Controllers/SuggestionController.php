<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Suggestion;

class SuggestionController extends Controller
{
	public function __construct(){
		$this->middleware('api-auth')->except('store');
	}
	// FUNCIONES QUE PERMITEN VISUALIZAR LAS SUGERENCIAS
	public function index(Request $request){
		// Solicitar la información a la base de datos
		$suggestions = Suggestion::with('Location')
								 ->get();
		if(sizeof($suggestions) != 0){
			$data = array(
				'status'			=> 'success',
				'code'			=> 200,
				'suggestions'	=> $suggestions
			);
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 404,
				'message'		=> 'No se han encontrado sugerencias en la base de datos de la plataforma'
			);
		}

		// Devolver la respuesta
		return response()->json($data, $data['code']);
	}

	public function show(Request $request, $id){
		// Solicitar la información de la base de datos
		$suggestion = Suggestion::with('Location')
								->find($id);

		if(is_object($suggestion) && $suggestion != null){
			$data = array(
				'status'		=> 'success',
				'code'			=> 200,
				'suggestion'	=> $suggestion
			);
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 404,
				'message'		=> 'No se han encontrado sugerencias con el id '.$id
			);
		}

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	// FUNCIONES QUE PERMITEN AGREGAR LAS SUGERENCIAS
	public function store(Request $request){
		// Recoger los datos json de la request
		$json = $request->input('json', null);
		$params = json_decode($json);
		$params_array = json_decode($json, true);

		if(is_object($params) && $params != null){
			// Validar los datos json
			$validate = \Validator::make($params_array, [
				'name'			=> 'required|regex:/^[\pL\s\-]+$/u',
				'surname'		=> 'required|regex:/^[\pL\s\-]+$/u',
				'documentId'	=> 'required|numeric',
				'sexo'			=> 'required',
				'address'		=> 'required',
				'location_id'	=> 'required|numeric',
				'neighborhood'	=> 'required',
				'phone'			=> 'required|numeric',
				'email'			=> 'required|email',
				'suggestion'	=> 'required',
				'conditions'	=> 'required',
				'medium'		=> 'required'
			]);
			if($validate->fails()){
				$data = array(
					'status'	=> 'error',
					'code'		=> 400,
					'message'	=> 'La validación de datos ha fallado. Comuniquese con el administrador de la plataforma',
					'errors'	=> $validate->errors()
				);
			} else{
				// Guardar los datos en la base de datos
				$suggestion = new Suggestion();
				$suggestion->name 			= $params->name;
				$suggestion->surname 		= $params->surname;
				$suggestion->documentId 	= $params->documentId;
				$suggestion->sexo 			= $params->sexo;
				$suggestion->address 		= strtoupper($params->address);
				$suggestion->location_id 	= $params->location_id;
				$suggestion->neighborhood 	= strtoupper($params->neighborhood);
				$suggestion->phone 			= $params->phone;
				$suggestion->email 			= $params->email;
				$suggestion->suggestion 	= $params->suggestion;
				$suggestion->conditions 	= $params->conditions;
				$suggestion->medium 		= $params->medium;

				$suggestion->save();

				$data = array(
					'status'	=> 'success',
					'code'		=> 201,
					'message'	=> 'Se ha registrado correctamente una nueva sugerencia',
					'suggestion'=> $suggestion
				);
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

	// FUNCIONES QUE PERMITEN ACTUALIZAR LAS SUGERENCIAS
	public function update(Request $request, $id){
		// Comprobar si el usuario autenticado tiene permisos
		$jwtAuth = new \JwtAuth();
		$token = $request->header('Authorization');
		$user = $jwtAuth->checkToken($token, true);

		if($user->role == 'admin'){
			// Recoger los datos json del request
			$json = $request->input('json', null);
			$params = json_decode($json);
			$params_array = json_decode($json, true);

			if(is_object($params) && $params != null){
				// Validar los datos del json
				$validate = \Validator::make($params_array, [
					'name'			=> 'required|regex:/^[\pL\s\-]+$/u',
					'surname'		=> 'required|regex:/^[\pL\s\-]+$/u',
					'documentId'	=> 'required|numeric',
					'sexo'			=> 'required',
					'address'		=> 'required',
					'location_id'	=> 'required|numeric',
					'neighborhood'	=> 'required',
					'phone'			=> 'required|numeric',
					'email'			=> 'required|email',
					'suggestion'	=> 'required',
					'conditions'	=> 'required',
					'medium'		=> 'required'
				]);
				if($validate->fails()){
					$data = array(
						'status'	=> 'error',
						'code'		=> 400,
						'message'	=> 'La validación de datos ha fallado. Comuniquese con el administrador de la plataforma',
						'errors'	=> $validate->errors()
					);
				} else{
					// Comprobar si existe la sugrencia a actualizar
					$suggestion = Suggestion::find($id);
					if(is_object($suggestion) && $suggestion != null){
						// Eliminar lo que no se desea actualizar
						unset($params_array['id']);
						unset($params_array['created_at']);
						unset($params_array['updated_at']);

						// Actualizar los datos en la base de datos
						$suggestion = Suggestion::where('id', $id)
												->update($params_array);

						if($suggestion != 0){
							$data = array(
								'status'	=> 'success',
								'code'		=> 201,
								'message'	=> 'Se ha actualizado la sugerencia '.$id.' correctamente',
								'changes'	=> $params_array
							);
						} else{
							$data = array(
								'status'	=> 'error',
								'code'		=> 404,
								'message'	=> 'No se ha podido actualizar la sugerencia '.$id
							);					
						}
					} else{
						$data = array(
							'status'		=> 'error',
							'code'			=> 404,
							'message'		=> 'La sugerencia que está intentado actualizar no existe'
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
		}
			
		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	// FUNCIONES PARA ELIMINAR LAS SUGERENCIAS
	public function destroy(Request $request, $id){
		// Comprobar si el usuario autenticado es el administrador
		$jwtAuth = new \JwtAuth();
		$token = $request->header('Authorization');
		$user = $jwtAuth->checkToken($token, true);

		if($user->role == 'admin'){
			// Comprobar si la sugerencia existe
			$suggestion = Suggestion::find($id);

			if(is_object($suggestion) && $suggestion != null){
				$suggestion->delete();

				$data = array(
					'status'		=> 'success',
					'code'			=> 200,
					'message'		=> 'La sugerencia '.$id.' se ha eliminado correctamente'
				);
			} else{
				$data = array(
					'status'		=> 'error',
					'code'			=> 404,
					'message'		=> 'LLa sugerencia que está intentando eliminar no existe en la base de datos'
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
}
