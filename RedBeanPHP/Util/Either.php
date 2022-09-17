<?php

namespace RedBeanPHP\Util;

/**
 * Either Utility
 *
 * The Either Utility class provides an easy way to
 * substitute the NULL coalesce operator in RedBeanPHP
 * (since the lazy loading interface interferes with the ??
 * operator) in a way that can also be used in older PHP-versions.
 * 
 * @file    RedBeanPHP/Util/Either.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Either {

	/**
	 * @var mixed
	 */
	private $result;

	/**
	 * Constructs a new Either-instance.
	 *
	 * Example usage:
	 * 
	 * <code>
	 * $author = $text
	 * 				->either()
	 * 				->page
	 * 				->book
	 * 				->autor
	 * 				->name
	 * 				->or('unknown');
	 * </code>
	 * 
	 * The Either-class lets you access bean properties without having to do
	 * NULL-checks. The mechanism resembles the use of the ?? somewhat but
	 * offers backward compatibility with older PHP versions. The mechanism also works
	 * on arrays.
	 *
	 * <code>
	 * $budget = $company
	 * 				->either()
	 * 				->sharedProject
	 * 				->first()
	 * 				->budget
	 * 				->or(0);
	 * </code>
	 */
	public function __construct($result) {
		$this->result = $result;
	}

	/**
	 * Extracts a value from the wrapped object and stores
	 * it in the internal result object. If the desired
	 * value cannot be found, the internal result object will be set
	 * to NULL. Chainable.
	 *
	 * @param string $something name of the property you wish to extract the value of
	 *
	 * @return self
	 */
	public function __get($something) {
		if (is_object($this->result)) {
			$this->result = $this->result->{$something};
		} else {
			$this->result = NULL;
		}
		return $this;
	}

	/**
	 * Extracts the first element of the array in the internal result
	 * object and stores it as the new value of the internal result object.
	 * Chainable.
	 *
	 * @return self
	 */
	public function first() {
		if (is_array($this->result)) {
			reset($this->result);
			$key = key($this->result);
			if (isset($this->result[$key])) {
				$this->result = $this->result[$key];
			} else {
				$this->result = NULL;
			}
		}
		return $this;
	}

	/**
	 * Extracts the last element of the array in the internal result
	 * object and stores it as the new value of the internal result object.
	 * Chainable.
	 *
	 * @return self
	 */
	public function last() {
		if (is_array($this->result)) {
			end($this->result);
			$key = key($this->result);
			if (isset($this->result[$key])) {
				$this->result = $this->result[$key];
			} else {
				$this->result = NULL;
			}
		}
		return $this;
	}

	/**
	 * Extracts the specified element of the array in the internal result
	 * object and stores it as the new value of the internal result object.
	 * Chainable.
	 *
	 * @return self
	 */
	public function index( $key = 0 ) {
		if (is_array($this->result)) {
			if (isset($this->result[$key])) {
				$this->result = $this->result[$key];
			} else {
				$this->result = NULL;
			}
		}
		return $this;
	}

	/**
	 * Resolves the Either-instance to a final value, either the value
	 * contained in the internal result object or the value specified
	 * in the or() function.
	 *
	 * @param mixed $value value to resolve to if internal result equals NULL
	 *
	 * @return mixed
	 */
	public function _or( $value ) {
		$reference = (is_null($this->result)) ? $value : $this->result;
		return $reference;
	}
}
