# How to use

## Creating a route

Simple:
	
	$route['get']['/blog'] = array('Blog','index');	

Routes with vars:

	$route['get']['/tag/:tag'] = array('Blog','tags');
	
Routes with vars and regex:

	$route['get']['/tag/:tag'] = array('Blog','tags',':tag'=>'[a-zA-Z0-9_]');
	
	
Named routes:

	$route['get']['/blog'] = array('Blog','index','as'=>'blog');
	$request->blog_path(); //=> /blog  
	
	$route['get']['/tag/:tag'] = array('Blog','tags','as'=>'tag');
	$request->tag_path('your-tag-name'); //=> /tag/your-tag-name/
	
	
Redirect routes:

	$route['*']['/twitter'] = array('redirect'=>'http://twitter.com');
	$route['*']['/twitter'] = array('redirect'=>'http://twitter.com',302);


## Setup your application

Include Request Handler file

	include 'RequestHandler.php';


Create a request object  

	$request = new RequestHandler($route,dirname(__FILE__));

or

	define('ROOT', dirname(__FILE__));
	$request = new RequestHandler($route);

Implement one action

	// If route is valid
	if ($request->valid) {

		// Get controller name
		$controller_name = $request->controller_name."Controller";
	
		// Include controller class
		include(ROOT.'/controllers/'.$controller_name.'.php');
	
		// Create Controller class
		$controller = new $controller_name($request);
	
		// Fire controller action.
		$controller->$request->action_name();
	}else{
		header("HTTP/1.0 404 Not Found");
		die();
	}


To get parameters use: `$request->params['key'];`


## .htaccess (required)

Add .htaccess file to your app root folder.
