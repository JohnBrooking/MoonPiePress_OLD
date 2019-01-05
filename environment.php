<?php

   // Include the security module, use path appropriate to situation
   include 'classes/security.php';

   // Instantiate the object, and set it as required

   $gSecurity = new EnvironmentSecurity();

   $gSecurity->urlRoot     ( 'http://www.moonpiepress.com' );
   $gSecurity->urlLogin    ( 'http://www.moonpiepress.com/login.php' );
   $gSecurity->MySQLhost   ( 'localhost' );
   $gSecurity->MySQLuser   ( 'moonpiepress' );
   $gSecurity->MySQLpwd    ( 'poetic' );
   $gSecurity->MySQLdb     ( 'moonpiepress' );
   $gSecurity->LoginUserVar( 'user' );
   $gSecurity->LoginPwdVar ( 'pwd' );
   $gSecurity->LoginPwdVal ( 'moxie' );

?>
