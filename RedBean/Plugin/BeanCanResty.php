<?php
/**
 * BeanCan Server.
 * A RESTy server for RedBeanPHP.
 *  
 * @file    RedBean/BeanCanResty.php
 * @desc    PHP Server Component for RedBean and Fuse.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 * 
 * The BeanCan Server is a lightweight, minimalistic server component for
 * RedBean that can perfectly act as an ORM middleware solution or a backend
 * for an AJAX application.
 * 
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_BeanCanResty implements RedBean_Plugin {
	
	/**
	 * HTTP Error codes used by Resty BeanCan Server.
	 */
	const HTTP_BAD_REQUEST = 400;
	const HTTP_FORBIDDEN_REQUEST = 403;
	const HTTP_NOT_FOUND = 404;
	const HTTP_INTERNAL_SERVER_ERROR = 500;
	
	/**
	 * @var RedBean_ModelHelper
	 */
	private $modelHelper;
	
	/**
	 * @var array
	 */
	private $whitelist;
	
	/**
	 * Writes a response object for the client (JSON encoded). Internal method.
	 *
	 * @param mixed   $result       result
	 * @param integer $errorCode    error code from server
	 * @param string  $errorMessage error message from server
	 *
	 * @return array $response
	 */
	private function resp($result = null, $errorCode = '500', $errorMessage = 'Internal Error') {
		$response = array(
			 'red-resty' => '1.0'
		);
		if ($result) {
			$response['result'] = $result;
		} else {
			$response['error'] = array(
				 'code' => $errorCode, 
				 'message' => $errorMessage					  
			);
		}
		return $response;
	}
	
	/**
	 * Handles a REST GET request.
	 * Returns the selected bean using the basic export method of the bean.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 * 
	 * @param RedBean_OODBBean $bean the bean you wish to obtain
	 * 
	 * @return array
	 */
	protected function get(RedBean_OODBBean $bean) {
		return $this->resp($bean->export()); 
	}
	
	/**
	 * Handles a REST POST request.
	 * Updates the bean described in the payload array in the database.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 * 
	 * Format of the payload array:
	 * 
	 * array(
	 *		'bean' => array( property => value pairs )
	 * )
	 * 
	 * @param RedBean_OODBBean $bean    the bean you wish to obtain
	 * @param array            $payload a simple payload array describing the bean
	 * 
	 * @return array
	 */
	protected function post(RedBean_OODBBean $bean, $payload) {
		if (!isset($payload['bean'])) {
			return $this->resp(null, self::HTTP_BAD_REQUEST, 'Missing parameter \'bean\'.');
		}
		if (!is_array($payload['bean'])) {
			return $this->resp(null, self::HTTP_BAD_REQUEST, 'Parameter \'bean\' must be object/array.');
		}
		foreach($payload['bean'] as $key => $value) {
			if (!is_string($key) || !is_string($value)) {
				return $this->resp(null, self::HTTP_BAD_REQUEST, 'Object "bean" invalid.');
			}
		}
		$bean->import($payload['bean']);
		RedBean_Facade::store($bean);
		$bean = RedBean_Facade::load($bean->getMeta('type'), $bean->id);
		return $this->resp($bean->export());
	}
	
	/**
	 * Handles a REST PUT request.
	 * Stores the bean described in the payload array in the database.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 * 
	 * Format of the payload array:
	 * 
	 * array(
	 *		'bean' => array( property => value pairs )
	 * )
	 * 
	 * @param RedBean_OODBBean $bean bean to use list of
	 * @param string           $type    type of bean you wish to add
	 * @param string           $list    name of the list you wish to store the bean in
	 * @param array            $payload a simple payload array describing the bean
	 * 
	 * @return array
	 */
	protected function put($bean, $type, $list, $payload) {
		if (!isset($payload['bean'])) {
			return $this->resp(null, self::HTTP_BAD_REQUEST, 'Missing parameter \'bean\'.');
		}
		if (!is_array($payload['bean'])) {
			return $this->resp(null, self::HTTP_BAD_REQUEST, 'Parameter \'bean\' must be object/array.');
		}
		foreach($payload['bean'] as $key => $value) {
			if (!is_string($key) || !is_string($value)) return $this->resp(null, self::HTTP_BAD_REQUEST, 'Object \'bean\' invalid.');
		}
		$newBean = RedBean_Facade::dispense($type);
		$newBean->import($payload['bean']);
		if (strpos($list, 'shared-') === false) {
			$listName = 'own'.ucfirst($list);
		} else {
			$listName = 'shared'.ucfirst(substr($list,7));
		}
		array_push($bean->$listName, $newBean); 
		RedBean_Facade::store($bean);
		$newBean = RedBean_Facade::load($newBean->getMeta('type'), $newBean->id);
		return $this->resp($newBean->export());
	}
	
	/**
	 * Handles a REST DELETE request.
	 * Deletes the selected bean.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 * 
	 * @param RedBean_OODBBean $bean    the bean you wish to delete
	 * 
	 * @return array
	 */
	protected function delete(RedBean_OODBBean $bean) {
		RedBean_Facade::trash($bean);
		return $this->resp('OK');
	}
	
	/**
	 * Handles a custom request method.
	 * Passes the arguments specified in 'param' to the method
	 * specified as request method of the selected bean.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 * 
	 * Payload array:
	 * 
	 * array('param' => array( 
	 *		param1, param2 etc..
	 * ))
	 * 
	 * @param RedBean_OODBBean $bean    the bean you wish to invoke a method on
	 * @param string           $method  method you wish to invoke
	 * @param array            $payload array with parameters
	 * 
	 * @return array
	 */
	protected function custom(RedBean_OODBBean $bean, $method, $payload) {
		if (!isset($payload['param'])) {
			return $this->resp(null, self::HTTP_BAD_REQUEST, 'No parameters.'); 
		}
		if (!is_array($payload['param'])) {
			return $this->resp(null, self::HTTP_BAD_REQUEST, 'Parameter \'param\' must be object/array.');
		}
		$answer = call_user_func_array(array($bean, $method), $payload['param']);
		return $this->resp($answer);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->modelHelper = new RedBean_ModelHelper;
	}
	
	/**
	 * Sets a whitelist with format: array('beantype'=>array('update','customMethod')) etc.
	 * or simply string 'all' (for backward compatibility).
	 * 
	 * @param array|string $whitelist  a white list of beans and methods that should be accessible through the BeanCan Server.
	 *
	 * @return RedBean_Plugin_BeanCan
	 */
	public function setWhitelist($whitelist) {
		$this->whitelist = $whitelist;
	}
	
	/**
	 * Handles a REST request.
	 * Returns a JSON response string.
	 * 
	 * @param RedBean_Bean $root    root bean for REST action
	 * @param string       $uri     the URI of the RESTful operation
	 * @param string       $method  the method you want to apply
	 * @param array        $payload payload (for POSTs)
	 * 
	 * @return string 
	 */
	public function handleREST($root, $uri, $method, $payload = array()) {
		try {
			if (!preg_match('|^[\w\-/]*$|', $uri)) {
				return $this->resp(null, self::HTTP_BAD_REQUEST, 'URI contains invalid characters.');
			}
			if (!is_array($payload)) {
				return $this->resp(null, self::HTTP_BAD_REQUEST, 'Payload needs to be array.');
			}
			$finder = new RedBean_Finder(RedBean_Facade::$toolbox);
			$uri = ((strlen($uri))) ? explode('/', ($uri)) : array();
			if ($method == 'PUT') {
				if (count($uri)<1) {
					 return $this->resp(null, self::HTTP_BAD_REQUEST, 'Missing list.');
				}
				$list = array_pop($uri); //grab the list
				$type = (strpos($list, 'shared-')===0) ? substr($list, 7) : $list;
				if (!preg_match('|^[\w]+$|', $type)) {
					return $this->resp(null, self::HTTP_BAD_REQUEST, 'Invalid list.');
				}
			}
			try {
				$bean = $finder->findByPath($root, $uri);
			} catch(Exception $e) {
				return $this->resp(null, self::HTTP_NOT_FOUND, $e->getMessage());
			}
			$beanType = $bean->getMeta('type');
			if (!($this->whitelist === 'all' 
					  || (isset($this->whitelist[$beanType]) && in_array($method, $this->whitelist[$beanType])))) {
				return $this->resp(null, self::HTTP_FORBIDDEN_REQUEST, 'This bean is not available. Set whitelist to "all" or add to whitelist.');
			}
			if ($method == 'GET') {
				return $this->get($bean);
			} elseif ($method == 'DELETE') {
				return $this->delete($bean);
			} elseif ($method == 'POST') {
				return $this->post($bean, $payload);
			} elseif ($method == 'PUT') {
				return $this->put($bean, $type, $list, $payload);	
			} else {
				return $this->custom($bean, $method, $payload);
			}
		} catch(Exception $e) {
			return $this->resp(null, self::HTTP_INTERNAL_SERVER_ERROR, 'Exception: '.$e->getCode());
		}
	}
}