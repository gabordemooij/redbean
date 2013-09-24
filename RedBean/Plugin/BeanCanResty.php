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
 * The Resty BeanCan Server is a handy tool for REST-like
 * middleware. Using the Resty BeanCan Server you can easily connect
 * RedBeanPHP as a backend to a Javascript Application Front-end.
 *
 * Please note that the Resty BeanCan server does not
 * aspire to implement a *perfect* RESTful protocol, not 
 * even *perfect* HTTP/1.1 protocol. The BeanCan offers REST-like
 * methods and HTTP-like response code. The actual handling of responses is
 * up to you, the BeanCan won't send any output or headers. Instead the
 * purpose of this class is to provide a practical tool for everyday
 * business.
 * 
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_BeanCanResty implements RedBean_Plugin
{
	/**
	 * HTTP Error codes used by Resty BeanCan Server.
	 */
	const C_HTTP_BAD_REQUEST           = 400;
	const C_HTTP_FORBIDDEN_REQUEST     = 403;
	const C_HTTP_NOT_FOUND             = 404;
	const C_HTTP_INTERNAL_SERVER_ERROR = 500;

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_ToolBox
	 */
	private $toolbox;

	/**
	 * @var array
	 */
	private $whitelist;

	/**
	 * @var array
	 */
	private $sqlSnippets = array();

	/**
	 * @var string
	 */
	private $method;

	/**
	 * @var array
	 */
	private $payload = array();

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * Reference bean, the bean used to find other beans in a REST request.
	 * All beans should be reachable given this root bean.
	 *
	 * @var RedBean_OODBBean
	 */
	private $root;

	/**
	 * Name of the currently selected list.
	 *
	 * @var string
	 */
	private $list;

	/**
	 * @var RedBean_OODBBean
	 */
	private $bean;

	/**
	 * Name of the type of the currently selected list.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Type of the currently selected bean.
	 *
	 * @var string
	 */
	private $beanType;

	/**
	 * List of bindings for the SQL snippet.
	 *
	 * @var array
	 */
	private $sqlBindings;

	/**
	 * An SQL snippet to sort or modify the contents of a list.
	 *
	 * @var string
	 */
	private $sqlSnippet;

	/**
	 * Writes a response object for the client (JSON encoded). Internal method.
	 * Returns a pseudo HTTP/REST response. You can refine or alter this response
	 * before sending it to the client.
	 *
	 * @param mixed   $result       result
	 * @param integer $errorCode    error code from server
	 * @param string  $errorMessage error message from server
	 *
	 * @return array $response
	 */
	private function resp( $result = NULL, $errorCode = '500', $errorMessage = 'Internal Error' )
	{
		$response = array( 'red-resty' => '1.0' );

		if ( $result !== NULL ) {
			$response['result'] = $result;
		} else {
			$response['error'] = array( 'code' => $errorCode, 'message' => $errorMessage );
		}

		return $response;
	}

	/**
	 * Handles a REST GET request.
	 * Returns the selected bean using the basic export method of the bean.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 *
	 * @return array
	 */
	private function get()
	{
		return $this->resp( $this->bean->export() );
	}

	/**
	 * Handles a REST PUT request.
	 * Updates the bean described in the payload array in the database.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 *
	 * Format of the payload array:
	 *
	 * array(
	 *        'bean' => array( property => value pairs )
	 * )
	 *
	 * @return array
	 */
	private function put()
	{
		if ( !isset( $this->payload['bean'] ) ) {
			return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Missing parameter \'bean\'.' );
		}

		if ( !is_array( $this->payload['bean'] ) ) {
			return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Parameter \'bean\' must be object/array.' );
		}

		foreach ( $this->payload['bean'] as $key => $value ) {
			if ( !is_string( $key ) || !is_string( $value ) ) {
				return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Object "bean" invalid.' );
			}
		}

		$this->bean->import( $this->payload['bean'] );

		$this->oodb->store( $this->bean );

		$this->bean = $this->oodb->load( $this->bean->getMeta( 'type' ), $this->bean->id );

		return $this->resp( $this->bean->export() );
	}

	/**
	 * Handles a REST POST request.
	 * Stores the bean described in the payload array in the database.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications.
	 *
	 * Format of the payload array:
	 *
	 * array(
	 *        'bean' => array( property => value pairs )
	 * )
	 *
	 * @return array
	 */
	private function post()
	{
		if ( !isset( $this->payload['bean'] ) ) {
			return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Missing parameter \'bean\'.' );
		}

		if ( !is_array( $this->payload['bean'] ) ) {
			return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Parameter \'bean\' must be object/array.' );
		}

		foreach ( $this->payload['bean'] as $key => $value ) {
			if ( !is_string( $key ) || !is_string( $value ) ) {
				return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Object \'bean\' invalid.' );
			}
		}

		$newBean = $this->oodb->dispense( $this->type );
		$newBean->import( $this->payload['bean'] );

		if ( strpos( $this->list, 'shared-' ) === FALSE ) {
			$listName = 'own' . ucfirst( $this->list );
		} else {
			$listName = 'shared' . ucfirst( substr( $this->list, 7 ) );
		}

		array_push( $this->bean->$listName, $newBean );

		$this->oodb->store( $this->bean );

		$newBean = $this->oodb->load( $newBean->getMeta( 'type' ), $newBean->id );

		return $this->resp( $newBean->export() );
	}

	/**
	 * Opens a list and returns the contents of the list.
	 * By default a list is interpreted as the own-list of the current bean.
	 * If the list begins with the prefix 'shared-' the shared list of the
	 * bean will be opened instead. Internal method.
	 *
	 * @return array
	 */
	private function openList()
	{
		$listOfBeans = array();

		$listName = ( strpos( $this->list, 'shared-' ) === 0 ) ? ( 'shared' . ucfirst( substr( $this->list, 7 ) ) ) : ( 'own' . ucfirst( $this->list ) );

		if ( $this->sqlSnippet ) {
			if ( preg_match( '/^(ORDER|GROUP|HAVING|LIMIT|OFFSET|TOP)\s+/i', ltrim( $this->sqlSnippet ) ) ) {
				$beans = $this->bean->with( $this->sqlSnippet, $this->sqlBindings )->$listName;
			} else {
				$beans = $this->bean->withCondition( $this->sqlSnippet, $this->sqlBindings )->$listName;
			}
		} else {
			$beans = $this->bean->$listName;
		}

		foreach ( $beans as $listBean ) {
			$listOfBeans[] = $listBean->export();
		}

		return $this->resp( $listOfBeans );
	}

	/**
	 * Handles a REST DELETE request.
	 * Deletes the selected bean.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications. Internal method.
	 *
	 * @return array
	 */
	private function delete()
	{
		$this->oodb->trash( $this->bean );

		return $this->resp( 'OK' );
	}

	/**
	 * Handles a custom request method.
	 * Passes the arguments specified in 'param' to the method
	 * specified as request method of the selected bean.
	 * Returns an array formatted according to RedBeanPHP REST BeanCan
	 * formatting specifications. Internal method.
	 *
	 * Payload array:
	 *
	 * array('param' => array(
	 *        param1, param2 etc..
	 * ))
	 *
	 * @return array
	 */
	private function custom()
	{
		if ( !isset( $this->payload['param'] ) ) {
			$this->payload['param'] = array();
		}

		if ( !is_array( $this->payload['param'] ) ) {
			return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Parameter \'param\' must be object/array.' );
		}

		$answer = call_user_func_array( array( $this->bean, $this->method ), $this->payload['param'] );
		
		return $this->resp( $answer );
	}

	/**
	 * Extracts SQL snippet and SQL bindings from the SQL bundle.
	 * Selects the appropriate SQL snippet for the list to be opened.
	 *
	 * @return void
	 */
	private function extractSQLSnippetsForGETList()
	{
		$sqlBundleItem = ( isset( $this->sqlSnippets[$this->list] ) ) ? $this->sqlSnippets[$this->list] : array( NULL, array() );

		if ( isset( $sqlBundleItem[0] ) ) {
			$this->sqlSnippet = $sqlBundleItem[0];
		}

		if ( isset( $sqlBundleItem[1] ) ) {
			$this->sqlBindings = $sqlBundleItem[1];
		}
	}

	/**
	 * Dispatches the REST request to the appropriate method.
	 * Returns a response array.
	 *
	 * @return array
	 */
	private function dispatch()
	{
		if ( $this->method == 'GET' ) {
			if ( $this->list === NULL ) {
				return $this->get();
			}

			return $this->openList();
		} elseif ( $this->method == 'DELETE' ) {
			return $this->delete();
		} elseif ( $this->method == 'POST' ) {
			return $this->post();
		} elseif ( $this->method == 'PUT' ) {
			return $this->put();
		}

		return $this->custom();
	}

	/**
	 * Determines whether the bean type and action appear on the whitelist.
	 *
	 * @return boolean
	 */
	private function isOnWhitelist()
	{
		return (
			$this->whitelist === 'all'
			|| (
				$this->list === null
				&& isset( $this->whitelist[$this->beanType] )
				&& in_array( $this->method, $this->whitelist[$this->beanType] )
				|| (
					$this->list !== null
					&& isset( $this->whitelist[$this->type] )
					&& in_array( $this->method, $this->whitelist[$this->type] )
				)
			)
		);
	}

	/**
	 * Finds a bean by its URI.
	 * Returns the bean identified by the specified URI. 
	 * 
	 * For more details 
	 * @see RedBean_Finder::findByPath
	 *
	 * @return void
	 */
	private function findBeanByURI()
	{
		$finder = new RedBean_Finder( $this->toolbox );

		$this->bean     = $finder->findByPath( $this->root, $this->uri );
		$this->beanType = $this->bean->getMeta( 'type' );
	}

	/**
	 * Extract list information.
	 * Returns FALSE if the list cannot be read due to incomplete specification, i.e.
	 * less than one entry in the URI array.
	 *
	 * @return boolean
	 */
	private function extractListInfo()
	{
		if ( $this->method == 'POST' ) {
			if ( count( $this->uri ) < 1 ) return FALSE;

			$this->list = array_pop( $this->uri );
			$this->type = ( strpos( $this->list, 'shared-' ) === 0 ) ? substr( $this->list, 7 ) : $this->list;
		} elseif ( $this->method === 'GET' && count( $this->uri ) > 1 ) {
			$lastItemInURI = $this->uri[count( $this->uri ) - 1];

			if ( $lastItemInURI === 'list' ) {
				array_pop( $this->uri );

				$this->list = array_pop( $this->uri );
				$this->type = ( strpos( $this->list, 'shared-' ) === 0 ) ? substr( $this->list, 7 ) : $this->list;

				$this->extractSQLSnippetsForGETList();
			}
		}

		return TRUE;
	}

	/**
	 * Checks whether the URI contains invalid characters.
	 *
	 * @return boolean
	 */
	private function isURIValid()
	{
		if ( preg_match( '|^[\w\-/]*$|', $this->uri ) ) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Extracts the URI.
	 *
	 * @return void
	 */
	private function extractURI()
	{
		$this->uri = ( ( strlen( $this->uri ) ) ) ? explode( '/', ( $this->uri ) ) : array();
	}

	/**
	 * Handles the REST request and returns a response array.
	 *
	 * @return array
	 */
	private function handleRESTRequest()
	{
		try {
			if ( $this->isURIValid() ) {
				return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'URI contains invalid characters.' );
			}

			if ( !is_array( $this->payload ) ) {
				return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Payload needs to be array.' );
			}

			$this->extractURI();

			if ( $this->extractListInfo() === FALSE ) {
				return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Missing list.' );
			}

			if ( !is_null( $this->type ) && !preg_match( '|^[\w]+$|', $this->type ) ) {
				return $this->resp( NULL, self::C_HTTP_BAD_REQUEST, 'Invalid list.' );
			}

			try {
				$this->findBeanByURI();
			} catch ( Exception $e ) {
				return $this->resp( NULL, self::C_HTTP_NOT_FOUND, $e->getMessage() );
			}

			if ( !$this->isOnWhitelist() ) {
				return $this->resp( NULL, self::C_HTTP_FORBIDDEN_REQUEST, 'This bean is not available. Set whitelist to "all" or add to whitelist.' );
			}

			return $this->dispatch();
		} catch ( Exception $e ) {
			return $this->resp( NULL, self::C_HTTP_INTERNAL_SERVER_ERROR, 'Exception: ' . $e->getCode() );
		}
	}

	/**
	 * Clears internal state of the REST BeanCan.
	 *
	 * @return void
	 */
	private function clearState()
	{
		$this->list        = NULL;
		$this->bean        = NULL;
		$this->type        = NULL;
		$this->beanType    = NULL;
		$this->sqlBindings = array();
		$this->sqlSnippet  = NULL;
	}

	/**
	 * Constructor.
	 * Creates a new instance of the Resty BeanCan Server.
	 * If no toolbox is provided the Resty BeanCan Server object will
	 * try to obtain the toolbox currently used by the RedBeanPHP facade.
	 * If you use only the R-methods and not the advanced objects this should be fine.
	 *
	 * @param RedBean_ToolBox $toolbox (optional)
	 */
	public function __construct( $toolbox = NULL )
	{
		if ( $toolbox instanceof RedBean_ToolBox ) {
			$this->toolbox = $toolbox;
			$this->oodb    = $toolbox->getRedBean();
		} else {
			$this->toolbox = RedBean_Facade::getToolBox();
			$this->oodb    = RedBean_Facade::getRedBean();
		}
	}

	/**
	 * The Resty BeanCan uses a white list to determine whether the current
	 * request is allowed.
	 * 
	 * A whitelist has the following format: 
	 * 
	 * array( 'book' 
	 *	            => array( 'POST', 'GET', 'publish'),
	 *	       'page'
	 *             => etc...
	 * 
	 * this will allow the methods 'POST', 'GET' and 'publish' for beans of type 'book'.
	 * To allow all methods on all beans pass the string 'all'.
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
	 * Handles a REST request.
	 * Returns a JSON response string.
	 *
	 * The first argument need to be the reference bean, or root bean (for instance 'user 1').
	 * The second argument is a path to select a bean relative to the root.
	 * For instance to select the 3rd page of a book of a user: 'book/1/page/3'.
	 * The third argument need to specify the REST method (GET/POST/DELETE/PUT) or NON-REST method
	 * (sendMail) to invoke. Optional arguments include the payload ($_POST) and
	 * a list of SQL snippets (the SQL bundle). The SQL bundle contains additional SQL and bindings
	 * per type, if a list gets accessed the SQL with the type-key of the list will be used to filter
	 * or sort the results.
	 * 
	 * Only method-bean combinations mentioned in the whitelist will be allowed.
	 * Also note that handleREST accepts ALL kinds of methods. You can pass proper HTTP methods
	 * or fabricated methods. The latter will just cause the methods to be invoked on the specified beans.
	 * 
	 * @param RedBean_OODBBean $root        root bean for REST action
	 * @param string           $uri         the URI of the RESTful operation
	 * @param string           $method      the method you want to apply
	 * @param array            $payload     payload (for POSTs)
	 * @param array            $sqlSnippets a bundle of SQL snippets to use
	 *
	 * @return string
	 */
	public function handleREST( $root, $uri, $method, $payload = array(), $sqlSnippets = array() )
	{
		$this->sqlSnippets = $sqlSnippets;
		$this->method      = $method;
		$this->payload     = $payload;
		$this->uri         = $uri;
		$this->root        = $root;

		$this->clearState();

		$result = $this->handleRESTRequest();

		return $result;
	}
}