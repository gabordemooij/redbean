<?php
/**
 * BeanCan
 *  
 * @file			RedBean/BeanCan.php
 * @desc			Server Interface for RedBean and Fuse.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 * 
 * The BeanCan Server is a lightweight, minimalistic server interface for
 * RedBean that can perfectly act as an ORM middleware solution or a backend
 * for an AJAX application.
 * 
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_BeanCan implements RedBean_Plugin {
	/**
	 * @var RedBean_ModelHelper
	 */
	private $modelHelper;
	/**
	 * @var array
	 */
	private $whitelist;
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->modelHelper = new RedBean_ModelHelper;
	}
	/**
	 * Writes a response object for the client (JSON encoded). Internal method.
	 *
	 * @param mixed   $result       result
	 * @param integer $id           request ID
	 * @param integer $errorCode    error code from server
	 * @param string  $errorMessage error message from server
	 *
	 * @return string $json JSON encoded response.
	 */
	private function resp($result = null, $id = null, $errorCode = '-32603', $errorMessage = 'Internal Error') {
		$response = array('jsonrpc' => '2.0');
		if (!is_null($id)) { $response['id'] = $id; }
		if ($result) {
			$response['result'] = $result;
		} else {
			$response['error'] = array('code' => $errorCode, 'message' => $errorMessage);
		}
		return (json_encode($response));
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
	 * Processes a JSON object request.
	 * Second parameter can be a white list with format: array('beantype'=>array('update','customMethod')) etc.
	 * or simply string 'all' (for backward compatibility).
	 * 
	 * @param array $jsonObject        JSON request object
	 * @param array|string $whitelist  a white list of beans and methods that should be accessible through the BeanCan Server.
	 *
	 * @return mixed $result result
	 */
	public function handleJSONRequest($jsonString) {
		//Decode JSON string
		$jsonArray = json_decode($jsonString, true);
		if (!$jsonArray) return $this->resp(null, null, -32700, 'Cannot Parse JSON');
		if (!isset($jsonArray['jsonrpc'])) return $this->resp(null, null, -32600, 'No RPC version');
		if (($jsonArray['jsonrpc'] != '2.0')) return $this->resp(null, null, -32600, 'Incompatible RPC Version');
		//DO we have an ID to identify this request?
		if (!isset($jsonArray['id'])) return $this->resp(null, null, -32600, 'No ID');
		//Fetch the request Identification String.
		$id = $jsonArray['id'];
		//Do we have a method?
		if (!isset($jsonArray['method'])) return $this->resp(null, $id, -32600, 'No method');
		//Do we have params?
		if (!isset($jsonArray['params'])) {
			$data = array();
		} else {
			$data = $jsonArray['params'];
		}
		//Check method signature
		$method = explode(':', trim($jsonArray['method']));
		if (count($method) !== 2) {
			return $this->resp(null, $id, -32600, 'Invalid method signature. Use: BEAN:ACTION');
		}
		//Collect Bean and Action
		$beanType = $method[0];
		$action = $method[1];
		if (!($this->whitelist === 'all' || (isset($this->whitelist[$beanType]) && in_array($action,$this->whitelist[$beanType])))) {
			return $this->resp(null, $id, -32600, 'This bean is not available. Set whitelist to "all" or add to whitelist.');
		}
		//May not contain anything other than ALPHA NUMERIC chars and _
		if (preg_match('/\W/', $beanType)) return $this->resp(null, $id, -32600, 'Invalid Bean Type String');
		if (preg_match('/\W/', $action)) return $this->resp(null, $id, -32600, 'Invalid Action String');
		try {
			switch($action) {
				case 'store':
					if (!isset($data[0])) return $this->resp(null, $id, -32602, 'First param needs to be Bean Object');
					$data = $data[0];
					if (!isset($data['id'])) $bean = RedBean_Facade::dispense($beanType); else $bean = RedBean_Facade::load($beanType, $data['id']);
					$bean->import($data);
					$rid = RedBean_Facade::store($bean);
					return $this->resp($rid, $id);
				case 'load':
					if (!isset($data[0])) return $this->resp(null, $id, -32602, 'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType, $data[0]);
					return $this->resp($bean->export(), $id);
				case 'trash':
					if (!isset($data[0])) return $this->resp(null, $id, -32602, 'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType, $data[0]);
					RedBean_Facade::trash($bean);
					return $this->resp('OK', $id);
				case 'export':
					if (!isset($data[0])) return $this->resp(null, $id, -32602, 'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType, $data[0]);
					$array = RedBean_Facade::exportAll(array($bean), true);
					return $this->resp($array, $id);
				default:
					$modelName = $this->modelHelper->getModelName($beanType);
					if (!class_exists($modelName)) return $this->resp(null, $id, -32601, 'No such bean in the can!');
					$beanModel = new $modelName;
					if (!method_exists($beanModel, $action)) return $this->resp(null, $id, -32601, "Method not found in Bean: $beanType ");
					return $this->resp(call_user_func_array(array($beanModel, $action), $data), $id);
			}
		} catch(Exception $exception) {
			return $this->resp(null, $id, -32099, $exception->getCode().'-'.$exception->getMessage());
		}
	}
	/**
	 * Support for RESTFul GET-requests.
	 * Only supports very BASIC REST requests, for more functionality please use
	 * the JSON-RPC 2 interface.
	 * 
	 * @param string $pathToResource RESTFul path to resource
	 * 
	 * @return string $json a JSON encoded response ready for sending to client
	 */
	public function handleRESTGetRequest($pathToResource) {
		if (!is_string($pathToResource)) return $this->resp(null, 0, -32099, 'IR');
		$resourceInfo = explode('/', $pathToResource);
		$type = $resourceInfo[0];
		try {
			if (count($resourceInfo) < 2) {
				return $this->resp(RedBean_Facade::findAndExport($type));
			} else {
				$id = (int) $resourceInfo[1];
				return $this->resp(RedBean_Facade::load($type, $id)->export(), $id);
			}
		} catch(Exception $e) {
			return $this->resp(null, 0, -32099);
		}
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
			if (!preg_match('|^[\w\-/]*$|', $uri)) return $this->resp(null, 0, -32700, 'URI contains invalid characters.');
			if (!is_array($payload)) return $this->resp(null, 0, -32700, 'Payload needs to be array.');
			$finder = new RedBean_Finder(RedBean_Facade::$toolbox);
			$uri = ((strlen($uri))) ? explode('/', ($uri)) : array();
			if ($method == 'PUT') {
				if (count($uri)<1) {
					 return $this->resp(null, 0, -32602, 'Missing list.');
				}
				$list = array_pop($uri); //grab the list
				$type = (strpos($list, 'shared-')===0) ? substr($list, 7) : $list;
				if (!preg_match('|^[\w]+$|', $type)) return $this->resp(null, 0, -32700, 'Invalid list.');
			}
			try {
				$bean = $finder->findByPath($root, $uri);
			} catch(Exception $e) {
				return $this->resp(null, 0, -32600, $e->getMessage());
			}
			$beanType = $bean->getMeta('type');
			if (!($this->whitelist === 'all' || (isset($this->whitelist[$beanType]) && in_array($method,$this->whitelist[$beanType])))) {
				return $this->resp(null, 0, -32600, 'This bean is not available. Set whitelist to "all" or add to whitelist.');
			}
			if ($method == 'GET') {
				return $this->resp($bean->export()); 
			} elseif ($method == 'DELETE') {
				RedBean_Facade::trash($bean);
				return $this->resp('OK');
			} elseif ($method == 'POST') {
				if (!isset($payload['bean'])) return $this->resp(null, 0, -32602, 'Missing parameter \'bean\'.');
				if (!is_array($payload['bean'])) return $this->resp(null, 0, -32602, 'Parameter \'bean\' must be object/array.');
				foreach($payload['bean'] as $key => $value) {
					if (!is_string($key) || !is_string($value)) return $this->resp(null, 0, -32602, 'Object "bean" invalid.');
				}
				$bean->import($payload['bean']);
				$id = RedBean_Facade::store($bean);
				$bean = RedBean_Facade::load($bean->getMeta('type'), $bean->id);
				return $this->resp($bean->export());
			} elseif ($method == 'PUT') {
				if (!isset($payload['bean'])) return $this->resp(null, 0, -32602, 'Missing parameter \'bean\'.');
				if (!is_array($payload['bean'])) return $this->resp(null, 0, -32602, 'Parameter \'bean\' must be object/array.');
				foreach($payload['bean'] as $key => $value) {
					if (!is_string($key) || !is_string($value)) return $this->resp(null, 0, -32602, 'Object \'bean\' invalid.');
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
				if (!isset($payload['param'])) return $this->resp(null, 0, -32600, 'No parameters.'); 
				if (!is_array($payload['param'])) return $this->resp(null, 0, -32602, 'Parameter \'param\' must be object/array.');
				$answer = call_user_func_array(array($bean,$method), $payload['param']);
				return $this->resp($answer);
			}
		}
		catch(Exception $e) {
			return $this->resp(null, 0, -32099, 'Exception: '.$e->getCode());
		}
	}
}