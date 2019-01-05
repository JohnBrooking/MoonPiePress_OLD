<?php

   /*

      $Header: wSamples.php  Revision:1.3  Saturday, November 10, 2007 9:18:32 AM  JohnB $

      $Log3: C:\Documents and Settings\jwbrooking\My Documents\Computer Work\QVCS Archives\moonpiepress.com\www\classes\wSamples.qiq $
      
        Widget-based Samples object.
      
      Revision 1.3  by: JohnB  Rev date: 11/10/2007 9:18:32 AM
        Small fix for quote doubling problem.
      
      Revision 1.2  by: JohnB  Rev date: 10/10/2007 5:49:04 PM
        Fixed up some previously unnoticed declaration warnings.
      
      Revision 1.1  by: JohnB  Rev date: 7/20/2006 10:12:32 PM
        Final bug fixes.
      
      $Endlog$

   */

   require_once "widget.php";
   require_once 'htmlOutput.php';

   class Samples extends dataWidget {

      /* mySqlTable properties */

      protected $tableName = 'samples';
      protected $arrTableDef = array( 'sampleID'   => 'TINYINT(4) UNSIGNED PRIMARY KEY AUTO_INCREMENT'
                                    , 'bookID'     => 'TINYINT(4) UNSIGNED REFERENCES books ( BookID )'
                                    , 'Title'      => 'VARCHAR(80)'
                                    , 'text'       => 'TEXT'
                                    );
      protected $keyFieldName = 'sampleID';

      /* dataWidget properties */

      protected $name = 'sample';

      public function __constructor() {
         parent::__constructor();
      }

      protected function printData( $viewName, $invokedFor, $row = FALSE ) {
         if( $invokedFor == self::DATA_ROW ) {
            $text = str_replace( "''", "'", $row['text'] )
         ?>
            <div class='sample'>
               <p class='title'>
                  <?php
                  if($this->isAdmin()) {
                     print "<a title='Edit this sample' href='" . $this->editLink( $row['sampleID'] ) . "'><img src='/images/edit.gif' border='0'/></a> ";
                     print "<a title='Delete this sample' href='" . $this->deleteLink( $row['sampleID'], "subSamples.php?bookID=$row[bookID]" ) . "'><img src='/images/CutX.gif' border='0'/></a> ";
                  }
                  print "$row[Title]";
                  ?>
               </p>
               <p class='text'><?= htmlOutput::output( $text ) ?></p>
            </div>
         <?php
         }
      }

      protected function printForm( $row, $fkValues ) {
      ?>
         <input type='hidden' name='bookID' value='<?= $fkValues['bookID'] ?>'/>
         Title: <input name='Title' type='text' size='30' value='<?= isset( $row['Title'] ) ? $row['Title'] : '' ?>'/><br/>
         <br/><textarea name='text' rows='40' cols='60'><?= isset( $row['text'] ) ? $row['text'] : '' ?></textarea><br/>
      <?php
      }

      protected function buildSubmitArray() {
         $retArray = array();
         if( is_numeric( $_POST['bookID'] )) {
            $retArray = array( 'bookID' => $_POST['bookID']
                             , 'Title'  => $_POST['Title']
                             , 'text'   => $_POST['text']
                             );
         }
         return $retArray;
      }

      public function countSamples( $bookID ) {
         return $this->readValue( 'COUNT(*)', "bookID = $bookID" );
      }

   } // class Samples

?>
