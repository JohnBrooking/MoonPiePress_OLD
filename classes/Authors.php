<?php

   require_once("mySqlTable.php");

   class Authors extends mySqlTable {

      protected $tableName = 'authors';
      protected $keyFieldName = 'AuthorID';
      protected $arrTableDef = array( 'authorID'   => 'TINYINT(4) UNSIGNED PRIMARY KEY AUTO_INCREMENT'
                                    , 'nameLast'   => 'VARCHAR(25)'
                                    , 'nameFirstMI'=> 'VARCHAR(40)'
                                    , 'imageURL'   => 'VARCHAR(255)'
                                    , 'bioText'    => 'TEXT'
                                    );

      public static function jumpLabel( $first, $last ) {
         $first = preg_replace( '/[^\w]/', '', $first );
         $last = preg_replace( '/[^\w]/', '', $last );
         return substr( $first, 0, 4 ) . substr( $last, 0, 4 );
      }

   }

?>
