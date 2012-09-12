<?php
/**
 * RedUNIT_Sqlite_Setget
 * 
 * @file 			RedUNIT/Sqlite/Setget.php
 * @description		Tests whether values are stored correctly.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Sqlite_Setget extends RedUNIT_Sqlite {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		asrt(setget("-1"),"-1");
		asrt(setget(-1),"-1");
		asrt(setget("-0.25"),"-0.25");
		asrt(setget(-0.25),"-0.25");
		asrt(setget("0.12345678"),"0.12345678");
		asrt(setget(0.12345678),"0.12345678");
		asrt(setget("-0.12345678"),"-0.12345678");
		asrt(setget(-0.12345678),"-0.12345678");
		asrt(setget("2147483647"),"2147483647");
		asrt(setget(2147483647),"2147483647");
		asrt(setget(-2147483647),"-2147483647");
		asrt(setget("-2147483647"),"-2147483647");
		asrt(setget("2147483648"),"2147483648");
		asrt(setget("-2147483648"),"-2147483648");
		asrt(setget("199936710040730"),"199936710040730");
		asrt(setget("-199936710040730"),"-199936710040730");
		asrt(setget("2010-10-11"),"2010-10-11");
		asrt(setget("2010-10-11 12:10"),"2010-10-11 12:10");
		asrt(setget("2010-10-11 12:10:11"),"2010-10-11 12:10:11");
		asrt(setget("x2010-10-11 12:10:11"),"x2010-10-11 12:10:11");
		asrt(setget("a"),"a");
		asrt(setget("."),".");
		asrt(setget("\""),"\"");
		asrt(setget("just some text"),"just some text");
		asrt(setget(true),"1");
		asrt(setget(false),"0");
		asrt(setget("true"),"true");
		asrt(setget("false"),"false");
		asrt(setget("null"),"null");
		asrt(setget("NULL"),"NULL");
		asrt(setget(null),null);
		asrt((setget(0)==0),true);
		asrt((setget(1)==1),true);
		asrt((setget(true)==true),true);
		asrt((setget(false)==false),true);
	}

}