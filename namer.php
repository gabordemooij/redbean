<?php

function filterNames($names, $context=null) {
	
$map = [
	 'Exception' => 'RedException',
	 'Default'   => 'RDefault',
	 'PDO'		 => 'RPDO',
	 'Toolbox'	=> 'ToolBox',

];

if (is_array($context)) {
	if ($names == 'Facade' && in_array('BeanHelper',$context)) {
		return 'FacadeBeanHelper';
	}
}

if (isset($map[$names])) return $map[$names];
return $names;
	
}


function convert2namespaces($file, $skipClass = false) {

	$code = file_get_contents($file);

	if (!$skipClass) {
		
	
		$fileParts = explode('/', $file);
		$nameParts = [];
		foreach($fileParts as $part) {

			$part = str_replace('.php', '', $part);
			if ($part === 'RedBean') $part = 'RedBeanPHP';

			$nameParts[] = filterNames($part, $fileParts);
		}

		$xname = ''.implode('\\', $nameParts);
		array_pop($nameParts);
		$cname = ''.implode('\\', $nameParts);

		//add the namespace
		$namespace = 'namespace '.$cname.'; @@USELIST@@ ';
		$code = '<'.'?'.'php '.PHP_EOL.$namespace.substr($code,5);


		//update the classname
		$info = pathinfo($file);
		$newClassName = filterNames($info['filename'], $nameParts);

		$code = preg_replace('/\nclass\s+(\w+)/s', "\n".'class '.$newClassName, $code);
		//$code = preg_replace('/\nabstract\s+(\w+)/s', "\n".'abstract '.$newClassName, $code);
		$code = preg_replace('/\ninterface\s+(\w+)/s', "\n".'interface '.$newClassName, $code);

	} else {
		$xname = '';
		$code = '<'.'?'.'php '.PHP_EOL.'@@USELIST@@ '.substr($code,5);
	}
		
	$useList = array();

	$code = preg_replace_callback('/RedBean_(\w+)/s', function($matches) use(&$useList, $xname){

		$oldClassName = $matches[1];
		
		$packageParts =[];
		$oldPackage = explode('_', $oldClassName);
		foreach($oldPackage as $part) {
			$packageParts[] = filterNames($part, $oldPackage);
		}
		$package = '\\ReadBean\\'.implode('\\', $packageParts);
		
		$oldClassNameParts = explode('_', $oldClassName);

		$newClassName = array_pop($oldClassNameParts);

		$newClassName = filterNames($newClassName, $oldClassNameParts);
		$use = "\nuse $package as $newClassName;";

		echo " $package != $xname \n";
		if ($package !== '\\'.$xname) 
		$useList[($package)] = $use;

		
		return $newClassName;


	}, $code);

	
	$list = implode('', $useList);

	$code = str_replace('@@USELIST@@', $list, $code);

	
	$natObjects = array(
		"IteratorAggregate", "ArrayAccess", "RuntimeException", "LogicException",
		"Exception", "PDO", "ArrayIterator", "Countable","DateTime"
	);

	foreach ( $natObjects as $natObj ) {
		$code = str_replace( " " . $natObj, "\\$natObj", $code );
		$code = str_replace( "(" . $natObj, "(\\$natObj", $code );
		$code = str_replace( "," . $natObj, ",\\$natObj", $code );
		$code = str_replace( ";" . $natObj, ";\\$natObj", $code );
		$code = str_replace( "{" . $natObj, "{\\$natObj", $code );
	}

	
	echo "\n\n";
	//echo substr($code,0,1500);
	
	//exit;
	return $code;
}


function globr($path, $find) {
$dh = opendir($path);
while (($file = readdir($dh)) !== false) {
	 if (substr($file, 0, 1) == '.') continue;
	 $rfile = "{$path}/{$file}";
	 if (is_dir($rfile)) {
		  foreach (globr($rfile, $find) as $ret) {
				yield $ret;
		  }
	 } else {
		  if (fnmatch($find, $file)) yield $rfile;
	 }
}
closedir($dh);
}

$files = globr('RedBean', '*.php');

$blackList = [
	 'RedBean/redbean.inc.php'
];

$all = '';
foreach($files as $file) {


	if (!in_array($file, $blackList)) {

		echo "\n * $file";

		
		$new = convert2namespaces($file);
		file_put_contents('copy/'.$file, $new);

	}

}



$files = globr('testing/RedUNIT', '*.php');

$blackList = [
	 'RedBean/redbean.inc.php'
];

$all = '';
foreach($files as $file) {


	if (!in_array($file, $blackList)) {

		echo "\n * $file";
		$new = convert2namespaces($file, true);
		file_put_contents('copy/'.$file, $new);

	}

}
