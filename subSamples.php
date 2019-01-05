<?php
   session_start();

   require( '../environment.php');
   if(! $gSecurity->dbConnect()) {
      print '<html><body>' . mysql_error() . '</body></html>';
      die;
   }
   $isLoggedIn = (( $userName = $gSecurity->getLoggedInUser()) !== FALSE );
   include("classes/wSamples.php");
   $samples = new Samples();
   $samples->setAdmin( $isLoggedIn );
   if( isset( $_GET['err'] )) {
      $samples->checkError();
   }
   elseif( is_numeric( $_REQUEST['bookID'] )) {
      $samples->handleSubmit( "catalog.php?BookID=$_REQUEST[bookID]", $_SERVER['SCRIPT_NAME'] );
   }
   unset($samples);
?>
