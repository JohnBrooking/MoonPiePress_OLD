<?php

require_once("mySqlTable.php");

class Links extends mySqlTable {

   protected $tableName = "links";
   protected $keyFieldName = "LinkID";
   protected $arrTableDef = array( "LinkID"       => "TINYINT(4) UNSIGNED PRIMARY KEY AUTO_INCREMENT"
                                 , "SequenceNum"  => "TINYINT(4) UNSIGNED"
                                 , "Address"      => "VARCHAR(80)"
                                 , "Description"  => "VARCHAR(256)"
                                 );

   public function insertRow( $address, $descr, $top = false ) {
      $retVal = false;
      $nextSeq = 1;

      if( $top ) {
         $retVal = $this->executeAction( "UPDATE links SET SequenceNum = SequenceNum + 1" );
      }
      else {
         $retVal = $nextSeq = $this->readValue( "MAX(SequenceNum) + 1" );
      }

      if( $retVal ) {
         $retVal = parent::insertRow( array( "SequenceNum" => $nextSeq
                                           , "Address" => $address
                                           , "Description" => $descr
                                           ));
      }
      return $retVal;
   }

};

?>
