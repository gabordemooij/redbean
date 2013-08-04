<?php
/**
 * Label Maker
 *
 * @file       RedBean/LabelMaker.php
 * @desc       Makes so-called label beans
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_LabelMaker
{

	/**
	 * @var RedBean_Toolbox
	 */
	protected $toolbox;

	/**
	 * Constructor.
	 *
	 * @param RedBean_ToolBox $toolbox
	 */
	public function __construct( RedBean_ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * A label is a bean with only an id, type and name property.
	 * This function will dispense beans for all entries in the array. The
	 * values of the array will be assigned to the name property of each
	 * individual bean.
	 *
	 * @param string $type   type of beans you would like to have
	 * @param array  $labels list of labels, names for each bean
	 *
	 * @return array
	 */
	public function dispenseLabels( $type, $labels )
	{
		$labelBeans = array();
		foreach ( $labels as $label ) {
			$labelBean       = $this->toolbox->getRedBean()->dispense( $type );
			$labelBean->name = $label;
			$labelBeans[]    = $labelBean;
		}

		return $labelBeans;
	}

	/**
	 * Gathers labels from beans. This function loops through the beans,
	 * collects the values of the name properties of each individual bean
	 * and stores the names in a new array. The array then gets sorted using the
	 * default sort function of PHP (sort).
	 *
	 * @param array $beans list of beans to loop
	 *
	 * @return array
	 */
	public function gatherLabels( $beans )
	{
		$labels = array();

		foreach ( $beans as $bean ) {
			$labels[] = $bean->name;
		}

		sort( $labels );

		return $labels;
	}
}
