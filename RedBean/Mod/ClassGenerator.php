<?php

class RedBean_Mod_ClassGenerator extends RedBean_Mod {

   /**
    *
    * @param <type> $classes
    * @param <type> $prefix
    * @param <type> $suffix
    * @return <type>
    */
    public function generate( $classes, $prefix = false, $suffix = false ) {

        if (!$prefix) {
                $prefix = RedBean_Setup_Namespace_PRFX;
        }

        if (!$suffix) {
                $suffix = RedBean_Setup_Namespace_SFFX;
        }

        $classes = explode(",",$classes);
        foreach($classes as $c) { // echo $c;
                $ns = '';
                $names = explode('\\', $c);
                $className = trim(end($names));
                if(count($names) > 1)
                {
                        $namespacestring = implode('\\', array_slice($names, 0, -1));
                        $ns = 'namespace ' . $namespacestring . " { ";
                }
                if ($c!=="" && $c!=="null" && !class_exists($c) &&
                                        preg_match("/^\s*[A-Za-z_][A-Za-z0-9_]*\s*$/",$className)){
                                        $tablename = $className;
                                        $fullname = $prefix.$className.$suffix;
                                        $toeval = $ns . " class ".$fullname." extends ". (($ns=='') ? '' : '\\' ) . "RedBean_Decorator {
                                        private static \$__static_property_type = \"".$this->provider->getToolBox()->getFilter()->table($tablename)."\";

                                        public function __construct(\$id=0, \$lock=false) {

                                                parent::__construct( RedBean_OODB::getInstance(), '".$this->provider->getToolBox()->getFilter()->table($tablename)."',\$id,\$lock);
                                        }

                                        public static function where( \$sql, \$slots=array() ) {
                                                return new RedBean_Can( RedBean_OODB::getInstance()->getToolBox(), self::\$__static_property_type, RedBean_OODB::getInstance()->getBySQL( \$sql, \$slots, self::\$__static_property_type) );
                                        }

                                        public static function listAll(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
                                                return RedBean_OODB::getInstance()->listAll(self::\$__static_property_type,\$start,\$end,\$orderby,\$sql);
                                        }

                                        public static function getReadOnly(\$id) {
                                                RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( false );
                                                \$me = new self( \$id );
                                                RedBean_OODB::getInstance()->getToolBox()->getLockManager()->setLocking( true );
                                                return \$me;
                                        }

                                        public static function exists( \$id ) {
                                            return  RedBean_OODB::getInstance()->getToolBox()->getBeanStore()->exists(self::\$__static_property_type, \$id);
                                        }

                                       

                                }";

                                if(count($names) > 1) {
                                        $toeval .= "}";
                                }

                                $teststring = (($ns!="") ? '\\'.$namespacestring.'\\'.$fullname : $fullname);
                                eval($toeval);
                                if (!class_exists( $teststring )) {
                                        throw new Exception("Failed to generate class");
                                }

                }
                else {
                        return false;
                }
        }
        return true;

    }


}

