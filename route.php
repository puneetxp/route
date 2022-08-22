<?php
/**
 * @author		Jesse Boyer <contact@jream.com>
 * @copyright	Copyright (C), 2011-12 Jesse Boyer
 * @license		GNU General Public License 3 (http://www.gnu.org/licenses/)
 *				Refer to the LICENSE file distributed within the package.
 *
 * @link		http://jream.com
 *
 * @internal	Inspired by Klein @ https://github.com/chriso/klein.php
 */

class Route
{
	/**
	* @var array $_routes
	*/
	private $_routes = [
	       "GET" => [],
	       "POST" => [],
	       "PUT" => [],
	       "DELETE" => []
	   ];

	/**
	* @var string $_trim Class-wide items to clean
	*/
	private $_trim = '/\^$';
		
	/**
	* add - Adds a URI and Function to the two lists
	*
	* @param string $uri A path such as about/system
	* @param object $function An anonymous function
	*/
	private $_uri = '';
	private $_method = '';
	private $_match_route = '';
	private $_roles = [];

	public function active_route_set() {
		$this->_uri = trim(isset($_REQUEST['uri']) ? filter_var($_REQUEST['uri'], FILTER_SANITIZE_URL) : '/', $this->_trim);
		$this->_method = isset($_SERVER['REQUEST_METHOD']) ? filter_var($_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_URL) : 'GET';
		$this->_realUri = explode('/', $this->_uri);
		$this->_roles = Sessions::roles();
	}
	
	public function listen() {
      $this->active_route_set();
      foreach ($this->_routes[$this->_method] as $route) {
         $route_uri = $route['uri'];
         if (preg_match("#^$route_uri$#", $this->_uri)) {
            $this->_match_route = $route;
            $this->match_permission();
            return;
         }
      }
      http_response_code(404);
      return;
   }

   public function match_permission() {
      if ($this->_match_route['roles'] === [''] || array_intersect($this->_match_route['roles'], $this->_roles)) {
         $this->run_route();
      } else {
         http_response_code(403);
      }
      return;
   }

   public function run_route() {
      $fakeUri = explode('/', $this->_match_route['uri']);
      foreach ($fakeUri as $key => $value) {
         if ($value == '.+') {
            $this->_match_route['value'][] = $this->_realUri[$key];
         }
      }
      $return = call_user_func_array($this->_match_route['call'], $this->_match_route['value']);
      echo $return;
      return;
   }

   public function get($uri, $function, $roles = [''], $value = []) {
      $this->_routes['GET'][] = ["uri" => trim($uri, $this->_trim), "roles" => $roles, "call" => $function, 'value' => $value];
   }

   public function post($uri, $function, $roles = [''], $value = []) {
      $this->_routes['POST'][] = ["uri" => trim($uri, $this->_trim), "roles" => $roles, "call" => $function, 'value' => $value];
   }

   public function put($uri, $function, $roles = [''], $value = []) {
      $this->_routes['PUT'][] = ["uri" => trim($uri, $this->_trim), "roles" => $roles, "call" => $function, 'value' => $value];
   }

   public function delete($uri, $function, $roles = [''], $value = []) {
      $this->_routes['DELETE'][] = ["uri" => trim($uri, $this->_trim), "roles" => $roles, "call" => $function, 'value' => $value];
   }

   public function crud($crud, $name, $permission, $controller) {
      if (in_array('r', $crud)) {
         Self::get($name, [$controller, 'index'], $permission['read']);
         Self::get($name . '/.+', [$controller, 'show'], $permission['read']);
      }
      if (in_array('c', $crud)) {
         Self::post($name, [$controller, 'store'], $permission['write']);
      }
      if (in_array('u', $crud)) {
         Self::put($name . '/.+', [$controller, 'update'], $permission['update']);
      }
      if (in_array('d', $crud)) {
         Self::delete($name . '/.+', [$controller, 'delete'], $permission['delete']);
      }
   }

   public function allroutes() {
      echo Response::json($this->_routes);
      return;
   }
	
}
