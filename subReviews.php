<?php
   session_start();

   require( '../environment.php');
   if(! $gSecurity->dbConnect()) {
      print '<html><body>' . mysql_error() . '</body></html>';
      die;
   }
   $isLoggedIn = (( $userName = $gSecurity->getLoggedInUser()) !== FALSE );

   include("classes/wReviews.php");
   $reviews = new Reviews();
   $reviews->setAdmin( $isLoggedIn );
   if( isset( $_GET['err'] )) {
      $reviews->checkError();
   }
   elseif( is_numeric( $_REQUEST['BookID'] )) {
      $reviews->handleSubmit( "catalog.php?BookID=$_REQUEST[BookID]", $_SERVER['SCRIPT_NAME'] );
   }
   unset($reviews);
?>
