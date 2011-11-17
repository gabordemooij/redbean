<?php

class RedUNIT_Base_Formats extends RedUNIT_Base {

	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		R::$writer->tableFormatter = new MyTableFormatter;
		$page = R::dispense("page");
		$page->title = "mypage";
		$id=R::store($page);
		$page = R::dispense("page");
		$page->title = "mypage2";
		R::store($page);
		$beans = R::find("page");
		asrt(count($beans),2);
		$user = R::dispense("user");
		$user->name="me";
		R::store($user);
		R::associate($user,$page);
		asrt(count(R::related($user,"page")),1);
		$page = R::load("page",$id);
		asrt($page->title,"mypage");
		R::associate($user,$page);
		asrt(count(R::related($user,"page")),2);
		asrt(count(R::related($page,"user")),1);
		$user2 = R::dispense("user");
		$user2->name="Bob";
		R::store($user2);
		$user3 = R::dispense("user");
		$user3->name="Kim";
		R::store($user3);
		$t = R::$writer->getTables();
		asrt(in_array("xx_page",$t),true);
		asrt(in_array("xx_page_user",$t),true);
		asrt(in_array("xx_user",$t),true);
		asrt(in_array("page",$t),false);
		asrt(in_array("page_user",$t),false);
		asrt(in_array("user",$t),false);
		$page2 = R::dispense("page");
		$page2->title = "mypagex";
		R::store($page2);
		R::associate($page,$page2,'{"bla":2}');
		$pgs = R::related($page,"page");
		$p = reset($pgs);
		asrt($p->title,"mypagex");
		asrt(R::getCell("select bla from xx_page_page where bla > 0"),"2");
		$t = R::$writer->getTables();
		asrt(in_array("xx_page_page",$t),true);
		asrt(in_array("page_page",$t),false);
		
				
		R::$writer->setBeanFormatter(new MyBeanFormatter());
		$blog = R::dispense('blog');
		$blog->title = 'testing';
		$blog->blog = 'tesing';
		R::store($blog);
		$blogpost = (R::load("blog",1));
		asrt((isset($blogpost->cms_blog_id)),false);
		asrt((isset($blogpost->blog_id)),true);
		asrt(in_array("blog_id",array_keys(R::$writer->getColumns("blog"))),true);
		asrt(in_array("cms_blog_id",array_keys(R::$writer->getColumns("blog"))),false);
		
		$post = R::dispense("post");
		$post->message = "hello";
		R::associate($blog,$post);
		asrt(count(R::related($blog,"post")),1);
		
		asrt(count(R::find("blog"," title LIKE '%est%' ")),1);
		$a = R::getAll("select * from ".tbl("blog")." ");
		asrt(count($a),1);

		
		
		
	}

}