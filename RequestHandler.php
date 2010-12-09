<?php
/**
 * Drumon Framework: Build fast web applications
 * Copyright (C) 2010 Sook - Desenvolvendo inovações (http://www.sook.com.br)
 * Licensed under GNU General Public License.
 */

/**
 * Class to add route system in ours applications.
 * 
 * This class is based on DooPHP Router and dan (http://blog.sosedoff.com/) url router.
 * @package class
 * @author Sook contato@sook.com.br
 */
class RequestHandler {
	/** 
	 * The controller name.
	 *
	 * @access public
	 * @var string
	 */
	public $controller_name;
	
	/** 
	 * The action name to execute from your application.
	 *
	 * @access public
	 * @var string
	 */
	public $action_name;
	
	/** 
	 * Stores information indicating whether it is a valid route.
	 *
	 * @access public
	 * @var boolean
	 */
	public $valid = false;
	
	/** 
	 * Array with all params in http request (GET e POST).
	 *
	 * @access public
	 * @var array
	 */
	public $params = array();
	
	/** 
	 * Where from last request.
	 *
	 * @access public
	 * @var string
	 */
	public $referer;
	
	/**
	 * Same the $_SERVER['request_uri']
	 *
	 * @var string
	 */
	public $uri;
	
	/** 
	 * The request method. (GET,POST,PUT,DELETE).
	 *
	 * @access public
	 * @var string
	 */
	public $method;
	
	
	public $routes;

	/**
	 * Carrega a rota através da função get_route
	 *
	 * @param array $route - Rota para redirecionamento de página.
	 * @param string $app_root - Endereço da pasta app do site.
	 * @access public
	 */
	public function __construct($routes, $app_root = ROOT ) {
		$this->routes = $routes;
		$route = $this->get_route($routes, $app_root);
		if(is_array($route)) {
			if(isset($route['redirect'])){
				if(!isset($route[0])) $route[0] = null;
				self::redirect($route['redirect'],$route[0]);
			}
			
			$this->controller_name = $route[0];
			$this->action_name = $route[1] ? $route[1] : 'index';
			$this->params = array_merge($this->params, $_GET, $_POST);
			if(isset($_SERVER['HTTP_REFERER'])) $this->referer = $_SERVER['HTTP_REFERER'];
			$this->uri = $_SERVER['REQUEST_URI'];
			$this->valid = true;
		}
	}
	
	/**
	 * Set action name
	 *
	 * @param string $name 
	 * @return void
	 */
	public function set_action_name($name) {
		$this->action_name = $name;
	}
	
	/**
	 * Set controller name
	 *
	 * @param string $name 
	 * @return void
	 */
	public function set_controller_name($name) {
		$this->controller_name = $name;
	}

	/**
	 * Search for a valid route.
	 *
	 * @access public
	 * @param string $app_root - Endereço da pasta app do site.
	 * @param array $route - Rota para redirecionamento de página.
	 * @return mixed - False, se não existir rota / Array com a Lista de Rotas.
	 */
	public function get_route($route, $app_root) {
		
		$this->method = (isset($_REQUEST['_method']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') ? strtolower($_REQUEST['_method']) : strtolower($_SERVER['REQUEST_METHOD']);

		$subfolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\','/',$app_root));
		$uri = str_replace($subfolder,'', $_SERVER['REQUEST_URI']);
		$uri = explode('?',$uri);
		$uri[0] = $this->strip_slash($uri[0]);

		// Retorna a rota se ela for o root
		if($uri[0] === '' || $uri[0] === '/index.php') {
			if(isset($route[$this->method]['/'])) return $route[$this->method]['/'];
			if(isset($route['*']['/'])) return $route['*']['/'];
		}else{
			
			// Junta as rotas do método com as rotas que aceitam todos os métodos.
			$route_list = array();
			if(isset($route['*'])) $route_list = $route['*'];
			
			// Pega as rotas defindas com o método requisitado e junta com o geral.
			if(isset($route[$this->method])) {
				$route_list = array_merge($route_list, $route[$this->method]);
				//if($route_list != NULL) {
				//$route_list = array_merge($route_list, $route[$this->method]);
				//}else{
				//$route_list = $route[$this->method];
				//}
			}
			// Se não existe rota já retorna false.
			if(empty($route_list)) return false;
			
			// Retorna rota se ela for estática.
			if(isset($route_list[$uri[0]])) {
				return $route_list[$uri[0]];
			}else if(isset($route_list[$uri[0].'/'])) {
				return $route_list[$uri[0].'/'];
			}
			
			$uri[0] = substr($uri[0],1);
			$uri_parts = explode('/', $uri[0]);	
			
			foreach ($route_list as $route => $values) {
				$route = $this->strip_slash(substr($route,1));
				$route_parts = explode('/', $this->strip_slash($route));

				// Pula caso seja o root.
				if(!$route) continue;
				
				// Pula se quantidade de partes for diferente.
				if(sizeof($uri_parts) !== sizeof($route_parts) ) continue;
				
				// Pula se parte estática for diferente.
				$static = explode(':',$route);
				if(substr($uri[0],0,strlen($static[0]))!==$static[0]) continue;
				
				// Pega as variaveis dinamicas
				preg_match_all('@:([\w]+)@', $route, $params_name, PREG_PATTERN_ORDER);

				$params_name = $params_name[0];
				
				// Se não tem variável então pula.
				if(!count($params_name)) continue;
				
				$route_regex = $route;
				$route_regex = str_replace('/','\/',$route_regex);
				
				foreach ($params_name as $name) {
					$regex = '[.a-zA-Z0-9_\+\-%]';
					if (isset($values[$name])) $regex = $values[$name];
					
					// TODO: rever se vai ficar ou não o preg no lugar do str
					//$route_regex = str_replace($name,'('.$regex.'+)',$route_regex);
					$route_regex = preg_replace('/'.$name.'/','('.$regex.'+)',$route_regex,1);
				}
				
				if(preg_match('/'.$route_regex.'/' , $uri[0], $matches) === 1){
					array_shift($matches);
					foreach ($params_name as $key => $value) {
						$this->params[substr($value,1)] = $matches[$key];
					}
					return $values;
				}
			}
			return false;
		}
	}
	
	
	public function __call($name, $arguments) {
		$named_route = str_replace('_path','',$name);

		if(substr($name,-5,strlen($name)-1) === '_path') {
			return $this->url_for($named_route,$arguments);
		}else{
			trigger_error('Method '.$name.' not exist');
		}
	}
	
	
	public function url_for($named_route,$params = array()) {
		
		// Junta as rotas do método com as rotas que aceitam todos os métodos.
		$route_list = array();
		if(isset($this->routes['*'])) $route_list = $this->routes['*'];
		
		// Pega as rotas defindas com o método requisitado e junta com o geral.
		if(isset($this->routes[$this->method])) {
			$route_list = array_merge($route_list, $this->routes[$this->method]);
		}
		$path = false;
		foreach ($route_list as $url => $route) {
			if(isset($route['as']) && $route['as'] == $named_route ) {
				
				// Pega as variaveis dinamicas
				preg_match_all('@:([\w]+)@', $url, $params_name, PREG_PATTERN_ORDER);

				$params_name = $params_name[0];
				
				// Se não tem variável então pula.
				if(count($params_name)){
					if (count($params) != count($params_name)) { die('Named route for '.$named_route.' expects '.count($params_name).' params not '.count($params).'.'); }
					$route_regex = $url;
					$route_regex = str_replace('/','\/',$route_regex);
					
					$i = 0;
					foreach ($params_name as $name) {
						$route_regex = preg_replace('/'.$name.'/',$params[$i],$route_regex,1);
						$i++;
					}
					$path = $route_regex;
				}else{
					$path = $url;
				}
			}
		}
		
		if(!$path) die('Named route for '.$named_route.' doenst exist.');
		
		return str_replace('\/','/',$path); ;
	}

	/**
	 * Redirecionamento para uma URL externa, com cabeçalho HTTP 302 enviados por padrão.
	 *
	 * @access public
	 * @param string $location - URL of the redirect location.
	 * @param code $code - HTTP status code to be sent with the header.
	 * @param boolean $exit - To end the application.
	 * @param array $headerBefore - Headers to be sent before header("Location: some_url_address").
	 * @param array $headerAfter - Headers to be sent after header("Location: some_url_address").
	 * @return void
	 */
	public static function redirect($location, $code=302, $exit=true, $headerBefore=NULL, $headerAfter=NULL){
		if($headerBefore!=NULL){
			for($i=0;$i<sizeof($headerBefore);$i++){
				header($headerBefore[$i]);
			}
		}
		header("Location: $location", true, $code);
		if($headerAfter!=NULL){
			for($i=0;$i<sizeof($headerBefore);$i++){
				header($headerBefore[$i]);
			}
		}
		if($exit) die;
	}

	/**
	 * Remove the slash(/) from the last char.
	 *
	 * @access public
	 * @param string $str - String com (/) a ser alterada.
	 * @return string - String sem a barra (/).
	 */
	protected function strip_slash($str) {
		if($str[strlen($str)-1]==='/'){
			$str = substr($str,0,-1);
		}
		return $str;
	}
}
?>
