<?php

class RedBean_Mod_ClassGenerator {

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
                                        $tablename = preg_replace("/_/","",$className);
                                        $fullname = $prefix.$className.$suffix;
                                        $toeval = $ns . " class ".$fullname." extends ". (($ns=='') ? '' : '\\' ) . "RedBean_Decorator {
                                        private static \$__static_property_type = \"".strtolower($tablename)."\";

                                        public function __construct(\$id=0, \$lock=false) {

                                                parent::__construct( RedBean_OODB::getInstance(), '".strtolower($tablename)."',\$id,\$lock);
                                        }

                                        public static function where( \$sql, \$slots=array() ) {
                                                return new RedBean_Can( RedBean_OODB::getInstance(), self::\$__static_property_type, RedBean_OODB::getInstance()->getBySQL( \$sql, \$slots, self::\$__static_property_type) );
                                        }

                                        public static function listAll(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
                                                return RedBean_OODB::getInstance()->listAll(self::\$__static_property_type,\$start,\$end,\$orderby,\$sql);
                                        }

                                        public static function getReadOnly(\$id) {
                                                RedBean_OODB::getInstance()->setLocking( false );
                                                \$me = new self( \$id );
                                                RedBean_OODB::getInstance()->setLocking( true );
                                                return \$me;
                                        }

                                        public function whereNS( \$sql, \$slots=array() ) {
                                                return self::where( \$sql, \$slots );
                                        }

                                        public function listAllNS(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
                                                self::listAll(\$start,\$end,\$orderby,\$sql);
                                        }
                                        public function getReadOnlyNS(\$id) {
                                                return self::getReadOnly(\$id);
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