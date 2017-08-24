<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

class Concurrency extends Base
{
	
	public function getTargetDrivers()
	{
		return array( 'pgsql','mysql' );
	}

	public function prepare() {
		R::close();
	}

	public function testConcurrency()
	{
		
		$c = pcntl_fork();
		if ($c == -1) exit(1);
		if (!$c) {
			R::selectDatabase($this->currentlyActiveDriverID . 'c');
			//R::getWriter()->setSqlSelectSnippet('FOR UPDATE');
			R::exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
			sleep(1);
			try { R::exec('SET autocommit = 0'); }catch( \Exception $e ){}
			R::freeze(true);
			R::begin();
			echo "CHILD: SUBTRACTING 2 START\n";
			$i = R::loadForUpdate('inventory', 1);
			$i->apples -= 2;
			sleep(4);
			R::store($i);
			R::commit();
			echo "CHILD: SUBTRACTING 2 DONE\n";
			echo (R::load('inventory', 1));
			echo "\n";
			exit(0);
		} else {
			R::selectDatabase($this->currentlyActiveDriverID . 'c');
			echo "PARENT: PREP START\n";
			R::nuke();
			$i = R::dispense('inventory');
			$i->apples = 10;
			R::store($i);
			//R::getWriter()->setSqlSelectSnippet('FOR UPDATE');
			R::exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
			echo "PARENT: PREP DONE\n";
			sleep(2);
			echo "PARENT: ADDING 5 START\n"; 
			try { R::exec('SET autocommit = 0'); }catch( \Exception $e ){}
			R::freeze(true);
			R::begin();
			$i = R::loadForUpdate('inventory', 1);
			print_r($i);
			$i->apples += 5;
			R::store($i);
			R::commit();
			echo "PARENT ADDING 5 DONE\n";
			//sleep(6);
			$i = R::getAll('select * from inventory where id = 1');
			print_r($i);
			//exit;
			asrt((int)$i[0]['apples'], 13);
			R::freeze(false);
			try { R::exec('SET autocommit = 1'); }catch( \Exception $e ){}
			pcntl_wait($status); 
			
			
		}
	}
}
