<?php

   /*

      $Header: wReviews.php  Revision:1.4  Saturday, November 10, 2007 9:18:32 AM  JohnB $

      $Log3: C:\Documents and Settings\jwbrooking\My Documents\Computer Work\QVCS Archives\moonpiepress.com\www\classes\wReviews.qiq $
      
        Widget-based Reviews object.
      
      Revision 1.4  by: JohnB  Rev date: 11/10/2007 9:18:32 AM
        Small fix for quote doubling problem.
      
      Revision 1.3  by: JohnB  Rev date: 10/10/2007 5:48:47 PM
        Fixed up some previously unnoticed declaration warnings.
      
      Revision 1.2  by: JohnB  Rev date: 7/20/2006 10:29:32 PM
        Added "new window will open" text.
      
      $Endlog$

   */

   require_once 'widget.php';
   require_once 'htmlOutput.php';

   class Reviews extends dataWidget {

      /* mySqlTable properties */

      protected $tableName = 'reviews';
      protected $arrTableDef = array( 'ReviewID'      => 'TINYINT(4) UNSIGNED PRIMARY KEY AUTO_INCREMENT'
                                    , 'BookID'        => 'TINYINT(4) UNSIGNED REFERENCES books ( BookID )'
                                    , 'ReviewBy'      => 'VARCHAR(128)'
                                    , 'ReviewText'    => 'TEXT'
                                    , 'MoreURL'       => 'VARCHAR(255)'
                                    , 'Entered'       => 'TIMESTAMP'
                                    , 'Modified'      => 'TIMESTAMP'
                                    );
      protected $keyFieldName = 'ReviewID';

      /* dataWidget properties */

      protected $name = 'review';

      public function __constructor() {
         parent::__constructor();
      }

      protected function printData( $viewName, $invokedFor, $row = FALSE ) {
         if( $invokedFor == self::DATA_ROW ) {
            $text = str_replace( "''", "'", $row['ReviewText'] )
         ?>
            <div class='review'>
               <p class='reviewer'>
                  <?php
                     if($this->isAdmin()) {
                        print "<a href='"
                            . $this->editLink( $row['ReviewID'] )
                            . "'><img src='/images/edit.gif' border='0'/></a>";
                        print "<a href='"
                            . $this->deleteLink( $row['ReviewID']
                                               , "subReviews.php?BookID=$row[BookID]"
                                               )
                            . "'><img src='/images/CutX.gif' border='0'/></a>";
                     }
                  ?>
                  by <?= htmlOutput::output( $row['ReviewBy'] ) ?>
               </p>
               <p class='reviewText'>
                  <?= htmlOutput::output( $text ) ?>
               </p>
               <?php
               if( $row['MoreURL'] ) {
                  print "<p class='more'><a href='http://$row[MoreURL]' target='_blank'>Read more...</a> <i>(a new window will open)</i></p>";
               }
            ?></div><?php
         }
      }

      protected function printForm( $row, $fkValues ) {
      ?>
         <input type='hidden' name='ReviewID' value='<?= $row['ReviewID'] ?>'/>
         <input type='hidden' name='BookID' value='<?= $fkValues['BookID'] ?>'/>

         <p>Reviewed By: <input name='ReviewBy' size='40' maxlength='255' value='<?= str_replace( "'", "&apos;", isset( $row['ReviewBy'] ) ? $row['ReviewBy'] : '' ); ?>'></p>

         <p>Review Text:<br/>
         <textarea name='ReviewText' rows='25' cols='60'><?= isset( $row['ReviewText'] ) ? $row['ReviewText'] : '' ?></textarea>
         </p>

         <p>Full review at http://<input name='MoreURL' size='40' maxlength='255' value='<?= isset( $row['MoreURL'] ) ? $row['MoreURL'] : '' ?>'></p>

      <?php
      }

      protected function buildSubmitArray() {
         $retArray = array();
         if( is_numeric( $_POST['BookID'] )) {
            $retArray = array( 'BookID'     => $_POST['BookID']
                             , 'ReviewBy'   => $_POST['ReviewBy']
                             , 'ReviewText' => $this->cleanHTML( $_POST['ReviewText'] )
                             , 'MoreURL'    => $_POST['MoreURL']
                             );
         }
         return $retArray;
      }

      public function countReviews( $bookID ) {
         return $this->readValue( 'COUNT(*)', "BookID = $bookID" );
      }

      public function readForBook( $bookID ) {
         if( is_numeric( $bookID )) {
            return parent::readRows( "Entered", "BookID = $bookID" );
         }
         else {
            return false;
         }
      }

   } // class Samples

?>
