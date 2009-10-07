<?php
/**
 * RedBean Tools
 * Tool Collection for RedBean
 * @package 		RedBean/Tools.php
 * @description		A series of Tools of RedBean
 * @author			Desfrenes
 * @license			BSD
 */
class RedBean_Tools
{
	/**
	 * 
	 * @var unknown_type
	 */
    private static $class_definitions = array();
    
    /**
     * 
     * @var unknown_type
     */
    private static $remove_whitespaces;


    private static $count = 0;
    /**
     * 
     * @param $root
     * @param $callback
     * @param $recursive
     * @return unknown_type
     */
    public static function walk_dir( $root, $callback, $recursive = true )
    {
        $root = realpath($root);
        $dh   = @opendir( $root );
        if( false === $dh )
        {
            return false;
        }
        while(false !==  ($file = readdir($dh)))
        {
            if( "." == $file || ".." == $file )
            {
                continue;
            }
            call_user_func( $callback, "{$root}/{$file}" );
            if( false !== $recursive && is_dir( "{$root}/{$file}" ))
            {
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
    public static function compile($file = '', $removeWhiteSpaces = true)
    {
        self::$remove_whitespaces = $removeWhiteSpaces;
        self::$class_definitions = array();
        $base = dirname(__FILE__) . '/';
        self::walk_dir($base,'Redbean_Tools::stripClassDefinition');
        $str ='';
        ksort(self::$class_definitions);
        foreach( self::$class_definitions as $k=>$v){
            //echo "\n".$k.' - '.$file;
            $str .= $v;
        }
        
        $content = str_replace("\r\n","\n", '<?php ' . "\n" . file_get_contents($base . 'license.txt') . "\n" . $str);
        if(!empty($file))
        {
            file_put_contents($file, $content);
        }
        return $content;
    }
 
    /**
     * 
     * @param $file
     * @return unknown_type
     */
    private static function stripClassDefinition($file)
    {
        if(is_file($file) && substr($file, -4) == '.php')
        {
          
            $index = (substr_count($file, "/") * 1000) + (++self::$count);

            if(self::$remove_whitespaces)
            {
                self::$class_definitions[$index] = "\n" . trim(str_replace('<?php', '', php_strip_whitespace($file)));
            }
            else
            {
                self::$class_definitions[$index] = "\n" . trim(str_replace('<?php', '', trim(file_get_contents($file))));
            }
        }
    }
}