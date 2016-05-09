<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\QueryWriter as QueryWriter;

/**
 * Glue
 *
 * RedBeanPHP does NOT parse entire queries and it does not
 * ship with a query builder. However because RedBeanPHP
 * facilitates mixing-in SQL snippets using methods like
 * find(), via(), with(), withCondition() and so on...,
 * it needs to be able to figure out how two query part strings
 * can be connected to eachother. In particular parts beginning with or
 * without 'WHERE', 'AND' and 'OR'. This test checks whether the
 * QueryWriter has the ability to glue together query parts correctly.
 * The gluer is part of the QueryWriter, however since this narrow
 * slice of SQL syntax is so generic it's been implemented at Writer
 * level and all drivers in RedBeanPHP inherit the generic implementation.
 * At the moment of writing no additional glue methods had to be
 * implemented.
 *
 * @file    RedUNIT/Blackhole/Glue.php
 * @desc    Tests query gluer.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Glue extends Blackhole
{
	/**
	 * Tests whether we can intelligently glue together SQL snippets.
	 *
	 * @return void
	 */
	public function testGlue()
	{
		$writer = R::getWriter();
		//Can we add a condition without having to type 'WHERE' - usual suspects
		asrt( $writer->glueSQLCondition( ' name = ? '), ' WHERE  name = ? ' );
		asrt( $writer->glueSQLCondition( ' value1 > ? OR value < ? '), ' WHERE  value1 > ? OR value < ? ' );
		//Does it recognize NON-WHERE conditions? - usual suspects
		asrt( $writer->glueSQLCondition( ' ORDER BY name '), ' ORDER BY name ' );
		asrt( $writer->glueSQLCondition( ' LIMIT 10 '), ' LIMIT 10 ' );
		asrt( $writer->glueSQLCondition( ' OFFSET 20 '), ' OFFSET 20 ' );
		//highly doubtful but who knows... - I think nobody will ever use this in a query snippet.
		asrt( $writer->glueSQLCondition( ' GROUP BY grp '), ' GROUP BY grp ' );
		asrt( $writer->glueSQLCondition( ' HAVING x = ? '), ' HAVING x = ? ' );
		//can we replace WHERE with AND ?
		asrt( $writer->glueSQLCondition( ' AND name = ? ', QueryWriter::C_GLUE_WHERE ), ' WHERE  name = ? ' );
		//can we glue with AND instead of WHERE ?
		asrt( $writer->glueSQLCondition( ' value1 > ? OR value < ? ', QueryWriter::C_GLUE_AND ), ' AND  value1 > ? OR value < ? ' );
		//non-cases
		asrt( $writer->glueSQLCondition( ' GROUP BY grp ', QueryWriter::C_GLUE_WHERE ), ' GROUP BY grp ' );
		asrt( $writer->glueSQLCondition( ' GROUP BY grp ', QueryWriter::C_GLUE_AND ), ' GROUP BY grp ' );
	}
}
