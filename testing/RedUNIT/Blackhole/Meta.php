<?php

class RedUNIT_Blackhole_Meta extends RedUNIT_Blackhole {

	public function run() {
		$bean = new RedBean_OODBBean;
		$bean->setMeta( "this.is.a.custom.metaproperty" , "yes" );
		asrt($bean->getMeta("this.is.a.custom.metaproperty"),"yes");
		asrt($bean->getMeta("nonexistant"),NULL);
		asrt($bean->getMeta("nonexistant","abc"),"abc");
		asrt($bean->getMeta("nonexistant.nested"),NULL);
		asrt($bean->getMeta("nonexistant,nested","abc"),"abc");
		$bean->setMeta("test.two","second");
		asrt($bean->getMeta("test.two"),"second");
		$bean->setMeta("another.little.property","yes");
		asrt($bean->getMeta("another.little.property"),"yes");
		asrt($bean->getMeta("test.two"),"second");
		
		//copy meta
		$bean = new RedBean_OODBBean;
		$bean->setMeta("meta.meta","123");
		$bean2 = new RedBean_OODBBean;
		asrt($bean2->getMeta("meta.meta"),NULL);
		$bean2->copyMetaFrom($bean);
		asrt($bean2->getMeta("meta.meta"),"123");
		
		
	}

}