<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Joins
 *
 * Tests the @joined keyword, this keyword in an SQL snippet
 * allows you to join another table and use one or more of its columns
 * in the query snippet, for instance for sorting or filtering.
 *
 * @file    RedUNIT/Base/Joins.php
 * @desc    Tests joins in ownLists and trees.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Joins extends Base
{

	private function quickTestBeans( $beans, $property ) {
		$list = array();
		foreach( $beans as $bean ) {
			$list[] = $bean->{$property};
		}
		sort($list);
		return implode(',', $list);
	}

	/**
	 * Test whether we can use [via] notation in parsed joins.
	 *
	 * @return void
	 */
	public function testParsedJoinsWithVia()
	{
		$project1 = R::dispense('project');
		$project2 = R::dispense('project');
		$project1->title = 'project1';
		$project2->title = 'project2';
		$employee1 = R::dispense('employee');
		$employee2 = R::dispense('employee');
		$employee1->name = 'a';
		$employee2->name = 'b';
		$participant1 = R::dispense('participant');
		$participant2 = R::dispense('participant');
		$participant3 = R::dispense('participant');
		$participant1->employee = $employee1;
		$participant2->employee = $employee1;
		$participant3->employee = $employee2;
		$participant1->project = $project1;
		$participant2->project = $project2;
		$participant3->project = $project2;
		R::storeAll(array($participant1,$participant2,$participant3));
		$projects = R::find('project', ' @shared.employee[via:participant].name LIKE ? ', array('a'));
		asrt(count($projects),2);
		$projects = R::find('project', ' @shared.employee[via:participant].name LIKE ? AND project.title = ? ', array('a','project1'));
		asrt(count($projects),1);
		$projects = R::find('project', ' @shared.employee[via:participant].name LIKE ? AND project.title = ? ', array('a','project2'));
		asrt(count($projects),1);
		$projects = R::find('project', ' @shared.employee[via:participant].name LIKE ? ', array('b'));
		asrt(count($projects),1);
		$projects = R::find('project', ' @shared.employee[via:participant].name LIKE ? AND project.title = ? ', array('b','project1'));
		asrt(count($projects),0);
		$projects = R::find('project', ' @shared.employee[via:participant].name LIKE ? AND project.title = ? ', array('b','project2'));
		asrt(count($projects),1);
		/* shouldnt work if no [via:...] and no global aliases */
		$projects = R::find('project', ' @shared.participant.name LIKE ? ', array('a'));
		asrt(count($projects),0);
		$projects = R::find('project', ' @shared.participant.name LIKE ? AND project.title = ? ', array('a','project1'));
		asrt(count($projects),0);
		$projects = R::find('project', ' @shared.participant.name LIKE ? AND project.title = ? ', array('a','project2'));
		asrt(count($projects),0);
		$projects = R::find('project', ' @shared.participant.name LIKE ? ', array('b'));
		asrt(count($projects),0);
		$projects = R::find('project', ' @shared.participant.name LIKE ? AND project.title = ? ', array('b','project1'));
		asrt(count($projects),0);
		$projects = R::find('project', ' @shared.participant.name LIKE ? AND project.title = ? ', array('b','project2'));
		asrt(count($projects),0);
		R::aliases(array('work'=>'project'));
		$company1 = R::dispense('company');
		$company2 = R::dispense('company');
		$company1->work = $project1;
		$company2->work = $project2;
		R::storeAll( array( $company1, $company2 ) );
		$companies = R::find('company', ' @joined.work.shared.employee[via:participant].name LIKE ? ', array('a'));
		asrt(count($companies),2);
		$companies = R::find('company', ' @joined.work.shared.employee[via:participant].name LIKE ? AND @joined.work.title = ? ', array('a','project1'));
		asrt(count($companies),1);
		$companies = R::find('company', ' @joined.work.shared.employee[via:participant].name LIKE ? AND @joined.work.title = ? ', array('a','project2'));
		asrt(count($companies),1);
		$companies = R::find('company', ' @joined.work.shared.employee[via:participant].name LIKE ? ', array('b'));
		asrt(count($companies),1);
		$companies = R::find('company', ' @joined.work.shared.employee[via:participant].name LIKE ? AND @joined.work.title = ?', array('b','project1'));
		asrt(count($companies),0);
		$companies = R::find('company', ' @joined.work.shared.employee[via:participant].name LIKE ? AND @joined.work.title = ? ', array('b','project2'));
		asrt(count($companies),1);
	}

	/**
	 * Can we use joins with aliases?
	 * Can we perform bean->alias->ownlist and bean->fetchas->other
	 * like operation in SQL as well?
	 *
	 * In this test:
	 * Albert: Almanac, Study Guide
	 * Bob: Poetry Bundle, Study Guide (coauthor)
	 *
	 * Texts:
	 * Example -> in Study Guide (as source)
	 * Essay   -> in Study Guide (as book)
	 * Poem    -> in Poetry Bundle (as book)
	 * Poem    -> also in Almanac (as magazine)
	 *
	 * @return void
	 */
	public function testParsedJoinsWithAliasing()
	{
		R::nuke();
		R::aliases(array());
		$albert = R::dispense('person');
		$bob    = R::dispense('person');
		$guide  = R::dispense('book');
		$almanac= R::dispense('book');
		$poetry = R::dispense('book');
		$albert->firstname = 'Albert';
		$bob->firstname    = 'Bob';
		$guide->title = 'Study Guide';
		$almanac->title = 'Almanac';
		$poetry->title = 'Poems';
		$guide->author = $albert;
		$guide->coauthor = $bob;
		$almanac->author = $albert;
		$poetry->author = $bob;
		$poem = R::dispense('text');
		$essay = R::dispense('text');
		$example = R::dispense('text');
		$poem->content = 'poem';
		$essay->content = 'essay';
		$example->content = 'example';
		$poem->magazine = $almanac;
		$poem->book = $poetry;
		$essay->book = $guide;
		$example->source = $guide;
		$fiction = R::dispense('tag');
		$nonfiction = R::dispense('tag');
		$fiction->description    = 'fiction';
		$nonfiction->description = 'non-fiction';
		$example->sharedTag[] = $nonfiction;
		$poem->sharedTag[] = $fiction;
		$essay->sharedTag = array( $nonfiction, $fiction );
		R::storeAll( array( $poem, $essay, $example ) );
		$books = R::find('book', ' @joined.author.firstname = ? ', array('Bob'));
		asrt(count($books), 0);
		$books = R::find('book', ' @joined.author.firstname = ? ', array('Albert'));
		asrt(count($books), 0);
		$books = R::find('book', ' @joined.person[as:author].firstname = ? ', array('Bob'));
		asrt(count($books), 1);
		$books = R::find('book', ' @joined.person[as:author].firstname = ? ', array('Albert'));
		asrt(count($books), 2);
		R::freeze( TRUE );
		try { $books = R::find('book', ' @joined.author.firstname = ? ', array('Bob')); fail(); } catch ( \Exception $e ) { pass(); }
		try { $books = R::find('book', ' @joined.author.firstname = ? ', array('Albert')); fail();	} catch ( \Exception $e ) {pass();}
		$books = R::find('book', ' @joined.person[as:author].firstname = ? ', array('Bob'));
		asrt(count($books), 1);
		$books = R::find('book', ' @joined.person[as:author].firstname = ? ', array('Albert'));
		asrt(count($books), 2);
		R::aliases(array('author' => 'person','coauthor' => 'person'));
		$books = R::find('book', ' @joined.author.firstname = ? ', array('Bob'));
		asrt(count($books), 1);
		$books = R::find('book', ' @joined.author.firstname = ? ', array('Albert'));
		asrt(count($books), 2);
		$books = R::find('book', ' @joined.person[as:author].firstname = ? ', array('Bob'));
		asrt(count($books), 1);
		$books = R::find('book', ' @joined.person[as:author].firstname = ? ', array('Albert'));
		asrt(count($books), 2);
		R::freeze( FALSE );
		R::aliases(array());
		//If we want to find all the people who authored books like X
		$authors = R::find( 'person', ' @own.book[author].title LIKE ? ', array( '%Study%' ) );
		asrt( count($authors), 1 );
		$authors = R::find( 'person', ' @own.book[alias:author].title LIKE ? ', array( '%Study%' ) );
		asrt( count($authors), 1 );
		//yields Almanac and Poems
		$authors = R::find( 'person', ' @own.book[author].title LIKE ? ', array( '%m%' ) );
		asrt( count($authors), 2 );
		//yields Almanac and Poems
		$authors = R::find( 'person', ' @own.book[alias:author].title LIKE ? ', array( '%m%' ) );
		asrt( count($authors), 2 );
		//yields nothing
		$authors = R::find( 'person', ' @own.book[author].title LIKE ? ', array( '%x%' ) );
		asrt( count($authors), 0 );
		//yields nothing
		$authors = R::find( 'person', ' @own.book[alias:author].title LIKE ? ', array( '%x%' ) );
		asrt( count($authors), 0 );
		//If we want to find all the people who authored books starting with X
		$authors = R::find( 'person', ' @own.book[coauthor].title LIKE ? ', array( '%Study%' ) );
		asrt( count($authors), 1 );
		R::freeze( TRUE );
		$authors = R::find( 'person', ' @own.book[author].title LIKE ? ', array( '%Study%' ) );
		asrt( count($authors), 1 );
		$authors = R::find( 'person', ' @own.book[alias:author].title LIKE ? ', array( '%Study%' ) );
		asrt( count($authors), 1 );
		//yields Almanac and Poems
		$authors = R::find( 'person', ' @own.book[author].title LIKE ? ', array( '%m%' ) );
		asrt( count($authors), 2 );
		//yields Almanac and Poems
		$authors = R::find( 'person', ' @own.book[alias:author].title LIKE ? ', array( '%m%' ) );
		asrt( count($authors), 2 );
		asrt( $this->quickTestBeans( $authors, 'firstname' ), 'Albert,Bob');
		//yields nothing
		$authors = R::find( 'person', ' @own.book[author].title LIKE ? ', array( '%x%' ) );
		asrt( count($authors), 0 );
		//yields nothing
		$authors = R::find( 'person', ' @own.book[alias:author].title LIKE ? ', array( '%x%' ) );
		asrt( count($authors), 0 );
		$authors = R::find( 'person', ' @own.book[coauthor].title LIKE ? ', array( '%Study%' ) );
		asrt( count($authors), 1 );
		asrt( $this->quickTestBeans( $authors, 'firstname' ), 'Bob');
		R::freeze(FALSE);
		$books = R::find( 'book', ' @joined.person[author/coauthor].firstname = ?', array( 'Bob' ) );
		asrt( count($books), 2 );
		asrt( $this->quickTestBeans( $books, 'title' ), 'Poems,Study Guide');
		//If we want all books where the author or the coauthor is named 'Bob':
		$books = R::find( 'book', ' @joined.person[as:author/coauthor].firstname = ?', array( 'Bob' ) );
		asrt( count($books), 2 );
		asrt( $this->quickTestBeans( $books, 'title' ), 'Poems,Study Guide');
		//If we want all books where the author or the coauthor is named 'Albert':
		$books = R::find( 'book', ' @joined.person[as:author/].firstname = ?', array( 'Albert' ) );
		asrt( count($books), 2 );
		asrt( $this->quickTestBeans( $books, 'title' ), 'Almanac,Study Guide');
		$books = R::find( 'book', ' @joined.person[as:coauthor/author].firstname = ?', array( 'Albert' ) );
		asrt( count($books), 2 );
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].title LIKE ?', array( '%Study%' ) );
		asrt( count($authors), 2 );
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].title LIKE ?', array( '%Poem%' ) );
		asrt( count($authors), 1 );
		asrt( $this->quickTestBeans( $authors, 'firstname' ), 'Bob');
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].title LIKE ?', array( '%Alm%' ) );
		asrt( $this->quickTestBeans( $authors, 'firstname' ), 'Albert');
		asrt( count($authors), 1 );
		R::freeze(TRUE);
		$books = R::find( 'book', ' @joined.person[author/coauthor].firstname = ?', array( 'Bob' ) );
		asrt( count($books), 2 );
		$books = R::find( 'book', ' @joined.person[as:author/coauthor].firstname = ?', array( 'Bob' ) );
		asrt( count($books), 2 );
		$books = R::find( 'book', ' @joined.person[as:author/coauthor].firstname = ?', array( 'Albert' ) );
		asrt( count($books), 2 );
		$books = R::find( 'book', ' @joined.person[as:author/coauthor].firstname = ?', array( 'Albert' ) );
		asrt( count($books), 2 );
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].title LIKE ?', array( '%Study%' ) );
		asrt( count($authors), 2 );
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].title LIKE ?', array( '%Poem%' ) );
		asrt( count($authors), 1 );
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].title LIKE ?', array( '%Alm%' ) );
		asrt( count($authors), 1 );
		R::freeze(FALSE);
		//2 people as author/coauthor have written a book (study guide) that contains the essay
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text.content = ?', array( 'essay' ) );
		asrt( count($authors), 2 );
		//2 people as author/coauthor have written a book as source that contains the example
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text[alias:source].content = ?', array( 'example' ) );
		asrt( count($authors), 2 );
		//1 person as author/coauthor has written a book as magazine/source that contains the poem
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text[alias:source/magazine].content = ?', array( 'poem' ) );
		asrt( count($authors), 1 );
		//If we include book, we get 2 authors because the poem is also in the poetry bundle (book)
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text[alias:book/source/magazine].content = ?', array( 'poem' ) );
		asrt( count($authors), 2 );
		R::freeze(TRUE);
		//2 people as author/coauthor have written a book (study guide) that contains the essay
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text.content = ?', array( 'essay' ) );
		asrt( count($authors), 2 );
		//2 people as author/coauthor have written a book as source that contains the example
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text[alias:source].content = ?', array( 'example' ) );
		asrt( count($authors), 2 );
		//1 person as author/coauthor has written a book as magazine/source that contains the poem
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text[alias:source/magazine].content = ?', array( 'poem' ) );
		asrt( count($authors), 1 );
		//If we include book, we get 2 authors because the poem is also in the poetry bundle (book)
		$authors = R::find( 'person', ' @own.book[alias:author/coauthor].own.text[alias:book/source/magazine].content = ?', array( 'poem' ) );
		asrt( count($authors), 2 );
		R::freeze(FALSE);
		//Get all texts in books authored by Bob
		$texts = R::find('text', ' @joined.book.joined.person[as:author].firstname = ? ',array('Bob'));
		asrt( count($texts), 1 );
		//Get all texts in books authored by Bob as author/coauthor
		$texts = R::find('text', ' @joined.book.joined.person[as:author/coauthor].firstname = ? ',array('Bob'));
		asrt( count($texts), 2 );
		//Get all texts in books as magazines or sources authored by Bob as author/coauthor
		$texts = R::find('text', ' @joined.book[as:magazine/source].joined.person[as:author/coauthor].firstname = ? ',array('Bob'));
		asrt( count($texts), 1 );
		//Get all texts in books as magazines or sources or books authored by Bob as author/coauthor
		$texts = R::find('text', ' @joined.book[as:magazine/source/book].joined.person[as:author/coauthor].firstname = ? ',array('Bob'));
		asrt( count($texts), 3 );
		//Get all texts in books as magazines or sources or books authored by Albert as author/coauthor
		$texts = R::find('text', ' @joined.book[as:magazine/source/book].joined.person[as:author/coauthor].firstname = ? ',array('Albert'));
		asrt( count($texts), 3 );
		R::freeze(TRUE);
		//Get all texts in books authored by Bob
		$texts = R::find('text', ' @joined.book.joined.person[as:author].firstname = ? ',array('Bob'));
		asrt( count($texts), 1 );
		//Get all texts in books authored by Bob as author/coauthor
		$texts = R::find('text', ' @joined.book.joined.person[as:author/coauthor].firstname = ? ',array('Bob'));
		asrt( count($texts), 2 );
		//Get all texts in books as magazines or sources authored by Bob as author/coauthor
		$texts = R::find('text', ' @joined.book[as:magazine/source].joined.person[as:author/coauthor].firstname = ? ',array('Bob'));
		asrt( count($texts), 1 );
		//Get all texts in books as magazines or sources or books authored by Bob as author/coauthor
		$texts = R::find('text', ' @joined.book[as:magazine/source/book].joined.person[as:author/coauthor].firstname = ? ',array('Bob'));
		asrt( count($texts), 3 );
		//Get all texts in books as magazines or sources or books authored by Albert as author/coauthor
		$texts = R::find('text', ' @joined.book[as:magazine/source/book].joined.person[as:author/coauthor].firstname = ? ',array('Albert'));
		asrt( count($texts), 3 );
		R::freeze(FALSE);
		//Get all texts in books as magazines or sources or books authored by Albert as author/coauthor that have been tagged
		//as non-fiction, i.e. the example text and the essay.
		$texts = R::find('text', '
			@joined.book[as:magazine/source/book].joined.person[as:author/coauthor].firstname = ?
			AND
			@shared.tag.description = ?
			',array('Albert', 'non-fiction'));
		asrt( count($texts), 2 );
		//Get all texts in books or sources or books authored by Albert as author/coauthor that have been tagged
		//as non-fiction, i.e. nothing, only the essay
		$texts = R::find('text', '
			@joined.book[as:source/book].joined.person[as:author/coauthor].firstname = ?
			AND
			@shared.tag.description = ?
			',array('Albert', 'fiction'));
		asrt( count($texts), 1 );
	}

	/**
	 * Non-join keywords starting with @ should be
	 * left untouched.
	 */
	public function testNonJoinsLeftUntouched()
	{
		$writer = R::getWriter();
		$types = array( 'book', 'cafe', 'bean' );
		$sqls  = array(
		'@version',
		'@oined.satire',
		'@oined.satire.laugh',
		'@hared.lookalike.title',
		'@powned.by.a.hacker',
		'nothing here!',
		'shared.person.name',
		'owned.thing.name',
		'joined.thing.name',
		'shared.person.shared.tag.name',
		'owned.thing.shared.tag.name',
		'joined.thing.shared.tag.name',
		'shared.person.joined.tag.name',
		'owned.thing.joined.tag.name',
		'joined.thing.joined.tag.name',
		'shared.person.owned.tag.name',
		'owned.thing.owned.tag.name',
		'joined.thing.owned.tag.name'
		);
		$ctes   = array( TRUE, FALSE );
		foreach($types as $type) {
			foreach($sqls as $sql) {
				foreach($ctes as $cte) {
					$same = $writer->parseJoin( $type, $sql, $cte );
					asrt( trim($same), trim($sql) );
				}
			}
		}
	}

	/**
	 * Can we join multiple tables with the same parent?
	 * 
	 * @return void
	 */
	public function testMultipleJoinsSameParent()
	{
		R::nuke();
		$TheHagueNorthEnd = R::dispense(array(
			'_type' => 'painting',
			'title' => 'Northend, The Hague',
			'artist' => array(
				'_type' => 'artist',
				'name'  => 'Isaac Israels',
				'sharedCategory' => array(
					array( '_type' => 'category', 'label' => 'Haagse School' )
				)
			),
			'ownDetail' => array(
				array(
					'_type' => 'detail',
					'description' => 'awnings',
					'sharedCategory' => array(
						array( '_type' => 'category', 'label' => 'object' )
					)
				),
				array(
					'_type' => 'detail',
					'description' => 'sunlight',
					'sharedCategory' => array(
						array( '_type' => 'category', 'label' => 'daytime' )
					)
				),
			)
		));
		$Nighthawks = R::dispense(array(
			'_type' => 'painting',
			'title' => 'Nighthawks',
			'artist' => array(
				'_type' => 'artist',
				'name'  => 'Hopper',
				'sharedCategory' => array(
					array( '_type' => 'category', 'label' => 'American Realism' )
				)
			),
			'ownDetail' => array(
				array(
					'_type' => 'detail',
					'description' => 'percolator',
					'sharedCategory' => array(
						array( '_type' => 'category', 'label' => 'object' )
					)
				),
				array(
					'_type' => 'detail',
					'description' => 'cigarette',
					'sharedCategory' => array(
						array( '_type' => 'category', 'label' => 'object' )
					)
				),
				array(
					'_type' => 'detail',
					'description' => 'night',
					'sharedCategory' => array(
						array( '_type' => 'category', 'label' => 'nocturnal' )
					)
				)
			)
		));
		R::storeAll( array( $Nighthawks, $TheHagueNorthEnd ) );
		$paintings = R::find('painting', '
			@joined.artist.shared.category.label = ?
			OR
			@own.detail.shared.category.label= ?
		', array('American Realism', 'nocturnal'));
		asrt(count($paintings),1);
		$painting = reset($paintings);
		asrt($painting->title, 'Nighthawks');
		$paintings = R::find('painting', '
			@joined.artist.shared.category.label = ?
			OR
			@own.detail.shared.category.label= ?
		', array('Haagse School', 'daytime'));
		asrt(count($paintings),1);
		$painting = reset($paintings);
		asrt($painting->title, 'Northend, The Hague');
		$paintings = R::find('painting', '
			@own.detail.shared.category.label= ?
		', array('object'));
		asrt(count($paintings),2);
		$paintings = R::find('painting', '
			@own.detail.shared.category.label= ?
			AND @own.detail.description= ?
		', array('object', 'percolator'));
		asrt(count($paintings),1);
		$painting = reset($paintings);
		asrt($painting->title, 'Nighthawks');
		$paintings = R::find('painting', '
			@own.detail.shared.category.label= ?
			AND @own.detail.description= ?
		', array('object', 'ashtray'));
		asrt(count($paintings),0);
	}

	/**
	 * Complex tests for the parsed joins featured.
	 *
	 * @return void
	 */
	public function testComplexParsedJoins()
	{
		R::nuke();
		$other = R::dispense('book');
		R::store( $other );
		$book = R::dispense('book');
		$page = R::dispense('page');
		$paragraph = R::dispense('paragraph');
		$paragraph->title = 'hello';
		$book->title = 'book';
		$book->ownPage[] = $page;
		$page->ownParagraph[] = $paragraph;
		$figure = R::dispense('figure');
		$chart = R::dispense('chart');
		$chart->title = 'results';
		$page->ownFigure[] = $figure;
		$figure->ownChart[] = $chart;
		R::store($book);
		$books = R::find('book',' @own.page.own.paragraph.title = ? OR @own.page.own.figure.own.chart.title = ?', array('hello','results'));
		asrt(count($books),1);
		$book = reset($books);
		asrt($book->title, 'book');
		R::nuke();
		R::aliases(array( 'author' => 'person' ));
		$book   = R::dispense('book');
		$author = R::dispense('person');
		$detail = R::dispense('detail');
		$shop   = R::dispense('shop');
		$shop->name    = 'Books4you';
		$shop2          = R::dispense('shop');
		$shop2->name    = 'Readers Delight';
		$author->name  = 'Albert';
		$detail->title = 'Book by Albert';
		$book->ownDetailList[] = $detail;
		$book->author = $author;
		$shop->sharedBookList[] = $book;
		$book2   = R::dispense('book');
		$author2 = R::dispense('person');
		$detail2 = R::dispense('detail');
		$author2->name  = 'Bert';
		$detail2->title = 'Book by Bert';
		$book2->ownDetailList[] = $detail2;
		$book2->author = $author2;
		$shop->sharedBookList[] = $book2;
		$shop2->sharedBookList[] = $book2;
		R::store($shop);
		R::store($shop2);
		//joined+own
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? ', array('Albert', 'Book by Albert'));
		asrt(count($books),1);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? ', array('%ert%', '%Book by%'));
		asrt(count($books),2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? ', array('%ert%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? ', array('%ert%', 'Old Bookshop'));
		asrt(count($books),0);
		//joined+shared
		$books = R::find('book', ' @joined.author.name LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Books%'));
		asrt(count($books),2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Read%'));
		asrt(count($books),1);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', 'Old Bookshop'));
		asrt(count($books),0);
		//own+shared
		$books = R::find('book', ' @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Read%'));
		asrt(count($books),1);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Book%'));
		asrt(count($books),2);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', 'Old Bookshop'));
		asrt(count($books),0);
		//joined+own+shared
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Book by%', 'Books%'));
		asrt(count($books),2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Book by%', 'Read%'));
		asrt(count($books),1);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Book by%', 'Old'));
		asrt(count($books),0);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @own.detail.title LIKE ? AND @shared.shop.name LIKE ? ', array('%ert%', '%Book by%', '%'));
		asrt(count($books),2);
		//joined+joined
		$page = R::dispense('page');
		$page->text = 'Lorem Ipsum';
		$category = R::dispense('category');
		$category->name = 'biography';
		$publisher = R::dispense('publisher');
		$publisher->name = 'Good Books';
		$book->publisher = $publisher;
		$book->ownPageList[] = $page;
		$category->sharedBookList[] = $book;
		$page2 = R::dispense('page');
		$page2->text = 'Blah Blah';
		$category2 = R::dispense('category');
		$category2->name = 'fiction';
		$publisher2 = R::dispense('publisher');
		$publisher2->name = 'Gutenberg';
		$book2->publisher = $publisher2;
		$book2->ownPageList = array($page2);
		$category2->sharedBookList[] = $book2;
		R::store($category);
		R::store($category2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @joined.publisher.name LIKE ?', array('%ert%', 'Good Books'));
		asrt(count($books),1);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @joined.publisher.name LIKE ?', array('%ert%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @joined.publisher.name LIKE ?', array('Unknown', '%'));
		asrt(count($books),0);
		$books = R::find('book', ' @joined.author.name LIKE ? AND @joined.publisher.name LIKE ?', array('%', '%'));
		asrt(count($books),2);
		//shared+shared
		$books = R::find('book', ' @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('Reader%', 'fiction'));
		asrt(count($books),1);
		$books = R::find('book', ' @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('Book%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('Book%', 'biography'));
		asrt(count($books),1);
		$books = R::find('book', ' @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('Old Bookshop', '%'));
		asrt(count($books),0);
		$books = R::find('book', ' @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('%', 'horror'));
		asrt(count($books),0);
		//own+own
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?', array('Book%', 'Blah%'));
		asrt(count($books),1);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?', array('Book%', 'Lorem%'));
		asrt(count($books),1);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?', array('Book%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?', array('%', '%'));
		asrt(count($books),2);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?', array('%', 'Nah'));
		asrt(count($books),0);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?', array('Nah', '%'));
		asrt(count($books),0);
		//joined+joined+shared+shared+own+own
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ? 
		AND @joined.publisher.name LIKE ? AND @joined.author.name LIKE ?
		AND @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('Book%', 'Lorem%','Good%','Albert','Books4%','bio%'));
		asrt(count($books),1);
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ? 
		AND @joined.publisher.name LIKE ? AND @joined.author.name LIKE ?
		AND @shared.shop.name LIKE ? AND @shared.category.name LIKE ?', array('%', '%','%','%','%','%'));
		asrt(count($books),2);
		//in order clause
		$book = R::findOne('book', ' ORDER BY @shared.category.name ASC LIMIT 1');
		asrt($book->author->name, 'Albert');
		$book = R::findOne('book', ' ORDER BY @shared.category.name DESC LIMIT 1');
		asrt($book->author->name, 'Bert');
		$book = R::findOne('book', ' ORDER BY @own.detail.title ASC LIMIT 1');
		asrt($book->author->name, 'Albert');
		$book = R::findOne('book', ' ORDER BY @own.detail.title DESC LIMIT 1');
		asrt($book->author->name, 'Bert');
		//order+criteria
		$book = R::findOne('book', ' @joined.publisher.name LIKE ? ORDER BY @shared.category.name ASC LIMIT 1', array('%'));
		asrt($book->author->name, 'Albert');
		$book = R::findOne('book', ' @joined.publisher.name LIKE ? ORDER BY @shared.category.name DESC LIMIT 1', array('%'));
		asrt($book->author->name, 'Bert');
		$book = R::findOne('book', ' @joined.publisher.name LIKE ? ORDER BY @own.detail.title ASC LIMIT 1', array('%'));
		asrt($book->author->name, 'Albert');
		$book = R::findOne('book', ' @joined.publisher.name LIKE ? ORDER BY @own.detail.title DESC LIMIT 1', array('%'));
		asrt($book->author->name, 'Bert');
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?
		AND @joined.publisher.name LIKE ? AND @joined.author.name LIKE ?
		AND @shared.shop.name LIKE ? AND @shared.category.name LIKE ?
		ORDER BY @own.detail.title ASC
		', array('%', '%','%','%','%','%'));
		asrt(count($books),2);
		$first = reset($books);
		$last  = end($books);
		asrt($first->author->name, 'Albert');
		asrt($last->author->name, 'Bert');
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?
		AND @joined.publisher.name LIKE ? AND @joined.author.name LIKE ?
		AND @shared.shop.name LIKE ? AND @shared.category.name LIKE ?
		ORDER BY
		@shared.shop.name DESC,
		@own.detail.title ASC
		', array('%', '%','%','%','%','%'));
		asrt(count($books),2);
		$first = reset($books);
		$last  = end($books);
		asrt($first->author->name, 'Bert');
		asrt($last->author->name, 'Albert');
		$books = R::find('book', ' @own.detail.title LIKE ? AND @own.page.text LIKE ?
		AND @joined.publisher.name LIKE ? AND @joined.author.name LIKE ?
		AND @shared.shop.name LIKE ? AND @shared.category.name LIKE ?
		ORDER BY
		@joined.publisher.name ASC,
		@shared.shop.name DESC,
		@own.detail.title ASC
		', array('%', '%','%','%','%','%'));
		asrt(count($books),2);
		$first = reset($books);
		$last  = end($books);
		asrt($first->author->name, 'Albert');
		asrt($last->author->name, 'Bert');
	}

	/**
	 * Tests new parsed joins.
	 *
	 * @return void
	 */
	public function testParsedJoins()
	{
		$person = R::dispense('person');
		$person->name = 'Albert';
		$book = R::dispense('book');
		$book->title = 'Book by Albert.';
		$book->author = $person;
		$person->movement = R::dispense(array('_type'=>'movement','name'=>'romanticism'));
		R::store( $book );
		$albert = $person;
		$person = R::dispense('person');
		$person->name = 'Bert';
		$book = R::dispense('book');
		$book->title = 'Book by Bert.';
		$book->author = $person;
		$bert = $person;
		$person->movement = R::dispense(array('_type'=>'movement','name'=>'gothic'));
		R::store( $book );
		R::aliases(array( 'author' => 'person' ));
		$books = R::find( 'book', ' @joined.author.name LIKE ? ', array('A%'));
		asrt(count($books), 1);
		$book = reset($books);
		asrt($book->title,'Book by Albert.');
		asrt($book->fetchAs('person')->author->name,'Albert');
		$people = R::find( 'person', ' @joined.movement.name = ? ', array('romanticism')); // This works (aliases not involved)
		asrt(count($people), 1);
		$people = R::find( 'person', ' @joined.movement.name = ? ', array('gothic')); // This works (aliases not involved)
		asrt(count($people), 1);		
		$people = R::find( 'person', ' @joined.movement.name = ? ', array('popscience')); // This works (aliases not involved)
		asrt(count($people), 0);
		$movements = R::find( 'movement', ' @own.author.name LIKE ? ', array( 'A%' )); // This works
		asrt(count($movements), 1);
		$movement = reset($movements);
		asrt($movement->name, 'romanticism');
		R::freeze(TRUE);
		try{
			R::find( 'person', ' @own.book.title LIKE ? ', array( 'A%' )); // This doesn't work as RedBean cannot guess which column it should bind the person to in the book table.
			fail();
		} catch(\Exception $e) {
			pass();
		}
		R::freeze(FALSE);
		$group = R::dispense('group');
		$group->name = 'a';
		$group->sharedAuthorList =  array($bert, $albert);
		R::store($group);
		$group = R::dispense('group');
		$group->name = 'b';
		$group->sharedAuthorList =  array($bert);
		R::store($group);
		$groups = R::find( 'group', ' @shared.author.name = ? ', array( 'Bert' )); // This works
		asrt(count($groups),2);
		R::tag($albert, array('male','writer'));
		R::tag($bert, array('male'));
		$people = R::find( 'person', ' @shared.tag.title = ? ', array( 'writer' )); // This works (aliases not involved)
		asrt(count($people),1);
		R::tag($albert, array('male','writer'));
		R::tag($bert, array('male'));
		$people = R::find( 'person', ' @shared.tag.title = ? ', array( 'male' )); // This works (aliases not involved)
		asrt(count($people),2);
		$user1 = R::dispense('user');
		$user1->name = 'user1';
		$user2 = R::dispense('user');
		$user2->name = 'user2';
		$status = R::dispense('status');
		$status->whitelist = TRUE;
		$user1->status = $status;
		$status2 = R::dispense('status');
		$status2->whitelist = FALSE;
		$user2->status = $status2;
		R::storeAll(array($user1,$user2));
		$whitelisted = R::find( 'user', ' @joined.status.whitelist = ? ', array( 1 ) );
		asrt(count($whitelisted), 1);
		$user = reset($whitelisted);
		asrt($user->name, 'user1');
		$whitelisted = R::find( 'user', ' @joined.status.whitelist = ? ', array( 0 ) );
		R::debug(0);
		asrt(count($whitelisted), 1);
		$user = reset($whitelisted);
		asrt($user->name, 'user2');
	}

	/**
	 * Tests joins with ownCount().
	 * 
	 * @return void
	 */
	public function testJoinsInCount()
	{
		R::nuke();
		$author = R::dispense( 'author' );
		$book = R::dispense( 'book' );
		$info = R::dispense( 'info' );
		$info->title = 'x';
		$author->xownBookList[] = $book;
		$book->info = $info;
		$book = R::dispense( 'book' );
		$info = R::dispense( 'info' );
		$info->title = 'y';
		$author->xownBookList[] = $book;
		$book->info = $info;
		R::store( $author );
		$author = $author->fresh();
		$books = $author->withCondition(' @joined.info.title != ? ', array('x'))->countOwn('book');
		asrt($books, 1);
		$books = $author->withCondition(' @joined.info.title != ? ', array('y'))->countOwn('book');
		asrt($books, 1);
		$books = $author->withCondition(' @joined.info.title IN (?,?) ', array('x','y'))->countOwn('book');
		asrt($books, 2);
	}

	/**
	 * Test Joins.
	 *
	 * @return void
	 */
	public function testJoins()
	{
		R::nuke();
		list($a1, $a2, $a3) = R::dispense('area', 3);
		list($p1, $p2) = R::dispense('person', 2);
		list($v1, $v2, $v3, $v4) = R::dispense('visit', 4);
		$a1->name = 'Belgium';
		$a2->name = 'Arabia';
		$a3->name = 'France';
		$v1->person = $p1;
		$v2->person = $p1;
		$v3->person = $p2;
		$v4->person = $p2;
		$v1->area = $a3;
		$v2->area = $a2;
		$v3->area = $a2;
		$v4->area = $a1;
		$v1->label = 'v1 to France';
		$v2->label = 'v2 to Arabia';
		$v3->label = 'v3 to Arabia';
		$v4->label = 'v4 to Belgium';
		R::storeAll( array($v1,$v2,$v3,$v4) );
		$visits = $p1->ownVisit;
		asrt( is_array( $visits ), TRUE );
		asrt( count( $visits ), 2 );
		$names = array();
		foreach( $visits as $visit ) {
			asrt( isset( $visit->label ), TRUE );
			asrt( isset( $visit->name ), FALSE );
			asrt( isset( $visit->visit_id ), FALSE );
			$names[] = $visit->label;
		}
		$labelList = implode( ',', $names );
		asrt( $labelList, 'v1 to France,v2 to Arabia' );
		$visits = $p1
			->with('ORDER BY @joined.area.name ASC')->ownVisit;
		asrt( is_array( $visits ), TRUE );
		asrt( count( $visits ), 2 );
		$names = array();
		foreach( $visits as $visit ) {
			asrt( isset( $visit->label ), TRUE );
			asrt( isset( $visit->name ), FALSE );
			asrt( isset( $visit->visit_id ), FALSE );
			$names[] = $visit->label;
		}
		$labelList = implode( ',', $names );
		asrt( $labelList, 'v2 to Arabia,v1 to France' );
	}

	/**
	 * Helper for the next test.
	 *
	 * @param array  $books      the books we are going to check
	 * @param string $numberList the numbers that are expected
	 *
	 * @return void
	 */
	private function checkBookNumbers( $books, $numberList )
	{
		$numbers = explode( ',', $numberList );
		asrt( is_array( $books ), TRUE );
		asrt( count( $books ), count( $numbers ) );
		$bookNumbers = '';
		$bookNumberArray = array();
		foreach( $books as $book ) {
			asrt( isset( $book->num ), TRUE );
			asrt( isset( $book->title), FALSE );
			$bookNumberArray[] = $book->num;
		}
		$bookNumbers = implode( ',', $bookNumberArray);
		asrt( $bookNumbers, $numberList );
	}

	/**
	 * Tests the more complicated scenarios for
	 * with-joins.
	 *
	 * @return void
	 */
	private function testComplexCombinationsJoins()
	{
		$author = R::dispense( 'author' );
		$books = R::dispense( 'book', 4 );
		$books[0]->num = 0;
		$books[1]->num = 1;
		$books[2]->num = 2;
		$books[3]->num = 3;
		$books[0]->info = R::dispense('info')->setAttr('title', 'Learning PHP');
		$books[1]->info = R::dispense('info')->setAttr('title', 'Learning PHP and JavaScript');
		$books[2]->info = R::dispense('info')->setAttr('title', 'Learning Cobol');
		$books[3]->info = R::dispense('info')->setAttr('title','Gardening for Beginners');
		$books[0]->category = R::dispense('category')->setAttr('title', 'computers');
		$books[1]->category = R::dispense('category')->setAttr('title', 'computers');
		$books[2]->category = R::dispense('category')->setAttr('title', 'computers');
		$books[3]->category = R::dispense('category')->setAttr('title','gardening');
		$author->ownBookList = $books;
		R::store($author);
		//Base test...
		$books = $author->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2,3' );
		//Just a basic Join...
		$books = $author->withCondition(' @joined.info.title LIKE ? ORDER BY book.num ASC ', array( '%PHP%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1' );
		//Mix Join and criteria
		$books = $author->withCondition(' @joined.info.title LIKE ? AND num > 0 ORDER BY book.num ASC ', array( '%PHP%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '1' );
		//Basic join
		$books = $author->withCondition(' @joined.info.title LIKE ? ORDER BY book.num ASC', array( '%ing%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2,3' );
		//Two joins
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.category.title = ? ORDER BY book.num ASC', array( '%ing%', 'computers' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2' );
		//Join the same type twice... and order
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.category.title = ? ORDER BY @joined.info.title ASC ', array( '%ing%', 'computers' ) )->ownBookList;
		$this->checkBookNumbers( $books, '2,0,1' );
		//Join the same type twice
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.info.title LIKE ? ORDER BY book.num ASC', array( '%ing%', '%Learn%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '0,1,2' );
		//Join the same type 3 times and order
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.info.title LIKE ? ORDER BY @joined.info.title DESC', array( '%ing%', '%Learn%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '1,0,2' );
		//Join the same type 3 times and order and limit
		$books = $author->withCondition(' @joined.info.title LIKE ? AND @joined.info.title LIKE ? ORDER BY @joined.info.title DESC LIMIT 1', array( '%ing%', '%Learn%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '1' );
		//Other combinations I can think of...
		$books = $author->withCondition(' @joined.category.title LIKE ? ORDER BY @joined.info.title DESC', array( '%ing%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '3' );
		$books = $author->withCondition(' @joined.category.title LIKE ? AND num < 4 ORDER BY @joined.info.title DESC', array( '%ing%' ) )->ownBookList;
		$this->checkBookNumbers( $books, '3' );
		//multiple ordering
		$books = $author->with(' ORDER BY @joined.category.title ASC, @joined.info.title ASC' )->ownBookList;
		$this->checkBookNumbers( $books, '2,0,1,3' );
		$books = $author->with(' ORDER BY @joined.category.title DESC, @joined.info.title ASC' )->ownBookList;
		$this->checkBookNumbers( $books, '3,2,0,1' );
		$books = $author->with(' ORDER BY @joined.category.title DESC, @joined.info.title ASC LIMIT 2' )->ownBookList;
		$this->checkBookNumbers( $books, '3,2' );
	}

	/**
	 * Tests the more complicated scenarios for
	 * with-joins.
	 *
	 * @return void
	 */
	public function testComplexInFrozenMode()
	{
		R::freeze( FALSE );
		$this->testComplexCombinationsJoins();
		R::freeze( TRUE );
		$this->testComplexCombinationsJoins();
		R::freeze( FALSE );
	}

	/**
	 * Tests R::setNarrowFieldMode() and
	 * OODBBean::ignoreJoinFeature().
	 */
	public function testSystemWideSettingsForJoins()
	{
		R::nuke();
		$author = R::dispense( 'author' );
		$book = R::dispense( 'book' );
		$info = R::dispense( 'info' );
		$info->title = 'x';
		$author->xownBookList[] = $book;
		$book->info = $info;
		R::store( $author );
		$author = $author->fresh();
		$books = $author->withCondition(' @joined.info.title != ? ', array('y1') )->xownBookList;
		$firstBook = reset( $books );
		asrt( isset( $firstBook->title ), FALSE );
		R::setNarrowFieldMode( FALSE );
		$author = $author->fresh();
		$books = $author->withCondition(' @joined.info.title != ? ', array('y2') )->xownBookList;
		$firstBook = reset( $books );
		asrt( isset( $firstBook->title ), TRUE );
		R::setNarrowFieldMode( TRUE );
	}
}
