<?php
/**
 * RedBean Domain Object
 * @file			RedBean/UnitOfWork.php
 * @description		This is an extra convenience class
 *					that implements my own version of the
 *					well known unit of work pattern using PHP 5.3 closures.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_UnitOfWork {

	/**
	 * Associative multi dimensional array
	 * containing all the tasks and their tags.
	 * @var array
	 */
	private $todoList = array();

	/**
	 * Adds a piece of work to the list.
	 * @param string $tagName
	 * @param closure $closure
	 */
	public function addWork( $tagName, $closure ) {
		if (strlen($tagName)>0) {
			if (!isset($this->todoList[$tagName])) {
				//make a new entry in the todolist
				$this->todoList[$tagName]=array();
			}
			$this->todoList[$tagName][] = $closure;
		}
	}

	/**
	 * Executes a piece of work (job) identified by the
	 * tagname argument.
	 * @param string $tagName
	 */
	public function doWork( $tagName ) {
		if (isset($this->todoList[$tagName])) {
			foreach($this->todoList[$tagName] as $job) {
				$job();
			}
		}
	}


}
