<?php

   /*

      $Header$

      $Log3$

   */

   include_once( "myStrptime.php" );

   function inp2DB( $inpDate ) {
      $dateStr = false;
      if( $dtArray = myStrptime( $inpDate )) {
         $dateStr = sprintf( "%4d-%02d-%02d", $dtArray[5] + 1900
                           , $dtArray[4], $dtArray[3]
                           );
         //$dateStr = ( $dtArray[5] + 1900 ) . "-$dtArray[4]-$dtArray[3]";
         if( $dtArray[2] || $dtArray[1] ) {
            $dateStr .= sprintf( " %02d:%02d", $dtArray[2], $dtArray[1] ); //" $dtArray[2]:$dtArray[1]";
         }
      }
      return $dateStr;
   }

   function db2Inp( $dbDate ) {
      $dateStr = false;
      $dateTime = explode( ' ', $dbDate );
      $datePart = explode( '-', $dateTime[0] );
      $timePart = explode( ':', $dateTime[1] );
      if( $datePart[2] ) {
         $dateStr = "$datePart[1]/$datePart[2]/$datePart[0]";
         if( $timePart[0] + $timePart[1] ) {
            $dateStr .= " ";
            $ampm = "AM";
            if( $timePart[0] == 0 ) {
               $dateStr .= "12";
            }
            elseif( $timePart[0] > 12 ) {
               $dateStr .= ( $timePart[0] - 12 );
               $ampm = "PM";
            }
            else {
               $dateStr .= trim( $timePart[0], ' 0' );
            }
            if( $timePart[1] != "00" ) {
               $dateStr .= ":$timePart[1]";
            }
            $dateStr .= " $ampm";
         }
      }
      return $dateStr;
   }

   function db2Out( $dbDate ) {
      $dateStr = false;
      $dateTime = explode( ' ', $dbDate );
      $datePart = explode( '-', $dateTime[0] );
      $timePart = explode( ':', $dateTime[1] );
      $time = mktime( $timePart[0], $timePart[1], $timePart[2]
                    , $datePart[1], $datePart[2], $datePart[0]
                    );
      $dateStr = strftime( '%A, %B %e, %Y', $time );  // Note: %e not supported
      if( $timePart[0] + $timePart[1] ) {             // by Windows
         if( $timePart[0] > 12 ) {
            $ampm = " PM";
            $timePart[0] -= 12;
         }
         else {
            $ampm = " AM";
            if( $timePart[0] == 0 ) { $timePart[0] = 12; }
         }
         $dateStr .= '; ' . trim( $timePart[0], ' 0' );
         if( $timePart[1] + 0 ) { $dateStr .= ":$timePart[1]"; }
         $dateStr .= $ampm;
      }
      return $dateStr;
   }

   /* TESTING
   print "<html><body>";
   $testArr = array( "10/13"
                   , "1/1/07 9 p.m."
                   , "3/15/02 8 AM"
                   , "3/15/02, 8 AM"
                   , "11/5 4:13 PM"
                   , "1/1/01 12:17"
                   , "this is not a date!"
                   );
   foreach( $testArr as $s ) {
      $db = inp2DB($s);
      $inp = db2Inp($db);
      $out = db2Out($db);
      $db2 = inp2DB($inp);
      print "<p><b>$s</b><br/>inp2DB: \"$db\"<br/>db2Inp: \"$inp\"<br/>db2Out: \"$out\"<br/>inp2DB: \"$db2\"</p>";
   }
   print "</body></html>";
   */

?>
