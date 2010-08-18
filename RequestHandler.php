<?php
/**
 * Class to add route system in ours applications.
 * 
 * This class is based on DooPHP Router and dan (http://blog.sosedoff.com/) url router.
 *
 * @author Danillo César danillos@gmail.com.br
 */
class RequestHandler {
	/** 
	 * The controller name.
	 *
	 * @access public
	 */
	public $controller_name;
	
	/** 
	 * The action name to execute from your application.
	 *
	 * @access public
	 */
	public $action_name;
	
	/** 
	 * Stores information indicating whether it is a valid route.
	 *
	 * @access public
	 */
	public $valid = false;
	
	/** 
	 * Array with all params in http request (GET e POST)
	 *
	 * @access public
	 */
	public $params = array();
	
	/** 
	 * Where from last request.
	 *
	 * @access public
	 */
	public $referer;
	
	/** 
	 * The request method. (GET,POST,PUT,DELETE)
	 *
	 * @access public
	 */
	public $method;

	/**
	 * Carrega a rota através da função getRoute
	 *
	 * @param array $route Rota para redirecionamento de página
	 * @param string $app_root Endereço da pasta app do site
	 * @access public
	 * @return void
	 */
	public function __construct($route, $app_root = ROOT ) {
		$route = $this->getRoute($route, $app_root);
		if(is_array($route)) {
			if(isset($route['redirect'])){
				if(!isset($route[0])) $route[0] = null;
				self::redirect($route['redirect'],$route[0]);
			}
			$this->controller_name = $route[0];
			$this->action_name = $route[1] ? $route[1] : 'index';
			$this->params = array_merge($this->params, $_GET, $_POST);
			if(isset($_SERVER['HTTP_REFERER'])) $this->referer = $_SERVER['HTTP_REFERER'];
			$this->valid = true;
		}
	}

	/**
	 * Search for a valid route.
	 *
	 * @access public
	 * @param string $app_root Endereço da pasta app do site
	 * @param array $route Rota para redirecionamento de página
	 * @return mixed
	 */
	public function getRoute($route, $app_root) {
		$this->method = strtolower($_SERVER['REQUEST_METHOD']);
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
			$route_list = NULL;
			if(isset($route['*'])) $route_list = $route['*'];
			// Pega as rotas defindas com o método requisitado.
			if(isset($route[$this->method])){
				if($route_list != NULL) {
					$route_list = array_merge($route_list, $route[$this->method]);
				}else{
					$route_list = $route[$this->method];
				}
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
				$route = substr($route,1);
				$route_parts = explode('/', $route);
				// Pula caso seja o root.
				if(!$route) continue;
				// Pula se quantidade de partes for diferente.
				if(sizeof($uri_parts) !== sizeof($route_parts) ) continue;
				// Pula se parte estatica for diferente.
				$static = explode(':',$route);
				if(substr($uri[0],0,strlen($static[0]))!==$static[0]) continue;
				// Pega as variaveis dinamicas
				preg_match_all('@:([\w]+)@', $route, $p_names, PREG_PATTERN_ORDER);
				$p_names = $p_names[0];
				$route_regex = $route;
				$route_regex = str_replace('/','\/',$route_regex);
				foreach ($p_names as $name) {
					$regex = '[.a-zA-Z0-9_\+\-%]';
					if (isset($values[$name])) $regex = $values[$name];
					$route_regex = str_replace($name,'('.$regex.'+)',$route_regex);
				}
				if(preg_match('/'.$route_regex.'/' , $uri[0], $matches) === 1){
					array_shift($matches);
					foreach ($p_names as $key => $value) {
						$this->params[substr($value,1)] = $matches[$key];
					}
					return $values;
				}
			}
			return false;
		}
	}

	/**
	 * Redirecionamento para uma URL externa, com cabeçalho HTTP 302 enviados por padrão
	 *
	 * @access public
	 * @param string $location URL of the redirect location
	 * @param code $code HTTP status code to be sent with the header
	 * @param boolean $exit to end the application
	 * @param array $headerBefore Headers to be sent before header("Location: some_url_address");
	 * @param array $headerAfter Headers to be sent after header("Location: some_url_address");
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
	 * @param string $str
	 * @return string
	 */
	protected function strip_slash($str) {
		if($str[strlen($str)-1]==='/'){
			$str = substr($str,0,-1);
		}
		return $str;
	}
}
?>
