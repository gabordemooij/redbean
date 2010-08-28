<?php
/**
 * BeanCan
 * A Server Interface for RedBean and Fuse.
 *
 * The BeanCan Server is a lightweight, minimalistic server interface for
 * RedBean that can perfectly act as an ORM middleware solution or a backend
 * for an AJAX application.
 *
 * By Gabor de Mooij
 *
 */
class BeanCan {

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
         * Processes a batch request.
         * The Bean Can Server only works with JSON (Javascript Object Notation).
         * XML is not supported.
         *
         * A batch for the BeanCan Server should have the following format:
         *
         * {
         *  "label1": REQUEST,
         *  "label2": REQUEST
         *
         *   ...etc...
         * }
         *
         * The output from a BeanCan server looks like this:
         *
         * {
         *  "label1": OUTPUT,
         *  "label2": OUTPUT
         * }
         *
         * If an error occurs and the Bean Can Server has no way
         * to handle this error it will return a tiny JSON string:
         *
         * {
         *  "error":"<ERRORMESSAGESTRING>"
         * }
         *
         * @param string $jsonBatch
         */
        public function processBatch( $jsonString ) {

            //Decode the JSON string using PHP native JSON parser.
            $jsonArray = json_decode( $jsonString, true );

            //Prepare output array for response.
            $outputArray = array();

            //Iterate over the request batch.
            foreach($jsonArray as $bucketLabel=>$bucket) {

                //Execute request and store result under label.
                $outputArray[ $bucketLabel ] = $this->processRequest( $bucket );

            }

            //Output the JSON string directly
            die( json_encode($outputArray) );
        }


        /**
         * Processes a JSON object request.
         *
         * The format of the JSON Object string should be:
         *
         * {
         *      "bean":"<NAME OF THE BEAN YOU WANT TO INTERACT WITH>",
         *      "action":"<ACTION>",
         *      "data":"<DATA>"
         * }
         *
         * If Action is: "store","load" OR "trash" the data will be
         * imported in the bean, the bean will be passed to the R facade
         * and the action will be invoked on the R facade.
         *
         * If the Action is different, the BeanCan will find the model
         * associated with the bean and invoke the corresponding method
         * on the model passing the data as arguments.
         *
         *
         * @param array $jsonObject
         * @return mixed $result
         */
	private function processRequest( $jsonObject ) {

            
            if (!isset($jsonObject["bean"])) {
                echo "{'error':'nobean'}";
                exit;
            }
            if (!isset($jsonObject["action"])) {
                echo "{'error':'noaction'}";
                exit;
            }
            if (!isset($jsonObject["data"])) {
                $data = array();
            }
            else {
                $data = $jsonObject["data"];
            }

            $beanType = strtolower(trim($jsonObject["bean"]));
            $action = $jsonObject["action"];

            try {
                switch($action) {
                    case "store":

                        $data = $data[0];
                        
                        if (!isset($data["id"])) $bean = R::dispense($beanType); else
                            $bean = R::load($beanType,$data["id"]);

                      
                        $bean->import( $data );
                        $id = R::store($bean);
                        return $id;
                        break;
                    case "load":
                        $data = $data[0];
                        if (!isset($data["id"])) die("{'error':'noid'}");
                        $bean = R::load($beanType,$data["id"]);
                        return $bean->export();
                        break;
                    case "trash":
                        $data = $data[0];
                        if (!isset($data["id"])) die("{'error':'noid'}");
                        $bean = R::load($beanType,$data["id"]);
                        R::trash($bean);
                        return "OK";
                        break;
                    default:
                        $modelName = $this->modelHelper->getModelName( $beanType );
                        $beanModel = new $modelName;
                        return ( call_user_func_array(array($beanModel,$action), $data));
                }
            }
            catch(Exception $exception) {
                echo "{'exception':'".$exception->getCode()."-".$exception->getMessage()."'}";
                exit;
            }
	}
}

