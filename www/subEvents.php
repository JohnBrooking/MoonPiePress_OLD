<?php

   session_start();

   require( '../environment.php');
   $gSecurity->setRedirect();                // Set login/logout to return here
   if(! $gSecurity->dbConnect()) {
      print '<html><body>' . mysql_error() . '</body></html>';
      die;
   }
   $isLoggedIn = (( $userName = $gSecurity->getLoggedInUser()) !== FALSE );

   include("classes/Events.php");
   include("functions/dateUtils.php");

   $errMsg = "";
   $redir = preg_replace( '/\?(err|cid)=.+/', '', $_SERVER['HTTP_REFERER'] );
   $redir = preg_replace( '/#form/', '', $redir );

   if( !$isLoggedIn ) {
      $errMsg = "You are not allowed to use this script!";
   }
   else {

      $eventList = new Events();
      $eventList->setAdmin( $isLoggedIn );

      // Asked to delete?

      if( isset( $_GET['del'] ) && is_numeric( $_GET['del'] )) {
         $result = $eventList->deleteRow( $_GET['del'] );
      }

      // Else, adding or changing

      else {

         // Split date into components and validate them
         //$dtArray = myStrptime( $_POST['datetime'] );
         $dateStr = inp2DB( $_POST['datetime'] );
         if( !$dateStr ) {
            $errMsg = 'Badly-formatted date';
         }
         else {

            $data = array( 'Title'        => $_POST['title']
                         , 'DateTime'     => $dateStr
                         , 'Description'  => $_POST['descr']
                         );
                         //print "<p>ID: \"$_GET[id]\"</p>";
            if( is_numeric( $_POST['id'] )) {
               $result = $eventList->updateRow( $_POST['id'], $data );
            }
            else {
               $result = $eventList->insertRow( $data );
            }

         } // if date string okay

      } // if deleting, adding, or changing

      if( !$result ) {
         //$errMsg = mysql_error();
         $errMsg = $eventList->lastSQL;
      }

      unset( $eventList );

   } // if okay to use script

   if( $errMsg ) {
      $errMsg = "Problem: $errMsg. Please contact the webmaster with the following error message: 'Error " . mysql_errno() . " - " . mysql_error() . "'";
      $redir .= "?err=$errMsg";
   }
   header( "Location: $redir" );
   //print "Redirecting to '$redir'";

?>
