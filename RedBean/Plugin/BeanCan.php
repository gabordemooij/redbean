<?php
/**
 * BeanCan
 *  
 * @file			RedBean/BeanCan.php
 * @description		A Server Interface for RedBean and Fuse.
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
class RedBean_BeanCan {

	/**
	 * Holds a FUSE instance.
	 * @var RedBean_ModelHelper
	 */
	private $modelHelper;

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
	private function resp($result=null, $id=null, $errorCode='-32603',$errorMessage='Internal Error') {
		$response = array('jsonrpc'=>'2.0');
		if ($id) { $response['id'] = $id; }
		if ($result) {
			$response['result']=$result;
		}
		else {
			$response['error'] = array('code'=>$errorCode,'message'=>$errorMessage);
		}
		return (json_encode($response));
	}

	/**
	 * Processes a JSON object request.
	 *
	 * @param array $jsonObject JSON request object
	 *
	 * @return mixed $result result
	 */
	public function handleJSONRequest( $jsonString ) {
		//Decode JSON string
		$jsonArray = json_decode($jsonString,true);
		if (!$jsonArray) return $this->resp(null,null,-32700,'Cannot Parse JSON');
		if (!isset($jsonArray['jsonrpc'])) return $this->resp(null,null,-32600,'No RPC version');
		if (($jsonArray['jsonrpc']!='2.0')) return $this->resp(null,null,-32600,'Incompatible RPC Version');
		//DO we have an ID to identify this request?
		if (!isset($jsonArray['id'])) return $this->resp(null,null,-32600,'No ID');
		//Fetch the request Identification String.
		$id = $jsonArray['id'];
		//Do we have a method?
		if (!isset($jsonArray['method'])) return $this->resp(null,$id,-32600,'No method');
		//Do we have params?
		if (!isset($jsonArray['params'])) {
			$data = array();
		}
		else {
			$data = $jsonArray['params'];
		}
		//Check method signature
		$method = explode(':',trim($jsonArray['method']));
		if (count($method)!=2) {
			return $this->resp(null, $id, -32600,'Invalid method signature. Use: BEAN:ACTION');
		}
		//Collect Bean and Action
		$beanType = $method[0];
		$action = $method[1];
		//May not contain anything other than ALPHA NUMERIC chars and _
		if (preg_match('/\W/',$beanType)) return $this->resp(null, $id, -32600,'Invalid Bean Type String');
		if (preg_match('/\W/',$action)) return $this->resp(null, $id, -32600,'Invalid Action String');

		try {
			switch($action) {
				case 'store':
					if (!isset($data[0])) return $this->resp(null, $id, -32602,'First param needs to be Bean Object');
					$data = $data[0];
					if (!isset($data['id'])) $bean = RedBean_Facade::dispense($beanType); else
						$bean = RedBean_Facade::load($beanType,$data['id']);
					$bean->import( $data );
					$rid = RedBean_Facade::store($bean);
					return $this->resp($rid, $id);
				case 'load':
					if (!isset($data[0])) return $this->resp(null, $id, -32602,'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType,$data[0]);
					return $this->resp($bean->export(),$id);
				case 'trash':
					if (!isset($data[0])) return $this->resp(null, $id, -32602,'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType,$data[0]);
					RedBean_Facade::trash($bean);
					return $this->resp('OK',$id);
				default:
					$modelName = $this->modelHelper->getModelName( $beanType );
					if (!class_exists($modelName)) return $this->resp(null, $id, -32601,'No such bean in the can!');
					$beanModel = new $modelName;
					if (!method_exists($beanModel,$action)) return $this->resp(null, $id, -32601,"Method not found in Bean: $beanType ");
					return $this->resp( call_user_func_array(array($beanModel,$action), $data), $id);
			}
		}
		catch(Exception $exception) {
			return $this->resp(null, $id, -32099,$exception->getCode().'-'.$exception->getMessage());
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
	public function handleRESTGetRequest( $pathToResource ) {
		if (!is_string($pathToResource)) return $this->resp(null,0,-32099,'IR');
		$resourceInfo = explode('/',$pathToResource);
		$type = $resourceInfo[0];
		try {
			if (count($resourceInfo) < 2) {
				return $this->resp(RedBean_Facade::findAndExport($type));
			}
			else {
				$id = (int) $resourceInfo[1];
				return $this->resp(RedBean_Facade::load($type,$id)->export(),$id);
			}
		}
		catch(Exception $e) {
			return $this->resp(null,0,-32099);
		}
	}
}

