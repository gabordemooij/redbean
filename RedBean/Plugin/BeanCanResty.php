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
				return $this->resp(null, 400, 'URI contains invalid characters.');
			}
			if (!is_array($payload)) {
				return $this->resp(null, 400, 'Payload needs to be array.');
			}
			$finder = new RedBean_Finder(RedBean_Facade::$toolbox);
			$uri = ((strlen($uri))) ? explode('/', ($uri)) : array();
			if ($method == 'PUT') {
				if (count($uri)<1) {
					 return $this->resp(null, 400, 'Missing list.');
				}
				$list = array_pop($uri); //grab the list
				$type = (strpos($list, 'shared-')===0) ? substr($list, 7) : $list;
				if (!preg_match('|^[\w]+$|', $type)) {
					return $this->resp(null, 400, 'Invalid list.');
				}
			}
			try {
				$bean = $finder->findByPath($root, $uri);
			} catch(Exception $e) {
				return $this->resp(null, 404, $e->getMessage());
			}
			$beanType = $bean->getMeta('type');
			if (!($this->whitelist === 'all' 
					  || (isset($this->whitelist[$beanType]) && in_array($method,$this->whitelist[$beanType])))) {
				return $this->resp(null, 403, 'This bean is not available. Set whitelist to "all" or add to whitelist.');
			}
			if ($method == 'GET') {
				return $this->resp($bean->export()); 
			} elseif ($method == 'DELETE') {
				RedBean_Facade::trash($bean);
				return $this->resp('OK');
			} elseif ($method == 'POST') {
				if (!isset($payload['bean'])) {
					return $this->resp(null, 400, 'Missing parameter \'bean\'.');
				}
				if (!is_array($payload['bean'])) {
					return $this->resp(null, 400, 'Parameter \'bean\' must be object/array.');
				}
				foreach($payload['bean'] as $key => $value) {
					if (!is_string($key) || !is_string($value)) {
						return $this->resp(null, 400, 'Object "bean" invalid.');
					}
				}
				$bean->import($payload['bean']);
				$id = RedBean_Facade::store($bean);
				$bean = RedBean_Facade::load($bean->getMeta('type'), $bean->id);
				return $this->resp($bean->export());
			} elseif ($method == 'PUT') {
				if (!isset($payload['bean'])) {
					return $this->resp(null, 400, 'Missing parameter \'bean\'.');
				}
				if (!is_array($payload['bean'])) {
					return $this->resp(null, 400, 'Parameter \'bean\' must be object/array.');
				}
				foreach($payload['bean'] as $key => $value) {
					if (!is_string($key) || !is_string($value)) return $this->resp(null, 400, 'Object \'bean\' invalid.');
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
			} else {
				if (!isset($payload['param'])) {
					return $this->resp(null, 400, 'No parameters.'); 
				}
				if (!is_array($payload['param'])) {
					return $this->resp(null, 400, 'Parameter \'param\' must be object/array.');
				}
				$answer = call_user_func_array(array($bean, $method), $payload['param']);
				return $this->resp($answer);
			}
		} catch(Exception $e) {
			return $this->resp(null, 500, 'Exception: '.$e->getCode());
		}
	}
}