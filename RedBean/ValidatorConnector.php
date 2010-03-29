<?php
class RedBean_ValidatorConnector {

	public function validate( $field, $value, $bean, $rules ) {
		
		//Is this field associated with a rule?
		if (isset($rules[$field])) {

			//fetch the rule that has been specified for this field.
			$ruleSet = $rules[$field];

			foreach($ruleSet as $rule) {

				//get the Validator Connector for this rule
				$validator = $this->getValidator( $rule );

				//Did we find a connector?
				if (method_exists($validator, "check")) {
					//try{
						$validator->check( $value );
						$bean->$field = $value;
					//}
					//catch(\Exception $exception) {
					//	return $exception;
					//}
				}

			}
		}
	}


	public function getValidator( $ruleName ) {


		return new $ruleName();

	}



}


