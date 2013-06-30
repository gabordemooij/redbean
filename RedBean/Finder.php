<?php
/**
 * RedBean Finder
 * 
 * @file			RedBean/Finder.php
 * @desc			Helper class to harmonize APIs.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_Finder {
	/**
	 * @var RedBean_ToolBox 
	 */
	protected $toolbox;
	/**
	 * @var RedBean_OODB
	 */
	protected $redbean;
	/**
	 * Constructor.
	 * The Finder requires a toolbox.
	 * 
	 * @param RedBean_ToolBox $toolbox 
	 */
	public function __construct(RedBean_ToolBox $toolbox) {
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
	}
	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return array $beans  beans
	 */
	public function find($type, $sql = null, $values = array()) {
		if ($sql instanceof RedBean_SQLHelper) list($sql, $values) = $sql->getQuery();
		if (!is_array($values)) throw new InvalidArgumentException('Expected array, ' . gettype($values) . ' given.');
		return $this->redbean->find($type, array(), array($sql, $values));
	}
	/**
	 * @see RedBean_Finder::find
	 * The findAll() method differs from the find() method in that it does
	 * not assume a WHERE-clause, so this is valid:
	 *
	 * R::findAll('person', ' ORDER BY name DESC ');
	 *
	 * Your SQL does not have to start with a valid WHERE-clause condition.
	 * 
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return array $beans  beans
	 */
	public function findAll($type, $sql = null, $values = array()) {
		if ($sql instanceof RedBean_SQLHelper) list($sql, $values) = $sql->getQuery();
		if (!is_array($values)) throw new InvalidArgumentException('Expected array, '.gettype($values).' given.');
		return $this->redbean->find($type, array(), array($sql, $values), true);
	}
	/**
	 * @see RedBean_Finder::find
	 * The variation also exports the beans (i.e. it returns arrays).
	 * 
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return array $arrays arrays
	 */
	public function findAndExport($type, $sql = null, $values = array()) {
		$items = $this->find($type, $sql, $values);
		$arr = array();
		foreach($items as $key => $item) $arr[$key] = $item->export();
		return $arr;
	}
	/**
	 * @see RedBean_Finder::find
	 * This variation returns the first bean only.
	 * 
	 * @param string $type   type  
	 * @param string $sql    sql    
	 * @param array  $values values 
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public function findOne($type, $sql = null, $values = array()) {
		$items = $this->find($type, $sql, $values);
		$found = reset($items);
		if (!$found) return null;
		return $found;
	}
	/**
	 * @see RedBean_Finder::find
	 * This variation returns the last bean only.
	 * 
	 * @param string $type   type   
	 * @param string $sql    sql    
	 * @param array  $values values 
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public function findLast($type, $sql = null, $values = array()) {
		$items = $this->find($type, $sql, $values);
		$found = end($items);
		if (!$found) return null;
		return $found;
	}
	/**
	 * @see RedBean_Finder::find
	 * Convience method. Tries to find beans of a certain type,
	 * if no beans are found, it dispenses a bean of that type.
	 *
	 * @param  string $type   type
	 * @param  string $sql    sql
	 * @param  array  $values values
	 *
	 * @return array $beans Contains RedBean_OODBBean instances
	 */
	public function findOrDispense($type, $sql = null, $values = array()) {
		$foundBeans = $this->find($type, $sql, $values);
		if (count($foundBeans) == 0) return array($this->redbean->dispense($type)); else return $foundBeans;
	}
	/**
	 * Returns the bean identified by the RESTful path.
	 * For instance:
	 *		
	 *		$user 
	 * 		/site/1/page/3
	 * 
	 * returns page with ID 3 in ownPage of site 1 in ownSite of 
	 * $user bean.
	 * 
	 * Works with shared lists as well:
	 * 
	 *		$user
	 *		/site/1/page/3/shared-ad/4
	 * 
	 * Note that this method will open all intermediate beans so you can
	 * attach access control rules to each bean in the path.
	 * 
	 * @param RedBean_OODBBean $bean
	 * @param array            $steps  (an array representation of a REST path)
	 * 
	 * @return RedBean_OODBBean 
	 */
	public function findByPath($bean, $steps) {
		$n = count($steps);
		if (!$n) return $bean;
		if ($n % 2) throw new RedBean_Exception_Security('Invalid path: needs 1 more element.');
		for($i = 0; $i < $n; $i += 2) {
			if (trim($steps[$i])==='') throw new RedBean_Exception_Security('Cannot access list.');
			if (strpos($steps[$i],'shared-') === false) {
				$listName = 'own'.ucfirst($steps[$i]);
				$listType = $this->toolbox->getWriter()->esc($steps[$i]);
			} else {
				$listName = 'shared'.ucfirst(substr($steps[$i],7));
				$listType = $this->toolbox->getWriter()->esc(substr($steps[$i],7));
			}
			$list = $bean->withCondition(" {$listType}.id = ? ",array($steps[$i + 1]))->$listName;
			if (!is_array($list)) throw new RedBean_Exception_Security('Cannot access list.');
			if (!isset($list[$steps[$i + 1]])) throw new RedBean_Exception_Security('Cannot access bean.');
			$bean = $list[$steps[$i + 1]];
		}
		return $bean;
	}
}