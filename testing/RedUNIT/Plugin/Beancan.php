<?php
/**
 * RedUNIT_Plugin_Association
 * 
 * @file 			RedUNIT/Plugin/Beancan.php
 * @description		Tests BeanCan Server
 *					This test pack is part of the RedBeanPHP ORM Plugin test suite.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Beancan extends RedUNIT_Plugin {

	
	
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		
		
		$rs = ( s("candybar:store",array( array("brand"=>"funcandy","taste"=>"sweet") ) ) );
		testpack("Test create");
		asrt(is_string($rs),true);
		$rs = json_decode($rs,true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),true);
		asrt(($rs["result"]>0),true);
		asrt(isset($rs["error"]),false);
		asrt(count($rs),3);
		$oldid = $rs["result"];
		testpack("Test retrieve");
		$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),true);
		asrt(isset($rs["error"]),false);
		asrt(is_array($rs["result"]),true);
		asrt(count($rs["result"]),3);
		asrt($rs["result"]["id"],(string)$oldid);
		asrt($rs["result"]["brand"],"funcandy");
		asrt($rs["result"]["taste"],"sweet");
		testpack("Test update");
		$rs = json_decode( s("candybar:store",array( array( "id"=>$oldid, "taste"=>"salty" ) ),"42" ),true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"42");
		asrt(isset($rs["result"]),true);
		asrt(isset($rs["error"]),false);
		$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
		asrt($rs["result"]["taste"],"salty");
		$rs = json_decode( s("candybar:store",array( array("brand"=>"darkchoco","taste"=>"bitter") ) ), true );
		$id2 = $rs["result"];
		$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
		asrt($rs["result"]["brand"],"funcandy");
		asrt($rs["result"]["taste"],"salty");
		$rs = json_decode( s("candybar:load",array( $id2 ) ),true );
		asrt($rs["result"]["brand"],"darkchoco");
		asrt($rs["result"]["taste"],"bitter");
		testpack("Test delete");
		$rs = json_decode( s("candybar:trash",array( $oldid )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),true);
		asrt(isset($rs["error"]),false);
		asrt($rs["result"],"OK");
		$rs = json_decode( s("candybar:load",array( $oldid ) ),true );
		asrt(isset($rs["result"]),true);
		asrt(isset($rs["error"]),false);
		asrt($rs["result"]["id"],0);
		$rs = json_decode( s("candybar:load",array( $id2 ) ),true );
		asrt($rs["result"]["brand"],"darkchoco");
		asrt($rs["result"]["taste"],"bitter");
		testpack("Test Custom Method");
		$rs = json_decode( s("candybar:customMethod",array( "test" )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),true);
		asrt(isset($rs["error"]),false);
		asrt($rs["result"],"test!");
		$rs = json_decode( s("candybar:customMethodWithException",array( "test" )), true );
		asrt($rs["error"]["code"],-32099);
		asrt($rs["error"]["message"],'0-Oops!');
		
		testpack("Test Negatives: parse error");
		$can = new RedBean_Plugin_BeanCan;
		$rs =  json_decode( $can->handleJSONRequest( "crap" ), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),2);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),false);
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt(isset($rs["error"]["code"]),true);
		asrt($rs["error"]["code"],-32700);
		testpack("invalid request");
		$can = new RedBean_Plugin_BeanCan;
		$rs =  json_decode( $can->handleJSONRequest( '{"aa":"bb"}' ), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),2);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),false);
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt(isset($rs["error"]["code"]),true);
		asrt($rs["error"]["code"],-32600);
		$rs =  json_decode( $can->handleJSONRequest( '{"jsonrpc":"9.1"}' ), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),2);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),false);
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt(isset($rs["error"]["code"]),true);
		asrt($rs["error"]["code"],-32600);
		$rs =  json_decode( $can->handleJSONRequest( '{"id":9876,"jsonrpc":"9.1"}' ), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),2);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),false);
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt(isset($rs["error"]["code"]),true);
		asrt($rs["error"]["code"],-32600);
		$rs = json_decode( s("wrong",array( "test" )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt($rs["error"]["code"],-32600);
		asrt($rs["error"]["message"],"Invalid method signature. Use: BEAN:ACTION");
		
		$rs = json_decode( s(".;':wrong",array( "test" )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt($rs["error"]["code"],-32600);
		asrt($rs["error"]["message"],"Invalid Bean Type String");
		
		$rs = json_decode( s("wrong:.;'",array( "test" )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt($rs["error"]["code"],-32600);
		asrt($rs["error"]["message"],"Invalid Action String");
		
		$rs = json_decode( s("wrong:wrong",array( "test" )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt($rs["error"]["code"],-32601);
		asrt($rs["error"]["message"],"No such bean in the can!");
		$rs = json_decode( s("candybar:beHealthy",array( "test" )), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt($rs["error"]["code"],-32601);
		asrt($rs["error"]["message"],"Method not found in Bean: candybar ");
		$rs = json_decode( s("candybar:store"), true );
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]),true);
		asrt(($rs["id"]),"1234");
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt($rs["error"]["code"],-32602);
		$rs = json_decode( s("pdo:connect",array("abc")), true );
		asrt($rs["error"]["code"],-32601);
		$rs = json_decode( s("stdClass:__toString",array("abc")), true );
		asrt($rs["error"]["code"],-32601);
		
		R::nuke();
		$server = new RedBean_Plugin_BeanCan();
		$book = R::dispense('book');
		$book->title = 'book 1';
		$id1 = R::store($book);
		$book = R::dispense('book');
		$book->title = 'book 2';
		$id2 = R::store($book);
		
		asrt(json_decode($server->handleRESTGetRequest('book/'.$id1))->result->title,'book 1');
		asrt(json_decode($server->handleRESTGetRequest('book/'.$id2))->result->title,'book 2');
		$r = json_decode($server->handleRESTGetRequest('book'),true);
		$a = $r['result'];
		asrt(count($a),2);
		
		$r = json_decode($server->handleRESTGetRequest(''),true);
		$a = $r['error']['message'];
		asrt($a,'Internal Error');
		
		$r = json_decode($server->handleRESTGetRequest(array()),true);
		$a = $r['error']['message'];
		asrt($a,'IR');
		
		
		testpack('Test BeanCan:export');
		
		R::nuke();
		
		$briefcase = R::dispense('briefcase');
		$documents = R::dispense('document',2);
		$page = R::dispense('page');
		$author = R::dispense('author');
		
		$briefcase->name = 'green';
		$documents[0]->name = 'document 1';
		$page->content = 'Lorem Ipsum';
		$author->name = 'Someone';
		$briefcase->ownDocument = $documents;
		$documents[1]->ownPage[] = $page;
		$page->sharedAuthor[] = $author;
		$id = R::store($briefcase);
		
		$rs = json_decode(s('briefcase:export',array($id)),true);
		
		asrt((int)$rs['result'][0]['id'],(int)$id);
		asrt($rs['result'][0]['name'],'green');
		asrt($rs['result'][0]['ownDocument'][0]['name'],'document 1');
		asrt($rs['result'][0]['ownDocument'][1]['ownPage'][0]['content'],'Lorem Ipsum');
		asrt($rs['result'][0]['ownDocument'][1]['ownPage'][0]['sharedAuthor'][0]['name'],'Someone');
		
		$rs = json_decode(s('document:export',array($documents[1]->id)),true);
		
		asrt((int)$rs['result'][0]['id'],(int)$documents[1]->id);
		asrt($rs['result'][0]['ownPage'][0]['content'],'Lorem Ipsum');
		asrt($rs['result'][0]['ownPage'][0]['sharedAuthor'][0]['name'],'Someone');
		asrt($rs['result'][0]['briefcase']['name'],'green');
		
		
		testpack('BeanCan does not include the request id in the response if it is 0');
		$id = R::store(R::dispense('foo')->setAttr('prop1','val1'));
		$rs =  json_decode( $can->handleJSONRequest('{"jsonrpc":"2.0","method":"foo:load","params":['.$id.'],"id":0}'), true);
		asrt(isset($rs['id']),true);
		asrt($rs['id'],0);
		
	}
	
	
}