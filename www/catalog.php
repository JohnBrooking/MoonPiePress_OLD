<?php

      /*

         $Header: catalog.php  Revision:1.4  Saturday, December 01, 2007 9:35:24 PM  JohnB $

         $Log3: C:\Documents and Settings\jwbrooking\My Documents\Computer Work\QVCS Archives\moonpiepress.com\www\catalog.qiq $

           The catalog summary page

		 Revision 1.6  by: JohnB  Rev date: 12/28/2017 (manually versioned)
		   Fixed book title sorting to avoid apostrophes at the start of the title (or anywhere else)
		   
         Revision 1.4  by: JohnB  Rev date: 12/1/2007 9:35:24 PM
           Added book detail functionality that was formerly on the Books page to
           here.

         Revision 1.3  by: JohnB  Rev date: 11/17/2007 12:33:10 PM
           Added note about paying by PayPal, credit card, or check.

         Revision 1.2  by: JohnB  Rev date: 11/10/2007 11:00:48 AM
           Modified navigation bar for new catalog page.

         $Endlog$

      */

   session_start();

   require( '../environment.php');
   if(! $gSecurity->dbConnect()) {
      print '<html><body>' . mysql_error() . '</body></html>';
      die;
   }
   $isLoggedIn = (( $userName = $gSecurity->getLoggedInUser()) !== FALSE );
   $gSecurity->setRedirect();                // Set login/logout to return here

   require_once 'classes/Books.php';
   require_once 'classes/Authors.php';
   require_once 'classes/wReviews.php';
   require_once 'classes/wSamples.php';

   $books = new Books();
   $revs = new Reviews();
   $samples = new Samples();

   $books->setAdmin( $isLoggedIn );
   $revs->setAdmin( $isLoggedIn );
   $samples->setAdmin( $isLoggedIn );

   $htmlOutput = new htmlOutput();

   ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

   <head>

      <meta name='Author' content='John Brooking'>

      <title>Moon Pie Press - Our Catalog</title>

      <link href='site2.css' rel='stylesheet' type='text/css'/>

      <style type='text/css'>

         /* Page Overrides */

         #header, #navbar, #body {
            background-image: url('images/headerBooks.gif');
         }

         #header, #navbar .current, #navbar .highlight {
            background-color: rgb(102,102,153);
         }

         h2 {
            margin-bottom: 8px;
         }

         #login {
            margin: 0;
            position: absolute;
            top: 440px;
            left: 120px;
         }

         /* Page specific */

         #body {
            margin-right: 2em;
         }

         #body td, #body th {
            font-size: smaller;
         }

         form {
            margin: 0;
         } /* Each PayPal button is a form. We don't want any margin on it. */

         /* Catalog focus row */

         #focus {
            background-color: rgb(102,102,153);
            padding: 6px;
            color: white;
            font-weight: bold;
         }

         #focus a {
            color: white;
         }

         #focus img {
            margin-top: -1em;
            margin-left: -1em;
            margin-right: 1em;
            border: 1px solid white;
         }

         /* Catalog rows */

         #catalog {
            margin-bottom: 1em;
         }

         #catalog th, #catalog th a {
            padding: 2px;
            color: white;
            text-decoration: none;
         }

         #catalog tr a:hover {
            text-decoration: underline;
         }

         #catalog tr.r0 a:hover {
            color: rgb(102,102,153);
         }

         #catalog tr.r1 a:hover {
            color: white;
         }

         #catalog td {
            padding-left: 5px;
         }

         #catalog td a {
            color: black;
            text-decoration: none;
         }

         #catalog thead tr {
            background-color: #666;
         }

         #catalog tbody tr.r1 {
            background-color: rgb(194,194,214);
         }

         /* Focus book details */

         #focus_details {
            font-size: smaller;
            margin: 2em 1em 1em 300px;
         }

         #focus_details a.jump {
            font-weight: bold;
            text-decoration: none;
         }

         #focus_details a.jump:hover {
            text-decoration: underline;
         }

         p#title {
            color: #669;
            font-size: 175%;
            font-weight: bold;
         }

         /* Reviews */


         #reviews, #samples {
            padding-top: 2em;
            clear: both;
         }

         .reviewer {
            background-color: #DDD;
            padding: 2px;
            margin-right: 1em;
         }

         .preview {
            background-color: #FFC;
            border: 1px dashed gray;
         }

         /* Samples */

         #samples {
            /*border-top: 1px solid gray;*/
            padding-top: 1em;
            font-weight: bold;
         }

         #samples h2 {
            margin-top: 1em;
            margin-right: .5em;
            background-color: #ccd;
            padding: 3px;
         }

         #samples .title {
            font-size: larger;
         }

      </style>

   </head>

   <body>

      <a name='top'></a><div id='header'>&nbsp;</div> <!-- Show background color -->

      <!-- Navigation -->

      <div id='navbar'>

         <table cellspacing='0' cellpadding='0' border='0'>
            <tr class="row1">
               <td><a href="index.htm"              >HOME            </a></td>
               <td><a href="about.htm"              >ABOUT US        </a></td>
               <td><a href="writers_artists.htm"    >POETS           </a></td>
               <td><a href='artwork.htm'            >ARTISTS         </a></td>
               <td  class="current"                 >CATALOG             </td>
               <td><a href="events.php"                >READINGS/EVENTS</a></td>
			   <td><a href="DeepWater.htm"             >DEEP WATER</a>     </td>
               <td class='last'><a href='TakeHeart.htm'>TAKE HEART     </a></td>
            </tr>
         </table>

      </div>

      <div id='body'>

         <br> <!-- Without this, Firefox leaves a gap -->

         <h2>Our Latest Book</h2>

         <?php $books->displayData( 'catalogFocus' ); ?>

         <h2>The Complete Catalog</h2>

         <p style='font-size: smaller; margin-top: 0'>Sort the catalog by clicking the column headers. Click on a title to see more details, including reviews and a sample. Click on an author to read their bio. All links open a new window.</p>

         <p style='font-weight: bold;'>Please note that PayPal or a credit card may be used to order online. <u>You can also pay by check</u>, payable to Moon Pie Press, 16 Walton Street, Westbrook, ME  04092.</p>

         <p style='font-weight: bold; font-size: larger; color: red;'>ALL PRICES INCLUDE POSTAGE AND HANDLING.</p>
         
			<?php

            $sortParam = isset( $_GET['sort'] ) ? $_GET['sort'] : 'Title';
            $sortField = '';

            switch( $sortParam ) {
               case 'Title': $sortField = "REPLACE( Title, '\'', '' )"; break; 			// Avoid sorting Ted Bookey's
               case '-Title': $sortField = "REPLACE( Title, '\'', '' ) DESC"; break;	// "'Stitiously Speaking" at the top.
               case 'Author': $sortField = 'AuthorSort, Copyright, Entered'; break;
               case '-Author': $sortField = 'AuthorSort DESC, Copyright, Entered'; break;
               case 'Published': $sortField = 'Copyright, Entered'; break;
               case '-Published': $sortField = 'Copyright DESC, Entered DESC'; break;
               default: $sortField = 'Title'; break;
            }

            $books->setSortParam( $sortParam );
            $books->displayData( 'catalogList', array( $sortField ));
            $books->printViewCart();

         ?>

        <!-- Focus Book -->

         <a name='details'></a>
         <?php
            if( isset( $_GET['BookID'] ) && is_numeric( $_GET['BookID'] )) {
               print '<h1>Book Details</h1>';
               $books->displayData( 'focusByID', array( $_GET['BookID'] ));
            }
            else {
               print '<h1>Our Latest Book</h1>';
               $books->displayData( 'focusLatest' );
            }

            if( $sampCount = $samples->countSamples( $books->currBookID )) {
               print " <a class='jump' href='#samples'>Read a sample</a>";
            }
         ?>

         <!-- Reviews -->

         <?php

            if( $revs->countReviews( $books->currBookID )) {
               print "<div id='reviews'><h2>Reviews for <i>{$books->currTitle}</i></h2>";
               $revs->displayData( '', '', "BookID = {$books->currBookID}" );
               print "</div>";

            }

            if($revs->isAdmin()) {
               $revs->displayForm( 'subReviews.php', "<h2>%s Review</h2>\n"
                                 , 'Add a', 'Edit a'
                                 , array( 'BookID' => $books->currBookID )
                                 );
            }
         ?>

         <!-- Samples -->

         <?php

            if( $sampCount ) {
               print "<div id='samples'><h2>Sample from <i>{$books->currTitle}</i></h2>";
               $samples->displayData( '', '', "bookID = {$books->currBookID}" );
               print "</div>";
            }

            if( $samples->isAdmin() && ( $sampCount == 0 || ( isset( $_GET['edit'] ) && $_GET['edit'] == 'sample' ))) {
               $samples->displayForm( 'subSamples.php'
                     , "<h2>%s Sample</h2>\n", 'Add a', 'Edit a'
                     , array( 'bookID' => $books->currBookID ));
            }

         ?>

         <p style='font-size: smaller;'>Return to <a href='#top'>Catalog</p>

         <?php
            print "<p id='login'>";
            if( $isLoggedIn ) {
               print "<a href='logout.php'>Logout</a>";
            }
            else {
               print "<a href='login.php' style='color: white;'>Login</a>";
            }
            print "</p>";
         ?>

      </div> <!-- #body -->

   </body>

</html>
<?php
   unset($books);
   unset($revs);
   unset($samples);
   unset($htmlOutput);
?>
