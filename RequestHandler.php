<?php
	/**
	 * RequestHandler class
	 * This class was based on the DooPHP Router and dan(http://blog.sosedoff.com/) url router.
	 * Thanks.
	 * 
	 *
	 * @package default
	 * @author Danillo César de Oliveira Melo
	 */
	class RequestHandler {

		public $controller_name;
		public $action_name;
		public $valid = false;
		public $params = array();
		
		/**
		 * RequestHandler Constructor
		 *
		 * @return void
		 * @author Danillo César de Oliveira Melo
		 */
		public function __construct($route, $app_root = ROOT ) {
			$route = $this->getRoute($route, $app_root);
			if(is_array($route)) {
				$this->controller_name = $route[0];
				$this->action_name = $route[1] ? $route[1] : 'index';
				// TODO: Verificar se ta ok
				$this->params = array_merge($this->params, $_GET, $_POST);
				
				$this->valid = true;
			}
		}
		
		public function getRoute($route, $app_root) {
			$method = strtolower($_SERVER['REQUEST_METHOD']);
			$subfolder = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\','/',$app_root));
			$uri = str_replace($subfolder,'', $_SERVER['REQUEST_URI']);
			$uri = explode('?',$uri);
			$uri[0] = $this->strip_slash($uri[0]);
			
			// Retorna a rota se ela for o root
			if($uri[0] === '' || $uri[0] === '/index.php') {
				
				if(isset($route[$method]['/']))
         	return $route[$method]['/'];
        if(isset($route['*']['/']))
           return $route['*']['/'];

			}else{

				// Junta as rotas do método com as rotas que aceitam todos os métodos.
				$route_list = NULL;
				if(isset($route['*'])) $route_list = $route['*'];
				// Pega as rotas defindas com o método requisitado.
				if(isset($route[$method]) && $route_list != NULL) {
					$route_list = array_merge($route_list, $route[$method]);
				}else{
					$route_list = $route[$method];
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
						$route_regex = str_replace($name,'([.a-zA-Z0-9_\+\-%]+)',$route_regex);
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
		 * Remove slash from last char
		 *
		 * @param string $str 
		 * @return string
		 * @author Danillo César de Oliveira Melo
		 */
		protected function strip_slash($str) {
			if($str[strlen($str)-1]==='/'){
				$str = substr($str,0,-1);
			}
			return $str;
		}

	}
	
?>