<?php /*

                   .______.
_______   ____   __| _/\_ |__   ____ _____    ____
\_  __ \_/ __ \ / __ |  | __ \_/ __ \\__  \  /    \
 |  | \/\  ___// /_/ |  | \_\ \  ___/ / __ \|   |  \
 |__|    \___  >____ |  |___  /\___  >____  /___|  /
             \/     \/      \/     \/     \/     \/



RedBean Database Objects -
Written by Gabor de Mooij (c) copyright 2009-2012

RedBean is DUAL Licensed BSD and GPLv2. You may choose the license that fits
best for your project.


BSD/GPLv2 License

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
* Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
* Neither the name of RedBeanPHP nor the
names of its contributors may be used to endorse or promote products
derived from this software without specific prior written permission.


THIS SOFTWARE IS PROVIDED BY GABOR DE MOOIJ ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL GABOR DE MOOIJ BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

RedBeanPHP is Written by Gabor de Mooij (G.J.G.T de Mooij) Copyright (c) 2011.


GPLv2 LICENSE


        GNU GENERAL PUBLIC LICENSE
           Version 2, June 1991

 Copyright (C) 1989, 1991 Free Software Foundation, Inc.
 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 Everyone is permitted to copy and distribute verbatim copies
 of this license document, but changing it is not allowed.

          Preamble

  The licenses for most software are designed to take away your
freedom to share and change it.  By contrast, the GNU General Public
License is intended to guarantee your freedom to share and change free
software--to make sure the software is free for all its users.  This
General Public License applies to most of the Free Software
Foundation's software and to any other program whose authors commit to
using it.  (Some other Free Software Foundation software is covered by
the GNU Lesser General Public License instead.)  You can apply it to
your programs, too.

  When we speak of free software, we are referring to freedom, not
price.  Our General Public Licenses are designed to make sure that you
have the freedom to distribute copies of free software (and charge for
this service if you wish), that you receive source code or can get it
if you want it, that you can change the software or use pieces of it
in new free programs; and that you know you can do these things.

  To protect your rights, we need to make restrictions that forbid
anyone to deny you these rights or to ask you to surrender the rights.
These restrictions translate to certain responsibilities for you if you
distribute copies of the software, or if you modify it.

  For example, if you distribute copies of such a program, whether
gratis or for a fee, you must give the recipients all the rights that
you have.  You must make sure that they, too, receive or can get the
source code.  And you must show them these terms so they know their
rights.

  We protect your rights with two steps: (1) copyright the software, and
(2) offer you this license which gives you legal permission to copy,
distribute and/or modify the software.

  Also, for each author's protection and ours, we want to make certain
that everyone understands that there is no warranty for this free
software.  If the software is modified by someone else and passed on, we
want its recipients to know that what they have is not the original, so
that any problems introduced by others will not reflect on the original
authors' reputations.

  Finally, any free program is threatened constantly by software
patents.  We wish to avoid the danger that redistributors of a free
program will individually obtain patent licenses, in effect making the
program proprietary.  To prevent this, we have made it clear that any
patent must be licensed for everyone's free use or not licensed at all.

  The precise terms and conditions for copying, distribution and
modification follow.

        GNU GENERAL PUBLIC LICENSE
   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

  0. This License applies to any program or other work which contains
a notice placed by the copyright holder saying it may be distributed
under the terms of this General Public License.  The "Program", below,
refers to any such program or work, and a "work based on the Program"
means either the Program or any derivative work under copyright law:
that is to say, a work containing the Program or a portion of it,
either verbatim or with modifications and/or translated into another
language.  (Hereinafter, translation is included without limitation in
the term "modification".)  Each licensee is addressed as "you".

Activities other than copying, distribution and modification are not
covered by this License; they are outside its scope.  The act of
running the Program is not restricted, and the output from the Program
is covered only if its contents constitute a work based on the
Program (independent of having been made by running the Program).
Whether that is true depends on what the Program does.

  1. You may copy and distribute verbatim copies of the Program's
source code as you receive it, in any medium, provided that you
conspicuously and appropriately publish on each copy an appropriate
copyright notice and disclaimer of warranty; keep intact all the
notices that refer to this License and to the absence of any warranty;
and give any other recipients of the Program a copy of this License
along with the Program.

You may charge a fee for the physical act of transferring a copy, and
you may at your option offer warranty protection in exchange for a fee.

  2. You may modify your copy or copies of the Program or any portion
of it, thus forming a work based on the Program, and copy and
distribute such modifications or work under the terms of Section 1
above, provided that you also meet all of these conditions:

    a) You must cause the modified files to carry prominent notices
    stating that you changed the files and the date of any change.

    b) You must cause any work that you distribute or publish, that in
    whole or in part contains or is derived from the Program or any
    part thereof, to be licensed as a whole at no charge to all third
    parties under the terms of this License.

    c) If the modified program normally reads commands interactively
    when run, you must cause it, when started running for such
    interactive use in the most ordinary way, to print or display an
    announcement including an appropriate copyright notice and a
    notice that there is no warranty (or else, saying that you provide
    a warranty) and that users may redistribute the program under
    these conditions, and telling the user how to view a copy of this
    License.  (Exception: if the Program itself is interactive but
    does not normally print such an announcement, your work based on
    the Program is not required to print an announcement.)

These requirements apply to the modified work as a whole.  If
identifiable sections of that work are not derived from the Program,
and can be reasonably considered independent and separate works in
themselves, then this License, and its terms, do not apply to those
sections when you distribute them as separate works.  But when you
distribute the same sections as part of a whole which is a work based
on the Program, the distribution of the whole must be on the terms of
this License, whose permissions for other licensees extend to the
entire whole, and thus to each and every part regardless of who wrote it.

Thus, it is not the intent of this section to claim rights or contest
your rights to work written entirely by you; rather, the intent is to
exercise the right to control the distribution of derivative or
collective works based on the Program.

In addition, mere aggregation of another work not based on the Program
with the Program (or with a work based on the Program) on a volume of
a storage or distribution medium does not bring the other work under
the scope of this License.

  3. You may copy and distribute the Program (or a work based on it,
under Section 2) in object code or executable form under the terms of
Sections 1 and 2 above provided that you also do one of the following:

    a) Accompany it with the complete corresponding machine-readable
    source code, which must be distributed under the terms of Sections
    1 and 2 above on a medium customarily used for software interchange; or,

    b) Accompany it with a written offer, valid for at least three
    years, to give any third party, for a charge no more than your
    cost of physically performing source distribution, a complete
    machine-readable copy of the corresponding source code, to be
    distributed under the terms of Sections 1 and 2 above on a medium
    customarily used for software interchange; or,

    c) Accompany it with the information you received as to the offer
    to distribute corresponding source code.  (This alternative is
    allowed only for noncommercial distribution and only if you
    received the program in object code or executable form with such
    an offer, in accord with Subsection b above.)

The source code for a work means the preferred form of the work for
making modifications to it.  For an executable work, complete source
code means all the source code for all modules it contains, plus any
associated interface definition files, plus the scripts used to
control compilation and installation of the executable.  However, as a
special exception, the source code distributed need not include
anything that is normally distributed (in either source or binary
form) with the major components (compiler, kernel, and so on) of the
operating system on which the executable runs, unless that component
itself accompanies the executable.

If distribution of executable or object code is made by offering
access to copy from a designated place, then offering equivalent
access to copy the source code from the same place counts as
distribution of the source code, even though third parties are not
compelled to copy the source along with the object code.

  4. You may not copy, modify, sublicense, or distribute the Program
except as expressly provided under this License.  Any attempt
otherwise to copy, modify, sublicense or distribute the Program is
void, and will automatically terminate your rights under this License.
However, parties who have received copies, or rights, from you under
this License will not have their licenses terminated so long as such
parties remain in full compliance.

  5. You are not required to accept this License, since you have not
signed it.  However, nothing else grants you permission to modify or
distribute the Program or its derivative works.  These actions are
prohibited by law if you do not accept this License.  Therefore, by
modifying or distributing the Program (or any work based on the
Program), you indicate your acceptance of this License to do so, and
all its terms and conditions for copying, distributing or modifying
the Program or works based on it.

  6. Each time you redistribute the Program (or any work based on the
Program), the recipient automatically receives a license from the
original licensor to copy, distribute or modify the Program subject to
these terms and conditions.  You may not impose any further
restrictions on the recipients' exercise of the rights granted herein.
You are not responsible for enforcing compliance by third parties to
this License.

  7. If, as a consequence of a court judgment or allegation of patent
infringement or for any other reason (not limited to patent issues),
conditions are imposed on you (whether by court order, agreement or
otherwise) that contradict the conditions of this License, they do not
excuse you from the conditions of this License.  If you cannot
distribute so as to satisfy simultaneously your obligations under this
License and any other pertinent obligations, then as a consequence you
may not distribute the Program at all.  For example, if a patent
license would not permit royalty-free redistribution of the Program by
all those who receive copies directly or indirectly through you, then
the only way you could satisfy both it and this License would be to
refrain entirely from distribution of the Program.

If any portion of this section is held invalid or unenforceable under
any particular circumstance, the balance of the section is intended to
apply and the section as a whole is intended to apply in other
circumstances.

It is not the purpose of this section to induce you to infringe any
patents or other property right claims or to contest validity of any
such claims; this section has the sole purpose of protecting the
integrity of the free software distribution system, which is
implemented by public license practices.  Many people have made
generous contributions to the wide range of software distributed
through that system in reliance on consistent application of that
system; it is up to the author/donor to decide if he or she is willing
to distribute software through any other system and a licensee cannot
impose that choice.

This section is intended to make thoroughly clear what is believed to
be a consequence of the rest of this License.

  8. If the distribution and/or use of the Program is restricted in
certain countries either by patents or by copyrighted interfaces, the
original copyright holder who places the Program under this License
may add an explicit geographical distribution limitation excluding
those countries, so that distribution is permitted only in or among
countries not thus excluded.  In such case, this License incorporates
the limitation as if written in the body of this License.

  9. The Free Software Foundation may publish revised and/or new versions
of the General Public License from time to time.  Such new versions will
be similar in spirit to the present version, but may differ in detail to
address new problems or concerns.

Each version is given a distinguishing version number.  If the Program
specifies a version number of this License which applies to it and "any
later version", you have the option of following the terms and conditions
either of that version or of any later version published by the Free
Software Foundation.  If the Program does not specify a version number of
this License, you may choose any version ever published by the Free Software
Foundation.

  10. If you wish to incorporate parts of the Program into other free
programs whose distribution conditions are different, write to the author
to ask for permission.  For software which is copyrighted by the Free
Software Foundation, write to the Free Software Foundation; we sometimes
make exceptions for this.  Our decision will be guided by the two goals
of preserving the free status of all derivatives of our free software and
of promoting the sharing and reuse of software generally.

          NO WARRANTY

  11. BECAUSE THE PROGRAM IS LICENSED FREE OF CHARGE, THERE IS NO WARRANTY
FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE LAW.  EXCEPT WHEN
OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES
PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED
OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.  THE ENTIRE RISK AS
TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU.  SHOULD THE
PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING,
REPAIR OR CORRECTION.

  12. IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR
REDISTRIBUTE THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES,
INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING
OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED
TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY
YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER
PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE
POSSIBILITY OF SUCH DAMAGES.

*/


/**
 * Interface for database drivers
 *
 * @file			RedBean/Driver.php
 * @description		Describes the API for database classes
 *					The Driver API conforms to the ADODB pseudo standard
 *					for database drivers.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Driver {
	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param  string $sql SQL to be executed
	 *
	 * @return array $results result
	 */
	public function GetAll( $sql, $aValues=array() );

	/**
	 * Runs a query and fetches results as a column.
	 *
	 * @param  string $sql SQL Code to execute
	 *
	 * @return array	$results Resultset
	 */
	public function GetCol( $sql, $aValues=array() );

	/**
	 * Runs a query an returns results as a single cell.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return mixed $cellvalue result cell
	 */
	public function GetCell( $sql, $aValues=array() );

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return array $row result row
	 */
	public function GetRow( $sql, $aValues=array() );

	/**
	 * Executes SQL code and allows key-value binding.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL. This method has no return value.
	 *
	 * @param string $sql	  SQL Code to execute
	 * @param array  $aValues Values to bind to SQL query
	 *
	 * @return void
	 */
	public function Execute( $sql, $aValues=array() );

	/**
	 * Escapes a string for use in SQL using the currently selected
	 * driver driver.
	 *
	 * @param string $string string to be escaped
	 *
	 * @return string $string escaped string
	 */
	public function Escape( $str );

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer $id primary key ID
	 */
	public function GetInsertID();


	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected driver driver supports this feature.
	 *
	 * @return integer $numOfRows number of rows affected
	 */
	public function Affected_Rows();

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results. All SQL code that passes through the driver will be
	 * passes on to the screen for inspection.
	 * This method has no return value.
	 *
	 * @param boolean $trueFalse turn on/off
	 *
	 * @return void
	 */
	public function setDebugMode( $tf );

	/**
	 * Starts a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function CommitTrans();

	/**
	 * Commits a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function StartTrans();

	/**
	 * Rolls back a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function FailTrans();
}


/**
 * PDO Driver
 * @file			RedBean/PDO.php
 * @description		PDO Driver
 *					This Driver implements the RedBean Driver API
 * @author			Gabor de Mooij and the RedBeanPHP Community, Desfrenes
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright Desfrenes & Gabor de Mooij and the RedBeanPHP community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_Driver_PDO implements RedBean_Driver {


	/**
	 * Contains database DSN for connecting to database.
	 * @var string
	 */
	protected $dsn;
	
	/**
	 * Whether we are in debugging mode or not.
	 * @var boolean
	 */
	protected $debug = false;

	/**
	 * Holds an instance of ILogger implementation.
	 * @var RedBean_ILogger
	 */
	protected $logger = NULL;

	/**
	 * Holds the PDO instance.
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * Holds integer number of affected rows from latest query
	 * if driver supports this feature.
	 * @var integer
	 */
	protected $affected_rows;

	/**
	 * Holds result resource.
	 * @var integer
	 */
	protected $rs;


	/**
	 * Contains arbitrary connection data.
	 * @var array
	 */
	protected $connectInfo = array();


	/**
	 * Whether you want to use classic String Only binding -
	 * backward compatibility.
	 * @var bool
	 */
	public $flagUseStringOnlyBinding = false;

	/**
	 * Whether we are currently connected or not.
	 * This flag is being used to delay the connection until necessary.
	 * Delaying connections is a good practice to speed up scripts that
	 * don't need database connectivity but for some reason want to
	 * init RedbeanPHP.
	 * @var boolean
	 */
	protected $isConnected = false;

	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing PDO connection.
	 * Examples:
	 *    $driver = new RedBean_Driver_PDO($dsn, $user, $password);
	 *    $driver = new RedBean_Driver_PDO($existingConnection);
	 *
	 * @param string|PDO  $dsn	 database connection string
	 * @param string      $user optional
	 * @param string      $pass optional
	 *
	 * @return void
	 */
	public function __construct($dsn, $user = null, $pass = null) {
		if ($dsn instanceof PDO) {
			$this->pdo = $dsn;
			$this->isConnected = true;
			$this->pdo->setAttribute(1002, 'SET NAMES utf8');
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			// make sure that the dsn at least contains the type
			$this->dsn = $this->getDatabaseType();
		} else {
			$this->dsn = $dsn;
			$this->connectInfo = array( 'pass'=>$pass, 'user'=>$user );
		}
	}

	/**
	 * Establishes a connection to the database using PHP PDO
	 * functionality. If a connection has already been established this
	 * method will simply return directly. This method also turns on
	 * UTF8 for the database and PDO-ERRMODE-EXCEPTION as well as
	 * PDO-FETCH-ASSOC.
	 *
	 * @return void
	 */
	public function connect() {
		if ($this->isConnected) return;
		$user = $this->connectInfo['user'];
		$pass = $this->connectInfo['pass'];
		//PDO::MYSQL_ATTR_INIT_COMMAND
		$this->pdo = new PDO(
				  $this->dsn,
				  $user,
				  $pass,
				  array(1002 => 'SET NAMES utf8',
							 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
							 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

				  )
		);
		$this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
		$this->isConnected = true;
	}

	/**
	 * Binds parameters. This method binds parameters to a PDOStatement for
	 * Query Execution. This method binds parameters as NULL, INTEGER or STRING
	 * and supports both named keys and question mark keys.
	 *
	 * @param  PDOStatement $s       PDO Statement instance
	 * @param  array        $aValues values that need to get bound to the statement
	 *
	 * @return void
	 */
	protected function bindParams($s,$aValues) {
		foreach($aValues as $key=>&$value) {
			if (is_integer($key)) {

				if (is_null($value)){
					$s->bindValue($key+1,null,PDO::PARAM_NULL);
				}
				elseif (!$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt($value) && $value < 2147483648) {
					$s->bindParam($key+1,$value,PDO::PARAM_INT);
				}
				else {
					$s->bindParam($key+1,$value,PDO::PARAM_STR);
				}
			}
			else {
				if (is_null($value)){
					$s->bindValue($key,null,PDO::PARAM_NULL);
				}
				elseif (!$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt($value) &&  $value < 2147483648) {
					$s->bindParam($key,$value,PDO::PARAM_INT);
				}
				else {
					$s->bindParam($key,$value,PDO::PARAM_STR);
				}
			}

		}
	}

	/**
	 * Runs a query. Internal function, available for subclasses. This method
	 * runs the actual SQL query and binds a list of parameters to the query.
	 * slots. The result of the query will be stored in the protected property
	 * $rs (always array). The number of rows affected (result of rowcount, if supported by database)
	 * is stored in protected property $affected_rows. If the debug flag is set
	 * this function will send debugging output to screen buffer.
	 * 
	 * @throws RedBean_Exception_SQL 
	 * 
	 * @param string $sql     the SQL string to be send to database server
	 * @param array  $aValues the values that need to get bound to the query slots
	 */
	protected function runQuery($sql,$aValues) {
		$this->connect();
		if ($this->debug && $this->logger) {
			$this->logger->log($sql, $aValues);
		}
		try {
			if (strpos('pgsql',$this->dsn)===0) {
				$s = $this->pdo->prepare($sql, array(PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => true));
			}
			else {
				$s = $this->pdo->prepare($sql);
			}
			$this->bindParams( $s, $aValues );
			$s->execute();
			$this->affected_rows = $s->rowCount();
			if ($s->columnCount()) {
		    	$this->rs = $s->fetchAll();
		    	if ($this->debug && $this->logger) $this->logger->log('resultset: ' . count($this->rs) . ' rows');
	    	}
		  	else {
		    	$this->rs = array();
		  	}
		}catch(PDOException $e) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			$x = new RedBean_Exception_SQL( $e->getMessage(), 0);
			$x->setSQLState( $e->getCode() );
			throw $x;
		}
	}


	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param  string $sql SQL to be executed
	 *
	 * @return array $results result
	 */
	public function GetAll( $sql, $aValues=array() ) {
		$this->runQuery($sql,$aValues);
		return $this->rs;
	}

	 /**
	 * Runs a query and fetches results as a column.
	 *
	 * @param  string $sql SQL Code to execute
	 *
	 * @return array	$results Resultset
	 */
	public function GetCol($sql, $aValues=array()) {
		$rows = $this->GetAll($sql,$aValues);
		$cols = array();
		if ($rows && is_array($rows) && count($rows)>0) {
			foreach ($rows as $row) {
				$cols[] = array_shift($row);
			}
		}
		return $cols;
	}

	/**
	 * Runs a query an returns results as a single cell.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return mixed $cellvalue result cell
	 */
	public function GetCell($sql, $aValues=array()) {
		$arr = $this->GetAll($sql,$aValues);
		$row1 = array_shift($arr);
		$col1 = array_shift($row1);
		return $col1;
	}

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return array $row result row
	 */
	public function GetRow($sql, $aValues=array()) {
		$arr = $this->GetAll($sql, $aValues);
		return array_shift($arr);
	}

	

	/**
	 * Executes SQL code and allows key-value binding.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL. This method has no return value.
	 *
	 * @param string $sql	  SQL Code to execute
	 * @param array  $aValues Values to bind to SQL query
	 *
	 * @return void
	 */
	public function Execute( $sql, $aValues=array() ) {
		$this->runQuery($sql,$aValues);
		return $this->affected_rows;
	}

	/**
	 * Escapes a string for use in SQL using the currently selected
	 * PDO driver.
	 *
	 * @param string $string string to be escaped
	 *
	 * @return string $string escaped string
	 */
	public function Escape( $str ) {
		$this->connect();
		return substr(substr($this->pdo->quote($str), 1), 0, -1);
	}

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer $id primary key ID
	 */
	public function GetInsertID() {
		$this->connect();
		return (int) $this->pdo->lastInsertId();
	}

	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected PDO driver supports this feature.
	 *
	 * @return integer $numOfRows number of rows affected
	 */
	public function Affected_Rows() {
		$this->connect();
		return (int) $this->affected_rows;
	}

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results. All SQL code that passes through the driver will be
	 * passes on to the screen for inspection.
	 * This method has no return value.
	 *
	 * Additionally you can inject RedBean_ILogger implementation
	 * where you can define your own log() method
	 *
	 * @param boolean $trueFalse turn on/off
	 * @param RedBean_ILogger $logger 
	 *
	 * @return void
	 */
	public function setDebugMode( $tf, $logger = NULL ) {
		$this->connect();
		$this->debug = (bool)$tf;
		if ($this->debug and !$logger) $logger = new RedBean_Logger();
		$this->setLogger($logger);
	}


	/**
	 * Injects RedBean_ILogger object.
	 *
	 * @param RedBean_ILogger $logger
	 */
	public function setLogger( RedBean_ILogger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Gets RedBean_ILogger object.
	 *
	 * @return RedBean_ILogger
	 */
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * Starts a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function StartTrans() {
		$this->connect();
		$this->pdo->beginTransaction();
	}

	/**
	 * Commits a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function CommitTrans() {
		$this->connect();
		$this->pdo->commit();
	}

	/**
	 * Rolls back a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function FailTrans() {
		$this->connect();
		$this->pdo->rollback();
	}

	/**
	 * Returns the name of the database type/brand: i.e. mysql, db2 etc.
	 *
	 * @return string $typeName database identification
	 */
	public function getDatabaseType() {
		$this->connect();
		return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Returns the version number of the database.
	 *
	 * @return mixed $version version number of the database
	 */
	public function getDatabaseVersion() {
		$this->connect();
		return $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Returns the underlying PHP PDO instance.
	 *
	 * @return PDO $pdo PDO instance used by PDO wrapper
	 */
	public function getPDO() {
		$this->connect();
		return $this->pdo;
	}
	
	/**
	 * Closes database connection by destructing PDO.
	 */
	public function close() {
		$this->pdo = null;
		$this->isConnected = false;
	}
	
	/**
	 * Returns TRUE if the current PDO instance is connected.
	 * 
	 * @return boolean $yesNO 
	 */
	public function isConnected() {
		if (!$this->isConnected && !$this->pdo) return false;
		return true;
	}
	
	
}



/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * 
 * @file 			RedBean/RedBean_OODBBean.php
 * @description		The Bean class used for passing information
 * 
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODBBean implements IteratorAggregate, ArrayAccess, Countable {

    /**
     * Reference to NULL property for magic getter.
     * @var Null $null
     */
    private $null = null;


	/**
	 * Properties of the bean. These are kept in a private
	 * array called properties and exposed through the array interface.
	 * @var array $properties
	 */
	private $properties = array();

	/**
	 * Meta Data storage. This is the internal property where all
	 * Meta information gets stored.
	 * @var array
	 */
	private $__info = NULL;

	/**
	 * Contains a BeanHelper to access service objects like
	 * te association manager and OODB.
	 * @var RedBean_BeanHelper
	 */
	private $beanHelper = NULL;

	/**
	 * Contains the latest Fetch Type.
	 * A Fetch Type is a preferred type for the next nested bean.
	 * @var null
	 */
	private $fetchType = NULL;

	/** Returns the alias for a type
	 *
	 * @param  $type aliased type
	 *
	 * @return string $type type
	 */
	private function getAlias( $type ) {
		if ($this->fetchType) {
			$type = $this->fetchType;
			$this->fetchType = null;
		}
		return $type;
	}

	/**
	 * Sets the Bean Helper. Normally the Bean Helper is set by OODB.
	 * Here you can change the Bean Helper. The Bean Helper is an object
	 * providing access to a toolbox for the bean necessary to retrieve
	 * nested beans (bean lists: ownBean,sharedBean) without the need to
	 * rely on static calls to the facade (or make this class dep. on OODB).
	 *
	 * @param RedBean_IBeanHelper $helper
	 * @return void
	 */
	public function setBeanHelper(RedBean_IBeanHelper $helper) {
		$this->beanHelper = $helper;
	}


	/**
	 * Returns an ArrayIterator so you can treat the bean like
	 * an array with the properties container as its contents.
	 *
	 * @return ArrayIterator $arrayIt an array iterator instance with $properties
	 */
	public function getIterator() {
		return new ArrayIterator($this->properties);
	}

	/**
	 * Imports all values in associative array $array. Every key is used
	 * for a property and every value will be assigned to the property
	 * identified by the key. So basically this method converts the
	 * associative array to a bean by loading the array. You can filter
	 * the values using the $selection parameter. If $selection is boolean
	 * false, no filtering will be applied. If $selection is an array
	 * only the properties specified (as values) in the $selection
	 * array will be taken into account. To skip a property, omit it from
	 * the $selection array. Also, instead of providing an array you may
	 * pass a comma separated list of property names. This method is
	 * chainable because it returns its own object.
	 * Imports data into bean
	 *
	 * @param array        $array     what you want to import
	 * @param string|array $selection selection of values
	 * @param boolean      $notrim    if TRUE values will not be trimmed
	 *
	 *    @return RedBean_OODBBean $this
	 */
	public function import( $arr, $selection=false, $notrim=false ) {
		if (is_string($selection)) $selection = explode(',',$selection);
		//trim whitespaces
		if (!$notrim && is_array($selection)) foreach($selection as $k=>$s){ $selection[$k]=trim($s); }
		foreach($arr as $k=>$v) {
			if ($k!='__info') {
				if (!$selection || ($selection && in_array($k,$selection))) {
					$this->$k = $v;
				}
			}
		}
		return $this;
	}

	/**
	 * Very superficial export function
	 * @return array $properties 
	 */
	public function getProperties() {
		return $this->properties;
	}
	
	/**
	 * Exports the bean as an array.
	 * This function exports the contents of a bean to an array and returns
	 * the resulting array. If $meta eq uals boolean TRUE, then the array will
	 * also contain the __info section containing the meta data inside the
	 * RedBean_OODBBean Bean object.
	 * @param boolean $meta
	 * @return array $arr
	 */
	public function export($meta = false) {
		//$arr = $this->properties;
		$arr=array();
		foreach($this as $k=>$v) {
			if (is_array($v)) foreach($v as $i=>$b) $v[$i]=$b->export(); 
			$arr[$k] = $v;
		}
		if ($meta) $arr['__info'] = $this->__info;
		return $arr;
	}

	/**
	 * Exports the bean to an object.
	 * This function exports the contents of a bean to an object.
	 * @param object $obj
	 * @return array $arr
	 */
	public function exportToObj($obj) {
		foreach($this->properties as $k=>$v) {
			if (!is_array($v) && !is_object($v))
			$obj->$k = $v;
		}
	}

	/**
	 * Implements isset() function for use as an array.
	 * Returns whether bean has an element with key
	 * named $property. Returns TRUE if such an element exists
	 * and FALSE otherwise.
	 * @param string $property
	 * @return boolean $hasProperty
	 */
	public function __isset($property) {
		return (isset($this->properties[$property]));
	}



	/**
	 * Returns the ID of the bean no matter what the ID field is.
	 *
	 * @return string $id record Identifier for bean
	 */
	public function getID() {
		return (string) $this->id;
	}

	/**
	 * Unsets a property. This method will load the property first using
	 * __get.
	 *
	 * @param  string $property property
	 *
	 * @return void
	 */
	public function __unset($property) {
		$this->__get($property);
		$fieldLink = $property.'_id';
		if (isset($this->$fieldLink)) {
			//wanna unset a bean reference?
			$this->$fieldLink = null;
		}
		if ((isset($this->properties[$property]))) {
			unset($this->properties[$property]);
		}
	}

	/**
	 * Removes a property from the properties list without invoking
	 * an __unset on the bean.
	 *
	 * @param  string $property property that needs to be unset
	 *
	 * @return void
	 */
	public function removeProperty( $property ) {
		unset($this->properties[$property]);
	}


	/**
	 * Magic Getter. Gets the value for a specific property in the bean.
	 * If the property does not exist this getter will make sure no error
	 * occurs. This is because RedBean allows you to query (probe) for
	 * properties. If the property can not be found this method will
	 * return NULL instead.
	 * @param string $property
	 * @return mixed $value
	 */
	public function &__get( $property ) {
		if ($this->beanHelper)
		$toolbox = $this->beanHelper->getToolbox();
		if (!isset($this->properties[$property])) { 
			$fieldLink = $property.'_id'; 
			/**
			 * All this magic can be become very complex quicly. For instance,
			 * my PHP CLI produced a segfault while testing this code. Turns out that
			 * if fieldlink equals idfield, scripts tend to recusrively load beans and
			 * instead of giving a clue they simply crash and burn isnt that nice?
			 */
			if (isset($this->$fieldLink) && $fieldLink != $this->getMeta('sys.idfield')) {
				$this->setMeta('tainted',true); 
				$type =  $this->getAlias($property);
				$targetType = $this->properties[$fieldLink];
				$bean =  $toolbox->getRedBean()->load($type,$targetType);
				//return $bean;
				$this->properties[$property] = $bean;
				return $this->properties[$property];
			}
			if (strpos($property,'own')===0) {
				$firstCharCode = ord(substr($property,3,1));
				if ($firstCharCode>=65 && $firstCharCode<=90) {
					$type = (__lcfirst(str_replace('own','',$property)));
					$myFieldLink = $this->getMeta('type').'_id';
					$beans = $toolbox->getRedBean()->find($type,array(),array(" $myFieldLink = ? ",array($this->getID())));
					$this->properties[$property] = $beans;
					$this->setMeta('sys.shadow.'.$property,$beans);
					$this->setMeta('tainted',true);
					return $this->properties[$property];
				}
			}
			if (strpos($property,'shared')===0) {
				$firstCharCode = ord(substr($property,6,1));
				if ($firstCharCode>=65 && $firstCharCode<=90) {
					$type = (__lcfirst(str_replace('shared','',$property)));
					$keys = $toolbox->getRedBean()->getAssociationManager()->related($this,$type);
					if (!count($keys)) $beans = array(); else
					$beans = $toolbox->getRedBean()->batch($type,$keys);
					$this->properties[$property] = $beans;
					$this->setMeta('sys.shadow.'.$property,$beans);
					$this->setMeta('tainted',true);
					return $this->properties[$property];
				}
			}
			return $this->null;
		}
		return $this->properties[$property];
	}

	/**
	 * Magic Setter. Sets the value for a specific property.
	 * This setter acts as a hook for OODB to mark beans as tainted.
	 * The tainted meta property can be retrieved using getMeta("tainted").
	 * The tainted meta property indicates whether a bean has been modified and
	 * can be used in various caching mechanisms.
	 * @param string $property
	 * @param  mixed $value
	 */

	public function __set($property,$value) {
		$this->__get($property);
		$this->setMeta('tainted',true);
		$linkField = $property.'_id';
		if (isset($this->properties[$linkField]) && !($value instanceof RedBean_OODBBean)) {
			if (is_null($value) || $value === false) {
				return $this->__unset($property);
			}
			else {
				throw new RedBean_Exception_Security('Cannot cast to bean.');
			}
		}
		if ($value===false) {
			$value = '0';
		}
		if ($value===true) {
			$value = '1';
		}
		$this->properties[$property] = $value;
	}

	/**
	 * Returns the value of a meta property. A meta property
	 * contains extra information about the bean object that will not
	 * get stored in the database. Meta information is used to instruct
	 * RedBean as well as other systems how to deal with the bean.
	 * For instance: $bean->setMeta("buildcommand.unique", array(
	 * array("column1", "column2", "column3") ) );
	 * Will add a UNIQUE constaint for the bean on columns: column1, column2 and
     * column 3.
	 * To access a Meta property we use a dot separated notation.
	 * If the property cannot be found this getter will return NULL instead.
	 * @param string $path
	 * @param mixed $default
	 * @return mixed $value
	 */
	public function getMeta($path,$default = NULL) {
		return (isset($this->__info[$path])) ? $this->__info[$path] : $default;
	}

	/**
	 * Stores a value in the specified Meta information property. $value contains
	 * the value you want to store in the Meta section of the bean and $path
	 * specifies the dot separated path to the property. For instance "my.meta.property".
	 * If "my" and "meta" do not exist they will be created automatically.
	 * @param string $path
	 * @param mixed $value
	 */
	public function setMeta($path,$value) {
		$this->__info[$path] = $value;
	}

	/**
	 * Copies the meta information of the specified bean
	 * This is a convenience method to enable you to
	 * exchange meta information easily.
	 * @param RedBean_OODBBean $bean
	 * @return RedBean_OODBBean
	 */
	public function copyMetaFrom(RedBean_OODBBean $bean) {
		$this->__info = $bean->__info;
		return $this;
	}

	
	/**
	 * Reroutes a call to Model if exists. (new fuse)
	 * @param string $method
	 * @param array $args
	 * @return mixed $mixed
	 */
	public function __call($method, $args) {
		if (!isset($this->__info['model'])) {
			$model = $this->beanHelper->getModelForBean($this);
			if (!$model) return;
			$this->__info['model'] = $model;
		}
		if (!method_exists($this->__info['model'],$method)) return null;
		return call_user_func_array(array($this->__info['model'],$method), $args);
	}

	/**
	 * Implementation of __toString Method
	 * Routes call to Model.
	 * @return string $string
	 */
	public function __toString() {
		$string = $this->__call('__toString',array());
		if ($string === null) {
			return json_encode($this->properties);
		}
		else {
			return $string;
		}
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Call gets routed to __set.
	 *
	 * @param  mixed $offset offset string
	 * @param  mixed $value value
	 *
	 * @return void
	 */
	public function offsetSet($offset, $value) {
        $this->__set($offset, $value);
    }

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 *
	 * @param  mixed $offset property
	 *
	 * @return
	 */
    public function offsetExists($offset) {
        return isset($this->properties[$offset]);
    }

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Unsets a value from the array/bean.
	 *
	 * @param  mixed $offset property
	 *
	 * @return
	 */
    public function offsetUnset($offset) {
        unset($this->properties[$offset]);
    }

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Returns value of a property.
	 *
	 * @param  mixed $offset property
	 *
	 * @return
	 */
    public function offsetGet($offset) {
        return $this->__get($offset);
    }

	/**
	 * Chainable method to cast a certain ID to a bean; for instance:
	 * $person = $club->fetchAs('person')->member;
	 * This will load a bean of type person using member_id as ID.
	 *
	 * @param  string $type preferred fetch type
	 *
	 * @return RedBean_OODBBean
	 */
	public function fetchAs($type) {
		$this->fetchType = $type;
		return $this;
	}
	
	/**
	 * Implementation of Countable interface. Makes it possible to use
	 * count() function on a bean.
	 * 
	 * @return integer $numberOfProperties number of properties in the bean. 
	 */
	public function count() {
		return count($this->properties);
	}

	/**
	 * Checks wether a bean is empty or not.
	 * A bean is empty if it has no other properties than the id field OR
	 * if all the other property are empty().
	 * 
	 * @return boolean 
	 */
	public function isEmpty() {
		$empty = true;
		foreach($this->properties as $key=>$value) {
			if ($key=='id') continue;
			if (!empty($value)) { 
				$empty = false;
		
			}	
		}
		return $empty;
	}
}




/**
 * Observable
 * Base class for Observables
 * 
 * @file 			RedBean/Observable.php
 * @description		Part of the observer pattern in RedBean
 *
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedBean_Observable {
	/**
	 * Array that keeps track of observers.
	 * @var array
	 */
	private $observers = array();

	/**
	 * Implementation of the Observer Pattern.
	 * Adds a listener to this instance.
	 * This method can be used to attach an observer to an object.
	 * You can subscribe to a specific event by providing the ID
	 * of the event you are interested in. Once the event occurs
	 * the observable will notify the listeners by calling
	 * onEvent(); providing the event ID and either a bean or
	 * an information array.
	 *
	 * @param string           $eventname event
	 * @param RedBean_Observer $observer observer
	 *
	 * @return void
	 */
	public function addEventListener( $eventname, RedBean_Observer $observer ) {
		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}
		foreach($this->observers[$eventname] as $o) if ($o==$observer) return;
		$this->observers[ $eventname ][] = $observer;
	}

	/**
	 * Implementation of the Observer Pattern.
	 * Sends an event (signal) to the registered listeners
	 * This method is provided by the abstract class Observable for
	 * convience. Observables can use this method to notify their
	 * observers by sending an event ID and information parameter.
	 *
	 * @param string $eventname eventname
	 * @param mixed  $info      info
	 * @return unknown_ty
	 */
	public function signal( $eventname, $info ) {
		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}
		foreach($this->observers[$eventname] as $observer) {
			$observer->onEvent( $eventname, $info );
		}
	}
}

/**
 * Observer
 * 
 * @file 			RedBean/Observer.php
 * @description		Part of the observer pattern in RedBean
 * 
 * @author			Gabor de Mooijand the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Observer {

	/**
	 * Part of the RedBean Observer Infrastructure.
	 * The on-event method is called by an observable once the
	 * event the observer has been registered for occurs.
	 * Once the even occurs, the observable will signal the observer
	 * using this method, sending the event name and the bean or
	 * an information array.
	 *
	 * @param string $eventname
	 * @param RedBean_OODBBean mixed $info
	 */
	public function onEvent( $eventname, $bean );
}

/**
 * Adapter Interface
 *
 * @file 			RedBean/Adapter.php
 * @description		Describes the API for a RedBean Database Adapter.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
interface RedBean_Adapter {

	/**
	 * Returns the latest SQL statement
	 *
	 * @return string $SQLString SQLString
	 */
	public function getSQL();

	/**
	 * Escapes a value for usage in an SQL statement
	 *
	 * @param string $sqlvalue value
	 */
	public function escape( $sqlvalue );

	/**
	 * Executes an SQL Statement using an array of values to bind
	 * If $noevent is TRUE then this function will not signal its
	 * observers to notify about the SQL execution; this to prevent
	 * infinite recursion when using observers.
	 *
	 * @param string  $sql     SQL
	 * @param array   $aValues values
	 * @param boolean $noevent no event firing
	 */
	public function exec( $sql , $aValues=array(), $noevent=false);

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a multi dimensional resultset similar to getAll
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql     SQL
	 * @param array  $aValues values
	 */
	public function get( $sql, $aValues = array() );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a single row (one array) resultset.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql	  SQL
	 * @param array  $aValues values to bind
	 *
	 * @return array $aMultiDimArray row
	 */
	public function getRow( $sql, $aValues = array() );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a single column (one array) resultset.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql	  SQL
	 * @param array  $aValues values to bind
	 *
	 * @return array $aSingleDimArray column
	 */
	public function getCol( $sql, $aValues = array() );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a single cell, a scalar value as the resultset.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql     SQL
	 * @param array  $aValues values to bind
	 *
	 * @return string $sSingleValue value from cell
	 */
	public function getCell( $sql, $aValues = array() );

	/**
	 * Executes the SQL query specified in $sql and takes
	 * the first two columns of the resultset. This function transforms the
	 * resultset into an associative array. Values from the the first column will
	 * serve as keys while the values of the second column will be used as values.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql    SQL
	 * @param array  $values values to bind
	 *
	 * @return array $associativeArray associative array result set
	 */
	public function getAssoc( $sql, $values = array() );

	/**
	 * Returns the latest insert ID.
	 *
	 * @return integer $id primary key ID
	 */
	public function getInsertID();

	/**
	 * Returns the number of rows that have been
	 * affected by the last update statement.
	 *
	 * @return integer $count number of rows affected
	 */
	public function getAffectedRows();

	/**
	 * Returns the original database resource. This is useful if you want to
	 * perform operations on the driver directly instead of working with the
	 * adapter. RedBean will only access the adapter and never to talk
	 * directly to the driver though.
	 *
	 * @return object $driver driver
	 */
	public function getDatabase();
	
	/**
	 * This method is part of the RedBean Transaction Management
	 * mechanisms.
	 * Starts a transaction.
	 */
	public function startTransaction();

	/**
	 * This method is part of the RedBean Transaction Management
	 * mechanisms.
	 * Commits the transaction.
	 */
	public function commit();

	/**
	 * This method is part of the RedBean Transaction Management
	 * mechanisms.
	 * Rolls back the transaction.
	 */
	public function rollback();
	
	/**
	 * Closes database connection.
	 */
	public function close();

}

/**
 * DBAdapter		(Database Adapter)
 * @file			RedBean/Adapter/DBAdapter.php
 * @description		An adapter class to connect various database systems to RedBean
 * @author			Gabor de Mooij and the RedBeanPHP Community. 
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Adapter_DBAdapter extends RedBean_Observable implements RedBean_Adapter {

	/**
	 * ADODB compatible class
	 * @var RedBean_Driver
	 */
	private $db = null;

	/**
	 * Contains SQL snippet
	 * @var string
	 */
	private $sql = '';


	/**
	 * Constructor.
	 * Creates an instance of the RedBean Adapter Class.
	 * This class provides an interface for RedBean to work
	 * with ADO compatible DB instances.
	 *
	 * @param RedBean_Driver $database ADO Compatible DB Instance
	 */
	public function __construct($database) {
		$this->db = $database;
	}

	/**
	 * Returns the latest SQL Statement.
	 *
	 * @return string $SQL latest SQL statement
	 */
	public function getSQL() {
		return $this->sql;
	}

	/**
	 * Escapes a string for use in a Query.
	 *
	 * @param  string $sqlvalue SQL value to escape
	 *
	 * @return string $escapedValue escaped value
	 */
	public function escape( $sqlvalue ) {
		return $this->db->Escape($sqlvalue);
	}

	/**
	 * Executes SQL code; any query without
	 * returning a resultset.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string  $sql			SQL Code to execute
	 * @param  array   $values		assoc. array binding values
	 * @param  boolean $noevent   if TRUE this will suppress the event 'sql_exec'
	 *
	 * @return mixed  $undefSet	whatever driver returns, undefined
	 */
	public function exec( $sql , $aValues=array(), $noevent=false) {
		if (!$noevent) {
			$this->sql = $sql;
			$this->signal('sql_exec', $this);
		}
		return $this->db->Execute( $sql, $aValues );
	}

	/**
	 * Multi array SQL fetch. Fetches a multi dimensional array.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array  $result	two dimensional array result set
	 */
	public function get( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		return $this->db->GetAll( $sql,$aValues );
	}

	/**
	 * Executes SQL and fetches a single row.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array	$result	one dimensional array result set
	 */
	public function getRow( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		return $this->db->GetRow( $sql,$aValues );
	}

	/**
	 * Executes SQL and returns a one dimensional array result set.
	 * This function rotates the result matrix to obtain a column result set.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array  $result	one dimensional array result set
	 */
	public function getCol( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		return $this->db->GetCol( $sql,$aValues );
	}


	/**
	 * Executes an SQL Query and fetches the first two columns only.
	 * Then this function builds an associative array using the first
	 * column for the keys and the second result column for the
	 * values. For instance: SELECT id, name FROM... will produce
	 * an array like: id => name.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array  $result	multi dimensional assoc. array result set
	 */
	public function getAssoc( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		$rows = $this->db->GetAll( $sql, $aValues );
		$assoc = array();
		if ($rows) {
			foreach($rows as $row) {
				if (count($row)>0) {
					if (count($row)>1) {
						$key = array_shift($row);
						$value = array_shift($row);
					}
					elseif (count($row)==1) {
						$key = array_shift($row);
						$value=$key;
					}
					$assoc[ $key ] = $value;
				}
			}
		}
		return $assoc;
	}


	/**
	 * Retrieves a single cell.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql	  sql code to execute
	 * @param  array  $values assoc. array binding values
	 *
	 * @return array  $result scalar result set
	 */

	public function getCell( $sql, $aValues = array(), $noSignal = null ) {
		$this->sql = $sql;
		if (!$noSignal) $this->signal('sql_exec', $this);
		$arr = $this->db->getCol( $sql, $aValues );
		if ($arr && is_array($arr))	return ($arr[0]); else return false;
	}

	/**
	 * Returns latest insert id, most recently inserted id.
	 *
	 * @return integer $id latest insert ID
	 */
	public function getInsertID() {
		return $this->db->getInsertID();
	}

	/**
	 * Returns number of affected rows.
	 *
	 * @return integer $numOfAffectRows
	 */
	public function getAffectedRows() {
		return $this->db->Affected_Rows();
	}

	/**
	 * Unwrap the original database object.
	 *
	 * @return RedBean_Driver $database	returns the inner database object
	 */
	public function getDatabase() {
		return $this->db;
	}

	/**
	 * Transactions.
	 * Part of the transaction management infrastructure of RedBean.
	 * Starts a transaction.
	 */
	public function startTransaction() {
		return $this->db->StartTrans();
	}

	/**
	 * Transactions.
	 * Part of the transaction management infrastructure of RedBean.
	 * Commits a transaction.
	 */
	public function commit() {
		return $this->db->CommitTrans();
	}

	/**
	 * Transactions.
	 * Part of the transaction management infrastructure of RedBean.
	 * Rolls back transaction.
	 */
	public function rollback() {
		return $this->db->FailTrans();
	}
	
	
	/**
	 * Closes the database connection.
	 */
	public function close() {
		$this->db->close();
	}

}


/**
 * QueryWriter
 * Interface for QueryWriters
 * 
 * @file			RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * Notes:
 * - Whenever you see a parameter called $table or $type you should always
 * be aware of the fact that this argument contains a Bean Type string, not the
 * actual table name. These raw type names are passed to safeTable() to obtain the
 * actual name of the database table. Don't let the names confuse you $type/$table
 * refers to Bean Type, not physical database table names!
 * - This is the interface for FLUID database drivers. Drivers intended to support
 * just FROZEN mode should implement the IceWriter instead.
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_QueryWriter {

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a table has not been found in
	 * the database.
	 */
	const C_SQLSTATE_NO_SUCH_TABLE = 1;

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a perticular column has not
	 * been found in the database.
	 */
	const C_SQLSTATE_NO_SUCH_COLUMN = 2;

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a perticular column has not
	 * been found in the database.
	 */
	const C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = 3;

	/**
	 * Returns the tables that are in the database.
	 *
	 * @return array $arrayOfTables list of tables
	 */
	public function getTables();

	/**
	 * This method should create a table for the bean.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type type of bean you want to create a table for
	 *
	 * @return void
	 */
	public function createTable($type);

	/**
	 * Returns an array containing all the columns of the specified type.
	 * The format of the return array looks like this:
	 * $field => $type where $field is the name of the column and $type
	 * is a database specific description of the datatype.
	 *
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type type of bean you want to obtain a column list of
	 *
	 * @return array $listOfColumns list of columns ($field=>$type)
	 */
	public function getColumns($type);


	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type. This method is used to determine the minimum
	 * column type required to represent the given value.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType($value, $alsoScanSpecialForTypes=false);

	/**
	 * This method should add a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 *
	 */
	public function addColumn($type, $column, $field);

	/**
	 * This method should return a data type constant based on the
	 * SQL type definition. This function is meant to compare column data
	 * type to check whether a column is wide enough to represent the desired
	 * values.
	 *
	 * @param integer $typedescription SQL type description from database
	 *
	 * @return integer $type
	 */
	public function code($typedescription);

	/**
	 * This method should widen the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn($type, $column, $datatype);

	/**
	 * This method should update (or insert a record), it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type         name of the table to update
	 * @param array   $updatevalues list of update values
	 * @param integer $id			optional primary key ID value
	 *
	 * @return integer $id the primary key ID value of the new record
	 */
	public function updateRecord($type, $updatevalues, $id=null);


	/**
	 * This method should select a record. You should be able to provide a
	 * collection of conditions using the following format:
	 * array( $field1 => array($possibleValue1, $possibleValue2,... $possibleValueN ),
	 * ...$fieldN=>array(...));
	 * Also, additional SQL can be provided. This SQL snippet will be appended to the
	 * query string. If the $delete parameter is set to TRUE instead of selecting the
	 * records they will be deleted.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   type of bean to select records from
	 * @param array   $cond   conditions using the specified format
	 * @param string  $asql   additional sql
	 * @param boolean $delete  IF TRUE delete records (optional)
	 * @param boolean $inverse IF TRUE inverse the selection (optional)
	 *
	 * @return array $records selected records
	 */
	public function selectRecord($type, $conditions, $addSql = null, $delete = false, $inverse = false);


	/**
	 * This method should add a UNIQUE constraint index to a table on columns $columns.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type               type
	 * @param array  $columnsPartOfIndex columns to include in index
	 *
	 * @return void
	 */
	public function addUniqueIndex($type,$columns);

	
	/**
	 * This method should check whether the SQL state is in the list of specified states
	 * and returns true if it does appear in this list or false if it
	 * does not. The purpose of this method is to translate the database specific state to
	 * a one of the constants defined in this class and then check whether it is in the list
	 * of standard states provided.
	 *
	 * @param string $state sql state
	 * @param array  $list  list
	 *
	 * @return boolean $isInList
	 */
	public function sqlStateIn( $state, $list );

	/**
	 * This method should remove all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe($type);

	/**
	 * This method should count the number of beans of the given type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type type of bean to count
	 *
	 * @return integer $numOfBeans number of beans found
	 */
	public function count($type);

	/**
	 * This method should filter a column name so that it can
	 * be used safely in a query for a specific database.
	 *
	 * @param  string $name		the column name
	 * @param  bool   $noQuotes whether you want to omit quotes
	 *
	 * @return string $clean the clean version of the column name
	 */
	public function safeColumn($name, $noQuotes = false);

	/**
	 * This method should filter a type name so that it can
	 * be used safely in a query for a specific database. It actually
	 * converts a type to a table. TYPE -> TABLE
	 *
	 * @param string $name     the name of the type
	 * @param bool   $noQuotes whether you want to omit quotes in table name
	 *
	 * @return string $tablename clean table name for use in query
	 */
	public function safeTable($name, $noQuotes = false);

	/**
	 * This method should add a constraint. If one of the beans gets trashed
	 * the other, related bean should be removed as well.
	 *
	 * @param RedBean_OODBBean $bean1      first bean
	 * @param RedBean_OODBBean $bean2      second bean
	 *
	 * @return void
	 */
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 );

	/**
	 * This method should add a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	public function addFK( $type, $targetType, $field, $targetField);


	/**
	 * This method should add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  $type   type to add index to
	 * @param  $name   name of the new index
	 * @param  $column field to index
	 *
	 * @return void
	 */
	public function addIndex($type, $name, $column);
	
	/**
	 * Returns a modified value from ScanType.
	 * Used for special types.
	 * 
	 * @return mixed $value changed value 
	 */
	public function getValue();

}

/**
 * RedBean Abstract Query Writer
 *
 * @file 			RedBean/QueryWriter/AQueryWriter.php
 * @description		Quert Writer
 *					Represents an abstract Database to RedBean
 *					To write a driver for a different database for RedBean
 *					Contains a number of functions all implementors can
 *					inherit or override.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedBean_QueryWriter_AQueryWriter {

	/**
	 * Scanned value (scanType) 
	 * @var type
	 */
	protected $svalue;

	/**
	 * Supported Column Types.
	 * @var array
	 */
	public $typeno_sqltype = array();

	/**
	 * Holds a reference to the database adapter to be used.
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	
	/**
	 * default value to for blank field (passed to PK for auto-increment)
	 * @var string
	 */
	protected $defaultValue = 'NULL';

	/**
	 * character to escape keyword table/column names
	 * @var string
	 */
	protected $quoteCharacter = '';


	/**
	 * Constructor
	 * Sets the default Bean Formatter, use parent::__construct() in
	 * subclass to achieve this.
	 */
	public function __construct() {
		
	}

	/**
	 * Do everything that needs to be done to format a table name.
	 *
	 * @param string $name of table
	 *
	 * @return string table name
	 */
	public function safeTable($name, $noQuotes = false) {
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	/**
	 * Do everything that needs to be done to format a column name.
	 *
	 * @param string $name of column
	 *
	 * @return string $column name
	 */
	public function safeColumn($name, $noQuotes = false) {
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string sql
	 */
  	protected function getInsertSuffix ($table) {
    	return '';
  	}

	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string $table escaped string
	 */
	protected function check($table) {
		if ($this->quoteCharacter && strpos($table, $this->quoteCharacter)!==false) {
		  throw new Redbean_Exception_Security('Illegal chars in table name');
	    }
		return $this->adapter->escape($table);
	}

	/**
	 * Puts keyword escaping symbols around string.
	 *
	 * @param string $str keyword
	 *
	 * @return string $keywordSafeString escaped keyword
	 */
	protected function noKW($str) {
		$q = $this->quoteCharacter;
		return $q.$str.$q;
	}

	/**
	 * This method adds a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 *
	 */
	public function addColumn( $type, $column, $field ) {
		$table = $type;
		$type = $field;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$type = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$sql = "ALTER TABLE $table ADD $column $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * This method updates (or inserts) a record, it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type         name of the table to update
	 * @param array   $updatevalues list of update values
	 * @param integer $id			optional primary key ID value
	 *
	 * @return integer $id the primary key ID value of the new record
	 */
	public function updateRecord( $type, $updatevalues, $id=null) {
		$table = $type;
		if (!$id) {
			$insertcolumns =  $insertvalues = array();
			foreach($updatevalues as $pair) {
				$insertcolumns[] = $pair['property'];
				$insertvalues[] = $pair['value'];
			}
			return $this->insertRecord($table,$insertcolumns,array($insertvalues));
		}
		if ($id && !count($updatevalues)) return $id;
		
		$table = $this->safeTable($table);
		$sql = "UPDATE $table SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " {$this->safeColumn($uv["property"])} = ? ";
			$v[]=$uv['value'];
		}
		$sql .= implode(',', $p ) .' WHERE id = '.intval($id);
		$this->adapter->exec( $sql, $v );
		return $id;
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table			  table to perform query on
	 * @param array  $insertcolumns columns to be inserted
	 * @param array  $insertvalues  values to be inserted
	 *
	 * @return integer $insertid	  insert id from driver, new record id
	 */
	protected function insertRecord( $table, $insertcolumns, $insertvalues ) {
		$default = $this->defaultValue;
		$suffix = $this->getInsertSuffix($table);
		$table = $this->safeTable($table);
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
				$insertcolumns[$k] = $this->safeColumn($v);
			}
			$insertSQL = "INSERT INTO $table ( id, ".implode(',',$insertcolumns)." ) VALUES 
			( $default, ". implode(',',array_fill(0,count($insertcolumns),' ? '))." ) $suffix";

			foreach($insertvalues as $i=>$insertvalue) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}
			$result = count($ids)===1 ? array_pop($ids) : $ids;
		}
		else {
			$result = $this->adapter->getCell( "INSERT INTO $table (id) VALUES($default) $suffix");
		}
		if ($suffix) return $result;
		$last_id = $this->adapter->getInsertID();
		return $last_id;
	}


	/**
	 * This selects a record. You provide a
	 * collection of conditions using the following format:
	 * array( $field1 => array($possibleValue1, $possibleValue2,... $possibleValueN ),
	 * ...$fieldN=>array(...));
	 * Also, additional SQL can be provided. This SQL snippet will be appended to the
	 * query string. If the $delete parameter is set to TRUE instead of selecting the
	 * records they will be deleted.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @throws Exception
	 * @param string  $type    type of bean to select records from
	 * @param array   $cond    conditions using the specified format
	 * @param string  $asql    additional sql
	 * @param boolean $delete  IF TRUE delete records (optional)
	 * @param boolean $inverse IF TRUE inverse the selection (optional)
	 * @param boolean $all     IF TRUE suppress WHERE keyword, omitting WHERE clause
	 *
	 * @return array $records selected records
	 */
	public function selectRecord( $type, $conditions, $addSql=null, $delete=null, $inverse=false, $all=false ) { 
		if (!is_array($conditions)) throw new Exception('Conditions must be an array');
		$table = $this->safeTable($type);
		$sqlConditions = array();
		$bindings=array();
		foreach($conditions as $column=>$values) {
			if (!count($values)) continue;
			$sql = $this->safeColumn($column);
			$sql .= ' '.($inverse ? ' NOT ':'').' IN ( ';
			$sql .= implode(',',array_fill(0,count($values),'?')).') ';
			$sqlConditions[] = $sql;
			if (!is_array($values)) $values = array($values);
			foreach($values as $k=>$v) {
				$values[$k]=strval($v);
			}
			$bindings = array_merge($bindings,$values);
		}
		//$addSql can be either just a string or array($sql, $bindings)
		if (is_array($addSql)) {
			if (count($addSql)>1) {
				$bindings = array_merge($bindings,$addSql[1]);
			}
			else {
				$bindings = array();
			}
			$addSql = $addSql[0];

		}
		$sql = '';
		if (count($sqlConditions)>0) {
			$sql = implode(' AND ',$sqlConditions);
			$sql = " WHERE ( $sql ) ";
			if ($addSql) $sql .= " AND $addSql ";
		}
		elseif ($addSql) {
			if ($all) {
				$sql = " $addSql ";
			} 
			else {
				$sql = " WHERE $addSql ";
			}
		}
		$sql = (($delete) ? 'DELETE FROM ' : 'SELECT * FROM ').$table.$sql;
		$rows = $this->adapter->get($sql,$bindings);
		return $rows;
	}


	/**
	 * This method removes all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe($type) {
		$table = $type;
		$table = $this->safeTable($table);
		$sql = "TRUNCATE $table ";
		$this->adapter->exec($sql);
	}

	/**
	 * Counts rows in a table.
	 *
	 * @param string $beanType
	 *
	 * @return integer $numRowsFound
	 */
	public function count($beanType) {
		$sql = "SELECT count(*) FROM {$this->safeTable($beanType)} ";
		return (int) $this->adapter->getCell($sql);
	}

	/**
	 * This method should add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  $type   type to add index to
	 * @param  $name   name of the new index
	 * @param  $column field to index
	 *
	 * @return void
	 */
	public function addIndex($type, $name, $column) {
		$table = $type;
		$table = $this->safeTable($table);
		$name = preg_replace('/\W/','',$name);
		$column = $this->safeColumn($column);
		try{ $this->adapter->exec("CREATE INDEX $name ON $table ($column) "); }catch(Exception $e){}
	}

	/**
	 * This is a utility service method publicly available.
	 * It allows you to check whether you can safely treat an certain value as an integer by
	 * comparing an int-valled string representation with a default string casted string representation and
	 * a ctype-digit check. It does not take into account numerical limitations (X-bit INT), just that it
	 * can be treated like an INT. This is useful for binding parameters to query statements like
	 * Query Writers and drivers can do.
	 *
	 * @static
	 *
	 * @param  string $value string representation of a certain value
	 *
	 * @return boolean $value boolean result of analysis
	 */
	public static function canBeTreatedAsInt( $value ) {
		return (boolean) (ctype_digit(strval($value)) && strval($value)===strval(intval($value)));
	}


	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDependent = false) {
		$table = $this->safeTable($type);
		$tableNoQ = $this->safeTable($type,true);
		$targetTable = $this->safeTable($targetType);
		$column = $this->safeColumn($field);
		$columnNoQ = $this->safeColumn($field,true);
		$targetColumn  = $this->safeColumn($targetField);
		$targetColumnNoQ  = $this->safeColumn($targetField,true);
		$db = $this->adapter->getCell('select database()');
		$fkName = 'fk_'.$tableNoQ.'_'.$columnNoQ.'_'.$targetColumnNoQ.($isDependent ? '_casc':'');
		$cName = 'cons_'.$fkName;
		$cfks =  $this->adapter->getCell("
			SELECT CONSTRAINT_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME = '$tableNoQ'  AND COLUMN_NAME = '$columnNoQ' AND
			CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
		");
		$flagAddKey = false;
		
		try{
			//No keys
			if (!$cfks) {
				$flagAddKey = true; //go get a new key
			}
			//has fk, but different setting, --remove
			if ($cfks && $cfks!=$cName) {
				$this->adapter->exec("ALTER TABLE $table DROP FOREIGN KEY $cfks ");
				$flagAddKey = true; //go get a new key.
			}
			if ($flagAddKey) { 
				$this->adapter->exec("ALTER TABLE  $table
				ADD CONSTRAINT $cName FOREIGN KEY $fkName (  $column ) REFERENCES  $targetTable (
				$targetColumn) ON DELETE ".($isDependent ? 'CASCADE':'SET NULL').' ON UPDATE SET NULL ;');
			}
		}
		catch(Exception $e) { } //Failure of fk-constraints is not a problem

	}

	/**
	 * Returns the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records.
	 *
	 * @param  array $types two types array($type1,$type2)
	 *
	 * @return string $linktable name of the link table
	 */
	public static function getAssocTableFormat($types) {
		sort($types);
		return ( implode('_', $types) );
	}


	/**
	 * Adds a constraint. If one of the beans gets trashed
	 * the other, related bean should be removed as well.
	 *
	 * @param RedBean_OODBBean $bean1      first bean
	 * @param RedBean_OODBBean $bean2      second bean
	 * @param bool 			   $dontCache  by default we use a cache, TRUE = NO CACHING (optional)
	 *
	 * @return void
	 */
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table1 = $bean1->getMeta('type');
		$table2 = $bean2->getMeta('type');
		$writer = $this;
		$adapter = $this->adapter;
		$table = RedBean_QueryWriter_AQueryWriter::getAssocTableFormat( array( $table1,$table2) );
		
		$property1 = $bean1->getMeta('type') . '_id';
		$property2 = $bean2->getMeta('type') . '_id';
		if ($property1==$property2) $property2 = $bean2->getMeta('type').'2_id';

		$table = $adapter->escape($table);
		$table1 = $adapter->escape($table1);
		$table2 = $adapter->escape($table2);
		$property1 = $adapter->escape($property1);
		$property2 = $adapter->escape($property2);

		//Dispatch to right method
		return $this->constrain($table, $table1, $table2, $property1, $property2);
	}

	/**
	 * Checks whether a value starts with zeros. In this case
	 * the value should probably be stored using a text datatype instead of a
	 * numerical type in order to preserve the zeros.
	 * 
	 * @param string $value value to be checked.
	 */
	protected function startsWithZeros($value) {
		$value = strval($value);
		if (strlen($value)>1 && strpos($value,'0')===0 && strpos($value,'0.')!==0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Returns a modified value from ScanType.
	 * Used for special types.
	 * 
	 * @return mixed $value changed value 
	 */
	public function getValue(){
		return $this->svalue;
	}

}


/**
 * RedBean MySQLWriter
 *
 * @file			RedBean/QueryWriter/MySQL.php
 * @description		Represents a MySQL Database to RedBean
 *					To write a driver for a different database for RedBean
 *					you should only have to change this file.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_MySQL extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 * DATA TYPE
	 * Boolean Data type
	 * @var integer
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 *
	 * DATA TYPE
	 * Unsigned 8BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 *
	 * DATA TYPE
	 * Unsigned 32BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT32 = 2;

	/**
	 * DATA TYPE
	 * Double precision floating point number and
	 * negative numbers.
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 3;

	/**
	 * DATA TYPE
	 * Standard Text column (like varchar255)
	 * At least 8BIT character support.
	 * @var integer
	 */
	const C_DATATYPE_TEXT8 = 4;

	/**
	 * DATA TYPE
	 * Long text column (16BIT)
	 * @var integer
	 */
	const C_DATATYPE_TEXT16 = 5;

	/**
	 * 
	 * DATA TYPE
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 * @var integer
	 */
	const C_DATATYPE_TEXT32 = 6;

	/**
	 * Special type date for storing date values: YYYY-MM-DD
	 * @var integer
	 */	
	const C_DATATYPE_SPECIAL_DATE = 80;
	
	/**
	 * Special type datetime for store date-time values: YYYY-MM-DD HH:II:SS
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	

	/**
	 * 
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * Spatial types
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_POINT = 100;
	const C_DATATYPE_SPECIAL_LINESTRING = 101;
	const C_DATATYPE_SPECIAL_GEOMETRY = 102;
	const C_DATATYPE_SPECIAL_POLYGON = 103;
	const C_DATATYPE_SPECIAL_MULTIPOINT = 104;
	const C_DATATYPE_SPECIAL_MULTIPOLYGON = 105;
	const C_DATATYPE_SPECIAL_GEOMETRYCOLLECTION = 106;
	
	/**
	 * Holds the RedBean Database Adapter.
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * character to escape keyword table/column names
	 * @var string
	 */
  	protected $quoteCharacter = '`';

	/**
	 * Constructor.
	 * The Query Writer Constructor also sets up the database.
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 *
	 */
	public function __construct( RedBean_Adapter $adapter ) {
		
		$this->typeno_sqltype = array(
			  RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL=>"  SET('1')  ",
			  RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8=>' TINYINT(3) UNSIGNED ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32=>' INT(11) UNSIGNED ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE=>' DOUBLE ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8=>' VARCHAR(255) ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16=>' TEXT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32=>' LONGTEXT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE=>' DATE ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME=>' DATETIME ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POINT=>' POINT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_LINESTRING=>' LINESTRING  ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_GEOMETRY=>' GEOMETRY ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POLYGON=>' POLYGON ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_MULTIPOINT=>' MULTIPOINT ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_MULTIPOLYGON=>' MULTIPOLYGON ',
			  RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_GEOMETRYCOLLECTION=>' GEOMETRYCOLLECTION ',
		);
		
		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[trim(strtolower($v))]=$k;
		
		
		$this->adapter = $adapter;
		parent::__construct();
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID() {
		return self::C_DATATYPE_UINT32;
	}

	/**
	 * Returns all tables in the database.
	 *
	 * @return array $tables tables
	 */
	public function getTables() {
		return $this->adapter->getCol( 'show tables' );
	}

	/**
	 * Creates an empty, column-less table for a bean based on it's type.
	 * This function creates an empty table for a bean. It uses the
	 * safeTable() function to convert the type name to a table name.
	 *
	 * @param string $table type of bean you want to create a table for
	 *
	 * @return void
	 */
	public function createTable( $table ) {
		$table = $this->safeTable($table);
		$sql = "     CREATE TABLE $table (
                     id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                     PRIMARY KEY ( id )
                     ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table
	 *
	 * @return array $columns columns
	 */
	public function getColumns( $table ) {
		$table = $this->safeTable($table);
		$columnsRaw = $this->adapter->get("DESCRIBE $table");
		foreach($columnsRaw as $r) {
			$columns[$r['Field']]=$r['Type'];
		}
		return $columns;
	}

	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value, $flagSpecial=false ) {
		$this->svalue = $value;
		
		if (is_null($value)) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
		
		if ($flagSpecial) {
			if (strpos($value,'POINT(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POINT;
			}
			if (strpos($value,'LINESTRING(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_LINESTRING;
			}
			if (strpos($value,'POLYGON(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POLYGON;
			}
			if (strpos($value,'MULTIPOINT(')===0) {
				$this->svalue = $this->adapter->getCell('SELECT GeomFromText(?)',array($value));
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_MULTIPOINT;
			}
			
			
			if (preg_match('/^\d{4}\-\d\d-\d\d$/',$value)) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE;
			}
			if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/',$value)) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME;
			}
		}
		$value = strval($value);
		if (!$this->startsWithZeros($value)) {

			if ($value=='1' || $value=='') {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
			}
			if (is_numeric($value) && (floor($value)==$value) && $value >= 0 && $value <= 255 ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8;
			}
			if (is_numeric($value) && (floor($value)==$value) && $value >= 0  && $value <= 4294967295 ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32;
			}
			if (is_numeric($value)) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE;
			}
		}
		if (strlen($value) <= 255) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8;
		}
		if (strlen($value) <= 65535) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16;
		}
		return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32;
	}

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = false ) {
		$r = ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED);
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
	}

	/**
	 * This method upgrades the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype ) {
		$table = $type;
		$type = $datatype;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$newtype = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$changecolumnSQL = "ALTER TABLE $table CHANGE $column $column $newtype ";
		$this->adapter->exec( $changecolumnSQL );
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table table
	 * @param string $col1  column
	 * @param string $col2  column
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table);
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v) {
			$columns[$k]= $this->safeColumn($v);
		}
		$r = $this->adapter->get("SHOW INDEX FROM $table");
		$name = 'UQ_'.sha1(implode(',',$columns));
		if ($r) {
			foreach($r as $i) {
				if ($i['Key_name']== $name) {
					return;
				}
			}
		}
		$sql = "ALTER IGNORE TABLE $table
                ADD UNIQUE INDEX $name (".implode(',',$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Tests whether a given SQL state is in the list of states.
	 *
	 * @param string $state code
	 * @param array  $list  array of sql states
	 *
	 * @return boolean $yesno occurs in list
	 */
	public function sqlStateIn($state, $list) {
		$stateMap = array(
			'42S02'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23000'=>RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);
		return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'),$list); 
	}

	/**
	 * Add the constraints for a specific database driver: MySQL.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected function constrain($table, $table1, $table2, $property1, $property2) {
		try{
			$db = $this->adapter->getCell('select database()');
			$fks =  $this->adapter->getCell("
				SELECT count(*)
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND
				CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
					  ",array($db,$table));
			//already foreign keys added in this association table
			if ($fks>0) return false;
			$columns = $this->getColumns($table);
			if ($this->code($columns[$property1])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
				$this->widenColumn($table, $property1, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
			}
			if ($this->code($columns[$property2])!==RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32) {
				$this->widenColumn($table, $property2, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32);
			}

			$sql = "
				ALTER TABLE ".$this->noKW($table)."
				ADD FOREIGN KEY($property1) references `$table1`(id) ON DELETE CASCADE;
					  ";
			$this->adapter->exec( $sql );
			$sql ="
				ALTER TABLE ".$this->noKW($table)."
				ADD FOREIGN KEY($property2) references `$table2`(id) ON DELETE CASCADE
					  ";
			$this->adapter->exec( $sql );
			return true;
		} catch(Exception $e){ return false; }
	}

	/**
	 * Drops all tables in database
	 */
	public function wipeAll() {
		$this->adapter->exec('SET FOREIGN_KEY_CHECKS=0;');
		foreach($this->getTables() as $t) {
	 		try{
	 			$this->adapter->exec("drop table if exists`$t`");
	 		}
	 		catch(Exception $e){}
	 		try{
	 			$this->adapter->exec("drop view if exists`$t`");
	 		}
	 		catch(Exception $e){}
		}
		$this->adapter->exec('SET FOREIGN_KEY_CHECKS=1;');
	}


}


/**
 * RedBean SQLiteWriter with support for SQLite types
 *
 * @file				RedBean/QueryWriter/SQLiteT.php
 * @description			Represents a SQLite Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij and the RedBeanPHP Community
 * @license				BSD/GPLv2
 * 
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_SQLiteT extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {
	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 * Holds database adapter
	 */
	protected $adapter;

	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
  	protected $quoteCharacter = '`';

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 * DATA TYPE
	 * Integer Data type
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Numeric Data type (for REAL and date/time)
	 * @var integer
	 */
	const C_DATATYPE_NUMERIC = 1;

	/**
	 * DATA TYPE
	 * Text type
	 * @var integer
	 */
	const C_DATATYPE_TEXT = 2;

	/**
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 */
	public function __construct( RedBean_Adapter $adapter ) {
	
		$this->typeno_sqltype = array(
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_INTEGER=>'INTEGER',
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_NUMERIC=>'NUMERIC',
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_TEXT=>'TEXT',
		);
		
		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[$v]=$k;
		
				
		$this->adapter = $adapter;
		parent::__construct($adapter);
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID() {
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param  string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value, $flagSpecial=false ) {
		$this->svalue=$value;
		if ($value===false) return self::C_DATATYPE_INTEGER;
		if ($value===null) return self::C_DATATYPE_INTEGER; //for fks
		if ($this->startsWithZeros($value)) return self::C_DATATYPE_TEXT;
		if (is_numeric($value) && (intval($value)==$value) && $value<2147483648) return self::C_DATATYPE_INTEGER;
		if ((is_numeric($value) && $value < 2147483648)
				  || preg_match('/\d{4}\-\d\d\-\d\d/',$value)
				  || preg_match('/\d{4}\-\d\d\-\d\d\s\d\d:\d\d:\d\d/',$value)
		) {
			return self::C_DATATYPE_NUMERIC;
		}
		return self::C_DATATYPE_TEXT;
	}

	/**
	 * Adds a column of a given type to a table
	 *
	 * @param string  $table  table
	 * @param string  $column column
	 * @param integer $type	  type
	 */
	public function addColumn( $table, $column, $type) {
		$column = $this->check($column);
		$table = $this->check($table);
		$type=$this->typeno_sqltype[$type];
		$sql = "ALTER TABLE `$table` ADD `$column` $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = false ) {
		$r =  ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
	}

	/**
	 * Quote Items, to prevent issues with reserved words.
	 *
	 * @param array $items items to quote
	 *
	 * @return $quotedfItems quoted items
	 */
	private function quote( $items ) {
		foreach($items as $k=>$item) {
			$items[$k]=$this->noKW($item);
		}
		return $items;
	}

	/**
	 * This method upgrades the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype ) {
		$table = $this->safeTable($type,true);
		$column = $this->safeColumn($column,true);
		$newtype = $this->typeno_sqltype[$datatype];
		$oldColumns = $this->getColumns($type);
		$oldColumnNames = $this->quote(array_keys($oldColumns));
		$newTableDefStr='';
		foreach($oldColumns as $oldName=>$oldType) {
			if ($oldName != 'id') {
				if ($oldName!=$column) {
					$newTableDefStr .= ",`$oldName` $oldType";
				}
				else {
					$newTableDefStr .= ",`$oldName` $newtype";
				}
			}
		}
		$q = array();
		$q[] = "DROP TABLE IF EXISTS tmp_backup;";
		$q[] = "CREATE TEMPORARY TABLE tmp_backup(".implode(",",$oldColumnNames).");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "DROP TABLE `$table`;";
		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  );";
		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		foreach($q as $sq) {
			$this->adapter->exec($sq);
		}
	}


	/**
	 * Returns all tables in the database
	 *
	 * @return array $tables tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "SELECT name FROM sqlite_master
			WHERE type='table' AND name!='sqlite_sequence';" );
	}

	/**
	 * Creates an empty, column-less table for a bean.
	 *
	 * @param string $table table
	 */
	public function createTable( $table ) {
		$table = $this->safeTable($table);
		$sql = "CREATE TABLE $table ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table
	 *
	 * @return array $columns columns
	 */
	public function getColumns( $table ) {
		$table = $this->safeTable($table, true);
		$columnsRaw = $this->adapter->get("PRAGMA table_info('$table')");
		$columns = array();
		foreach($columnsRaw as $r) {
			$columns[$r['name']]=$r['type'];
		}
		return $columns;
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table   table
	 * @param string $column1 first column
	 * @param string $column2 second column
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table);
		$name = 'UQ_'.sha1(implode(',',$columns));
		$sql = "CREATE UNIQUE INDEX IF NOT EXISTS $name ON $table (".implode(',',$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Given an Database Specific SQLState and a list of QueryWriter
	 * Standard SQL States this function converts the raw SQL state to a
	 * database agnostic ANSI-92 SQL states and checks if the given state
	 * is in the list of agnostic states.
	 *
	 * @param string $state state
	 * @param array  $list  list of states
	 *
	 * @return boolean $isInArray whether state is in list
	 */
	public function sqlStateIn($state, $list) {
		$stateMap = array(
			'HY000'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'23000'=>RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);
		return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'),$list);
	}

	/**
	 * Counts rows in a table.
	 * Uses SQLite optimization for deleting all records (i.e. no WHERE)
	 *
	 * @param string $beanType
	 *
	 * @return integer $numRowsFound
	 */
	public function wipe($type) {
		$table = $this->safeTable($type);
		$this->adapter->exec("DELETE FROM $table");
	}

	/**
	 * Adds a foreign key to a type
	 *
	 * @param string  $type        type you want to modify table of
	 * @param string  $targetType  target type
	 * @param string  $field       field of the type that needs to get the fk
	 * @param string  $targetField field where the fk needs to point to
	 * @param boolean $isDep       whether this field is dependent on it's referenced record
	 *
	 * @return bool $success whether an FK has been added
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDep=false) {
		return $this->buildFK($type, $targetType, $field, $targetField, $isDep);
	}

	/**
	 * Adds a foreign key to a type
	 *
	 * @param  string $type        type you want to modify table of
	 * @param  string $targetType  target type
	 * @param  string $field       field of the type that needs to get the fk
	 * @param  string $targetField field where the fk needs to point to
	 * @param  integer $buildopt   0 = NO ACTION, 1 = ON DELETE CASCADE
	 *
	 * @return bool $success whether an FK has been added
	 */

	protected function buildFK($type, $targetType, $field, $targetField,$constraint=false) {
			try{
				$consSQL = ($constraint ? 'CASCADE' : 'SET NULL');
				$table = $this->safeTable($type,true);
				$targetTable = $this->safeTable($targetType,true);
				$field = $this->safeColumn($field,true);
				$targetField = $this->safeColumn($targetField,true);
				$oldColumns = $this->getColumns($type);
				$oldColumnNames = $this->quote(array_keys($oldColumns));
				$newTableDefStr='';
				foreach($oldColumns as $oldName=>$oldType) {
					if ($oldName != 'id') {
						$newTableDefStr .= ",`$oldName` $oldType";
					}
				}
				//retrieve old foreign keys
				$sqlGetOldFKS = "PRAGMA foreign_key_list('$table'); ";
				$oldFKs = $this->adapter->get($sqlGetOldFKS);
				$restoreFKSQLSnippets = "";
				foreach($oldFKs as $oldFKInfo) {
					if ($oldFKInfo['from']==$field && $oldFKInfo['on_delete']==$consSQL) {
						//this field already has a FK.
						return false;
					}
					if ($oldFKInfo['from']==$field && $oldFKInfo['on_delete']!=$consSQL) {
						//this field already has a FK.but needs to be replaced
						continue;
					}
					$oldTable = $table;
					$oldField = $oldFKInfo['from'];
					$oldTargetTable = $oldFKInfo['table'];
					$oldTargetField = $oldFKInfo['to'];
					$restoreFKSQLSnippets .= ", FOREIGN KEY(`$oldField`) REFERENCES `$oldTargetTable`(`$oldTargetField`) ON DELETE ".$oldFKInfo['on_delete'];
				}
				$fkDef = $restoreFKSQLSnippets;
				if ($constraint) {
					$fkDef .= ", FOREIGN KEY(`$field`) REFERENCES `$targetTable`(`$targetField`) ON DELETE CASCADE ";
				}
				else {
					$fkDef .= ", FOREIGN KEY(`$field`) REFERENCES `$targetTable`(`$targetField`) ON DELETE SET NULL ON UPDATE SET NULL";
				}
				$q = array();
				$q[] = "DROP TABLE IF EXISTS tmp_backup;";
				$q[] = "CREATE TEMPORARY TABLE tmp_backup(".implode(',',$oldColumnNames).");";
				$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
				$q[] = "PRAGMA foreign_keys = 0 ";
				$q[] = "DROP TABLE `$table`;";
				$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr $fkDef );";
				$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
				$q[] = "DROP TABLE tmp_backup;";
				$q[] = "PRAGMA foreign_keys = 1 ";
				foreach($q as $sq) {
					$this->adapter->exec($sq);
				}
				
				
				return true;
			}
			catch(Exception $e){ return false; }
	}


	/**
	 * Add the constraints for a specific database driver: SQLite.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected  function constrain($table, $table1, $table2, $property1, $property2) {
		$writer = $this;
		$adapter = $this->adapter;
		$firstState = $this->buildFK($table,$table1,$property1,'id',true);
		$secondState = $this->buildFK($table,$table2,$property2,'id',true);
		return ($firstState && $secondState);
	}

	/**
	 * Removes all tables and views from the database.
	 * 
	 * @return void
	 */
	public function wipeAll() {
		$this->adapter->exec('PRAGMA foreign_keys = 0 ');
		foreach($this->getTables() as $t) {
	 		try{
	 			$this->adapter->exec("drop table if exists`$t`");
	 		}
	 		catch(Exception $e){}
	 		try{
	 			$this->adapter->exec("drop view if exists`$t`");
	 		}
	 		catch(Exception $e){}
		}
		$this->adapter->exec('PRAGMA foreign_keys = 1 ');
	}

}


/**
 * RedBean PostgreSQL Query Writer
 * 
 * @file			RedBean/QueryWriter/PostgreSQL.php
 * @description		QueryWriter for the PostgreSQL database system.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_PostgreSQL extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {
	
	/**
	 * DATA TYPE
	 * Integer Data Type
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Double Precision Type
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 1;

	/**
	 * DATA TYPE
	 * String Data Type
	 * @var integer
	 */
	const C_DATATYPE_TEXT = 3;
	
	
	/**
	 * Special type date for storing date values: YYYY-MM-DD
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATE = 80;
	
	/**
	 * Special type date for storing date values: YYYY-MM-DD HH:MM:SS
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	
	const C_DATATYPE_SPECIAL_POINT		= 101;
	const C_DATATYPE_SPECIAL_LINE		= 102;
	const C_DATATYPE_SPECIAL_LSEG		= 103;
	const C_DATATYPE_SPECIAL_BOX		= 104;
	const C_DATATYPE_SPECIAL_CIRCLE		= 105;
	const C_DATATYPE_SPECIAL_POLYGON	= 106;
	
	
	
	/**
	 * Specified field type cannot be overruled
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;
	

	/**
	 * Holds Database Adapter
	 * @var RedBean_DBAdapter
	 */
	protected $adapter;

	/**
	 * character to escape keyword table/column names
	 * @var string
	 */
	protected $quoteCharacter = '"';

	/**
	 * Default Value
	 * @var string
	 */
	protected $defaultValue = 'DEFAULT';
	
	/**
	* This method returns the datatype to be used for primary key IDS and
	* foreign keys. Returns one if the data type constants.
	*
	* @return integer $const data type to be used for IDS.
	*/
	public function getTypeForID() {
		return self::C_DATATYPE_INTEGER;
	}
	
	/**
	 * Returns the insert suffix SQL Snippet
	 *
	 * @param string $table table
	 *
	 * @return  string $sql SQL Snippet
	 */
	protected function getInsertSuffix($table) {
		return 'RETURNING id ';
	}

	/**
	 * Constructor
	 * The Query Writer Constructor also sets up the database
	 *
	 * @param RedBean_DBAdapter $adapter adapter
	 */
	public function __construct( RedBean_Adapter_DBAdapter $adapter ) {
		
		
		$this->typeno_sqltype = array(
				  self::C_DATATYPE_INTEGER=>' integer ',
				  self::C_DATATYPE_DOUBLE=>' double precision ',
				  self::C_DATATYPE_TEXT=>' text ',
				  self::C_DATATYPE_SPECIAL_DATE => ' date ',
				  self::C_DATATYPE_SPECIAL_DATETIME => ' timestamp without time zone ',
				  self::C_DATATYPE_SPECIAL_POINT => ' point ',
				  self::C_DATATYPE_SPECIAL_LINE => ' line ',
				  self::C_DATATYPE_SPECIAL_LSEG => ' lseg ',
				  self::C_DATATYPE_SPECIAL_BOX => ' box ',
				  self::C_DATATYPE_SPECIAL_CIRCLE => ' circle ',
				  self::C_DATATYPE_SPECIAL_POLYGON => ' polygon ',
			
		);

		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[trim(strtolower($v))]=$k;
		
		
		$this->adapter = $adapter;
		parent::__construct();
	}

	/**
	 * Returns all tables in the database
	 *
	 * @return array $tables tables
	 */
	public function getTables() {
		return $this->adapter->getCol( "select table_name from information_schema.tables
		where table_schema = 'public'" );
	}

	/**
	 * Creates an empty, column-less table for a bean.
	 *
	 * @param string $table table to create
	 */
	public function createTable( $table ) {
		$table = $this->safeTable($table);
		$sql = " CREATE TABLE $table (id SERIAL PRIMARY KEY); ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table to get columns from
	 *
	 * @return array $columns array filled with column (name=>type)
	 */
	public function getColumns( $table ) {
		$table = $this->safeTable($table, true);
		$columnsRaw = $this->adapter->get("select column_name, data_type from information_schema.columns where table_name='$table'");
		foreach($columnsRaw as $r) {
			$columns[$r['column_name']]=$r['data_type'];
		}
		return $columns;
	}

	/**
	 * Returns the pgSQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param string $value value to determine type of
	 *
	 * @return integer $type type code for this value
	 */
	public function scanType( $value, $flagSpecial=false ) {
		
		$this->svalue=$value;
		
		if ($flagSpecial && $value) {
			if (preg_match('/^\d{4}\-\d\d-\d\d$/',$value)) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_DATE;
			}
			if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/',$value)) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_DATETIME;
			}
			if (strpos($value,'POINT(')===0) {
				$this->svalue = str_replace('POINT','',$value);
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_POINT;
			}
			if (strpos($value,'LSEG(')===0) {
				$this->svalue = str_replace('LSEG','',$value);
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_LSEG;
			}
			if (strpos($value,'BOX(')===0) {
				$this->svalue = str_replace('BOX','',$value);
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_BOX;
			}
			if (strpos($value,'CIRCLE(')===0) {
				$this->svalue = str_replace('CIRCLE','',$value);
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_CIRCLE;
			}
			if (strpos($value,'POLYGON(')===0) {
				$this->svalue = str_replace('POLYGON','',$value);
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_POLYGON;
			}
		}
		
		$sz = ($this->startsWithZeros($value));
		if ($sz) return self::C_DATATYPE_TEXT;
		if ($value===null || ($value instanceof RedBean_Driver_PDO_NULL) ||(is_numeric($value)
				  && floor($value)==$value
				  && $value < 2147483648
				  && $value > -2147483648)) {
			return self::C_DATATYPE_INTEGER;
		}
		elseif(is_numeric($value)) {
			return self::C_DATATYPE_DOUBLE;
		}
		else {
			return self::C_DATATYPE_TEXT;
		}
	}

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = false ) {
		$r = ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
	}

	/**
	 * This method upgrades the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype ) {
		$table = $type;
		$type = $datatype;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$newtype = $this->typeno_sqltype[$type];
		$changecolumnSQL = "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype ";
		$this->adapter->exec( $changecolumnSQL );
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table table to add index to
	 * @param string $col1  column to be part of index
	 * @param string $col2  column 2 to be part of index
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table, true);
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v) {
			$columns[$k]=$this->safeColumn($v);
		}
		$r = $this->adapter->get("SELECT
									i.relname as index_name
								FROM
									pg_class t,
									pg_class i,
									pg_index ix,
									pg_attribute a
								WHERE
									t.oid = ix.indrelid
									AND i.oid = ix.indexrelid
									AND a.attrelid = t.oid
									AND a.attnum = ANY(ix.indkey)
									AND t.relkind = 'r'
									AND t.relname = '$table'
								ORDER BY  t.relname,  i.relname;");

		$name = "UQ_".sha1($table.implode(',',$columns));
		if ($r) {
			foreach($r as $i) {
				if (strtolower( $i['index_name'] )== strtolower( $name )) {
					return;
				}
			}
		}
		$sql = "ALTER TABLE \"$table\"
                ADD CONSTRAINT $name UNIQUE (".implode(',',$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Given an Database Specific SQLState and a list of QueryWriter
	 * Standard SQL States this function converts the raw SQL state to a
	 * database agnostic ANSI-92 SQL states and checks if the given state
	 * is in the list of agnostic states.
	 *
	 * @param string $state state
	 * @param array  $list  list of states
	 *
	 * @return boolean $isInArray whether state is in list
	 */
	public function sqlStateIn($state, $list) {
		$stateMap = array(
			'42P01'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42703'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23505'=>RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);
		return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'),$list);
	}

	/**
	 * Adds a foreign key to a table. The foreign key will not have any action; you
	 * may configure this afterwards.
	 *
	 * @param  string $type        type you want to modify table of
	 * @param  string $targetType  target type
	 * @param  string $field       field of the type that needs to get the fk
	 * @param  string $targetField field where the fk needs to point to
	 *
	 * @return bool $success whether an FK has been added
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDep = false) {
		try{
			$table = $this->safeTable($type);
			$column = $this->safeColumn($field);
			$tableNoQ = $this->safeTable($type,true);
			$columnNoQ = $this->safeColumn($field,true);
			$targetTable = $this->safeTable($targetType);
			$targetTableNoQ = $this->safeTable($targetType,true);
			$targetColumn  = $this->safeColumn($targetField);
			$targetColumnNoQ  = $this->safeColumn($targetField,true);
			
			
			$sql = "SELECT
					tc.constraint_name, 
					tc.table_name, 
					kcu.column_name, 
					ccu.table_name AS foreign_table_name,
					ccu.column_name AS foreign_column_name,
					rc.delete_rule
					FROM 
					information_schema.table_constraints AS tc 
					JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
					JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
					JOIN information_schema.referential_constraints AS rc ON ccu.constraint_name = rc.constraint_name
					WHERE constraint_type = 'FOREIGN KEY' AND tc.table_catalog=current_database()
					AND tc.table_name = '$tableNoQ' 
					AND ccu.table_name = '$targetTableNoQ'
					AND kcu.column_name = '$columnNoQ'
					AND ccu.column_name = '$targetColumnNoQ'
					";
	
			
			$row = $this->adapter->getRow($sql);
			
			$flagAddKey = false;
			
			if (!$row) $flagAddKey = true;
			
			if ($row) { 
				if (($row['delete_rule']=='SET NULL' && $isDep) || 
					($row['delete_rule']!='SET NULL' && !$isDep)) {
					//delete old key
					$flagAddKey = true; //and order a new one
					$cName = $row['constraint_name'];
					$sql = "ALTER TABLE $table DROP CONSTRAINT $cName ";
					$this->adapter->exec($sql);
				} 
				
			}
			
			if ($flagAddKey) {
			$delRule = ($isDep ? 'CASCADE' : 'SET NULL');	
			$this->adapter->exec("ALTER TABLE  $table
					ADD FOREIGN KEY (  $column ) REFERENCES  $targetTable (
					$targetColumn) ON DELETE $delRule ON UPDATE SET NULL DEFERRABLE ;");
					return true;
			}
			return false;
			
		}
		catch(Exception $e){ return false; }
	}



	/**
	 * Add the constraints for a specific database driver: PostgreSQL.
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected function constrain($table, $table1, $table2, $property1, $property2) {
		try{
			$writer = $this;
			$adapter = $this->adapter;
			$fkCode = 'fk'.md5($table.$property1.$property2);
			$sql = "
						SELECT
								c.oid,
								n.nspname,
								c.relname,
								n2.nspname,
								c2.relname,
								cons.conname
						FROM pg_class c
						JOIN pg_namespace n ON n.oid = c.relnamespace
						LEFT OUTER JOIN pg_constraint cons ON cons.conrelid = c.oid
						LEFT OUTER JOIN pg_class c2 ON cons.confrelid = c2.oid
						LEFT OUTER JOIN pg_namespace n2 ON n2.oid = c2.relnamespace
						WHERE c.relkind = 'r'
						AND n.nspname IN ('public')
						AND (cons.contype = 'f' OR cons.contype IS NULL)
						AND
						(  cons.conname = '{$fkCode}a'	OR  cons.conname = '{$fkCode}b' )

					  ";

			$rows = $adapter->get( $sql );
			if (!count($rows)) {
				$sql1 = "ALTER TABLE \"$table\" ADD CONSTRAINT
						  {$fkCode}a FOREIGN KEY ($property1)
							REFERENCES \"$table1\" (id) ON DELETE CASCADE ";
				$sql2 = "ALTER TABLE \"$table\" ADD CONSTRAINT
						  {$fkCode}b FOREIGN KEY ($property2)
							REFERENCES \"$table2\" (id) ON DELETE CASCADE ";
				$adapter->exec($sql1);
				$adapter->exec($sql2);
			}
			return true;
		}
		catch(Exception $e){ return false; }
	}

	/**
	 * Removes all tables and views from the database.
	 */
	public function wipeAll() {
      	$this->adapter->exec('SET CONSTRAINTS ALL DEFERRED');
      	foreach($this->getTables() as $t) {
      		$t = $this->noKW($t);
	 		try{
	 			$this->adapter->exec("drop table if exists $t CASCADE ");
	 		}
	 		catch(Exception $e){  }
	 		try{
	 			$this->adapter->exec("drop view if exists $t CASCADE ");
	 		}
	 		catch(Exception $e){  throw $e; }
		}
		$this->adapter->exec('SET CONSTRAINTS ALL IMMEDIATE');
	}

}


/**
 * RedBean CUBRID Writer 
 *
 * @file				RedBean/QueryWriter/CUBRID.php
 * @description			Represents a CUBRID Database to RedBean
 *						To write a driver for a different database for RedBean
 *						you should only have to change this file.
 * @author				Gabor de Mooij and the RedBeanPHP Community
 * @license				BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 
 */
class RedBean_QueryWriter_CUBRID extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter {

	
	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 *
	 * DATA TYPE
	 * Signed 4 byte Integer
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Double precision floating point number
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 1;
	
	/**
	 *
	 * DATA TYPE
	 * Variable length text
	 * @var integer
	 */
	const C_DATATYPE_STRING = 2;

	
	/**
	 * Special type date for storing date values: YYYY-MM-DD
	 * @var integer
	 */	
	const C_DATATYPE_SPECIAL_DATE = 80;
	
	/**
	 * Special type datetime for store date-time values: YYYY-MM-DD HH:II:SS
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	

	/**
	 * 
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;

	
	
	/**
	 * Holds the RedBean Database Adapter.
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * character to escape keyword table/column names
	 * @var string
	 */
  	protected $quoteCharacter = '`';
	
	/**
	 * Do everything that needs to be done to format a table name.
	 *
	 * @param string $name of table
	 *
	 * @return string table name
	 */
	public function safeTable($name, $noQuotes = false) {
		$name = strtolower($name);
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	
	/**
	 * Do everything that needs to be done to format a column name.
	 *
	 * @param string $name of column
	 *
	 * @return string $column name
	 */
	public function safeColumn($name, $noQuotes = false) {
		$name = strtolower($name);
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}
	
	/**
	 * Constructor.
	 * The Query Writer Constructor also sets up the database.
	 *
	 * @param RedBean_Adapter_DBAdapter $adapter adapter
	 *
	 */
	public function __construct( RedBean_Adapter $adapter ) {
		
		$this->typeno_sqltype = array(
			RedBean_QueryWriter_CUBRID::C_DATATYPE_INTEGER => ' INTEGER ',
			RedBean_QueryWriter_CUBRID::C_DATATYPE_DOUBLE => ' DOUBLE ',
			RedBean_QueryWriter_CUBRID::C_DATATYPE_STRING => ' STRING ',
			RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATE => ' DATE ',
			RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
		);
		
		$this->sqltype_typeno = array();
		foreach($this->typeno_sqltype as $k=>$v)
		$this->sqltype_typeno[trim(($v))]=$k;
		$this->sqltype_typeno['STRING(1073741823)'] = self::C_DATATYPE_STRING;
		
		$this->adapter = $adapter;
		parent::__construct();
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID() {
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * Returns all tables in the database.
	 *
	 * @return array $tables tables
	 */
	public function getTables() { 
		$rows = $this->adapter->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );
		return $rows;
	}

	/**
	 * Creates an empty, column-less table for a bean based on it's type.
	 * This function creates an empty table for a bean. It uses the
	 * safeTable() function to convert the type name to a table name.
	 *
	 * @param string $table type of bean you want to create a table for
	 *
	 * @return void
	 */
	public function createTable( $table ) {
		$rawTable = $this->safeTable($table,true);
		$table = $this->safeTable($table);
		
		$sql = '     CREATE TABLE '.$table.' (
                     "id" integer AUTO_INCREMENT,
					 CONSTRAINT "pk_'.$rawTable.'_id" PRIMARY KEY("id")
		             )';
		$this->adapter->exec( $sql );
	}



	/**
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table
	 *
	 * @return array $columns columns
	 */
	public function getColumns( $table ) {
		$columns = array();
		$table = $this->safeTable($table);
		$columnsRaw = $this->adapter->get("SHOW COLUMNS FROM $table");
		foreach($columnsRaw as $r) {
			$columns[$r['Field']]=$r['Type'];
		}
		return $columns;
	}

	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value, $flagSpecial=false ) {
		$this->svalue = $value;
		
		if (is_null($value)) {
			return self::C_DATATYPE_INTEGER;
		}
		
		if ($flagSpecial) {
			if (preg_match('/^\d{4}\-\d\d-\d\d$/',$value)) {
				return self::C_DATATYPE_SPECIAL_DATE;
			}
			if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/',$value)) {
				return self::C_DATATYPE_SPECIAL_DATETIME;
			}
		}
		$value = strval($value);
		if (!$this->startsWithZeros($value)) {

			if (is_numeric($value) && (floor($value)==$value) && $value >= -2147483648  && $value <= 2147483648 ) {
				return self::C_DATATYPE_INTEGER;
			}
			if (is_numeric($value)) {
				return self::C_DATATYPE_DOUBLE;
			}
		}
		
		return self::C_DATATYPE_STRING;
	}

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = false ) {
		
		
		$r = ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED);
		
		if ($includeSpecials) return $r;
		if ($r > self::C_DATATYPE_SPECIFIED) return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	
	/**
	 * This method adds a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 *
	 */
	public function addColumn( $type, $column, $field ) {
		$table = $type;
		$type = $field;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$type = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$sql = "ALTER TABLE $table ADD COLUMN $column $type ";
		$this->adapter->exec( $sql );
	}


	/**
	 * This method upgrades the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype ) {
		$table = $type;
		$type = $datatype;
		$table = $this->safeTable($table);
		$column = $this->safeColumn($column);
		$newtype = array_key_exists($type, $this->typeno_sqltype) ? $this->typeno_sqltype[$type] : '';
		$changecolumnSQL = "ALTER TABLE $table CHANGE $column $column $newtype ";
		$this->adapter->exec( $changecolumnSQL );
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table table
	 * @param string $col1  column
	 * @param string $col2  column
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table,$columns ) {
		$table = $this->safeTable($table);
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v) {
			$columns[$k]= $this->safeColumn($v);
		}
		$r = $this->adapter->get("SHOW INDEX FROM $table");
		$name = 'UQ_'.sha1(implode(',',$columns));
		if ($r) {
			foreach($r as $i) { 
				if (strtoupper($i['Key_name'])== strtoupper($name)) {
					return;
				}
			}
		}
		$sql = "ALTER TABLE $table
                ADD CONSTRAINT UNIQUE $name (".implode(',',$columns).")";
		$this->adapter->exec($sql);
	}

	/**
	 * Tests whether a given SQL state is in the list of states.
	 *
	 * @param string $state code
	 * @param array  $list  array of sql states
	 *
	 * @return boolean $yesno occurs in list
	 */
	public function sqlStateIn($state, $list) {
		/*$stateMap = array(
			'HY000'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22'=>RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'HY000'=>RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);*/
		
		if ($state=='HY000') {
			if (in_array(RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION,$list)) return true;
			if (in_array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,$list)) return true;
			if (in_array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,$list)) return true;
		}
		return false;
		//return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'),$list); 
	}

	
	/**
	 * Adds a constraint. If one of the beans gets trashed
	 * the other, related bean should be removed as well.
	 *
	 * @param RedBean_OODBBean $bean1      first bean
	 * @param RedBean_OODBBean $bean2      second bean
	 * @param bool 			   $dontCache  by default we use a cache, TRUE = NO CACHING (optional)
	 *
	 * @return void
	 */
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table1 = $bean1->getMeta('type');
		$table2 = $bean2->getMeta('type');
		$writer = $this;
		$adapter = $this->adapter;
		$table = RedBean_QueryWriter_AQueryWriter::getAssocTableFormat( array( $table1,$table2) );
		$property1 = $bean1->getMeta('type') . '_id';
		$property2 = $bean2->getMeta('type') . '_id';
		if ($property1==$property2) $property2 = $bean2->getMeta('type').'2_id';
		//Dispatch to right method
		return $this->constrain($table, $table1, $table2, $property1, $property2);
	}

	
	/**
	 * Add the constraints for a specific database driver: CUBRID
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string			  $table     table
	 * @param string			  $table1    table1
	 * @param string			  $table2    table2
	 * @param string			  $property1 property1
	 * @param string			  $property2 property2
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected function constrain($table, $table1, $table2, $property1, $property2) {
		$writer = $this;
		$adapter = $this->adapter;
		$firstState = $this->buildFK($table,$table1,$property1,'id',true);
		$secondState = $this->buildFK($table,$table2,$property2,'id',true);
		return ($firstState && $secondState);
	}

	
	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDependent = false) {
		return $this->buildFK($type, $targetType, $field, $targetField, $isDependent);
	}
	
	
	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type	       type that will have a foreign key field
	 * @param  string $targetType  points to this type
	 * @param  string $field       field that contains the foreign key value
	 * @param  string $targetField field where the fk points to
	 *
	 * @return void
	 */
	protected function buildFK($type, $targetType, $field, $targetField,$isDep=false) {
		$table = $this->safeTable($type);
		$tableNoQ = $this->safeTable($type,true);
		$targetTable = $this->safeTable($targetType);
		$targetTableNoQ = $this->safeTable($targetType,true);
		$column = $this->safeColumn($field);
		$columnNoQ = $this->safeColumn($field,true);
		$targetColumn  = $this->safeColumn($targetField);
		$targetColumnNoQ  = $this->safeColumn($targetField,true);
		$keys = $this->getKeys($targetTableNoQ,$tableNoQ);
		$needsToAddFK = true;
		$needsToDropFK = false;
		foreach($keys as $key) {
			if ($key['FKTABLE_NAME']==$tableNoQ && $key['FKCOLUMN_NAME']==$columnNoQ) { 
				//already has an FK
				$needsToDropFK = true;
				if ((($isDep && $key['DELETE_RULE']==0) || (!$isDep && $key['DELETE_RULE']==3))) {
					return false;
				}
				
			}
		}
		
		if ($needsToDropFK) {
			$sql = "ALTER TABLE $table DROP FOREIGN KEY {$key['FK_NAME']} ";
			$this->adapter->exec($sql);
		}
		
		$casc = ($isDep ? 'CASCADE' : 'SET NULL');
		$sql = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc ";
		$this->adapter->exec($sql);
		
	}	
	
	
	/**
	 * Drops all tables in database
	 */
	public function wipeAll() {
		foreach($this->getTables() as $t) {
			foreach($this->getKeys($t) as $k) {
				$this->adapter->exec("ALTER TABLE \"{$k['FKTABLE_NAME']}\" DROP FOREIGN KEY \"{$k['FK_NAME']}\"");
			}
			$this->adapter->exec("DROP TABLE \"$t\"");
		}
		foreach($this->getTables() as $t) {
			$this->adapter->exec("DROP TABLE \"$t\"");
		}
	}
	
	
	/**
	 * Obtains the keys of a table using the PDO schema function.
	 * 
	 * @param type $table
	 * @return type 
	 */
	protected function getKeys($table,$table2=null) {
		$pdo = $this->adapter->getDatabase()->getPDO();
		$keys = $pdo->cubrid_schema(PDO::CUBRID_SCH_EXPORTED_KEYS,$table);//print_r($keys);
		if ($table2) $keys = array_merge($keys, $pdo->cubrid_schema(PDO::CUBRID_SCH_IMPORTED_KEYS,$table2) );//print_r($keys);
		
		return $keys;
	}
	
	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string $table escaped string
	 */
	protected function check($table) {
		if ($this->quoteCharacter && strpos($table, $this->quoteCharacter)!==false) {
		  throw new Redbean_Exception_Security('Illegal chars in table name');
	    }
		return $table;
	}
	
}

/**
 * RedBean Exception Base
 *
 * @file			RedBean/Exception.php
 * @description		Represents the base class
 * 					for RedBean Exceptions
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Exception extends Exception {}

/**
 * RedBean Exception SQL
 *
 * @file			RedBean/Exception/SQL.php
 * @description		Represents a generic database exception independent of the
 *					underlying driver.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Exception_SQL extends Exception {

	/**
	 * Holds the current SQL Strate code.
	 * @var string
	 */
	private $sqlState;

	/**
	 * Returns an ANSI-92 compliant SQL state.
	 *
	 * @return string $state ANSI state code
	 */
	public function getSQLState() {
		return $this->sqlState;
	}

	/**
	 * @todo parse state to verify valid ANSI92!
	 * Stores ANSI-92 compliant SQL state.
	 *
	 * @param string $sqlState code
	 * 
	 * @return void
	 */
	public function setSQLState( $sqlState ) {
		$this->sqlState = $sqlState;
	}

	/**
	 * To String prints both code and SQL state.
	 *
	 * @return string $message prints this exception instance as a string
	 */
	public function __toString() {
		return '['.$this->getSQLState().'] - '.$this->getMessage();
	}
}

/**
 * Exception Security.
 * Part of the RedBean Exceptions Mechanism.
 *
 * @file			RedBean/Exception
 * @description		Represents a subtype in the RedBean Exception System.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Exception_Security extends RedBean_Exception {}

/**
 * RedBean Object Oriented DataBase
 * 
 * @file			RedBean/OODB.php
 * @description 	RedBean OODB
 * 
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * The RedBean OODB Class is the main class of RedBeanPHP.
 * It takes RedBean_OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODB extends RedBean_Observable {

	/**
	 * Chill mode, for fluid mode but with a list of beans / types that
	 * are considered to be stable and don't need to be modified.
	 * @var array 
	 */
	private $chillList = array();
	
	/**
	 * List of dependencies. Format: $type => array($depensOnMe, $andMe)
	 * @var array
	 */
	private $dep = array();

	/**
	 * Secret stash. Used for batch loading.
	 * @var array
	 */
	private $stash = NULL;

	/**
	 * Contains the writer for OODB.
	 * @var RedBean_Adapter_DBAdapter
	 */
	private $writer;
	/**
	 * Whether this instance of OODB is frozen or not.
	 * In frozen mode the schema will not de modified, in fluid mode
	 * the schema can be adjusted to meet the needs of the developer.
	 * @var boolean
	 */
	private $isFrozen = false;

	/**
	 * Bean Helper. The bean helper to give to the beans. Bean Helpers
	 * assist beans in getting hold of a toolbox.
	 * @var null|\RedBean_BeanHelperFacade
	 */
	private $beanhelper = null;

	/**
	 * The RedBean OODB Class is the main class of RedBean.
	 * It takes RedBean_OODBBean objects and stores them to and loads them from the
	 * database as well as providing other CRUD functions. This class acts as a
	 * object database.
	 * Constructor, requires a DBAadapter (dependency inversion)
	 * @param RedBean_Adapter_DBAdapter $adapter
	 */
	public function __construct( $writer ) {
		if ($writer instanceof RedBean_QueryWriter) {
			$this->writer = $writer;
		}
		$this->beanhelper = new RedBean_BeanHelperFacade();
	}

	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 * 
	 * You can also pass an array containing a selection of frozen types.
	 * Let's call this chilly mode, it's just like fluid mode except that
	 * certain types (i.e. tables) aren't touched.
	 * 
	 * @param boolean|array $trueFalse
	 */
	public function freeze( $tf ) {
		if (is_array($tf)) {
			$this->chillList = $tf;
			$this->isFrozen = false;
		}
		else
		$this->isFrozen = (boolean) $tf;
	}


	/**
	 * Returns the current mode of operation of RedBean.
	 * In fluid mode the database
	 * structure is adjusted to accomodate your objects.
	 * In frozen mode
	 * this is not the case.
	 * 
	 * @return boolean $yesNo TRUE if frozen, FALSE otherwise
	 */
	public function isFrozen() {
		return (bool) $this->isFrozen;
	}

	/**
	 * Dispenses a new bean (a RedBean_OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a RedBean_OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 * 
	 * @param string $type type of bean you want to dispense
	 * 
	 * @return RedBean_OODBBean $bean the new bean instance
	 */
	public function dispense($type ) {
		$this->signal( 'before_dispense', $type );
		$bean = new RedBean_OODBBean();
		$bean->setBeanHelper($this->beanhelper);
		$bean->setMeta('type',$type );
		$bean->setMeta('sys.id','id');
		$bean->id = 0;
		if (!$this->isFrozen) $this->check( $bean );
		$bean->setMeta('tainted',true);
		$this->signal('dispense',$bean );
		return $bean;
	}

	/**
	 * Sets bean helper to be given to beans.
	 * Bean helpers assist beans in getting a reference to a toolbox.
	 *
	 * @param RedBean_IBeanHelper $beanhelper helper
	 *
	 * @return void
	 */
	public function setBeanHelper( RedBean_IBeanHelper $beanhelper) {
		$this->beanhelper = $beanhelper;
	}


	/**
	 * Checks whether a RedBean_OODBBean bean is valid.
	 * If the type is not valid or the ID is not valid it will
	 * throw an exception: RedBean_Exception_Security.
	 * @throws RedBean_Exception_Security $exception
	 * 
	 * @param RedBean_OODBBean $bean the bean that needs to be checked
	 * 
	 * @return void
	 */
	public function check( RedBean_OODBBean $bean ) {
		//Is all meta information present?
		if (!isset($bean->id) ) {
			throw new RedBean_Exception_Security('Bean has incomplete Meta Information id ');
		}
		if (!($bean->getMeta('type'))) {
			throw new RedBean_Exception_Security('Bean has incomplete Meta Information II');
		}
		//Pattern of allowed characters
		$pattern = '/[^a-z0-9_]/i';
		//Does the type contain invalid characters?
		if (preg_match($pattern,$bean->getMeta('type'))) {
			throw new RedBean_Exception_Security('Bean Type is invalid');
		}
		//Are the properties and values valid?
		foreach($bean as $prop=>$value) {
			if (
					  is_array($value) ||
					  (is_object($value)) ||
					  strlen($prop)<1 ||
					  preg_match($pattern,$prop)
			) {
				throw new RedBean_Exception_Security("Invalid Bean: property $prop  ");
			}
		}
	}


	/**
	 * Searches the database for a bean that matches conditions $conditions and sql $addSQL
	 * and returns an array containing all the beans that have been found.
	 * 
	 * Conditions need to take form:
	 * 
	 * array(
	 * 		'PROPERTY' => array( POSSIBLE VALUES... 'John','Steve' )
	 * 		'PROPERTY' => array( POSSIBLE VALUES... )
	 * );
	 * 
	 * All conditions are glued together using the AND-operator, while all value lists
	 * are glued using IN-operators thus acting as OR-conditions.
	 * 
	 * Note that you can use property names; the columns will be extracted using the
	 * appropriate bean formatter.
	 * 
	 * @throws RedBean_Exception_SQL 
	 * 
	 * @param string  $type       type of beans you are looking for
	 * @param array   $conditions list of conditions
	 * @param string  $addSQL	  SQL to be used in query
	 * @param boolean $all        whether you prefer to use a WHERE clause or not (TRUE = not)
	 * 
	 * @return array $beans		  resulting beans
	 */
	public function find($type,$conditions=array(),$addSQL=null,$all=false) {
		try {
			$beans = $this->convertToBeans($type,$this->writer->selectRecord($type,$conditions,$addSQL,false,false,$all));
			return $beans;
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN)
			)) throw $e;
		}
		return array();
	}


	/**
	 * Checks whether the specified table already exists in the database.
	 * Not part of the Object Database interface!
	 * 
	 * @param string $table table name (not type!)
	 * 
	 * @return boolean $exists whether the given table exists within this database.
	 */
	public function tableExists($table) {
		//does this table exist?
		$tables = $this->writer->getTables();
		return in_array(($table), $tables);
	}


	/**
	 * Processes all column based build commands.
	 * A build command is an additional instruction for the Query Writer. It is processed only when
	 * a column gets created. The build command is often used to instruct the writer to write some
	 * extra SQL to create indexes or constraints. Build commands are stored in meta data of the bean.
	 * They are only for internal use, try to refrain from using them in your code directly.
	 *
	 * @param  string           $table    name of the table to process build commands for
	 * @param  string           $property name of the property to process build commands for
	 * @param  RedBean_OODBBean $bean     bean that contains the build commands
	 *
	 * @return void
	 */
	protected function processBuildCommands($table, $property, RedBean_OODBBean $bean) {
		if ($inx = ($bean->getMeta('buildcommand.indexes'))) {
			if (isset($inx[$property])) $this->writer->addIndex($table,$inx[$property],$property);
		}
	}



	/**
	 * Process groups. Internal function. Processes different kind of groups for
	 * storage function. Given a list of original beans and a list of current beans,
	 * this function calculates which beans remain in the list (residue), which
	 * have been deleted (are in the trashcan) and which beans have been added
	 * (additions). 
	 *
	 * @param  array $originals originals
	 * @param  array $current   the current beans
	 * @param  array $additions beans that have been added
	 * @param  array $trashcan  beans that have been deleted
	 * @param  array $residue   beans that have been left untouched
	 *
	 * @return array $result 	new relational state
	 */
	private function processGroups( $originals, $current, $additions, $trashcan, $residue ) {
		return array(
			array_merge($additions,array_diff($current,$originals)),
			array_merge($trashcan,array_diff($originals,$current)),
			array_merge($residue,array_intersect($current,$originals))
		);
	}

	
	/**
	 * Figures out the desired type given the cast string ID.
	 * 
	 * @param string $cast cast identifier
	 * 
	 * @return integer $typeno 
	 */
	private function getTypeFromCast($cast) {
		if ($cast=='string') {
			$typeno = $this->writer->scanType('STRING');
		}
		elseif ($cast=='id') {
			$typeno = $this->writer->getTypeForID();
		}
		elseif(isset($this->writer->sqltype_typeno[$cast])) {
			$typeno = $this->writer->sqltype_typeno[$cast];
		}
		else {
			throw new RedBean_Exception('Invalid Cast');
		}
		return $typeno;
	}
	
	/**
	 * Processes an embedded bean. First the bean gets unboxed if possible.
	 * Then, the bean is stored if needed and finally the ID of the bean
	 * will be returned.
	 * 
	 * @param RedBean_OODBBean|Model $v the bean or model
	 * 
	 * @return  integer $id
	 */
	private function prepareEmbeddedBean($v) {
		if (!$v->id || $v->getMeta('tainted')) {
			$this->store($v);
		}
		return $v->id;
	}
	
	/**
	 * Stores a bean in the database. This function takes a
	 * RedBean_OODBBean Bean Object $bean and stores it
	 * in the database. If the database schema is not compatible
	 * with this bean and RedBean runs in fluid mode the schema
	 * will be altered to store the bean correctly.
	 * If the database schema is not compatible with this bean and
	 * RedBean runs in frozen mode it will throw an exception.
	 * This function returns the primary key ID of the inserted
	 * bean.
	 *
	 * @throws RedBean_Exception_Security $exception
	 * 
	 * @param RedBean_OODBBean | RedBean_SimpleModel $bean bean to store
	 *
	 * @return integer $newid resulting ID of the new bean
	 */
	public function store( $bean ) { 
		if ($bean instanceof RedBean_SimpleModel) $bean = $bean->unbox();
		if (!($bean instanceof RedBean_OODBBean)) throw new RedBean_Exception_Security('OODB Store requires a bean, got: '.gettype($bean));
		$processLists = false;
		foreach($bean as $k=>$v) if (is_array($v) || is_object($v)) { $processLists = true; break; }
		if (!$processLists && !$bean->getMeta('tainted')) return $bean->getID();
		$this->signal('update', $bean );
		foreach($bean as $k=>$v) if (is_array($v) || is_object($v)) { $processLists = true; break; }
		if ($processLists) {
			//Define groups
			$sharedAdditions = $sharedTrashcan = $sharedresidue = $sharedItems = array();
			$ownAdditions = $ownTrashcan = $ownresidue = array();
			$tmpCollectionStore = array();
			$embeddedBeans = array();
			foreach($bean as $p=>$v) {
				if ($v instanceof RedBean_SimpleModel) $v = $v->unbox(); 
				if ($v instanceof RedBean_OODBBean) {
					$linkField = $p.'_id';
					$bean->$linkField = $this->prepareEmbeddedBean($v);
					$bean->setMeta('cast.'.$linkField,'id');
					$embeddedBeans[$linkField] = $v;
					$tmpCollectionStore[$p]=$bean->$p;
					$bean->removeProperty($p);
				}
				if (is_array($v)) {
					$originals = $bean->getMeta('sys.shadow.'.$p);
					if (!$originals) $originals = array();
					if (strpos($p,'own')===0) {
						list($ownAdditions,$ownTrashcan,$ownresidue)=$this->processGroups($originals,$v,$ownAdditions,$ownTrashcan,$ownresidue);
						$bean->removeProperty($p);
					}
					elseif (strpos($p,'shared')===0) {
						list($sharedAdditions,$sharedTrashcan,$sharedresidue)=$this->processGroups($originals,$v,$sharedAdditions,$sharedTrashcan,$sharedresidue);
						$bean->removeProperty($p);
					}
					else {}
				}
			}
		}
		$this->storeBean($bean);

		if ($processLists) {
			$this->processEmbeddedBeans($bean,$embeddedBeans);
			$myFieldLink = $bean->getMeta('type').'_id';
			//Handle related beans
			$this->processTrashcan($bean,$ownTrashcan);
			$this->processAdditions($bean,$ownAdditions);
			$this->processResidue($ownresidue);
			foreach($sharedTrashcan as $trash) $this->assocManager->unassociate($trash,$bean);
			$this->processSharedAdditions($bean,$sharedAdditions);
			foreach($sharedresidue as $residue) $this->store($residue);
		}
		$this->signal('after_update',$bean);
		return (int) $bean->id;
	}
	
	/**
	 * Stores a cleaned bean; i.e. only scalar values. This is the core of the store()
	 * method. When all lists and embedded beans (parent objects) have been processed and
	 * removed from the original bean the bean is passed to this method to be stored
	 * in the database.
	 * 
	 * @param RedBean_OODBBean $bean the clean bean 
	 */
	private function storeBean(RedBean_OODBBean $bean) {
		if (!$this->isFrozen) $this->check($bean);
		//what table does it want
		$table = $bean->getMeta('type');
		if ($bean->getMeta('tainted')) {
			//Does table exist? If not, create
			if (!$this->isFrozen && !$this->tableExists($this->writer->safeTable($table,true))) {
				$this->writer->createTable( $table );
				$bean->setMeta('buildreport.flags.created',true);
			}
			if (!$this->isFrozen) {
				$columns = $this->writer->getColumns($table) ;
			}
			//does the table fit?
			$insertvalues = array();
			$insertcolumns = array();
			$updatevalues = array();
			foreach( $bean as $p=>$v ) {
				$origV = $v;
				if ($p!='id') {
					if (!$this->isFrozen) {
						//Not in the chill list?
						if (!in_array($bean->getMeta('type'),$this->chillList)) {
							//Does the user want to specify the type?
							if ($bean->getMeta("cast.$p",-1)!==-1) {
								$cast = $bean->getMeta("cast.$p");
								$typeno = $this->getTypeFromCast($cast);
							}
							else {
								$cast = false;		
								//What kind of property are we dealing with?
								$typeno = $this->writer->scanType($v,true);
								$v = $this->writer->getValue();
							}
							//Is this property represented in the table?
							if (isset($columns[$this->writer->safeColumn($p,true)])) {
								//rescan
								$v = $origV;
								if (!$cast) $typeno = $this->writer->scanType($v,false);
								//yes it is, does it still fit?
								$sqlt = $this->writer->code($columns[$this->writer->safeColumn($p,true)]);
								if ($typeno > $sqlt) {
									//no, we have to widen the database column type
									$this->writer->widenColumn( $table, $p, $typeno );
									$bean->setMeta('buildreport.flags.widen',true);
								}
							}
							else {
								//no it is not
								$this->writer->addColumn($table, $p, $typeno);
								$bean->setMeta('buildreport.flags.addcolumn',true);
								//@todo: move build commands here... more practical
								$this->processBuildCommands($table,$p,$bean);
							}
						}
					}
					//Okay, now we are sure that the property value will fit
					$insertvalues[] = $v;
					$insertcolumns[] = $p;
					$updatevalues[] = array( 'property'=>$p, 'value'=>$v );
				}
			}
			if (!$this->isFrozen && ($uniques = $bean->getMeta('buildcommand.unique'))) {
				foreach($uniques as $unique) $this->writer->addUniqueIndex( $table, $unique );
			}
			$rs = $this->writer->updateRecord( $table, $updatevalues, $bean->id );
			$bean->id = $rs;
			$bean->setMeta('tainted',false);
		}
	}
	
	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles shared addition lists; i.e. the $bean->sharedObject properties.
	 * 
	 * @param RedBean_OODBBean $bean             the bean
	 * @param array            $sharedAdditions  list with shared additions
	 */
	private function processSharedAdditions($bean,$sharedAdditions) {
		foreach($sharedAdditions as $addition) {
			if ($addition instanceof RedBean_OODBBean) {
				$this->assocManager->associate($addition,$bean);
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
	}
	
	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles own lists; i.e. the $bean->ownObject properties.
	 * A residue is a bean in an own-list that stays where it is. This method
	 * checks if there have been any modification to this bean, in that case
	 * the bean is stored once again, otherwise the bean will be left untouched.
	 *  
	 * @param RedBean_OODBBean $bean       the bean
	 * @param array            $ownresidue list 
	 */
	private function processResidue($ownresidue) {
		foreach($ownresidue as $residue) {
			if ($residue->getMeta('tainted')) {
				$this->store($residue);
			}
		}
	}
	
	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles own lists; i.e. the $bean->ownObject properties.
	 * A trash can bean is a bean in an own-list that has been removed 
	 * (when checked with the shadow). This method
	 * checks if the bean is also in the dependency list. If it is the bean will be removed.
	 * If not, the connection between the bean and the owner bean will be broken by
	 * setting the ID to NULL.
	 *  
	 * @param RedBean_OODBBean $bean        the bean
	 * @param array            $ownTrashcan list 
	 */
	private function processTrashcan($bean,$ownTrashcan) {
		$myFieldLink = $bean->getMeta('type').'_id';
		foreach($ownTrashcan as $trash) {
			if (isset($this->dep[$trash->getMeta('type')]) && in_array($bean->getMeta('type'),$this->dep[$trash->getMeta('type')])) {
				$this->trash($trash);
			}
			else {
				$trash->$myFieldLink = null;
				$this->store($trash);
			}
		}
	}
	
	/**
	 * Processes embedded beans.
	 * Each embedded bean will be indexed and foreign keys will
	 * be created if the bean is in the dependency list.
	 * 
	 * @param RedBean_OODBBean $bean          bean
	 * @param array            $embeddedBeans embedded beans
	 */
	private function processEmbeddedBeans($bean, $embeddedBeans) {
		foreach($embeddedBeans as $linkField=>$embeddedBean) {
			if (!$this->isFrozen) {
				$this->writer->addIndex($bean->getMeta('type'),
							'index_foreignkey_'.$embeddedBean->getMeta('type'),
							 $linkField);
				$isDep = $this->isDependentOn($bean->getMeta('type'),$embeddedBean->getMeta('type'));
				$this->writer->addFK($bean->getMeta('type'),$embeddedBean->getMeta('type'),$linkField,'id',$isDep);
			}
		}
		
	}
	
	/**
	 * Part of the store() functionality.
	 * Handles all new additions after the bean has been saved.
	 * Stores addition bean in own-list, extracts the id and
	 * adds a foreign key. Also adds a constraint in case the type is
	 * in the dependent list.
	 * 
	 * @param RedBean_OODBBean $bean         bean
	 * @param array            $ownAdditions list of addition beans in own-list 
	 */
	private function processAdditions($bean,$ownAdditions) {
		$myFieldLink = $bean->getMeta('type').'_id';
		foreach($ownAdditions as $addition) {
			if ($addition instanceof RedBean_OODBBean) {  
				$addition->$myFieldLink = $bean->id;
				$addition->setMeta('cast.'.$myFieldLink,'id');
				$this->store($addition);
				if (!$this->isFrozen) {
					$this->writer->addIndex($addition->getMeta('type'),
						'index_foreignkey_'.$bean->getMeta('type'),
						 $myFieldLink);
					$isDep = $this->isDependentOn($addition->getMeta('type'),$bean->getMeta('type'));
					$this->writer->addFK($addition->getMeta('type'),$bean->getMeta('type'),$myFieldLink,'id',$isDep);
				}
			}
			else {
				throw new RedBean_Exception_Security('Array may only contain RedBean_OODBBeans');
			}
		}
		
	}
	
	/**
	 * Checks whether reference type has been marked as dependent on target type.
	 * This is the result of setting reference type as a key in R::dependencies() and
	 * putting target type in its array. 
	 * 
	 * @param string $refType   reference type
	 * @param string $otherType other type / target type
	 * 
	 * @return boolean 
	 */
	protected function isDependentOn($refType,$otherType) {
		return (boolean) (isset($this->dep[$refType]) && in_array($otherType,$this->dep[$refType]));
	}
	

	/**
	 * Loads a bean from the object database.
	 * It searches for a RedBean_OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the RedBean_OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 * 
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 * 
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 * 
	 * @return RedBean_OODBBean $bean loaded bean
	 */
	public function load($type,$id) {
		$this->signal('before_open',array('type'=>$type,'id'=>$id));
		$bean = $this->dispense( $type );
		if ($this->stash && isset($this->stash[$id])) {
			$row = $this->stash[$id];
		}
		else {
			try {
				$rows = $this->writer->selectRecord($type,array('id'=>array($id)));
			}catch(RedBean_Exception_SQL $e ) {
				if (
				$this->writer->sqlStateIn($e->getSQLState(),
				array(
					RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
					RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)
				) {
					$rows = 0;
					if ($this->isFrozen) throw $e; //only throw if frozen;
				}
			}
			if (!$rows) return $bean; // $this->dispense($type); -- no need...
			$row = array_pop($rows);
		}
		foreach($row as $p=>$v) {
			//populate the bean with the database row
			$bean->$p = $v;
		}
		$this->signal('open',$bean );
		$bean->setMeta('tainted',false);
		return $bean;
	}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified RedBean_OODBBean
	 * Bean Object from the database.
	 * 
	 * @throws RedBean_Exception_Security $exception
	 * 
	 * @param RedBean_OODBBean|RedBean_SimpleModel $bean bean you want to remove from database
	 */
	public function trash( $bean ) {
		if ($bean instanceof RedBean_SimpleModel) $bean = $bean->unbox();
		if (!($bean instanceof RedBean_OODBBean)) throw new RedBean_Exception_Security('OODB Store requires a bean, got: '.gettype($bean));
		$this->signal('delete',$bean);
		foreach($bean as $p=>$v) {
			if ($v instanceof RedBean_OODBBean) {
				$bean->removeProperty($p);
			}
			if (is_array($v)) {
				if (strpos($p,'own')===0) {
					$bean->removeProperty($p);
				}
				elseif (strpos($p,'shared')===0) {
					$bean->removeProperty($p);
				}
			}
		}
		if (!$this->isFrozen) $this->check( $bean );
		try {
			$this->writer->selectRecord($bean->getMeta('type'),
				array('id' => array( $bean->id) ),null,true );
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		$bean->id = 0;
		$this->signal('after_delete', $bean );
	}

	/**
	 * Returns an array of beans. Pass a type and a series of ids and
	 * this method will bring you the correspondig beans.
	 * 
	 * important note: Because this method loads beans using the load()
	 * function (but faster) it will return empty beans with ID 0 for 
	 * every bean that could not be located. The resulting beans will have the
	 * passed IDs as their keys.
	 *
	 * @param string $type type of beans 
	 * @param array  $ids  ids to load
	 *
	 * @return array $beans resulting beans (may include empty ones)
	 */
	public function batch( $type, $ids ) {
		if (!$ids) return array();
		$collection = array();
		try {
			$rows = $this->writer->selectRecord($type,array('id'=>$ids));
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;

			$rows = false;
		}
		$this->stash = array();
		if (!$rows) return array();
		foreach($rows as $row) {
			$this->stash[$row['id']] = $row;
		}
		foreach($ids as $id) {
			$collection[ $id ] = $this->load( $type, $id );
		}
		$this->stash = NULL;
		return $collection;
	}

	/**
	 * This is a convenience method; it converts database rows
	 * (arrays) into beans. Given a type and a set of rows this method
	 * will return an array of beans of the specified type loaded with
	 * the data fields provided by the result set from the database.
	 * 
	 * @param string $type type of beans you would like to have
	 * @param array  $rows rows from the database result
	 * 
	 * @return array $collectionOfBeans collection of beans
	 */
	public function convertToBeans($type, $rows) {
		$collection = array();
		$this->stash = array();
		foreach($rows as $row) {
			$id = $row['id'];
			$this->stash[$id] = $row;
			$collection[ $id ] = $this->load( $type, $id );
		}
		$this->stash = NULL;
		return $collection;
	}

	/**
	 * Returns the number of beans we have in DB of a given type.
	 *
	 * @param string $type type of bean we are looking for
	 *
	 * @return integer $num number of beans found
	 */
	public function count($type) {
		try {
			return (int) $this->writer->count($type);
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		return 0;
	}

	/**
	 * Trash all beans of a given type.
	 *
	 * @param string $type type
	 *
	 * @return boolean $yesNo whether we actually did some work or not..
	 */
	public function wipe($type) {
		try {
			$this->writer->wipe($type);
			return true;
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
			return false;
		}
	}

	/**
	 * Returns an Association Manager for use with OODB.
	 * A simple getter function to obtain a reference to the association manager used for
	 * storage and more.
	 *
	 * @throws Exception
	 * @return RedBean_AssociationManager $assoc Association Manager
	 */
	public function getAssociationManager() {
		if (!isset($this->assocManager)) throw new Exception('No association manager available.');
		return $this->assocManager;
	}

	/**
	 * Sets the association manager instance to be used by this OODB.
	 * A simple setter function to set the association manager to be used for storage and
	 * more.
	 * 
	 * @param RedBean_AssociationManager $assoc sets the association manager to be used
	 * 
	 * @return void
	 */
	public function setAssociationManager(RedBean_AssociationManager $assoc) {
		$this->assocManager = $assoc;
	}
	
	
	public function setDepList($dep) {
		$this->dep = $dep;
	}

}




/**
 * ToolBox
 * Contains most important redbean tools
 * 
 * @file			RedBean/ToolBox.php
 * @description		The ToolBox acts as a resource locator for RedBean but can
 *					be integrated in larger resource locators (nested).
 *					It does not do anything more than just store the three most
 *					important RedBean resources (tools): the database adapter,
 *					the redbean core class (oodb) and the query writer.
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ToolBox {

	/**
	 * Reference to the RedBeanPHP OODB Object Database instance
	 * @var RedBean_OODB
	 */
	protected $oodb;

	/**
	 * Reference to the Query Writer
	 * @var RedBean_QueryWriter
	 */
	protected $writer;

	/**
	 * Reference to the database adapter
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * Constructor.
	 * The Constructor of the ToolBox takes three arguments: a RedBean_OODB $redbean
	 * object database, a RedBean_Adapter $databaseAdapter and a
	 * RedBean_QueryWriter $writer. It stores these objects inside and acts as
	 * a micro service locator. You can pass the toolbox to any object that needs
	 * one of the RedBean core objects to interact with.
	 *
	 * @param RedBean_OODB              $oodb    Object Database
	 * @param RedBean_Adapter_DBAdapter $adapter Adapter
	 * @param RedBean_QueryWriter       $writer  Writer
	 *
	 * return RedBean_ToolBox $toolbox Toolbox
	 */
	public function __construct(RedBean_OODB $oodb,RedBean_Adapter $adapter,RedBean_QueryWriter $writer) {
		$this->oodb = $oodb;
		$this->adapter = $adapter;
		$this->writer = $writer;
		return $this;
	}

	/**
	 * The Toolbox acts as a kind of micro service locator, providing just the
	 * most important objects that make up RedBean. You can pass the toolkit to
	 * any object that needs one of these objects to function properly.
	 * Returns the QueryWriter; normally you do not use this object but other
	 * object might want to use the default RedBean query writer to be
	 * database independent.
	 *
	 * @return RedBean_QueryWriter $writer writer
	 */
	public function getWriter() {
		return $this->writer;
	}

	/**
	 * The Toolbox acts as a kind of micro service locator, providing just the
	 * most important objects that make up RedBean. You can pass the toolkit to
	 * any object that needs one of these objects to function properly.
	 * Retruns the RedBean OODB Core object. The RedBean OODB object is
	 * the ultimate core of Redbean. It provides the means to store and load
	 * beans. Extract this object immediately after invoking a kickstart method.
	 *
	 * @return RedBean_OODB $oodb Object Database
	 */
	public function getRedBean() {
		return $this->oodb;
	}

	/**
	 * The Toolbox acts as a kind of micro service locator, providing just the
	 * most important objects that make up RedBean. You can pass the toolkit to
	 * any object that needs one of these objects to function properly.
	 * Returns the adapter. The Adapter can be used to perform queries
	 * on the database directly.
	 *
	 * @return RedBean_Adapter_DBAdapter $adapter Adapter
	 */
	public function getDatabaseAdapter() {
		return $this->adapter;
	}
}

/**
 * RedBean Association
 * 
 * @file			RedBean/AssociationManager.php
 * @description		Manages simple bean associations.
 *
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_AssociationManager extends RedBean_Observable {
	/**
	 * Contains a reference to the Object Database OODB
	 * @var RedBean_OODB
	 */
	protected $oodb;

	/**
	 * Contains a reference to the Database Adapter
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * Contains a reference to the Query Writer
	 * @var RedBean_QueryWriter
	 */
	protected $writer;


	/**
	 * Constructor
	 *
	 * @param RedBean_ToolBox $tools toolbox
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
		$this->toolbox = $tools;
	}

	/**
	 * Creates a table name based on a types array.
	 * Manages the get the correct name for the linking table for the
	 * types provided.
	 *
	 * @todo find a nice way to decouple this class from QueryWriter?
	 * 
	 * @param array $types 2 types as strings
	 *
	 * @return string $table table
	 */
	public function getTable( $types ) {
		return RedBean_QueryWriter_AQueryWriter::getAssocTableFormat($types);
	}
	/**
	 * Associates two beans with eachother using a many-to-many relation.
	 *
	 * @param RedBean_OODBBean $bean1 bean1
	 * @param RedBean_OODBBean $bean2 bean2
	 */
	public function associate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table = $this->getTable( array($bean1->getMeta('type') , $bean2->getMeta('type')) );
		$bean = $this->oodb->dispense($table);
		return $this->associateBeans( $bean1, $bean2, $bean );
	}

	
	/**
	 * Associates a pair of beans. This method associates two beans, no matter
	 * what types.Accepts a base bean that contains data for the linking record.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 * @param RedBean_OODBBean $bean  base bean
	 *
	 * @return mixed $id either the link ID or null
	 */
	protected function associateBeans(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $bean) {
		$property1 = $bean1->getMeta('type') . '_id';
		$property2 = $bean2->getMeta('type') . '_id';
		if ($property1==$property2) $property2 = $bean2->getMeta('type').'2_id';
		//add a build command for Unique Indexes
		$bean->setMeta('buildcommand.unique' , array(array($property1, $property2)));
		//add a build command for Single Column Index (to improve performance in case unqiue cant be used)
		$indexName1 = 'index_for_'.$bean->getMeta('type').'_'.$property1;
		$indexName2 = 'index_for_'.$bean->getMeta('type').'_'.$property2;
		$bean->setMeta('buildcommand.indexes', array($property1=>$indexName1,$property2=>$indexName2));
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$bean->setMeta("cast.$property1","id");
		$bean->setMeta("cast.$property2","id");
		$bean->$property1 = $bean1->id;
		$bean->$property2 = $bean2->id;
		try {
			$id = $this->oodb->store( $bean );
			//On creation, add constraints....
			if (!$this->oodb->isFrozen() &&
				$bean->getMeta('buildreport.flags.created')){
				$bean->setMeta('buildreport.flags.created',0);
				if (!$this->oodb->isFrozen())
				$this->writer->addConstraint( $bean1, $bean2 );
			}
			return $id;
		}
		catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
			))) throw $e;
		}
	}

	/**
	 * Returns all ids of beans of type $type that are related to $bean. If the
	 * $getLinks parameter is set to boolean TRUE this method will return the ids
	 * of the association beans instead. You can also add additional SQL. This SQL
	 * will be appended to the original query string used by this method. Note that this
	 * method will not return beans, just keys. For a more convenient method see the R-facade
	 * method related(), that is in fact a wrapper for this method that offers a more
	 * convenient solution. If you want to make use of this method, consider the
	 * OODB batch() method to convert the ids to beans.
	 * 
	 * Since 3.2, you can now also pass an array of beans instead just one
	 * bean as the first parameter.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param RedBean_OODBBean|array $bean     reference bean
	 * @param string				 $type     target type
	 * @param bool					 $getLinks whether you are interested in the assoc records
	 * @param bool					 $sql      room for additional SQL
	 *
	 * @return array $ids
	 */
	public function related( $bean, $type, $getLinks=false, $sql=false) {
		if (!is_array($bean) && !($bean instanceof RedBean_OODBBean)) throw new RedBean_Exception_Security('Expected array or RedBean_OODBBean but got:'.gettype($bean));
		$ids = array();
		if (is_array($bean)) {
			$beans = $bean;
			foreach($beans as $b) {
				if (!($b instanceof RedBean_OODBBean)) throw new RedBean_Exception_Security('Expected RedBean_OODBBean in array but got:'.gettype($b));
				$ids[] = $b->id;
			}
			$bean = reset($beans);
		}
		else $ids[] = $bean->id;
		$table = $this->getTable( array($bean->getMeta('type') , $type) );
		if ($type==$bean->getMeta('type')) {
			$type .= '2';
			$cross = 1;
		}
		else $cross=0;
		if (!$getLinks) $targetproperty = $type.'_id'; else $targetproperty='id';

		$property = $bean->getMeta('type').'_id';
		try {
				$sqlFetchKeys = $this->writer->selectRecord(
					  $table,
					  array( $property => $ids ),
					  $sql,
					  false
				);
				$sqlResult = array();
				foreach( $sqlFetchKeys as $row ) {
					if (isset($row[$targetproperty])) {
						$sqlResult[] = $row[$targetproperty];
					}
				}
				if ($cross) {
					$sqlFetchKeys2 = $this->writer->selectRecord(
							  $table,
							  array( $targetproperty => $ids),
							  $sql,
							  false
					);
					foreach( $sqlFetchKeys2 as $row ) {
						if (isset($row[$property])) {
							$sqlResult[] = $row[$property];
						}
					}
				}
			return $sqlResult; //or returns rows in case of $sql != empty
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
			return array();
		}
	}

	/**
	 * Breaks the association between two beans. This method unassociates two beans. If the
	 * method succeeds the beans will no longer form an association. In the database
	 * this means that the association record will be removed. This method uses the
	 * OODB trash() method to remove the association links, thus giving FUSE models the
	 * opportunity to hook-in additional business logic. If the $fast parameter is
	 * set to boolean TRUE this method will remove the beans without their consent,
	 * bypassing FUSE. This can be used to improve performance.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 * @param boolean          $fast  If TRUE, removes the entries by query without FUSE
	 */
	public function unassociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $fast=null) {
		$this->oodb->store($bean1);
		$this->oodb->store($bean2);
		$table = $this->getTable( array($bean1->getMeta('type') , $bean2->getMeta('type')) );
		$type = $bean1->getMeta('type');
		if ($type==$bean2->getMeta('type')) {
			$type .= '2';
			$cross = 1;
		}
		else $cross = 0;
		$property1 = $type.'_id';
		$property2 = $bean2->getMeta('type').'_id';
		$value1 = (int) $bean1->id;
		$value2 = (int) $bean2->id;
		try {
			$rows = $this->writer->selectRecord($table,array(
				$property1 => array($value1), $property2=>array($value2)),null,$fast
			);
			if ($cross) {
				$rows2 = $this->writer->selectRecord($table,array(
				$property2 => array($value1), $property1=>array($value2)),null,$fast
				);
				if ($fast) return;
				$rows = array_merge($rows,$rows2);
			}
			if ($fast) return;
			$beans = $this->oodb->convertToBeans($table,$rows);
			foreach($beans as $link) {
				$this->oodb->trash($link);
			}
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
		return;
	}

	/**
	 * Removes all relations for a bean. This method breaks every connection between
	 * a certain bean $bean and every other bean of type $type. Warning: this method
	 * is really fast because it uses a direct SQL query however it does not inform the
	 * models about this. If you want to notify FUSE models about deletion use a foreach-loop
	 * with unassociate() instead. (that might be slower though)
	 *
	 * @param RedBean_OODBBean $bean reference bean
	 * @param string           $type type of beans that need to be unassociated
	 *
	 * @return void
	 */
	public function clearRelations(RedBean_OODBBean $bean, $type) {
		$this->oodb->store($bean);
		$table = $this->getTable( array($bean->getMeta('type') , $type) );
		if ($type==$bean->getMeta('type')) {
			$property2 = $type.'2_id';
			$cross = 1;
		}
		else $cross = 0;
		$property = $bean->getMeta('type').'_id';
		try {
			$this->writer->selectRecord( $table, array($property=>array($bean->id)),null,true);
			if ($cross) {
				$this->writer->selectRecord( $table, array($property2=>array($bean->id)),null,true);
			}
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
		}
	}

	/**
	 * Given two beans this function returns TRUE if they are associated using a
	 * many-to-many association, FALSE otherwise.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param RedBean_OODBBean $bean1 bean
	 * @param RedBean_OODBBean $bean2 bean
	 *
	 * @return bool $related whether they are associated N-M
	 */
	public function areRelated(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		if (!$bean1->getID() || !$bean2->getID()) return false;
		$table = $this->getTable( array($bean1->getMeta('type') , $bean2->getMeta('type')) );
		$type = $bean1->getMeta('type');
		if ($type==$bean2->getMeta('type')) {
			$type .= '2';
			$cross = 1;
		}
		else $cross = 0;
		$property1 = $type.'_id';
		$property2 = $bean2->getMeta('type').'_id';
		$value1 = (int) $bean1->id;
		$value2 = (int) $bean2->id;
		try {
			$rows = $this->writer->selectRecord($table,array(
				$property1 => array($value1), $property2=>array($value2)),null
			);
			if ($cross) {
				$rows2 = $this->writer->selectRecord($table,array(
				$property2 => array($value1), $property1=>array($value2)),null
				);
				$rows = array_merge($rows,$rows2);
			}
		}catch(RedBean_Exception_SQL $e) {
			if (!$this->writer->sqlStateIn($e->getSQLState(),
			array(
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
			)) throw $e;
			return false;
		}
		return (count($rows)>0);
	}
}

/**
 * RedBean Extended Association
 * 
 * @file			RedBean/ExtAssociationManager.php
 * @description		Manages complex bean associations.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ExtAssociationManager extends RedBean_AssociationManager {

	/**
	 * Associates two beans with eachother. This method connects two beans with eachother, just
	 * like the other associate() method in the Association Manager. The difference is however
	 * that this method accepts a base bean, this bean will be used as the basis of the
	 * association record in the link table. You can thus add additional properties and
	 * even foreign keys.
	 *
	 * @param RedBean_OODBBean $bean1 bean 1
	 * @param RedBean_OODBBean $bean2 bean 2
	 * @param RedBean_OODBBean $bbean base bean for association record
	 *
	 * @return void
	 */
	public function extAssociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, RedBean_OODBBean $baseBean ) {
		$table = $this->getTable( array($bean1->getMeta('type') , $bean2->getMeta('type')) );
		$baseBean->setMeta('type', $table );
		return $this->associateBeans( $bean1, $bean2, $baseBean );
	}
}

/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you.
 * 
 * @file 			RedBean/Setup.php
 * @description		Helper class to quickly setup RedBean for you
 * 
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Setup {

	/**
	 * This method checks the DSN string. If the DSN string contains a
	 * database name that is not supported by RedBean yet then it will
	 * throw an exception RedBean_Exception_NotImplemented. In any other
	 * case this method will just return boolean TRUE.
	 * @throws RedBean_Exception_NotImplemented
	 * @param string $dsn
	 * @return boolean $true
	 */
	private static function checkDSN($dsn) {
		$dsn = trim($dsn);
		$dsn = strtolower($dsn);
		if (
		strpos($dsn, 'mysql:')!==0
				  && strpos($dsn,'sqlite:')!==0
				  && strpos($dsn,'pgsql:')!==0
				  && strpos($dsn,'cubrid:')!==0
		) {
			trigger_error('Unsupported DSN');
		}
		else {
			return true;
		}
	}


	/**
	 * Generic Kickstart method.
	 * This is the generic kickstarter. It will prepare a database connection
	 * using the $dsn, the $username and the $password you provide.
	 * If $frozen is boolean TRUE it will start RedBean in frozen mode, meaning
	 * that the database cannot be altered. If RedBean is started in fluid mode
	 * it will adjust the schema of the database if it detects an
	 * incompatible bean.
	 * This method returns a RedBean_Toolbox $toolbox filled with a
	 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
	 * RedBean_OODB; the object database. To start storing beans in the database
	 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
	 * to the RedBean object.
	 * Optionally instead of using $dsn you may use an existing PDO connection.
	 * Example: RedBean_Setup::kickstart($existingConnection, true);
	 *
	 * @param  string|PDO $dsn      Database Connection String (or PDO instance)
	 * @param  string     $username Username for database
	 * @param  string     $password Password for database
	 * @param  boolean    $frozen   Start in frozen mode?
	 *
	 * @return RedBean_ToolBox $toolbox
	 */
	public static function kickstart($dsn,$username=NULL,$password=NULL,$frozen=false ) {
		if ($dsn instanceof PDO) {
			$pdo = new RedBean_Driver_PDO($dsn);
			$dsn = $pdo->getDatabaseType() ;
		}
		else {
			self::checkDSN($dsn);
			$pdo = new RedBean_Driver_PDO($dsn,$username,$password);
		}
		$adapter = new RedBean_Adapter_DBAdapter($pdo);
		if (strpos($dsn,'pgsql')===0) {
			$writer = new RedBean_QueryWriter_PostgreSQL($adapter);
		}
		else if (strpos($dsn,'sqlite')===0) {
			$writer = new RedBean_QueryWriter_SQLiteT($adapter);
		}
		else if (strpos($dsn,'cubrid')===0) {
			$writer = new RedBean_QueryWriter_CUBRID($adapter);
		}
		else {
			$writer = new RedBean_QueryWriter_MySQL($adapter);
		}
		$redbean = new RedBean_OODB($writer);
		if ($frozen) $redbean->freeze(true);
		$toolbox = new RedBean_ToolBox($redbean,$adapter,$writer);
		//deliver everything back in a neat toolbox
		return $toolbox;
	}
}

/**
 * RedBean interface for Model Formatting - Part of FUSE
 * 
 * @file			RedBean/ModelFormatter.php
 * @description 	RedBean IModelFormatter
 *
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_IModelFormatter {

	/**
	 * ModelHelper will call this method of the class
	 * you provide to discover the model
	 *
	 * @param string $model
	 *
	 * @return string $formattedModel
	 */
	public function formatModel( $model );


}


/**
 * RedBean interface for Logging
 * 
 * @name    RedBean ILogger
 * @file    RedBean/ILogger.php
 * @author    Gabor de Mooij
 * @license   BSD
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_ILogger {

  /**
   * Redbean will call this method to log your data
   *
   * @param ...
   */
  public function log();


}


/**
 * RedBean class for Logging
 * 
 * @name    RedBean ILogger
 * @file    RedBean/ILogger.php
 * @author    Gabor de Mooij
 * @license   BSD
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Logger implements RedBean_ILogger {

  /**
   * Default logger method logging to STDOUT
   *
   * @param ...
   */
  public function log() {
    if (func_num_args() > 0) {
      foreach (func_get_args() as $argument) {
        if (is_array($argument)) echo print_r($argument,true); else echo $argument;
		echo "<br>\n";
      }
    }
  }
}




/**
 * RedBean Bean Helper Interface
 * 
 * @file			RedBean/IBeanHelper.php
 * @description		Interface for Bean Helper.
 *					A little bolt that glues the whole machinery together.
 * 		
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
interface RedBean_IBeanHelper {

	/**
	 * @abstract
	 * @return RedBean_Toolbox $toolbox toolbox
	 */
	public function getToolbox();
	
	public function getModelForBean(RedBean_OODBBean $bean);
	
}


/**
 * RedBean Bean Helper
 * @file			RedBean/BeanHelperFacade.php
 * @description		Finds the toolbox for the bean.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_BeanHelperFacade implements RedBean_IBeanHelper {

	/**
	 * Returns a reference to the toolbox. This method returns a toolbox
	 * for beans that need to use toolbox functions. Since beans can contain
	 * lists they need a toolbox to lazy-load their relationships.
	 *  
	 * @return RedBean_ToolBox $toolbox toolbox containing all kinds of goodies
	 */
	public function getToolbox() {
		return RedBean_Facade::$toolbox;
	}
	
	/**
	 * Fuse connector.
	 * Gets the model for a bean $bean.
	 * Allows you to implement your own way to find the
	 * right model for a bean and to do dependency injection
	 * etc.
	 *
	 * @param RedBean_OODBBean $bean bean
	 *  
	 * @return type 
	 */
	public function getModelForBean(RedBean_OODBBean $bean) {
		$modelName = RedBean_ModelHelper::getModelName( $bean->getMeta('type'), $bean );
		if (!class_exists($modelName)) return null;
		$obj = RedBean_ModelHelper::factory($modelName);
		$obj->loadBean($bean);
		return $obj;
	}
	
}


/**
 * SimpleModel
 * 
 * @file 		RedBean/SimpleModel.php
 * @description	Part of FUSE
 * @author      Gabor de Mooij and the RedBeanPHP Team
 * @license		BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_SimpleModel {

	/**
	 * Contains the inner bean.
	 * @var RedBean_OODBBean
	 */
	protected $bean;

	/**
	 * Used by FUSE: the ModelHelper class to connect a bean to a model.
	 * This method loads a bean in the model.
	 *
	 * @param RedBean_OODBBean $bean bean
	 */
	public function loadBean( RedBean_OODBBean $bean ) {
		$this->bean = $bean;
	}

	/**
	 * Magic Getter to make the bean properties available from
	 * the $this-scope.
	 *
	 * @param string $prop property
	 *
	 * @return mixed $propertyValue value
	 */
	public function __get( $prop ) {
		return $this->bean->$prop;
	}

	/**
	 * Magic Setter
	 *
	 * @param string $prop  property
	 * @param mixed  $value value
	 */
	public function __set( $prop, $value ) {
		$this->bean->$prop = $value;
	}

	/**
	 * Isset implementation
	 *
	 * @param  string $key key
	 *
	 * @return
	 */
	public function __isset($key) {
		return (isset($this->bean->$key));
	}
	
	/**
	 * Box the bean using the current model.
	 * 
	 * @return RedBean_SimpleModel $box a bean in a box
	 */
	public function box() {
		return $this;
	}
	
	/**
	 * Unbox the bean from the model.
	 * 
	 * @return RedBean_OODBBean $bean bean 
	 */
	public function unbox(){
		return $this->bean;
	}

}

/**
 * RedBean Model Helper
 * 
 * @file			RedBean/ModelHelper.php
 * @description		Connects beans to models, in essence 
 *					this is the core of so-called FUSE.
 * 		
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_ModelHelper implements RedBean_Observer {

	/**
	 * Holds a model formatter
	 * @var RedBean_IModelFormatter
	 */
	private static $modelFormatter;
	
	
	/**
	 * Holds a dependency injector
	 * @var type 
	 */
	private static $dependencyInjector;

	/**
	 * Connects OODB to a model if a model exists for that
	 * type of bean. This connector is used in the facade.
	 *
	 * @param string $eventName
	 * @param RedBean_OODBBean $bean
	 */
	public function onEvent( $eventName, $bean ) {
		$bean->$eventName();
	}


	/**
	 * Given a model ID (model identifier) this method returns the
	 * full model name.
	 *
	 * @param string $model
	 * @param RedBean_OODBBean $bean
	 * 
	 * @return string $fullname
	 */
	public static function getModelName( $model, $bean = null ) {
		if (self::$modelFormatter){
			return self::$modelFormatter->formatModel($model,$bean);
		}
		else {
			return 'Model_'.ucfirst($model);
		}
	}

	/**
	 * Sets the model formatter to be used to discover a model
	 * for Fuse.
	 *
	 * @param string $modelFormatter
	 */
	public static function setModelFormatter( $modelFormatter ) {
		self::$modelFormatter = $modelFormatter;
	}
	
	
	/**
	 * Obtains a new instance of $modelClassName, using a dependency injection
	 * container if possible.
	 * 
	 * @param string $modelClassName name of the model
	 */
	public static function factory( $modelClassName ) {
		if (self::$dependencyInjector) {
			return self::$dependencyInjector->getInstance($modelClassName);
		}
		return new $modelClassName();
	}

	/**
	 * Sets the dependency injector to be used.
	 * 
	 * @param RedBean_DependencyInjector $di injecto to be used
	 */
	public static function setDependencyInjector( RedBean_DependencyInjector $di ) {
		self::$dependencyInjector = $di;
	}
	
	/**
	 * Stops the dependency injector from resolving dependencies. Removes the
	 * reference to the dependency injector.
	 */
	public static function clearDependencyInjector() {
		self::$dependencyInjector = null;
	}
	
}

/**
 * RedBean SQL Helper
 *
 * @file				RedBean/SQLHelper.php
 * @description			Allows you to mix PHP and SQL as if they were
 * 						a unified language
 *					
 *						Simplest case:
 *
 *						$r->now(); //returns SQL time
 *
 *
 *						Another Example:
 *
 *						$f->begin()
 * 						->select('*')
 * 						->from('island')->where('id = ? ')->put(1)->get();
 *
 *						Another example:
 *			
 *						$f->begin()->show('tables')->get('col');
 *
 *	
 * @author				Gabor de Mooij and the RedBeanPHP community
 * @license				BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
 class RedBean_SQLHelper {

	/**
	 * Holds the database adapter for executing SQL queries.
	 * @var RedBean_Adapter 
	 */
	protected $adapter;

	/**
	 * Holds current mode
	 * @var boolean
	 */
	protected $capture = false;

	/**
	 * Holds SQL until now
	 * @var string
	 */
	protected $sql = '';
	
	/**
	 * Holds list of parameters for SQL Query
	 * @var array
	 */
	protected $params = array();

	/**
	 * Constructor
	 * 
	 * @param RedBean_DBAdapter $adapter database adapter for querying
	 */
	public function __construct(RedBean_Adapter $adapter) {
		$this->adapter = $adapter;
	}

	/**
	 * Magic method to construct SQL query
	 * 
	 * @param string $funcName name of the next SQL statement/keyword
	 * @param array  $args     list of statements to be seperated by commas
	 * 
	 * @return mixed $result   either self or result depending on mode 
	 */
	public function __call($funcName,$args=array()) {
		$funcName = str_replace('_',' ',$funcName);
		if ($this->capture) {
			$this->sql .= ' '.$funcName . ' '.implode(',', $args);
			return $this;
		}
		else {
			return $this->adapter->getCell('SELECT '.$funcName.'('.implode(',',$args).')');	
		}	
	}

	/**
	 * Begins SQL query
	 * 
	 * @return RedBean_SQLHelper $this chainable
	 */
	public function begin() {
		$this->capture = true;
		return $this;
	}
	
	/**
	 * Adds a value to the parameter list
	 * 
	 * @param mixed $param parameter to be added
	 * 
	 * @return RedBean_SQLHelper $this chainable
	 */
	public function put($param) {
		$this->params[] = $param;
		return $this;
	}
	
	/**
	 * Executes query and returns result
	 * 
	 * @return mixed $result
	 */
	public function get($what='') {
		$what = 'get'.ucfirst($what);
		$rs = $this->adapter->$what($this->sql,$this->params);
		$this->clear();
		return $rs;
	}
	
	/**
	 * Clears the parameter list as well as the SQL query string.
	 * 
	 * @return RedBean_SQLHelper $this chainable
	 */
	public function clear() {
		$this->sql = '';
		$this->params = array();
		$this->capture = false; //turn off capture mode (issue #142)
		return $this;
	}
	
	/**
	 * To explicitly add a piece of SQL.
	 * 
	 * @param string $sql sql
	 * 
	 * @return RedBean_SQLHelper 
	 */
	public function addSQL($sql) {
		if ($this->capture) {
			$this->sql .= ' '.$sql . ' ';
			return $this;
		}
	}
	
	
	/**
	 * Returns query parts.
	 * 
	 * @return array $queryParts query parts. 
	 */
	public function getQuery() {
		$list = array($this->sql,$this->params);
		$this->clear();
		return $list;
	}

	/**
	 * Writes a '(' to the sql query.
	 */
	public function open() {
		if ($this->capture) {
			$this->sql .= ' ( ';
			return $this;
		}
	}
	
	/**
	 * Writes a ')' to the sql query.
	 */
	public function close() {
		if ($this->capture) {
			$this->sql .= ' ) ';
			return $this;
		}
	}
	
}

/**
 * RedBean Tag Manager
 * 
 * @file			RedBean/TagManager.php
 * @description 	RedBean Tag Manager
 * 
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * Provides methods to tag beans and perform tag-based searches in the
 * bean database.
 * 
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_TagManager {
	
	/**
	 * The Tag Manager requires a toolbox
	 * @var RedBean_Toolbox 
	 */
	protected $toolbox;
	
	/**
	 * Association Manager to manage tag-bean relations
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;
	
	/**
	 * RedBeanPHP OODB instance
	 * @var RedBean_OODBBean 
	 */
	protected $redbean;
	
	
	/**
	 * Constructor,
	 * creates a new instance of TagManager.
	 * @param RedBean_Toolbox $toolbox 
	 */
	public function __construct( RedBean_Toolbox $toolbox ) {
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
		$this->associationManager = $this->redbean->getAssociationManager();
	}
	
	/**
	 * Finds a tag bean by it's title.
	 * 
	 * @param string $title title
	 * 
	 * @return RedBean_OODBBean $bean | null
	 */
	public function findTagByTitle($title) {
		$beans = $this->redbean->find('tag',array('title'=>array($title)));
		if ($beans) {
			return reset($beans);
		}
		return null;
	}
	
	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tests whether a bean has been associated with one ore more
	 * of the listed tags. If the third parameter is TRUE this method
	 * will return TRUE only if all tags that have been specified are indeed
	 * associated with the given bean, otherwise FALSE.
	 * If the third parameter is FALSE this
	 * method will return TRUE if one of the tags matches, FALSE if none
	 * match.
	 *
	 * @param  RedBean_OODBBean $bean bean to check for tags
	 * @param  array            $tags list of tags
	 * @param  boolean          $all  whether they must all match or just some
	 *
	 * @return boolean $didMatch whether the bean has been assoc. with the tags
	 */
	public function hasTag($bean, $tags, $all=false) {
		$foundtags = $this->tag($bean);
		if (is_string($foundtags)) $foundtags = explode(",",$tags);
		$same = array_intersect($tags,$foundtags);
		if ($all) {
			return (implode(",",$same)===implode(",",$tags));
		}
		return (bool) (count($same)>0);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Removes all sepcified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * @param  RedBean_OODBBean $bean    tagged bean
	 * @param  array            $tagList list of tags (names)
	 *
	 * @return void
	 */
	public function untag($bean,$tagList) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		foreach($tags as $tag) {
			if ($t = $this->findTagByTitle($tag)) {
				$this->associationManager->unassociate( $bean, $t );
			}
		}
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is null or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean
	 * @param mixed				$tagList tags
	 *
	 * @return string $commaSepListTags
	 */
	public function tag( RedBean_OODBBean $bean, $tagList = null ) {
		if (is_null($tagList)) {
			$tags = array();
			$keys = $this->associationManager->related($bean, 'tag'); 
			if ($keys) {
				$tags = $this->redbean->batch('tag',$keys);
			}
			$foundTags = array();
			foreach($tags as $tag) {
				$foundTags[] = $tag->title;
			}
			return $foundTags;
		}
		$this->associationManager->clearRelations( $bean, 'tag' );
		$this->addTags( $bean, $tagList );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean  $bean    bean
	 * @param array				$tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public function addTags( RedBean_OODBBean $bean, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		if ($tagList===false) return;
		foreach($tags as $tag) {
			if (!$t = $this->findTagByTitle($tag)) {
				$t = $this->redbean->dispense('tag');
				$t->title = $tag;
				$this->redbean->store($t);
			}
			$this->associationManager->associate( $bean, $t );
		}
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param  $beanType type of bean you are looking for
	 * @param  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function tagged( $beanType, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		$collection = array();
		$tags = $this->redbean->find('tag',array('title'=>$tags));
		if (count($tags)>0) {
			$collectionKeys = $this->associationManager->related($tags,$beanType);
			if ($collectionKeys) {
				$collection = $this->redbean->batch($beanType,$collectionKeys);
			}
		}
		return $collection;
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * @param  $beanType type of bean you are looking for
	 * @param  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function taggedAll( $beanType, $tagList ) {
		if ($tagList!==false && !is_array($tagList)) $tags = explode( ",", (string)$tagList); else $tags=$tagList;
		$beans = array();
		foreach($tags as $tag) {
			$beans = $this->tagged($beanType,$tag);
			if (isset($oldBeans)) $beans = array_intersect_assoc($beans,$oldBeans);
			$oldBeans = $beans;
		}
		return $beans;
	}

}

/**
 * RedBean Facade
 * @file			RedBean/Facade.php
 * @description		Convenience class for RedBeanPHP.
 *					This class hides the object landscape of
 *					RedBeanPHP behind a single letter class providing
 *					almost all functionality with simple static calls.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_Facade {

	/**
	 * Collection of toolboxes
	 * @var array
	 */
	public static $toolboxes = array();
	/**
	 *
	 * Constains an instance of the RedBean Toolbox
	 * @var RedBean_ToolBox
	 *
	 */
	public static $toolbox;

	/**
	 * Constains an instance of RedBean OODB
	 * @var RedBean_OODB
	 */
	public static $redbean;

	/**
	 * Contains an instance of the Query Writer
	 * @var RedBean_QueryWriter
	 */
	public static $writer;

	/**
	 * Contains an instance of the Database
	 * Adapter.
	 * @var RedBean_DBAdapter
	 */
	public static $adapter;


	/**
	 * Contains an instance of the Association Manager
	 * @var RedBean_AssociationManager
	 */
	public static $associationManager;


	/**
	 * Contains an instance of the Extended Association Manager
	 * @var RedBean_ExtAssociationManager
	 */
	public static $extAssocManager;


	/**
	 * Holds an instance of Bean Exporter
	 * @var RedBean_Plugin_BeanExport
	 */
	public static $exporter;
	
	/**
	 * Holds the tag manager
	 * @var RedBean_TagManager
	 */
	public static $tagManager;

	/**
	 * Holds the Key of the current database.
	 * @var string
	 */
	public static $currentDB = '';

	/**
	 * Holds reference to SQL Helper
	 */
	public static $f;


	/**
	 * Get version
	 * @return string
	 */
	public static function getVersion() {
		return '3.2';
	}

	/**
	 * Kickstarts redbean for you. This method should be called before you start using
	 * RedBean. The Setup() method can be called without any arguments, in this case it will
	 * try to create a SQLite database in /tmp called red.db (this only works on UNIX-like systems).
	 *
	 * @param string $dsn      Database connection string
	 * @param string $username Username for database
	 * @param string $password Password for database
	 *
	 * @return void
	 */
	public static function setup( $dsn=NULL, $username=NULL, $password=NULL ) {
		if (function_exists('sys_get_temp_dir')) $tmp = sys_get_temp_dir(); else $tmp = 'tmp';
		if (is_null($dsn)) $dsn = 'sqlite:/'.$tmp.'/red.db';
		self::addDatabase('default',$dsn,$username,$password);
		self::selectDatabase('default');
		return self::$toolbox;
	}


	/**
	 * Adds a database to the facade, afterwards you can select the database using
	 * selectDatabase($key).
	 *
	 * @param string      $key    ID for the database
	 * @param string      $dsn    DSN for the database
	 * @param string      $user   User for connection
	 * @param null|string $pass   Password for connection
	 * @param bool        $frozen Whether this database is frozen or not
	 *
	 * @return void
	 */
	public static function addDatabase( $key, $dsn, $user=null, $pass=null, $frozen=false ) {
		self::$toolboxes[$key] = RedBean_Setup::kickstart($dsn,$user,$pass,$frozen);
	}


	/**
	 * Selects a different database for the Facade to work with.
	 *
	 * @param  string $key Key of the database to select
	 * @return int 1
	 */
	public static function selectDatabase($key) {
		if (self::$currentDB===$key) return false;
		self::configureFacadeWithToolbox(self::$toolboxes[$key]);
		self::$currentDB = $key;
		return true;
	}


	/**
	 * Toggles DEBUG mode.
	 * In Debug mode all SQL that happens under the hood will
	 * be printed to the screen or logged by provided logger.
	 *
	 * @param boolean $tf
	 * @param RedBean_ILogger $logger
	 */
	public static function debug( $tf = true, $logger = NULL ) {
		if (!$logger) $logger = new RedBean_Logger;
		self::$adapter->getDatabase()->setDebugMode( $tf, $logger );
	}

	/**
	 * Stores a RedBean OODB Bean and returns the ID.
	 *
	 * @param  RedBean_OODBBean|RedBean_SimpleModel $bean bean
	 *
	 * @return integer $id id
	 */
	public static function store( $bean ) {
		return self::$redbean->store( $bean );
	}


	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 *
	 * You can also pass an array containing a selection of frozen types.
	 * Let's call this chilly mode, it's just like fluid mode except that
	 * certain types (i.e. tables) aren't touched.
	 *
	 * @param boolean|array $trueFalse
	 */
	public static function freeze( $tf = true ) {
		self::$redbean->freeze( $tf );
	}


	/**
	 * Loads the bean with the given type and id and returns it.
	 *
	 * @param string  $type type
	 * @param integer $id   id of the bean you want to load
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function load( $type, $id ) {
		return self::$redbean->load( $type, $id );
	}

	/**
	 * Deletes the specified bean.
	 *
	 * @param RedBean_OODBBean|RedBean_SimpleModel $bean bean to be deleted
	 *
	 * @return mixed
	 */
	public static function trash( $bean ) {
		return self::$redbean->trash( $bean );
	}

	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 *
	 * @param string $type type
	 *
	 *
	 */
	public static function dispense( $type, $num = 1 ) {
		if ($num==1) {
			return self::$redbean->dispense( $type );
		}
		else {
			$beans = array();
			for($v=0; $v<$num; $v++) $beans[] = self::$redbean->dispense( $type );
			return $beans;
		}
	}

	/**
	 * Convience method. Tries to find beans of a certain type,
	 * if no beans are found, it dispenses a bean of that type.
	 *
	 * @param  string $type   type of bean you are looking for
	 * @param  string $sql    SQL code for finding the bean
	 * @param  array  $values parameters to bind to SQL
	 *
	 * @return array $beans Contains RedBean_OODBBean instances
	 */
	public static function findOrDispense( $type, $sql, $values ) {
		$foundBeans = self::find($type,$sql,$values);
		if (count($foundBeans)==0) return array(self::dispense($type)); else return $foundBeans;
	}

	/**
	 * Associates two Beans. This method will associate two beans with eachother.
	 * You can then get one of the beans by using the related() function and
	 * providing the other bean. You can also provide a base bean in the extra
	 * parameter. This base bean allows you to add extra information to the association
	 * record. Note that this is for advanced use only and the information will not
	 * be added to one of the beans, just to the association record.
	 * It's also possible to provide an array or JSON string as base bean. If you
	 * pass a scalar this function will interpret the base bean as having one
	 * property called 'extra' with the value of the scalar.
	 *
	 * @param RedBean_OODBBean $bean1 bean that will be part of the association
	 * @param RedBean_OODBBean $bean2 bean that will be part of the association
	 * @param mixed $extra            bean, scalar, array or JSON providing extra data.
	 *
	 * @return mixed
	 */
	public static function associate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $extra = null ) {
		//No extra? Just associate like always (default)
		if (!$extra) {
			return self::$associationManager->associate( $bean1, $bean2 );
		}
		else{
			if (!is_array($extra)) {
				$info = json_decode($extra,true);
				if (!$info) $info = array('extra'=>$extra);
			}
			else {
				$info = $extra;
			}
			$bean = RedBean_Facade::dispense('typeLess');
			$bean->import($info);
			return self::$extAssocManager->extAssociate($bean1, $bean2, $bean);
		}

	}


	/**
	 * Breaks the association between two beans.
	 * This functions breaks the association between a pair of beans. After
	 * calling this functions the beans will no longer be associated with
	 * eachother. Calling related() with either one of the beans will no longer
	 * return the other bean.
	 *
	 * @param RedBean_OODBBean $bean1 bean
	 * @param RedBean_OODBBean $bean2 bean
	 *
	 * @return mixed
	 */
	public static function unassociate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 , $fast=false) {
		return self::$associationManager->unassociate( $bean1, $bean2, $fast );
	}

	/**
	 * Returns all the beans associated with $bean.
	 * This method will return an array containing all the beans that have
	 * been associated once with the associate() function and are still
	 * associated with the bean specified. The type parameter indicates the
	 * type of beans you are looking for. You can also pass some extra SQL and
	 * values for that SQL to filter your results after fetching the
	 * related beans.
	 *
	 * Dont try to make use of subqueries, a subquery using IN() seems to
	 * be slower than two queries!
	 *
	 * Since 3.2, you can now also pass an array of beans instead just one
	 * bean as the first parameter.
	 *
	 * @param RedBean_OODBBean|array $bean the bean you have
	 * @param string				 $type the type of beans you want
	 * @param string				 $sql  SQL snippet for extra filtering
	 * @param array					 $val  values to be inserted in SQL slots
	 *
	 * @return array $beans	beans yielded by your query.
	 */
	public static function related( $bean, $type, $sql=null, $values=array()) {
		$keys = self::$associationManager->related( $bean, $type );
		if (count($keys)==0) return array();
		if (!$sql) return self::batch($type, $keys);
		$rows = self::$writer->selectRecord( $type, array('id'=>$keys),array($sql,$values),false );
		return self::$redbean->convertToBeans($type,$rows);
	}

	/**
	* Returns only single associated bean.
	*
	* @param RedBean_OODBBean $bean bean provided
	* @param string $type type of bean you are searching for
	* @param string $sql SQL for extra filtering
	* @param array $values values to be inserted in SQL slots
	*
	*
	* @return RedBean_OODBBean $bean
	*/
	public static function relatedOne( RedBean_OODBBean $bean, $type, $sql=null, $values=array() ) {
		$beans = self::related($bean, $type, $sql, $values);
		if (count($beans)==0) return null;
		return reset( $beans );
	}

	/**
	 * Checks whether a pair of beans is related N-M. This function does not
	 * check whether the beans are related in N:1 way.
	 *
	 * @param RedBean_OODBBean $bean1 first bean
	 * @param RedBean_OODBBean $bean2 second bean
	 *
	 * @return bool $yesNo whether they are related
	 */
	public static function areRelated( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		return self::$associationManager->areRelated($bean1,$bean2);
	}


	/**
	 * The opposite of related(). Returns all the beans that are not
	 * associated with the bean provided.
	 *
	 * @param RedBean_OODBBean $bean   bean provided
	 * @param string           $type   type of bean you are searching for
	 * @param string           $sql    SQL for extra filtering
	 * @param array            $values values to be inserted in SQL slots
	 *
	 * @return array $beans beans
	 */
	public static function unrelated(RedBean_OODBBean $bean, $type, $sql=null, $values=array()) {
		$keys = self::$associationManager->related( $bean, $type );
		$rows = self::$writer->selectRecord( $type, array('id'=>$keys), array($sql,$values), false, true );
		return self::$redbean->convertToBeans($type,$rows);

	}



	/**
	 * Clears all associated beans.
	 * Breaks all many-to-many associations of a bean and a specified type.
	 *
	 * @param RedBean_OODBBean $bean bean you wish to clear many-to-many relations for
	 * @param string           $type type of bean you wish to break associatons with
	 *
	 * @return void
	 */
	public static function clearRelations( RedBean_OODBBean $bean, $type ) {
		self::$associationManager->clearRelations( $bean, $type );
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return array $beans  beans
	 */
	public static function find( $type, $sql=null, $values=array() ) {
		if ($sql instanceof RedBean_SQLHelper) list($sql,$values) = $sql->getQuery();
		if (!is_array($values)) throw new InvalidArgumentException('Expected array, ' . gettype($values) . ' given.');
		return self::$redbean->find($type,array(),array($sql,$values));
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * The findAll() method differs from the find() method in that it does
	 * not assume a WHERE-clause, so this is valid:
	 *
	 * R::findAll('person',' ORDER BY name DESC ');
	 *
	 * Your SQL does not have to start with a valid WHERE-clause condition.
	 *
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return array $beans  beans
	 */
	public static function findAll( $type, $sql=null, $values=array() ) {
		if (!is_array($values)) throw new InvalidArgumentException('Expected array, ' . gettype($values) . ' given.');
		return self::$redbean->find($type,array(),array($sql,$values),true);
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * The variation also exports the beans (i.e. it returns arrays).
	 *
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return array $arrays arrays
	 */
	public static function findAndExport($type, $sql=null, $values=array()) {
		$items = self::find( $type, $sql, $values );
		$arr = array();
		foreach($items as $key=>$item) {
			$arr[$key]=$item->export();
		}
		return $arr;
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * This variation returns the first bean only.
	 *
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function findOne( $type, $sql=null, $values=array()) {
		$items = self::find($type,$sql,$values);
		$found = reset($items);
		if (!$found) return null;
		return $found;
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 * This variation returns the last bean only.
	 *
	 * @param string $type   type   the type of bean you are looking for
	 * @param string $sql    sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $values values array of values to be bound to parameters in query
	 *
	 * @return RedBean_OODBBean $bean
	 */
	public static function findLast( $type, $sql=null, $values=array() ) {
		$items = self::find( $type, $sql, $values );
		$found = end( $items );
		if (!$found) return null;
		return $found;
	}

	/**
	 * Returns an array of beans. Pass a type and a series of ids and
	 * this method will bring you the correspondig beans.
	 *
	 * important note: Because this method loads beans using the load()
	 * function (but faster) it will return empty beans with ID 0 for
	 * every bean that could not be located. The resulting beans will have the
	 * passed IDs as their keys.
	 *
	 * @param string $type type of beans
	 * @param array  $ids  ids to load
	 *
	 * @return array $beans resulting beans (may include empty ones)
	 */
	public static function batch( $type, $ids ) {
		return self::$redbean->batch($type, $ids);
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql    SQL query to execute
	 * @param array  $values values a list of values to be bound to query parameters
	 *
	 * @return integer $affected  number of affected rows
	 */
	public static function exec( $sql, $values=array() ) {
		return self::query('exec',$sql,$values);
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql    SQL query to execute
	 * @param array  $values values a list of values to be bound to query parameters
	 *
	 * @return array $results
	 */
	public static function getAll( $sql, $values=array() ) {
		return self::query('get',$sql,$values);
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql    SQL query to execute
	 * @param array  $values values a list of values to be bound to query parameters
	 *
	 * @return string $result scalar
	 */
	public static function getCell( $sql, $values=array() ) {
		return self::query('getCell',$sql,$values);
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql    SQL query to execute
	 * @param array  $values values a list of values to be bound to query parameters
	 *
	 * @return array $results
	 */
	public static function getRow( $sql, $values=array() ) {
		return self::query('getRow',$sql,$values);
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql	 sql    SQL query to execute
	 * @param array  $values values a list of values to be bound to query parameters
	 *
	 * @return array $results
	 */
	public static function getCol( $sql, $values=array() ) {
		return self::query('getCol',$sql,$values);
	}

	/**
	 * Internal Query function, executes the desired query. Used by
	 * all facade query functions. This keeps things DRY.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param string $method desired query method (i.e. 'cell','col','exec' etc..)
	 * @param string $sql    the sql you want to execute
	 * @param array  $values array of values to be bound to query statement
	 *
	 * @return array $results results of query
	 */
	private static function query($method,$sql,$values) {
		if (!self::$redbean->isFrozen()) {
			try {
				$rs = RedBean_Facade::$adapter->$method( $sql, $values );
			}catch(RedBean_Exception_SQL $e) {
				if(self::$writer->sqlStateIn($e->getSQLState(),
				array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE)
				)) {
					return array();
				}
				else {
					throw $e;
				}
			}
			return $rs;
		}
		else {
			return RedBean_Facade::$adapter->$method( $sql, $values );
		}
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 * Results will be returned as an associative array. The first
	 * column in the select clause will be used for the keys in this array and
	 * the second column will be used for the values. If only one column is
	 * selected in the query, both key and value of the array will have the
	 * value of this field for each row.
	 *
	 * @param string $sql	 sql    SQL query to execute
	 * @param array  $values values a list of values to be bound to query parameters
	 *
	 * @return array $results
	 */
	public static function getAssoc($sql,$values=array()) {
		return self::query('getAssoc',$sql,$values);
	}


	/**
	 * Makes a copy of a bean. This method makes a deep copy
	 * of the bean.The copy will have the following features.
	 * - All beans in own-lists will be duplicated as well
	 * - All references to shared beans will be copied but not the shared beans themselves
	 * - All references to parent objects (_id fields) will be copied but not the parents themselves
	 * In most cases this is the desired scenario for copying beans.
	 * This function uses a trail-array to prevent infinite recursion, if a recursive bean is found
	 * (i.e. one that already has been processed) the ID of the bean will be returned.
	 * This should not happen though.
	 *
	 * Note:
	 * This function does a reflectional database query so it may be slow.
	 *
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	public static function dup($bean,$trail=array(),$pid=false) {
		$duplicationManager = new RedBean_DuplicationManager(self::$toolbox);
		return $duplicationManager->dup($bean, $trail,$pid);
	}

	/**
	 * Exports a collection of beans. Handy for XML/JSON exports with a
	 * Javascript framework like Dojo or ExtJS.
	 * What will be exported:
	 * - contents of the bean
	 * - all own bean lists (recursively)
	 * - all shared beans (not THEIR own lists)
	 *
	 * @param	array|RedBean_OODBBean $beans beans to be exported
	 *
	 * @return	array $array exported structure
	 */
	public static function exportAll($beans) {
		$array = array();
		if (!is_array($beans)) $beans = array($beans);
		foreach($beans as $bean) {
			$f = self::dup($bean,array(),true);
			$array[] = $f->export();
		}
		return $array;
	}


	/**
	 * Given an array of two beans and a property, this method
	 * swaps the value of the property.
	 * This is handy if you need to swap the priority or orderNo
	 * of an item (i.e. bug-tracking, page order).
	 *
	 * @param array  $beans    beans
	 * @param string $property property
	 */
	public static function swap( $beans, $property ) {
		$bean1 = array_shift($beans);
		$bean2 = array_shift($beans);
		$tmp = $bean1->$property;
		$bean1->$property = $bean2->$property;
		$bean2->$property = $tmp;
		RedBean_Facade::store($bean1);
		RedBean_Facade::store($bean2);
	}

	/**
	 * Converts a series of rows to beans.
	 *
	 * @param string $type type
	 * @param array  $rows must contain an array of arrays.
	 *
	 * @return array $beans
	 */
	public static function convertToBeans($type,$rows) {
		return self::$redbean->convertToBeans($type,$rows);
	}


	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tests whether a bean has been associated with one ore more
	 * of the listed tags. If the third parameter is TRUE this method
	 * will return TRUE only if all tags that have been specified are indeed
	 * associated with the given bean, otherwise FALSE.
	 * If the third parameter is FALSE this
	 * method will return TRUE if one of the tags matches, FALSE if none
	 * match.
	 *
	 * @param  RedBean_OODBBean $bean bean to check for tags
	 * @param  array            $tags list of tags
	 * @param  boolean          $all  whether they must all match or just some
	 *
	 * @return boolean $didMatch whether the bean has been assoc. with the tags
	 */
	public static function hasTag($bean, $tags, $all=false) {
		return self::$tagManager->hasTag($bean,$tags,$all);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Removes all sepcified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * @param  RedBean_OODBBean $bean    tagged bean
	 * @param  array            $tagList list of tags (names)
	 *
	 * @return void
	 */
	public static function untag($bean,$tagList) {
		return self::$tagManager->untag($bean,$tagList);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is null or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean $bean    bean
	 * @param mixed				$tagList tags
	 *
	 * @return string $commaSepListTags
	 */
	public static function tag( RedBean_OODBBean $bean, $tagList = null ) {
		return self::$tagManager->tag($bean,$tagList);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param RedBean_OODBBean  $bean    bean
	 * @param array				$tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public static function addTags( RedBean_OODBBean $bean, $tagList ) {
		return self::$tagManager->addTags($bean,$tagList);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param  $beanType type of bean you are looking for
	 * @param  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public static function tagged( $beanType, $tagList ) {
		return self::$tagManager->tagged($beanType,$tagList);
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * @param  $beanType type of bean you are looking for
	 * @param  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public static function taggedAll( $beanType, $tagList ) {
		return self::$tagManager->taggedAll($beanType,$tagList);
	}


	/**
	 * Wipes all beans of type $beanType.
	 *
	 * @param string $beanType type of bean you want to destroy entirely.
	 */
	public static function wipe( $beanType ) {
		return RedBean_Facade::$redbean->wipe($beanType);
	}

	/**
	 * Counts beans
	 *
	 * @param string $beanType type of bean
	 *
	 * @return integer $numOfBeans
	 */

	public static function count( $beanType ) {
		return RedBean_Facade::$redbean->count($beanType);
	}

	/**
	 * Configures the facade, want to have a new Writer? A new Object Database or a new
	 * Adapter and you want it on-the-fly? Use this method to hot-swap your facade with a new
	 * toolbox.
	 *
	 * @param RedBean_ToolBox $tb toolbox
	 *
	 * @return RedBean_ToolBox $tb old, rusty, previously used toolbox
	 */
	public static function configureFacadeWithToolbox( RedBean_ToolBox $tb ) {
		$oldTools = self::$toolbox;
		self::$toolbox = $tb;
		self::$writer = self::$toolbox->getWriter();
		self::$adapter = self::$toolbox->getDatabaseAdapter();
		self::$redbean = self::$toolbox->getRedBean();
		self::$associationManager = new RedBean_AssociationManager( self::$toolbox );
		self::$redbean->setAssociationManager(self::$associationManager);
		self::$extAssocManager = new RedBean_ExtAssociationManager( self::$toolbox );
		$helper = new RedBean_ModelHelper();
		self::$redbean->addEventListener('update', $helper );
		self::$redbean->addEventListener('open', $helper );
		self::$redbean->addEventListener('delete', $helper );
		self::$associationManager->addEventListener('delete', $helper );
		self::$redbean->addEventListener('after_delete', $helper );
		self::$redbean->addEventListener('after_update', $helper );
		self::$redbean->addEventListener('dispense', $helper );
		self::$tagManager = new RedBean_TagManager( self::$toolbox );
		self::$f = new RedBean_SQLHelper(self::$adapter);
		return $oldTools;
	}



	/**
	 * facade method for Cooker Graph.
	 *
	 * @param array   $array            array containing POST/GET fields or other data
	 * @param boolean $filterEmptyBeans whether you want to exclude empty beans
	 *
	 * @return array $arrayOfBeans Beans
	 */
	public static function graph($array,$filterEmpty=false) {
		$cooker = new RedBean_Cooker();
		$cooker->setToolbox(self::$toolbox);
		return $cooker->graph($array,$filterEmpty);
	}



	/**
	 * Facade Convience method for adapter transaction system.
	 * Begins a transaction.
	 *
	 * @return void
	 */
	public static function begin() {
		self::$adapter->startTransaction();
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Commits a transaction.
	 *
	 * @return void
	 */
	public static function commit() {
		self::$adapter->commit();
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Rolls back a transaction.
	 *
	 * @return void
	 */
	public static function rollback() {
		self::$adapter->rollback();
	}

	/**
	 * Returns a list of columns. Format of this array:
	 * array( fieldname => type )
	 * Note that this method only works in fluid mode because it might be
	 * quite heavy on production servers!
	 *
	 * @param  string $table   name of the table (not type) you want to get columns of
	 *
	 * @return array  $columns list of columns and their types
	 */
	public static function getColumns($table) {
		return self::$writer->getColumns($table);
	}

	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array $array
	 * @return string $slots
	 */
	public static function genSlots($array) {
		if (count($array)>0) {
			$filler = array_fill(0,count($array),'?');
			return implode(',',$filler);
		}
		else {
			return '';
		}
	}

	/**
	 * Nukes the entire database.
	 */
	public static function nuke() {
		if (!self::$redbean->isFrozen()) {
			self::$writer->wipeAll();
		}
	}

	/**
	 * Sets a list of dependencies.
	 * A dependency list contains an entry for each dependent bean.
	 * A dependent bean will be removed if the relation with one of the
	 * dependencies gets broken.
	 *
	 * Example:
	 *
	 * array(
	 *	'page' => array('book','magazine')
	 * )
	 *
	 * A page will be removed if:
	 *
	 * unset($book->ownPage[$pageID]);
	 *
	 * or:
	 *
	 * unset($magazine->ownPage[$pageID]);
	 *
	 * but not if:
	 *
	 * unset($paper->ownPage[$pageID]);
	 *
	 *
	 * @param array $dep list of dependencies
	 */
	public static function dependencies($dep) {
		self::$redbean->setDepList($dep);
    }

	/**
	 * Short hand function to store a set of beans at once, IDs will be
	 * returned as an array. For information please consult the R::store()
	 * function.
	 * A loop saver.
	 *
	 * @param array $beans list of beans to be stored
	 *
	 * @return array $ids list of resulting IDs
	 */
	public static function storeAll($beans) {
		$ids = array();
		foreach($beans as $bean) $ids[] = self::store($bean);
		return $ids;
	}

	/**
	 * Short hand function to trash a set of beans at once.
	 * For information please consult the R::trash() function.
	 * A loop saver.
	 *
	 * @param array $beans list of beans to be trashed
	 */
	public static function trashAll($beans) {
		foreach($beans as $bean) self::trash($bean);
	}

	/**
	 * A label is a bean with only an id, type and name property.
	 * This function will dispense beans for all entries in the array. The
	 * values of the array will be assigned to the name property of each
	 * individual bean.
	 *
	 * @param string $type   type of beans you would like to have
	 * @param array  $labels list of labels, names for each bean
	 *
	 * @return array $bean a list of beans with type and name property
	 */
	public static function dispenseLabels($type,$labels) {
		$labelBeans = array();
		foreach($labels as $label) {
			$labelBean = self::dispense($type);
			$labelBean->name = $label;
			$labelBeans[] = $labelBean;
		}
		return $labelBeans;
	}

	/**
	 * Gathers labels from beans. This function loops through the beans,
	 * collects the values of the name properties of each individual bean
	 * and stores the names in a new array. The array then gets sorted using the
	 * default sort function of PHP (sort).
	 *
	 * @param array $beans list of beans to loop
	 *
	 * @return array $array list of names of beans
	 */
	public function gatherLabels($beans) {
		$labels = array();
		foreach($beans as $bean) $labels[] = $bean->name;
		sort($labels);
		return $labels;
	}


	/**
	 * Closes the database connection.
	 */
	public static function close() {
		if (isset(self::$adapter)){
			self::$adapter->close();
		}
	}

	/**
	 * Activates TimeLine Schema Alteration monitoring and
	 * Query logging.
	 *
	 * @param type $filename
	 */
	public static function log($filename) {
		$tl = new RedBean_Plugin_TimeLine($filename);
		self::$adapter->addEventListener('sql_exec',$tl);
	}


	/**
	 * Simple convenience function, returns ISO date formatted representation
	 * of $time.
	 *
	 * @param mixed $time UNIX timestamp
	 *
	 * @return type
	 */
	public static function isoDate( $time = null ) {
		if (!$time) $time = time();
		return @date('Y-m-d',$time);
	}

	/**
	 * Simple convenience function, returns ISO date time
	 * formatted representation
	 * of $time.
	 *
	 * @param mixed $time UNIX timestamp
	 *
	 * @return type
	 */
	public static function isoDateTime( $time = null) {
		if (!$time) $time = time();
		return @date('Y-m-d H:i:s',$time);
	}

}

//Compatibility with PHP 5.2 and earlier
function __lcfirst( $str ){	return (string)(strtolower(substr($str,0,1)).substr($str,1)); }


/**
 * BeanCan
 *  
 * @file			RedBean/BeanCan.php
 * @description		A Server Interface for RedBean and Fuse.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 * 
 * The BeanCan Server is a lightweight, minimalistic server interface for
 * RedBean that can perfectly act as an ORM middleware solution or a backend
 * for an AJAX application.
 * 
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_BeanCan {

	/**
	 * Holds a FUSE instance.
	 * @var RedBean_ModelHelper
	 */
	private $modelHelper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->modelHelper = new RedBean_ModelHelper;
	}

	/**
	 * Writes a response object for the client (JSON encoded). Internal method.
	 *
	 * @param mixed   $result       result
	 * @param integer $id           request ID
	 * @param integer $errorCode    error code from server
	 * @param string  $errorMessage error message from server
	 *
	 * @return string $json JSON encoded response.
	 */
	private function resp($result=null, $id=null, $errorCode='-32603',$errorMessage='Internal Error') {
		$response = array('jsonrpc'=>'2.0');
		if ($id) { $response['id'] = $id; }
		if ($result) {
			$response['result']=$result;
		}
		else {
			$response['error'] = array('code'=>$errorCode,'message'=>$errorMessage);
		}
		return (json_encode($response));
	}

	/**
	 * Processes a JSON object request.
	 *
	 * @param array $jsonObject JSON request object
	 *
	 * @return mixed $result result
	 */
	public function handleJSONRequest( $jsonString ) {
		//Decode JSON string
		$jsonArray = json_decode($jsonString,true);
		if (!$jsonArray) return $this->resp(null,null,-32700,'Cannot Parse JSON');
		if (!isset($jsonArray['jsonrpc'])) return $this->resp(null,null,-32600,'No RPC version');
		if (($jsonArray['jsonrpc']!='2.0')) return $this->resp(null,null,-32600,'Incompatible RPC Version');
		//DO we have an ID to identify this request?
		if (!isset($jsonArray['id'])) return $this->resp(null,null,-32600,'No ID');
		//Fetch the request Identification String.
		$id = $jsonArray['id'];
		//Do we have a method?
		if (!isset($jsonArray['method'])) return $this->resp(null,$id,-32600,'No method');
		//Do we have params?
		if (!isset($jsonArray['params'])) {
			$data = array();
		}
		else {
			$data = $jsonArray['params'];
		}
		//Check method signature
		$method = explode(':',trim($jsonArray['method']));
		if (count($method)!=2) {
			return $this->resp(null, $id, -32600,'Invalid method signature. Use: BEAN:ACTION');
		}
		//Collect Bean and Action
		$beanType = $method[0];
		$action = $method[1];
		//May not contain anything other than ALPHA NUMERIC chars and _
		if (preg_match('/\W/',$beanType)) return $this->resp(null, $id, -32600,'Invalid Bean Type String');
		if (preg_match('/\W/',$action)) return $this->resp(null, $id, -32600,'Invalid Action String');

		try {
			switch($action) {
				case 'store':
					if (!isset($data[0])) return $this->resp(null, $id, -32602,'First param needs to be Bean Object');
					$data = $data[0];
					if (!isset($data['id'])) $bean = RedBean_Facade::dispense($beanType); else
						$bean = RedBean_Facade::load($beanType,$data['id']);
					$bean->import( $data );
					$rid = RedBean_Facade::store($bean);
					return $this->resp($rid, $id);
				case 'load':
					if (!isset($data[0])) return $this->resp(null, $id, -32602,'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType,$data[0]);
					return $this->resp($bean->export(),$id);
				case 'trash':
					if (!isset($data[0])) return $this->resp(null, $id, -32602,'First param needs to be Bean ID');
					$bean = RedBean_Facade::load($beanType,$data[0]);
					RedBean_Facade::trash($bean);
					return $this->resp('OK',$id);
				default:
					$modelName = $this->modelHelper->getModelName( $beanType );
					if (!class_exists($modelName)) return $this->resp(null, $id, -32601,'No such bean in the can!');
					$beanModel = new $modelName;
					if (!method_exists($beanModel,$action)) return $this->resp(null, $id, -32601,"Method not found in Bean: $beanType ");
					return $this->resp( call_user_func_array(array($beanModel,$action), $data), $id);
			}
		}
		catch(Exception $exception) {
			return $this->resp(null, $id, -32099,$exception->getCode().'-'.$exception->getMessage());
		}
	}
	
	/**
	 * Support for RESTFul GET-requests.
	 * Only supports very BASIC REST requests, for more functionality please use
	 * the JSON-RPC 2 interface.
	 * 
	 * @param string $pathToResource RESTFul path to resource
	 * 
	 * @return string $json a JSON encoded response ready for sending to client
	 */
	public function handleRESTGetRequest( $pathToResource ) {
		if (!is_string($pathToResource)) return $this->resp(null,0,-32099,'IR');
		$resourceInfo = explode('/',$pathToResource);
		$type = $resourceInfo[0];
		try {
			if (count($resourceInfo) < 2) {
				return $this->resp(RedBean_Facade::findAndExport($type));
			}
			else {
				$id = (int) $resourceInfo[1];
				return $this->resp(RedBean_Facade::load($type,$id)->export(),$id);
			}
		}
		catch(Exception $e) {
			return $this->resp(null,0,-32099);
		}
	}
}



/**
 * RedBean Cooker
 * @file			RedBean/Cooker.php
 * @description		Turns arrays into bean collections for easy persistence.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * The Cooker is a little candy to make it easier to read-in an HTML form.
 * This class turns a form into a collection of beans plus an array
 * describing the desired associations.
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Cooker {
	
	/**
	 * This flag indicates whether empty strings in beans will be
	 * interpreted as NULL or not. TRUE means Yes, will be converted to NULL,
	 * FALSE means empty strings will be stored as such (conversion to 0 for integer fields).
	 * @var boolean
	 */
	private static $useNULLForEmptyString = false;

	/**
	 * Sets the toolbox to be used by graph()
	 *
	 * @param RedBean_Toolbox $toolbox toolbox
	 * @return void
	 */
	public function setToolbox(RedBean_Toolbox $toolbox) {
		$this->toolbox = $toolbox;
		$this->redbean = $this->toolbox->getRedbean();
	}

	/**
	 * Turns an array (post/request array) into a collection of beans.
	 * Handy for turning forms into bean structures that can be stored with a
	 * single call.
	 * 
	 * Typical usage:
	 * 
	 * $struct = R::graph($_POST);
	 * R::store($struct);
	 * 
	 * Example of a valid array:
	 * 
	 *	$form = array(
	 *		'type'=>'order',
	 *		'ownProduct'=>array(
	 *			array('id'=>171,'type'=>'product'),
	 *		),
	 *		'ownCustomer'=>array(
	 *			array('type'=>'customer','name'=>'Bill')
	 *		),
	 * 		'sharedCoupon'=>array(
	 *			array('type'=>'coupon','name'=>'123'),
	 *			array('type'=>'coupon','id'=>3)
	 *		)
	 *	);
	 * 
	 * Each entry in the array will become a property of the bean.
	 * The array needs to have a type-field indicating the type of bean it is
	 * going to be. The array can have nested arrays. A nested array has to be
	 * named conform the bean-relation conventions, i.e. ownPage/sharedPage
	 * each entry in the nested array represents another bean.
	 *  
	 * @param	array   $array       array to be turned into a bean collection
	 * @param   boolean $filterEmpty whether you want to exclude empty beans
	 *
	 * @return	array $beans beans
	 */
	public function graph( $array, $filterEmpty = false ) {
      	$beans = array();
		if (is_array($array) && isset($array['type'])) {
			$type = $array['type'];
			unset($array['type']);
			//Do we need to load the bean?
			if (isset($array['id'])) {
				$id = (int) $array['id'];
				$bean = $this->redbean->load($type,$id);
			}
			else {
				$bean = $this->redbean->dispense($type);
			}
			foreach($array as $property=>$value) {
				if (is_array($value)) {
					$bean->$property = $this->graph($value,$filterEmpty);
				}
				else {
					if($value == '' && self::$useNULLForEmptyString){
						$bean->$property = null;
                    }
					else
					$bean->$property = $value;
				}
			}
			return $bean;
		}
		elseif (is_array($array)) {
			foreach($array as $key=>$value) {
				$listBean = $this->graph($value,$filterEmpty);
				if (!($listBean instanceof RedBean_OODBBean)) {
					throw new RedBean_Exception_Security('Expected bean but got :'.gettype($listBean)); 
				}
				if ($listBean->isEmpty()) {  
					if (!$filterEmpty) { 
						$beans[$key] = $listBean;
					}
				}
				else { 
					$beans[$key] = $listBean;
				}
			}
			return $beans;
		}
		else {
			throw new RedBean_Exception_Security('Expected array but got :'.gettype($array)); 
		}
	}
	
	/**
	 * Toggles the use-NULL flag.
	 *  
	 * @param boolean $yesNo 
	 */
	public function setUseNullFlag($yesNo) {
		self::$useNULLForEmptyString = (boolean) $yesNo;
	}
	
}


/**
 * Query Logger
 *
 * @file 			RedBean/Plugin/QueryLogger.php
 * @description		Query logger, can be attached to an observer that
 * 					signals the sql_exec event.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_Plugin_QueryLogger implements RedBean_Observer {

	/**
	 * @var array
	 * contains log messages
	 */
	protected $logs = array();

	/**
	 * Creates a new instance of the Query Logger and attaches
	 * this logger to the adapter.
	 *
	 * @static
	 * @param RedBean_Observable $adapter the adapter you want to attach to
	 *
	 * @return RedBean_Plugin_QueryLogger $querylogger instance of the Query Logger
	 */
	public static function getInstanceAndAttach( RedBean_Observable $adapter ) {
		$queryLog = new RedBean_Plugin_QueryLogger;
		$adapter->addEventListener( 'sql_exec', $queryLog );
		return $queryLog;
	}

	/**
	 * Singleton pattern
	 * Constructor - private
	 */
	private function __construct(){}

	/**
	 * Implementation of the onEvent() method for Observer interface.
	 * If a query gets executed this method gets invoked because the
	 * adapter will send a signal to the attached logger.
	 *
	 * @param  string $eventName          ID of the event (name)
	 * @param  RedBean_DBAdapter $adapter adapter that sends the signal
	 *
	 * @return void
	 */
	public function onEvent( $eventName, $adapter ) {
		if ($eventName=='sql_exec') {
			$this->logs[] = $adapter->getSQL();
		}
	}

	/**
	 * Searches the logs for the given word and returns the entries found in
	 * the log container.
	 *
	 * @param  string $word word to look for
	 *
	 * @return array $entries entries that contain the keyword
	 */
	public function grep( $word ) {
		$found = array();
		foreach($this->logs as $log) {
			if (strpos($log,$word)!==false) {
				$found[] = $log;
			}
		}
		return $found;
	}

	/**
	 * Returns all the logs.
	 *
	 * @return array $logs logs
	 */
	public function getLogs() {
		return $this->logs;
	}

	/**
	 * Clears the logs.
	 *
	 * @return void
	 */
	public function clear() {
		$this->logs = array();
	}
}


/**
 * TimeLine 
 *
 * @file 			RedBean/Plugin/TimeLine.php
 * @description		Monitors schema changes to ease deployment.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_Plugin_TimeLine extends RedBean_Plugin_QueryLogger {
	
	/**
	 * Path to file to write SQL and comments to.
	 * 
	 * @var string 
	 */
	protected $file;
	
	/**
	 * Constructor.
	 * Requires a path to an existing and writable file.
	 * 
	 * @param string $outputPath path to file to write schema changes to. 
	 */
	public function __construct($outputPath) {
		if (!file_exists($outputPath) || !is_writable($outputPath)) 
			throw new RedBean_Exception_Security('Cannot write to file: '.$outputPath);
		$this->file = $outputPath;
	}
	
	/**
	 * Implementation of the onEvent() method for Observer interface.
	 * If a query gets executed this method gets invoked because the
	 * adapter will send a signal to the attached logger.
	 *
	 * @param  string $eventName          ID of the event (name)
	 * @param  RedBean_DBAdapter $adapter adapter that sends the signal
	 *
	 * @return void
	 */
	public function onEvent( $eventName, $adapter ) {
		if ($eventName=='sql_exec') {
			$sql = $adapter->getSQL();
			$this->logs[] = $sql;
			if (strpos($sql,'ALTER')===0) {
				$write = "-- ".date('Y-m-d H:i')." | Altering table. \n";
				$write .= $sql;
				$write .= "\n\n";
			}
			if (strpos($sql,'CREATE')===0) {
				$write = "-- ".date('Y-m-d H:i')." | Creating new table. \n";
				$write .= $sql;
				$write .= "\n\n";
			}
			if (isset($write)) {
				file_put_contents($this->file,$write,FILE_APPEND);
			}
		}
	}
	
	
}

/**
 * RedBean Dependency Injector
 * 
 * @file			RedBean/DependencyInjector.php
 * @description		A default dependency injector that can be subclassed to
 *					suit your needs.
 * 		
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_DependencyInjector {
	
	/**
	 * List of dependencies.
	 * @var array 
	 */
	protected $dependencies = array();
	
	/**
	 * Adds a dependency to the list.
	 * You can add dependencies using this method. Pass both the key of the
	 * dependency and the dependency itself. The key of the dependency is a 
	 * name that should match the setter. For instance if you have a dependency
	 * class called My_Mailer and a setter on the model called setMailSystem
	 * you should pass an instance of My_Mailer with key MailSystem.
	 * The injector will now look for a setter called setMailSystem.
	 * 
	 * @param string $dependencyID name of the dependency (should match setter)
	 * @param mixed  $dependency   the service to be injected
	 */
	public function addDependency($dependencyID,$dependency) {
		$this->dependencies[$dependencyID] = $dependency;
	}
	
	/**
	 * Returns an instance of the class $modelClassName completely
	 * configured as far as possible with all the available
	 * service objects in the dependency list.
	 * 
	 * @param string $modelClassName the name of the class of the model
	 * 
	 * @return mixed $object the model/object
	 */
	public function getInstance($modelClassName) {
		$object = new $modelClassName;
		if ($this->dependencies && is_array($this->dependencies)) {
			foreach($this->dependencies as $key=>$dep) {
				$depSetter = 'set'.$key;
				if (method_exists($object,$depSetter)) {
					$object->$depSetter($dep);
				}
			}
		}
		return $object;
	}
}

/**
 * RedBean Duplication Manager
 * 
 * @file			RedBean/DuplicationManager.php
 * @description		Creates deep copies of beans
 * 		
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_DuplicationManager {
	
	
	/**
	 * The Dup Manager requires a toolbox
	 * @var RedBean_Toolbox 
	 */
	protected $toolbox;
	
	/**
	 * Association Manager 
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;
	
	/**
	 * RedBeanPHP OODB instance
	 * @var RedBean_OODBBean 
	 */
	protected $redbean;
	
	
	/**
	 * Constructor,
	 * creates a new instance of DupManager.
	 * @param RedBean_Toolbox $toolbox 
	 */
	public function __construct( RedBean_Toolbox $toolbox ) {
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
		$this->associationManager = $this->redbean->getAssociationManager();
	}
	
	/**
	 * Makes a copy of a bean. This method makes a deep copy
	 * of the bean.The copy will have the following features.
	 * - All beans in own-lists will be duplicated as well
	 * - All references to shared beans will be copied but not the shared beans themselves
	 * - All references to parent objects (_id fields) will be copied but not the parents themselves
	 * In most cases this is the desired scenario for copying beans.
	 * This function uses a trail-array to prevent infinite recursion, if a recursive bean is found
	 * (i.e. one that already has been processed) the ID of the bean will be returned.
	 * This should not happen though.
	 *
	 * Note:
	 * This function does a reflectional database query so it may be slow.
	 *
	 * Note:
	 * this function actually passes the arguments to a protected function called
	 * duplicate() that does all the work. This method takes care of creating a clone
	 * of the bean to avoid the bean getting tainted (triggering saving when storing it).
	 * 
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	public function dup($bean,$trail=array(),$pid=false) {
		$beanCopy = clone($bean);
		return $this->duplicate($beanCopy,$trail,$pid);
	}
	
	/**
	 * Makes a copy of a bean. This method makes a deep copy
	 * of the bean.The copy will have the following features.
	 * - All beans in own-lists will be duplicated as well
	 * - All references to shared beans will be copied but not the shared beans themselves
	 * - All references to parent objects (_id fields) will be copied but not the parents themselves
	 * In most cases this is the desired scenario for copying beans.
	 * This function uses a trail-array to prevent infinite recursion, if a recursive bean is found
	 * (i.e. one that already has been processed) the ID of the bean will be returned.
	 * This should not happen though.
	 *
	 * Note:
	 * This function does a reflectional database query so it may be slow.
	 *
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	protected function duplicate($bean,$trail=array(),$pid=false) {
	$type = $bean->getMeta('type');
		$key = $type.$bean->getID();
		if (isset($trail[$key])) return $bean;
		$trail[$key]=$bean;
		$copy =$this->redbean->dispense($type);
		$copy->import( $bean->getProperties() );
		$copy->id = 0;
		$tables = $this->toolbox->getWriter()->getTables();
		foreach($tables as $table) {
			if (strpos($table,'_')!==false || $table==$type) continue;
			$owned = 'own'.ucfirst($table);
			$shared = 'shared'.ucfirst($table);
			if ($beans = $bean->$owned) {
				$copy->$owned = array();
				foreach($beans as $subBean) {
					array_push($copy->$owned,$this->duplicate($subBean,$trail,$pid));
				}
			}
			$copy->setMeta('sys.shadow.'.$owned,null);
			if ($beans = $bean->$shared) {
				$copy->$shared = array();
				foreach($beans as $subBean) {
					array_push($copy->$shared,$subBean);
				}
			}
			$copy->setMeta('sys.shadow.'.$shared,null);

		}

		if ($pid) $copy->id = $bean->id;
		return $copy;
	}
}


class R extends RedBean_Facade{
}
