<?php
/**
 * RedBean IOptimizer
 * @file				RedBean/Plugin/IOptimizer.php
 * @description			Describes the interface of an optimizer.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Plugin_IOptimizer {

	/**
	 * Each optimizer plugin should have a means to set basic
	 * information; table, column and value.
	 *
	 * @param string $table table
	 */
	public function setTable($table);

	/**
	 * Sets the column.
	 *
	 * @param string $column column
	 */
	public function setColumn($column);

	/**
	 * Sets the value.
	 *
	 * @param string $value value
	 */
	public function setValue($value);

	/**
	 * Called by the optimizer. This asks the plugin to optimize
	 * the table based on column and value information provided.
	 * If the optimize() method returns false, no further optimizations
	 * are allowed. In case of true the optimizer will advance to the next
	 * optimizer in the collection.
	 *
	 * @return boolean $yesNo further optimization allowed
	 */
	public function optimize();

}