<?php
/**
 * Created by PhpStorm.
 * User: prive
 * Date: 2-feb-2011
 * Time: 22:51:29
 * To change this template use File | Settings | File Templates.
 */
 
class RedBean_Plugin_BeanMachine_Summary {

	protected $beanMachine;
	protected $beanType;
	protected $summaryType;

	/**
	 * @var RedBean_ToolBox 
	 */
	protected $toolbox;

	public function __construct(RedBean_Plugin_BeanMachine $beanMachine) {

		$this->beanMachine = $beanMachine;

	}

	public function summarize( $beanType, $summary, $linkTable  ) {
		$this->beanType = $beanType;
		$this->summary = $summary;
		$this->linkTable = $linkTable;


	}


	public function setToolbox(RedBean_ToolBox $toolbox) {
		$this->toolbox = $toolbox;
	}

	public function getBeans() {
		$rows = $this->toolbox->getDatabaseAdapter()->get( $this );
		foreach($rows as $row) {
			$bean = $this->toolbox->getRedbean()->dispense($this->beanType);
		}

	}


	public function __toString() {

		$b = $this->beanMachine;
/*
		SELECT b.id, (

SELECT COUNT( * )
FROM book_page,
PAGE WHERE book_page.book_id = b.id
AND page.id = book_page.page_id
)
FROM book AS b
LIMIT 0 , 30*/



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


	
		return (string) $b;

	}

}
