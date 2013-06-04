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
		
		testpack('Test new Resty BeanCan');
		R::nuke();
		$user = R::dispense('user');
		$user->name = 'me';
		$site = R::dispense('site');
		$site->name = 'site 1';
		$page = R::dispense('page');
		$page->name = 'page 1';
		$ad = R::dispense('ad');
		$ad->name = 'an ad';
		$ad2 = R::dispense('ad');
		$ad2->name = 'an ad2';
		$page->sharedAd[] = $ad;
		$page->sharedAd[] = $ad2;
		$site->ownPage[] = $page;
		$user->ownSite[] = $site;
		R::store($user);

		$can = new RedBean_Plugin_BeanCan;
		$can->setWhitelist('all');
		$resp = $can->handleREST($user, '@!#?', 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'], 'URI contains invalid characters.');
		asrt((string)$resp['error']['code'], '-32700');
		$resp = $can->handleREST($user, 'blah', 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'], 'Invalid path: needs 1 more element.');
		asrt((string)$resp['error']['code'], '-32600');
		asrt((string)$resp['jsonrpc'], '2.0');
		asrt((string)$resp['id'], '0');
		$resp = $can->handleREST($user, '/blah', 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'], 'Cannot access list.');
		asrt((string)$resp['error']['code'], '-32600');
		asrt((string)$resp['jsonrpc'], '2.0');
		asrt((string)$resp['id'], '0');
		$resp = $can->handleREST($user, 'site/2', 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'], 'Cannot access bean.');
		asrt((string)$resp['error']['code'], '-32600');
		asrt((string)$resp['jsonrpc'], '2.0');
		asrt((string)$resp['id'], '0');
		$resp = $can->handleREST($user, 'blah/2', 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'], 'Cannot access bean.');
		$resp = $can->handleREST($user, '', 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['jsonrpc'], '2.0');
		asrt((string)$resp['result']['name'], 'me');
		$resp = $can->handleREST($user, 'book', 'PUT', '');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['code'], '-32700');
		asrt((string)$resp['error']['message'], 'Payload needs to be array.');
		
		$resp = $can->handleREST($user, '', 'PUT',array());
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['code'], '-32602');
		asrt((string)$resp['error']['message'], 'Missing list.');
		$resp = $can->handleREST($user, 'shared-bo-ok', 'PUT',array());
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['code'], '-32700');
		asrt((string)$resp['error']['message'], 'Invalid list.');
		
		$resp = $can->handleREST($user, 'book', 'PUT', array('type'=>'book'));
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['code'], '-32602');
		asrt((string)$resp['error']['message'], 'Missing parameter \'bean\'.');
		
		//Send a GET /site/1 request to BeanCan Server 
		$resp = $can->handleREST($user, 'site/'.$site->id , 'GET');
		$resp = json_decode($resp, true);
	 	asrt((string)$resp['result']['id'], (string)$site->id);
		asrt((string)$resp['result']['name'], (string)$site->name);
		asrt((string)$resp['result']['user_id'], (string)$site->user_id);
		$can->setWhitelist(array('page'=>array('POST')));
		$resp = $can->handleREST($user, 'site/'.$site->id , 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'],'This bean is not available. Set whitelist to "all" or add to whitelist.');
		asrt((string)$resp['error']['code'], '-32600');
		$can->setWhitelist(array('site'=>array('POST')));
		$resp = $can->handleREST($user, 'site/'.$site->id , 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'],'This bean is not available. Set whitelist to "all" or add to whitelist.');
		asrt((string)$resp['error']['code'], '-32600');
		$can->setWhitelist(array('site'=>array('GET')));
		$resp = $can->handleREST($user, 'site/'.$site->id , 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['result']['id'], (string)$site->id);
		asrt((string)$resp['result']['name'], (string)$site->name);
		asrt((string)$resp['result']['user_id'], (string)$site->user_id);
		asrt(!isset($resp['error']), true);
		$can->setWhitelist('all');

		//Send a GET /site/1/page/1 request to BeanCan Server
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page/'.$page->id , 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['result']['id'], (string)$page->id);
		asrt((string)$resp['result']['name'], (string)$page->name);
		asrt((string)$resp['result']['site_id'], (string)$page->site_id);
		//Send a GET /site/1/page/1/shared-ad/1
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page/'.$page->id.'/shared-ad/'.$ad->id, 'GET');
		$resp = json_decode($resp, true);
		asrt((string)$resp['result']['id'], (string)$ad->id);
		asrt((string)$resp['result']['name'], (string)$ad->name);
		//Send a PUT /site/1/page
		$payLoad = array(
			'type' => 'page',
			'bean' => array(
				'name' => 'my new page'
			)
		);
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page', 'PUT', $payLoad);
		$resp = json_decode($resp, true);
		$newPage = R::findOne('page',' name = ? ',array('my new page'));
		asrt((string)$resp['result']['id'], (string)$newPage->id);
		asrt((string)$resp['result']['name'], (string)$newPage->name);
		//Send a PUT /site/1/page/2/shared-ad
		$payLoad = array(
			'type' => 'ad',
			'bean' => array(
				'name' => 'my new ad'
			)
		);
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page/'.$page->id.'/shared-ad', 'PUT', $payLoad);
		$resp = json_decode($resp, true);
		$newAd = R::findOne('ad',' name = ? ',array('my new ad'));
		asrt((string)$resp['result']['id'], (string)$newAd->id);
		asrt((string)$resp['result']['name'], (string)$newAd->name);
		//Send a POST /site/1
		$payLoad = array(
			'bean' => array(
				'name' => 'The Original'
			)
		);
		$resp = $can->handleREST($user, 'site/'.$site->id, 'POST', $payLoad);
		$resp = json_decode($resp, true);
		asrt((string)$resp['result']['id'], (string)$site->id);
		asrt((string)$resp['result']['name'], 'The Original');
		
		//Send a DELETE /site/1/page/2/shared-ad/2
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page/'.$page->id.'/shared-ad/'.$newAd->id, 'DELETE');
		$resp = json_decode($resp, true);
		$newAd = R::findOne('ad',' name = ? ',array('my new ad'));
		asrt((string)$resp['result'], 'OK');
		asrt($newAd, null);
		
		//Send a MAIL /site/1/page/1
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page/'.$page->id, 'mail', array('param'=>array('me')));
		$resp = json_decode($resp, true);
		asrt((string)$resp['result'], 'mail has been sent to me');
		
		$resp = $can->handleREST($user, 'site/'.$site->id.'/page/'.$page->id, 'err', array('param'=>array('me')));
		$resp = json_decode($resp, true);
		asrt((string)$resp['error']['message'], 'Exception: 123');
		
		//test Access control
		$setting = R::dispense('setting');
		$option = R::dispense('option');
		$setting->ownOption[] = $option;
		$user->ownSetting[] = $setting;
		$option->name = 'secret';
		R::store($user);
		$resp = $can->handleREST($user, 'setting/'.$setting->id.'/option/'.$option->id, 'GET');
		$resp = json_decode($resp, true);
		asrt($resp['result']['name'], 'secret');
		$user2 = R::load('user', R::store(R::dispense('user')));
		$resp = $can->handleREST($user2, 'setting/'.$setting->id.'/option/'.$option->id, 'GET');
		$resp = json_decode($resp, true);
		asrt(isset($resp['error']),true);
		Model_Setting::$closed = true;
		$resp = $can->handleREST($user, 'setting/'.$setting->id.'/option/'.$option->id, 'GET');
		$resp = json_decode($resp, true);
		asrt(isset($resp['error']),true);
		Model_Setting::$closed = false;
		
		testpack("Test BeanCan Server 1 / create");
		R::nuke();
		$rs = ( s("candybar:store",array( array("brand"=>"funcandy","taste"=>"sweet") ) ) );
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
		
		testpack("Test Whitelist");
		$can = new RedBean_Plugin_BeanCan;
		$can->setWhitelist('all');
		$rs = json_decode( s("candybar:store",array( array("brand"=>"darkchoco","taste"=>"bitter") ), 1, ''), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]), true);
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt(isset($rs["error"]["code"]),true);
		asrt($rs["error"]["code"],-32600);
		asrt($rs["error"]["message"], 'This bean is not available. Set whitelist to "all" or add to whitelist.');
		$can = new RedBean_Plugin_BeanCan;
		$rs = json_decode( s("candybar:store",array( array("brand"=>"darkchoco","taste"=>"bitter") ), 1, array('candybar'=>array('like'))), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]), true);
		asrt(isset($rs["result"]),false);
		asrt(isset($rs["error"]),true);
		asrt(isset($rs["error"]["code"]),true);
		asrt($rs["error"]["code"],-32600);
		asrt($rs["error"]["message"], 'This bean is not available. Set whitelist to "all" or add to whitelist.');
		$can = new RedBean_Plugin_BeanCan;
		$rs = json_decode( s("candybar:store",array( array("brand"=>"darkchoco","taste"=>"bitter") ), 1, array('candybar'=>array('store'))), true);
		asrt(is_array($rs),true);
		asrt(empty($rs),false);
		asrt(count($rs),3);
		asrt(isset($rs["jsonrpc"]),true);
		asrt($rs["jsonrpc"],"2.0");
		asrt(isset($rs["id"]), true);
		asrt(isset($rs["result"]),true);
		asrt(isset($rs["error"]),false);
		
		testpack("Test Negatives: parse error");
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
		$can->setWhitelist('all');
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
		$can->setWhitelist('all');
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
		$can->setWhitelist('all');
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
		$can->setWhitelist('all');
		$rs =  json_decode( $can->handleJSONRequest('{"jsonrpc":"2.0","method":"foo:load","params":['.$id.'],"id":0}'), true);
		asrt(isset($rs['id']),true);
		asrt($rs['id'],0);	
	}
}


class Model_Page extends RedBean_SimpleModel {
	public function mail($who) {
		return 'mail has been sent to '.$who;
	}
	public function err() {
		throw new Exception('fake error',123);
	}
}

class Model_Setting extends RedBean_SimpleModel {
	public static $closed = false;	
	public function open() {
		if (self::$closed) throw new Exception('closed');
	}
}