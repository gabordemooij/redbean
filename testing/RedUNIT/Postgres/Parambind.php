<?php

class RedUNIT_Postgres_Parambind extends RedUNIT_Postgres {

	public function run() {
		testpack("param binding pgsql");
		$page = R::dispense("page");
		$page->name = "abc";
		$page->number = 2;
		R::store($page);
		R::exec("insert into page (name) values(:name) ", array(":name"=>"my name"));
		R::exec("insert into page (number) values(:one) ", array(":one"=>1));
		R::exec("insert into page (number) values(:one) ", array(":one"=>"1"));
		R::exec("insert into page (number) values(:one) ", array(":one"=>"1234"));
		R::exec("insert into page (number) values(:one) ", array(":one"=>"-21"));
		pass();
		
	}

}