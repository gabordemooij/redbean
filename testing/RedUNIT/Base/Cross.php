<?php

class RedUNIT_Base_Cross extends RedUNIT_Base {

	public function run() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		$a = new RedBean_AssociationManager( $toolbox );
		
		$page = $redbean->dispense("page");
		$page->name = "John's page";
		$idpage = $redbean->store($page);
		$page2 = $redbean->dispense("page");
		$page2->name = "John's second page";
		$idpage2 = $redbean->store($page2);
		$a->associate($page, $page2);
		
		$ids = $a->related($page, "page");
		asrt(count($ids),1);
		asrt(intval(array_pop($ids)),intval($idpage2));
		$ids = $a->related($page2, "page");
		asrt(count($ids),1);
		asrt(intval(array_pop($ids)),intval($idpage));
		$page3 = $redbean->dispense("page");
		$page3->name="third";
		$page4 = $redbean->dispense("page");
		$page4->name="fourth";
		$a->associate($page3,$page2);
		$a->associate($page2,$page4);
		$a->unassociate($page,$page2);
		asrt(count($a->related($page, "page")),0);
		$ids = $a->related($page2, "page");
		asrt(count($ids),2);
		asrt(in_array($page3->id,$ids),true);
		asrt(in_array($page4->id,$ids),true);
		asrt(in_array($page->id,$ids),false);
		asrt(count($a->related($page3, "page")),1);
		asrt(count($a->related($page4, "page")),1);
		$a->clearRelations($page2, "page");
		asrt(count($a->related($page2, "page")),0);
		asrt(count($a->related($page3, "page")),0);
		asrt(count($a->related($page4, "page")),0);
		try {
			$a->associate($page2,$page2);
			pass();
		}catch(RedBean_Exception_SQL $e) {
			fail();
		}
		//try{ $a->associate($page2,$page2); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		$pageOne = $redbean->dispense("page");
		$pageOne->name = "one";
		$pageMore = $redbean->dispense("page");
		$pageMore->name = "more";
		$pageEvenMore = $redbean->dispense("page");
		$pageEvenMore->name = "evenmore";
		$pageOther = $redbean->dispense("page");
		$pageOther->name = "othermore";
		set1toNAssoc($a,$pageOther, $pageMore);
		set1toNAssoc($a,$pageOne, $pageMore);
		set1toNAssoc($a,$pageOne, $pageEvenMore);
		asrt(count($a->related($pageOne, "page")),2);
		asrt(count($a->related($pageMore, "page")),1);
		asrt(count($a->related($pageEvenMore, "page")),1);
		asrt(count($a->related($pageOther, "page")),0);
				
	}	
	
}