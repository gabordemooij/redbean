<?php
/**
 * RedBean BeanMachine Example
 * @file			RedBean/Plugin/BeanMachine/Summary.php
 * @description		Example of a BeanMachine
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_Plugin_BeanMachine_Summary {

	/**
	 * 
	 * Contains the BeanMachine
	 * @var RedBean_Plugin_BeanMachine
	 */
	protected $beanMachine;
	
	/**
	 * 
	 * Type of reference bean
	 * @var string
	 */
	protected $beanType;
	
	/**
	 * 
	 * Type of summary bean
	 * @var string
	 */
	protected $summaryType;

	
	/**
	 * 
	 * Constructor
	 * 
	 * @param RedBean_Plugin_BeanMachine $beanMachine
	 */
	public function __construct(RedBean_Plugin_BeanMachine $beanMachine) {

		$this->beanMachine = $beanMachine;

	}

	/**
	 * 
	 * Configures the summary.
	 * 
	 * @param string $beanType  Type of reference bean
	 * @param string $summary   Type of summary bean
	 * @param string $linkTable Name of the link table to be used
	 * 
	 */
	public function summarize( $beanType, $summary, $linkTable  ) {
		$this->beanType = $beanType;
		$this->summary = $summary;
		$this->linkTable = $linkTable;
	}

		

	/**
	 * __toString override
	 * Returns the SQL to obtain the beans.
	 */
	public function __toString() {

		$b = $this->beanMachine;

		//Define the top-level clauses
		$b->addGroup("SELECT"," SELECT @ ",",")
			->addGroup("FROM", " FROM  @ ",",");
	
		//Define SubQuery
		$b->openGroup("SELECT")->addGroup("SUBQUERY"," (@) AS _count", "")
			->open()
			->addGroup("SUB-SELECT", " SELECT @ ", ",")
			->addGroup("SUB-FROM", " FROM @ ", ",")
			->addGroup("SUB-WHERE", " WHERE @ ", " AND ");


		//Fill in statistic
		$b->openGroup("SUB-SELECT")->add(" count(*) ");

		//Fill in the columns
		$b->openGroup("SELECT")->add(" title ");

		//Now, fill in tables
		$b->openGroup("FROM")->add(" {$this->beanType} AS ref ");
		$b->openGroup("SUB-FROM")->add(" {$this->linkTable} as linktable ")->add(" {$this->summary} as summary ");
		$b->openGroup("SUB-WHERE")
				->add(" linktable.{$this->beanType}_id = ref.id ")
				->add(" summary.id = linktable.{$this->summary}_id ");


		//return the resulting SQL code as a string
		return (string) $b;

	}

}
