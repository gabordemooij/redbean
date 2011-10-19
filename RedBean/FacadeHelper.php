<?php
/**
 * RedBean Facade Helper
 * A little helper bundles with the facade to allow you to have
 * multiple instances of a facade.
 *
 * @throws Exception
 *
 */
class RedBean_FacadeHelper {

	/**
	 * Contains the Key ID for the database this instance is connected to.
	 * You cannot change this.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Constructor for R-facade instance, requires a Database Key ID.
	 *
	 * @param  $key
	 */
	public function __construct($key) {
		$this->key = $key;
	}

	/**
	 * Call router. This function routes your call to the R-facade.
	 * Note that this method is actually quite expensive in terms of CPU because it uses
	 * a call_user_func, as time passes I will port methods from the static facade to this
	 * helper to speed things up.
	 *
	 * @param  string $func function to call
	 * @param  array  $args arguments to use when calling function
	 *
	 * @return mixed $whateverTheFunctionReturns
	 */
	public function __call($func,$args) {
		Redbean_Facade::selectDatabase($this->key);
		$func = "RedBean_Facade::$func";
		return call_user_func_array($func,$args);
	}
}