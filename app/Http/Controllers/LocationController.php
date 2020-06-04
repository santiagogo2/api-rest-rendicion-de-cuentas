<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Location;

class LocationController extends Controller
{
	public function __construct(){
		$this->middleware('api-auth', ['only' => ['destroy']]);
	}

	// FUNCIONES PARA OBTENER LA INFORMACIÓN DE LAS LOCALIDADES
	public function index(Request $request){
		// Obtener la información de la base de datos
		$locations = Location::all();

		if(sizeof($locations) != 0){
			$data = array(
				'status'		=> 'success',
				'code'			=> 200,
				'locations'		=> $locations
			);
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 404,
				'message'		=> 'No se han encontrado localidades en la base de datos'
			);
		}

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	public function show(Request $request, $id){
		// Obtener la información de la base de datos
		$location = Location::find($id);

		if(is_object($location) && $location != null){
			$data = array(
				'status'		=> 'success',
				'code'			=> 200,
				'location'		=> $location
			);
		} else{
			$data = array(
				'status'		=> 'error',
				'code'			=> 404,
				'message'		=> 'No se ha encontrado ninguna localidad con el id '.$id
			);
		}

		// Devolver respuesta
		return response()->json($data, $data['code']);
	}

	// FUNCIONES PARA GUARDAR LOCALIDADES
	public function store(Request $request){
		// Obtener los datos json del request
		$json = $request->input('json', null);
		$params = json_decode($json);
		$params_array = json_decode($json, true);

		if(is_object($params) && $params != null){
			// Validar los datos ingresados en el front
			$validate = \Validator::make($params_array, [
				'name'			=> 'required|unique:locations'
			]);
			if($validate->fails()){
				$data = array(
					'status'	=> 'error',
					'code'		=> 400,
					'message'	=> 'La validación de datos ha fallado',
					'errors'	=> $validate->errors()
				);
			} else{
				// Guardar los datos
				$location = new Location();
				$location->name = strtoupper($params->name);

				$location->save();

				$data = array(
					'status'	=> 'success',
					'code'		=> 201,
					'message'	=> 'Se ha registrado correctamente una nueva localidad',
					'location'	=> $location
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

	//FUNCIONES PARA ACTUALIZAR LAS LOCALIDADES
	public function update(Request $request, $id){
		// Recoger los datos json del request
		$json = $request->input('json', null);
		$params = json_decode($json);
		$params_array = json_decode($json, true);

		if(is_object($params) && $params != null){
			// Comprobar si la localidad ingresada existe en la base de datos
			$location = Location::find($id);
			if(is_object($location) && $location != null){
				// Validar los datos ingresados en el front
				$validate = \Validator::make($params_array, [
					'name'			=> 'required|unique:locations,name,'.$id
				]);
				if($validate->fails()){
					$data = array(
						'status'	=> 'error',
						'code'		=> 400,
						'message'	=> 'La validación de datos ha fallado',
						'errors'	=> $validate->errors()
					);
				} else{
					// Retirar los datos que no se desean actualizar
					unset($params_array['id']);
					unset($params_array['created_at']);
					unset($params_array['updated_at']);

					$params_array['name'] = strtoupper($params_array['name']);

					// Actualizar la localidad
					$location = Location::where('id', $id)
										->update($params_array);
					if($location != 0){
						$data = array(
							'status'	=> 'success',
							'code'		=> 201,
							'message'	=> 'Se ha actualizado la localidad '.$id.' correctamente',
							'changes'	=> $params_array
						);
					} else{
						$data = array(
							'status'	=> 'error',
							'code'		=> 404,
							'message'	=> 'No se ha podido actualizar la localidad '.$id
						);					
					}
				}
			} else{
				$data = array(
					'status'		=> 'error',
					'code'			=> 404,
					'message'		=> 'La localidad que está intentando actualizar no existe'
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

	// FUNCIONES PARA ELIMINAR LAS LOCALIDADES
	public function destroy(Request $request, $id){
		// Comprobar el rol del administrador
		$token = $request->header('Authorization');
		$jwtAuth = new \JwtAuth();
		$user = $jwtAuth->checkToken($token, true);

		if($user->role == 'admin'){
			// Comprobar si la localidad existe
			$location = Location::find($id);

			if(is_object($location) && $location != null){
				$location_name = $location->name;
				$location->delete();
				$data = array(
					'status'		=> 'success',
					'code'			=> 200,
					'message'		=> 'La localidad '.$location_name.' se ha eliminado correctamente'
				);
			} else{
				$data = array(
					'status'		=> 'error',
					'code'			=> 404,
					'message'		=> 'La localidad que está intentando eliminar no existe en la base de datos'
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
