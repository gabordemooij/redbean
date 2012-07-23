<?php
    $temp = array(
        'report_name' => $report_name,
        'db_name' => $db_name,
        'created_by' => $_SESSION['webdbUsername'],
        'report_sql' => $report_sql
    );

    if(1 == 0) {
        echo "Something\n";
    }
    else if(1 == 1) { echo "Something else\n"; }
    else {
        echo "Anything\n";
    }

    if(false) {
        /* $x = "3"; */ ; // something
    }
    else 
        $filter = $ops = array(2*3);

    $var1 
    += time();

    $var2 
    += 2;

    $a = 2*3;

    class AClass {
        public $a;
        var $b = "b";
        private $c = "b"; 
        protected $d = "b";

        function __construct() {
            echo "in Base class\n";
        }

        function foo() {
            return "hi";
        }
    }

                        $_normalization_xsl = <<<XSL
        <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ms="urn:schemas-microsoft-com:xslt" xmlns:dt="urn:schemas-microsoft-com:datatypes">
           <xsl:output method="xml" />
           <xsl:template match="/">
                <Property name='' type='class' class='DatabaseObjectTask'>
                    <Property name='id' type='string'><xsl:value-of select="/Property/Property/@TaskID" /></Property>
                    <Property name='name' type='string'><xsl:value-of select="/Property/Property/@TaskName" /></Property>
                    <Property name='description' type='string'><xsl:value-of select="/Property/Property/TaskDescription" /></Property>
                    <Property name='status_id' type='string'><xsl:value-of select="/Property/Property/@TaskStatusID" /></Property>
                    <Property name='status_name' type='string'><xsl:value-of select="/Property/Property/@TaskStatusName" /></Property>
                    <Property name='need_id' type='string'><xsl:value-of select="/Property/Property/@NeedID" /></Property>
                    <Property name='date_added' type='string'><xsl:value-of select="/Property/Property/@TaskDateAdded" /></Property>
                    <Property name='date_last_updated' type='string'><xsl:value-of select="/Property/Property/@TaskDateLastUpdated" /></Property>
                    <Property name='date_completed' type='string'><xsl:value-of select="/Property/Property/@TaskDateCompleted" /></Property>
                    <Property name='date_due' type='string'><xsl:value-of select="/Property/Property/@TaskDateDue" /></Property>
                    <Property name='resource_id' type='string'><xsl:value-of select="/Property/Property/@ResourceID" /></Property>
                    <Property name='resource_name' type='string'><xsl:value-of select="/Property/Property/@ResourceName" /></Property>
                    <Property name='sprint_id' type='string'><xsl:value-of select="/Property/Property/@SprintID" /></Property>                 
                    <Property name='percent_complete' type='string'><xsl:value-of select="/Property/Property/@TaskPercentComplete" /></Property>
                    <Property name='work' type='string'><xsl:value-of select="/Property/Property/@TaskWork" /></Property>
                    <Property name='work_remaining' type='string'><xsl:value-of select="/Property/Property/@TaskWorkRemaining" /></Property>
                    <Property name='priority_id' type='string'><xsl:value-of select="/Property/Property/@TaskPriorityID" /></Property>
                    <Property name='priority_name' type='string'><xsl:value-of select="/Property/Property/@TaskPriorityName" /></Property>                   
                    <Property name='date_started' type='string'><xsl:value-of select="/Property/Property/@TaskDateStarted" /></Property>
                    <Property name='creator_user_id' type='string'><xsl:value-of select="/Property/Property/@CreatorUserID" /></Property>
                    <Property name='creator_name' type='string'><xsl:value-of select="/Property/Property/@UserDisplayName" /></Property>
                </Property>
             </xsl:template>
         </xsl:stylesheet>       
XSL;
    class BClass extends AClass {
        private function _load_normalization_xsl()   
            {
                        $this->_normalization_xsl = "<?xml version='1.0' encoding=\"ISO-8859-1\"?>";
        }
        function __construct() {
            echo "in Sub class\n";
            parent::__construct();
        }

        function foo() {
            return "low";
        }
    }

    $b = new BClass();
    $b->foo();

    $a_variable = 
    "some string";
    $a_variable
    = "some string";

    $SupportLevelInfo['sla_units'] = $row['default_sla_units'];
    $SupportLevelInfo['support_level_description'] = 
    $row['support_level_description'];

    $strCollation = "str";
    echo '            <th>' . "\n"   
    . '                &nbsp;' . $strCollation 
    . '            </th>' . "\n"; 

    $heredoc = <<<STR
        <xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ms="urn:schemas-microsoft-com:xslt" xmlns:dt="urn:schemas-microsoft-com:datatypes">
           <xsl:output method="xml" />
           <xsl:template match="/">
STR;
    
    echo $heredoc;
?>
