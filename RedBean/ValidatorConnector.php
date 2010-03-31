<?php
/**
 * Validator Connector
 * @file			RedBean/ValidatorConnector.php
 * @description		Offers a flexible solution to
 *					connect beans with models and/or various
 *					validator systems in frameworks.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD license that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_ValidatorConnector {

	/**
	* Constains the validator rules.
	*/
	private $rules  = array();
	
	/**
	* Sets a list of rules (names of validators)
	* @param <array> $rules
	*/
	public function setRules( $rules ) {
		$this->rules = $rules;
	}

	/**
	* Validates a field-value combination for a bean.
	* Given a bean, this method will check the value
	* for the specified field using the rules.
	* @param <string> $field
	* @param <mixed> $value
	* @param <RedBean_OODBBean> $bean 
	*/
	public function validate( $field, $value, $bean) {
		$rules = $this->rules;
		//Is this field associated with a rule?
		if (isset($rules[$field])) {
			//fetch the rule that has been specified for this field.
			$ruleSet = $rules[$field];
			foreach($ruleSet as $rule) {
				//get the Validator Connector for this rule
				$validator = $this->getValidator( $rule );
				//Did we find a connector?
				if (method_exists($validator, "check")) {
						$validator->check( $value );
						$bean->$field = $value;
				}
			}
		}
	}

	/**
	* Given a rule name this method decides what Validator
	* should be returned.
	* @param <string> $ruleName
	* @return <Validator> $validator 
	*/
	public function getValidator( $ruleName ) {
		return new $ruleName();
	}

}


