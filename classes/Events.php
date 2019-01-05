<?php

   require_once("mySqlTable.php");

   class Events extends mySqlTable {

      protected $tableName = "events";
      protected $keyFieldName = "EventID";
      protected $arrTableDef = array( "EventID"      => "SMALLINT(6) UNSIGNED PRIMARY KEY AUTO_INCREMENT"
                                    //, "AuthorID"     => "TINYINT UNSIGNED REFERENCES Authors ( AuthorID )"
                                    , "Title"        => "VARCHAR(127)"
                                    , "DateTime"     => "DATETIME"
                                    , "Description"  => "TEXT"
                                    );       // Field definition

      public function readRowsDefault() {
         return parent::readRows( "DateTime, Title"
                                , "DateTime >= DATE_SUB( CURDATE(), INTERVAL 90 DAY )"
                                , "EventID, Title, Description, DateTime, IF( DateTime >= CURDATE(), 'future', 'past' ) AS future"
                                );
      }

      public function readPastN( $n = 30 ) {
         return $this->readRows( "DateTime DESC, Title"
                                , "DateTime >= DATE_SUB( CURDATE(), INTERVAL $n DAY ) AND DateTime < CURDATE()"
                                );
      }

      public function readUpcoming() {
         return $this->readRows( "DateTime, Title"
                                , "DateTime >= CURDATE()"
                                );
      }

   } // class Events

?>
