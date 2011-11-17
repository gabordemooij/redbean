<?php

class RedUNIT_Base_Fuse extends RedUNIT_Base {

	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		R::$writer->setBeanFormatter(new MyBeanFormatter());
		$blog = R::dispense('blog');
		$blog->title = 'testing';
		$blog->blog = 'tesing';
		R::store($blog);
		$blogpost = (R::load("blog",1));
		$post = R::dispense("post");
		$post->message = "hello";
		R::associate($blog,$post);
		$a = R::getAll("select * from ".tbl("blog")." ");
		
		
		RedBean_ModelHelper::setModelFormatter(new mymodelformatter);
		$w = R::dispense("weirdo");
		asrt($w->blah(),"yes!");
		
		R::tag($post,"lousy,smart");
		R::$flagUseLegacyTaggingAPI = true;
		asrt(R::tag($post),"lousy,smart");
		R::tag($post,"clever,smart");
		$tagz = R::tag($post);
		asrt(($tagz=="smart,clever" || $tagz=="clever,smart"),true);
		R::tag($blog,array("smart","interesting"));
		asrt(R::tag($blog),"smart,interesting");
		try{
		R::tag($blog,array("smart","interesting","lousy!"));
		pass();
		}catch(RedBean_Exception $e){ fail(); }
		asrt(R::tag($blog),"smart,interesting,lousy!");
		
		R::$flagUseLegacyTaggingAPI = false;
		asrt(implode(",",R::tag($blog)),"smart,interesting,lousy!");
		R::untag($blog,array("smart","interesting"));
		asrt(implode(",",R::tag($blog)),"lousy!");
		asrt(R::hasTag($blog,array("lousy!")),true);
		asrt(R::hasTag($blog,array("lousy!","smart")),true);
		asrt(R::hasTag($blog,array("lousy!","smart"),true),false);
		
		R::tag($blog, false);
		asrt(count(R::tag($blog)),0);
		
		R::tag($blog,array("funny","comic"));
		asrt(count(R::tag($blog)),2);
		R::addTags($blog,array("halloween"));
		asrt(count(R::tag($blog)),3);
		asrt(R::hasTag($blog,array("funny","commic","halloween"),true),false);
		R::unTag($blog,array("funny"));
		R::$flagUseLegacyTaggingAPI = true;
		R::addTags($blog,"horror");
		R::$flagUseLegacyTaggingAPI = false;
		asrt(count(R::tag($blog)),3);
		asrt(R::hasTag($blog,array("horror","commic","halloween"),true),false);
		//no double tags
		R::addTags($blog,"horror");
		asrt(R::hasTag($blog,array("horror","commic","halloween"),true),false);
		asrt(count(R::tag($blog)),3);
		
	
	}
	
}