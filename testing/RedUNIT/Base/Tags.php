<?php

class RedUNIT_Base_Tags extends RedUNIT_Base {

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
		
		
		testpack("fetch tagged items");
		R::exec("drop table author_book");
		R::exec("drop table author");
		R::exec("drop table book");
		R::wipe("book");
		R::wipe("tag");
		R::wipe("book_tag");
		$b = R::dispense("book");
		$b->title = 'horror';
		R::store($b);
		$c = R::dispense("book");
		$c->title = 'creepy';
		R::store($c);
		$d = R::dispense("book");
		$d->title = "chicklit";
		R::store($d);
		R::tag($b, "horror,classic");
		R::tag($d, "women,classic");
		R::tag($c, "horror");
		$x = R::tagged("book","classic");
		asrt(count($x),2);
		$x = R::tagged("book","classic,horror");
		
		asrt(count($x),3);
				
		
	}

}