<?php
/**
 * RedBean Tools
 * Tool Collection for RedBean
 * @package 		RedBean/Tools.php
 * @description		A series of Tools of RedBean
 * @author			Desfrenes
 * @license			BSD
 */
class RedBean_Tools {
	/**
	 *
	 * @var unknown_type
	 */
	private static $class_definitions;

	/**
	 *
	 * @var unknown_type
	 */
	private static $remove_whitespaces;

	/**
	 *
	 * @param $root
	 * @param $callback
	 * @param $recursive
	 * @return unknown_type
	 */
	public static function walk_dir( $root, $callback, $recursive = true ) {
		$root = realpath($root);
		$dh   = @opendir( $root );
		if( false === $dh ) {
			return false;
		}
		while(false !==  ($file = readdir($dh))) {
			if( "." == $file || ".." == $file
					  ||	"redbean.inc.php" == $file
					  ||	"example.php" == $file
			) {
				continue;
			}
			call_user_func( $callback, "{$root}/{$file}" );
			if( false !== $recursive && is_dir( "{$root}/{$file}" )) {
				Redbean_Tools::walk_dir( "{$root}/{$file}", $callback, $recursive );
			}
		}
		closedir($dh);
		return true;
	}

	/**
	 *
	 * @param $file
	 * @param $removeWhiteSpaces
	 * @return unknown_type
	 */
	public static function compile($file = '', $removeWhiteSpaces = true) {
		self::$remove_whitespaces = $removeWhiteSpaces;
		self::$class_definitions = '';
		$base = dirname(__FILE__) . '/';
		self::walk_dir($base,'Redbean_Tools::stripClassDefinition');
		$content = str_replace("\r\n","\n", '<?php ' . "\n/** " . file_get_contents($base . 'license.txt') . "**/\n" . self::$class_definitions);
		if(!empty($file)) {

			file_put_contents($file, $content);
		}
		return $content;
	}

	/**
	 *
	 * @param $file
	 * @return unknown_type
	 */
	private static function stripClassDefinition($file) {
		if(is_file($file) && substr($file, -4) == '.php') {
			echo "\n adding.... $file ";

			if(self::$remove_whitespaces) {
				self::$class_definitions .= "\n" . trim(str_replace('<?php', '', php_strip_whitespace($file)));
			}
			else {
				self::$class_definitions .= "\n" . trim(str_replace('<?php', '', trim(file_get_contents($file))));
			}
		}
	}
}