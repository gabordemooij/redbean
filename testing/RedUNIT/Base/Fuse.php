<?php
/**
 * RedUNIT_Base_Fuse 
 * 
 * @file 			RedUNIT/Base/Fuse.php
 * @description		Tests Fuse feature; coupling beans to models.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Fuse extends RedUNIT_Base {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();

		$blog = R::dispense('blog');
		$blog->title = 'testing';
		$blog->blog = 'tesing';
		R::store($blog);
		$blogpost = (R::load("blog",1));
		$post = R::dispense("post");
		$post->message = "hello";
		R::associate($blog,$post);
		$a = R::getAll("select * from blog ");
		RedBean_ModelHelper::setModelFormatter(new mymodelformatter);
		$w = R::dispense("weirdo");
		asrt($w->blah(),"yes!");
		R::tag($post,"lousy,smart");
		asrt(implode(',',R::tag($post)),"lousy,smart");
		R::tag($post,"clever,smart");
		$tagz = implode(',',R::tag($post));
		asrt(($tagz=="smart,clever" || $tagz=="clever,smart"),true);
		R::tag($blog,array("smart","interesting"));
		asrt(implode(',',R::tag($blog)),"smart,interesting");
		try{
		R::tag($blog,array("smart","interesting","lousy!"));
		pass();
		}catch(RedBean_Exception $e){ fail(); }
		asrt(implode(',',R::tag($blog)),"smart,interesting,lousy!");
		
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
		R::addTags($blog,"horror");
		asrt(count(R::tag($blog)),3);
		asrt(R::hasTag($blog,array("horror","commic","halloween"),true),false);
		//no double tags
		R::addTags($blog,"horror");
		asrt(R::hasTag($blog,array("horror","commic","halloween"),true),false);
		asrt(count(R::tag($blog)),3);
	}
}