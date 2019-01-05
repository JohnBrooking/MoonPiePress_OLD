<?php

   /*
      $Header: SeqCtrl.php  Revision:1.0  Monday, August 29, 2005 9:32:28 PM  JohnB $

      $Log3: C:\Documents and Settings\Owner\My Documents\John\Computer Work\QVCS Archives\CodeLib\PHP\SeqCtrl.qiq $
      
        A class to facilitate sequencing table rows via a screen interface.
        Requires a set of graphics files for the top, up, down, last, and
        delete icons, and has functions to draw the icon set as well as run
        the database statements to move a row to the indicated position.
      
      Revision 1.0  by: JohnB  Rev date: 8/29/2005 9:34:28 PM
        Initial revision.
      
      $Endlog$

   */


class SequenceControl {

   private $tableName, $IDField, $SeqField, $targetPage, $graphicNamePattern;

   public function SequenceControl( $tableName, $IDField, $SeqField, $targetPage, $graphicNamePattern ) {
      $this->tableName           = $tableName         ;
      $this->IDField             = $IDField           ;
      $this->SeqField            = $SeqField          ;
      $this->targetPage          = $targetPage        ;
      $this->graphicNamePattern  = $graphicNamePattern;
   }

   public function printControls( $idValue ) {
      foreach( array( 'top', 'up', 'down', 'bottom', 'delete' ) as $dir ) {
         $imgName = sprintf( $this->graphicNamePattern, $dir );
         print "<a href='" . $this->targetPage
             . "?id=" . $idValue
             . "&dir=" . $dir . "'>"
             . "<img src='" . $imgName
             . "' alt='Move " . $dir
             . "' title='Move " . $dir
             . "' border='0'></a>";
         if( $dir <> 'delete' ) { print "&nbsp;"; }
      }
   }

   public function moveRow( $idValue, $dir ) {
      $sql1 = ""; $sql2 = "";
      $givenSeq = 0; $maxSeq = 0;
      $retVal = false;

      $tableName          = $this->tableName         ;   // for convenience
      $IDField            = $this->IDField           ;
      $SeqField           = $this->SeqField          ;
      $targetPage         = $this->targetPage        ;
      $graphicNamePattern = $this->graphicNamePattern;

      if( $rs = mysql_query( "SELECT $SeqField FROM $tableName WHERE $IDField = $idValue" )) {
         if( $row = mysql_fetch_array( $rs, MYSQL_NUM )) {

            $givenSeq = $row[0];
            mysql_free_result($rs);

            if( $rs = mysql_query( "SELECT MAX($SeqField) FROM $tableName" )) {
               if( $row = mysql_fetch_array( $rs, MYSQL_NUM )) {

                  $maxSeq = $row[0];
                  mysql_free_result($rs);

                  switch( $dir ) {
                     case 'top':
                        $sql1 = "UPDATE $tableName SET $SeqField = $SeqField + 1 WHERE $SeqField < $givenSeq";
                        $sql2 = "UPDATE $tableName SET $SeqField = 1 WHERE $IDField = $idValue";
                        break;
                     case 'bottom':
                        $sql1 = "UPDATE $tableName SET $SeqField = $SeqField - 1 WHERE $SeqField > $givenSeq";
                        $sql2 = "UPDATE $tableName SET $SeqField = $maxSeq WHERE $IDField = $idValue";
                        break;
                     case 'up':
                        $sql1 = "UPDATE $tableName SET $SeqField = $SeqField + 1 WHERE $SeqField = $givenSeq - 1";
                        $sql2 = "UPDATE $tableName SET $SeqField = $SeqField - 1 WHERE $IDField = $idValue";
                        break;
                     case 'down':
                        $sql1 = "UPDATE $tableName SET $SeqField = $SeqField - 1 WHERE $SeqField = $givenSeq + 1";
                        $sql2 = "UPDATE $tableName SET $SeqField = $SeqField + 1 WHERE $IDField = $idValue";
                        break;
                     default:
                        $sql1 = "SELECT 'Bad parameter' FROM links";
                        $sql2 = "SELECT 'Bad parameter' FROM links";
                  }

                  if( mysql_query( $sql1 )) {
                     if( mysql_query( $sql2 )) {
                        $retVal = true;
                     }
                  }

               } // if fetched max sequence
            } // if opened recordset
         } // if fetched original value
      } // if opened recordset

      return $retVal;

   } // function moveRow

}
