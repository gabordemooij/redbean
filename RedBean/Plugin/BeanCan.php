<?php
/**
 * BeanCan Server.
 * A JSON-RPC/RESTy server for RedBeanPHP.
 *
 * @file    RedBean/BeanCan.php
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
class RedBean_Plugin_BeanCan implements RedBean_Plugin
{
	/**
	 * List of JSON RPC2 error code definitions.
	 */
	const C_JSONRPC2_PARSE_ERROR        = -32700;
	const C_JSONRPC2_INVALID_REQUEST    = -32600;
	const C_JSONRPC2_METHOD_NOT_FOUND   = -32601;
	const C_JSONRPC2_INVALID_PARAMETERS = -32602;
	const C_JSONRPC2_INTERNAL_ERROR     = -32603;
	const C_JSONRPC2_SPECIFIED_ERROR    = -32099;

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
	public function __construct()
	{
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
	 * @return string $json
	 */
	private function resp( $result = NULL, $id = NULL, $errorCode = '-32603', $errorMessage = 'Internal Error' )
	{
		$response = array( 'jsonrpc' => '2.0' );

		if ( !is_null( $id ) ) $response['id'] = $id;

		if ( $result ) {
			$response['result'] = $result;
		} else {
			$response['error'] = array(
				'code'    => $errorCode,
				'message' => $errorMessage
			);
		}

		return json_encode( $response );
	}

	/**
	 * Handles a JSON RPC 2 request to store a bean.
	 *
	 * @param string $id       request ID, identification for request
	 * @param string $beanType type of the bean you want to store
	 * @param array  $data     data array
	 *
	 * @return string
	 */
	private function store( $id, $beanType, $data )
	{
		if ( !isset( $data[0] ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_PARAMETERS, 'First param needs to be Bean Object' );
		}

		$data = $data[0];

		if ( !isset( $data['id'] ) ) {
			$bean = RedBean_Facade::dispense( $beanType );
		} else {
			$bean = RedBean_Facade::load( $beanType, $data['id'] );
		}

		$bean->import( $data );

		$rid = RedBean_Facade::store( $bean );

		return $this->resp( $rid, $id );
	}

	/**
	 * Handles a JSON RPC 2 request to load a bean.
	 *
	 * @param string $id       request ID, identification for request
	 * @param string $beanType type of the bean you want to store
	 * @param array  $data     data array containing the ID of the bean to load
	 *
	 * @return string
	 */
	private function load( $id, $beanType, $data )
	{
		if ( !isset( $data[0] ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_PARAMETERS, 'First param needs to be Bean ID' );
		}

		$bean = RedBean_Facade::load( $beanType, $data[0] );

		return $this->resp( $bean->export(), $id );
	}

	/**
	 * Handles a JSON RPC 2 request to trash a bean.
	 *
	 * @param string $id       request ID, identification for request
	 * @param string $beanType type of the bean you want to delete
	 * @param array  $data     data array
	 *
	 * @return string
	 */
	private function trash( $id, $beanType, $data )
	{
		if ( !isset( $data[0] ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_PARAMETERS, 'First param needs to be Bean ID' );
		}

		$bean = RedBean_Facade::load( $beanType, $data[0] );

		RedBean_Facade::trash( $bean );

		return $this->resp( 'OK', $id );
	}

	/**
	 * Handles a JSON RPC 2 request to export a bean.
	 *
	 * @param string $id       request ID, identification for request
	 * @param string $beanType type of the bean you want to export
	 * @param array  $data     data array
	 *
	 * @return string
	 */
	private function export( $id, $beanType, $data )
	{
		if ( !isset( $data[0] ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_PARAMETERS, 'First param needs to be Bean ID' );
		}

		$bean  = RedBean_Facade::load( $beanType, $data[0] );

		$array = RedBean_Facade::exportAll( array( $bean ), TRUE );

		return $this->resp( $array, $id );
	}

	/**
	 * Handles a JSON RPC 2 request to perform a custom operation on a bean.
	 *
	 * @param string $id       request ID, identification for request
	 * @param string $beanType type of the bean you want to store
	 * @param string $action   action you want to invoke on bean model
	 * @param array  $data     data array
	 *
	 * @return string
	 */
	private function custom( $id, $beanType, $action, $data )
	{
		$modelName = $this->modelHelper->getModelName( $beanType );

		if ( !class_exists( $modelName ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_METHOD_NOT_FOUND, 'No such bean in the can!' );
		}

		$beanModel = new $modelName;

		if ( !method_exists( $beanModel, $action ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_METHOD_NOT_FOUND, "Method not found in Bean: $beanType " );
		}

		return $this->resp( call_user_func_array( array( $beanModel, $action ), $data ), $id );
	}

	/**
	 * Extracts bean type, action identifier,
	 * data array and method name from json array.
	 *
	 * @param array $jsonArray JSON array containing the details
	 *
	 * @return array
	 */
	private function getDataFromJSON( $jsonArray )
	{
		$beanType = NULL;
		$action   = NULL;

		if ( !isset( $jsonArray['params'] ) ) {
			$data = array();
		} else {
			$data = $jsonArray['params'];
		}

		//Check method signature
		$method = explode( ':', trim( $jsonArray['method'] ) );

		if ( count( $method ) === 2 ) {
			//Collect Bean and Action
			$beanType = $method[0];
			$action   = $method[1];
		}

		return array( $beanType, $action, $data, $method );
	}

	/**
	 * Dispatches the JSON RPC request to one of the private methods.
	 *
	 * @param string $id       identification of request
	 * @param string $beanType type of the bean you wish to apply the action to
	 * @param string $action   action to apply
	 * @param array  $data     data array containing parameters or details
	 *
	 * @return array
	 */
	private function dispatch( $id, $beanType, $action, $data )
	{
		try {
			switch ( $action ) {
				case 'store':
					return $this->store( $id, $beanType, $data );
				case 'load':
					return $this->load( $id, $beanType, $data );
				case 'trash':
					return $this->trash( $id, $beanType, $data );
				case 'export':
					return $this->export( $id, $beanType, $data );
				default:
					return $this->custom( $id, $beanType, $action, $data );
			}
		} catch ( Exception $exception ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_SPECIFIED_ERROR, $exception->getCode() . '-' . $exception->getMessage() );
		}
	}

	/**
	 * Sets a whitelist with format: array('beantype'=>array('update','customMethod')) etc.
	 * or simply string 'all' (for backward compatibility).
	 *
	 * @param array|string $whitelist  a white list of beans and methods that should be accessible through the BeanCan Server.
	 *
	 * @return RedBean_Plugin_BeanCan
	 */
	public function setWhitelist( $whitelist )
	{
		$this->whitelist = $whitelist;

		return $this;
	}

	/**
	 * Processes a JSON object request.
	 * Second parameter can be a white list with format: array('beantype'=>array('update','customMethod')) etc.
	 * or simply string 'all' (for backward compatibility).
	 *
	 * @param array        $jsonObject JSON request object
	 * @param array|string $whitelist  a white list of beans and methods that should be accessible through the BeanCan Server.
	 *
	 * @return mixed $result result
	 */
	public function handleJSONRequest( $jsonString )
	{
		if ( !$jsonArray = json_decode( $jsonString, TRUE ) ) { //Decode JSON string
			return $this->resp( NULL, NULL, self::C_JSONRPC2_PARSE_ERROR, 'Cannot Parse JSON' );
		}

		if ( !isset( $jsonArray['jsonrpc'] ) ) {
			return $this->resp( NULL, NULL, self::C_JSONRPC2_INVALID_REQUEST, 'No RPC version' );
		}

		if ( ( $jsonArray['jsonrpc'] != '2.0' ) ) {
			return $this->resp( NULL, NULL, self::C_JSONRPC2_INVALID_REQUEST, 'Incompatible RPC Version' );
		}

		if ( !isset( $jsonArray['id'] ) ) { //DO we have an ID to identify this request?
			return $this->resp( NULL, NULL, self::C_JSONRPC2_INVALID_REQUEST, 'No ID' );
		}

		$id = $jsonArray['id']; //Fetch the request Identification String.

		if ( !isset( $jsonArray['method'] ) ) { //Do we have a method?
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_REQUEST, 'No method' );
		}

		list( $beanType, $action, $data, $method ) = $this->getDataFromJSON( $jsonArray ); //Do we have params?

		if ( count( $method ) !== 2 ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_REQUEST, 'Invalid method signature. Use: BEAN:ACTION' );
		}

		if ( !( $this->whitelist === 'all' || ( isset( $this->whitelist[$beanType] ) && in_array( $action, $this->whitelist[$beanType] ) ) ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_REQUEST, 'This bean is not available. Set whitelist to "all" or add to whitelist.' );
		}

		if ( preg_match( '/\W/', $beanType ) ) { //May not contain anything other than ALPHA NUMERIC chars and _
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_REQUEST, 'Invalid Bean Type String' );
		}

		if ( preg_match( '/\W/', $action ) ) {
			return $this->resp( NULL, $id, self::C_JSONRPC2_INVALID_REQUEST, 'Invalid Action String' );
		}

		return $this->dispatch( $id, $beanType, $action, $data );
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
	public function handleRESTGetRequest( $pathToResource )
	{
		if ( !is_string( $pathToResource ) ) {
			return $this->resp( NULL, 0, self::C_JSONRPC2_SPECIFIED_ERROR, 'IR' );
		}

		$resourceInfo = explode( '/', $pathToResource );

		$type = $resourceInfo[0];

		try {
			if ( count( $resourceInfo ) < 2 ) {
				return $this->resp( RedBean_Facade::findAndExport( $type ) );
			} else {
				$id = (int) $resourceInfo[1];

				return $this->resp( RedBean_Facade::load( $type, $id )->export(), $id );
			}
		} catch ( Exception $exception ) {
			return $this->resp( NULL, 0, self::C_JSONRPC2_SPECIFIED_ERROR );
		}
	}
}
