<?php 
namespace RedUNIT\Base;
use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use \RedBeanPHP\ToolBox as ToolBox;
use \RedBeanPHP\AssociationManager as AssociationManager;
use \RedBeanPHP\RedException\SQL as SQL;

/**
 * RedUNIT_Base_Via
 *
 * @file    RedUNIT/Base/Via.php
 * @desc    Tests Association API (N:N associations)
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Via extends Base
{
	/**
	 * Via specific tests.
	 * 
	 * @return void
	 */
	public function testViaAndSQL()
	{
		R::nuke();
		list($p1, $p2) = R::dispense('participant', 2);
		list($e1, $e2) = R::dispense('employee', 2);
		list($x1, $x2) = R::dispense('project', 2);

		$e1->name = 'Anna';
		$e2->name = 'John';

		$p1->project = $x1;
		$p1->employee = $e1;
		$p1->arole = 'designer';

		$p2->project = $x1;
		$p2->employee = $e2;
		$p2->arole = 'coder';

		R::storeAll([$p1, $p2]);


		$project = R::load('project', $x1->id);

		$designers = $project
				->withCondition(' participant.arole = ? ', ['designer'] )
				->via( 'participant' )
				->sharedEmployeeList;

		$anna = reset( $designers );
		asrt(count($designers), 1);
		asrt($anna->name, 'Anna');
		
		
		$coders = $project
				->withCondition(' participant.arole = ? ', ['coder'] )
				->via( 'participant' )
				->sharedEmployeeList;

		$john = reset( $coders );
		asrt(count($coders), 1);
		asrt($john->name, 'John');
	}
}