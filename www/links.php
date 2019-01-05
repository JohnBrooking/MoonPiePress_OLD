<?php

   session_start();

   require( '../environment.php');
   $gSecurity->setRedirect();                // Set login/logout to return here
   if(! $gSecurity->dbConnect()) {
      print '<html><body>' . mysql_error() . '</body></html>';
      die;
   }
   $isLoggedIn = (( $userName = $gSecurity->getLoggedInUser()) !== FALSE );

   include("classes/Links.php");
   include("classes/SeqCtrl.php");

   $linksTable = new Links();
   $linksTable->setAdmin( $isLoggedIn );

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>

   <meta name="Author" content="John Brooking">

   <title>Moon Pie Press - Links</title>

   <link href="site.css" rel="stylesheet" type="text/css">

   <style type="text/css">

      /* Page Overrides */

      body {
         background-image: url('images/headerAbout.gif');
      }

      #header, #navbar .current, #navbar .highlight {
         background-color: rgb(113,157,150);
      }

      /* Page-specific */

      #links tr td {
         padding: 0 10px 15px 0;
      }

   </style>

</head>

<body>

   <div id="header"></div>

   <!-- Navigation -->

   <table id="navbar" cellspacing="0" cellpadding="0">
      <tr class="row1">
         <td><a href="index.htm"                   >HOME           </a></td>
         <td class="highlight"><a href="about.htm" >ABOUT US       </a></td>
         <td><a href="writers_artists.htm"         >POETS           </a></td>
         <td><a href='artwork.htm'                 >ARTISTS         </a></td>
         <td><a href="catalog.php"                 >CATALOG        </a></td>
         <td><a href="events.php"                >READINGS/EVENTS</a></td>
		 <td><a href="DeepWater.htm"             >DEEP WATER</a>     </td>
         <td class='last'><a href='TakeHeart.htm'>TAKE HEART     </a></td>
      </tr>
      <tr>
         <td>&nbsp;</td>
         <td class="highlight"><a href="http://freshmoonpie.wordpress.com">OUR BLOG</a></td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
      </tr>
      <tr>
         <td>&nbsp;</td>
         <td class="highlight"><a href="contact.htm">CONTACT US</a></td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
      </tr>
      <tr>
         <td>&nbsp;</td>
         <td class="current">LINKS</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
      </tr>
      <tr>
         <td>&nbsp;</td>
         <td class="highlight"><a href="submissions.htm">SUBMISSIONS</a></td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
      </tr>
   </table>

   <!-- Content -->

   <div id="body">

	      <p><b>Here are links to some sites that we like:</b></p>

         <?php

            if( isset( $_GET['err']) && $_GET['err'] ) {
               print "<p class='error'>$_GET[err]</p>";
            }

            $sortControl = new SequenceControl( "links", "LinkID", "SequenceNum"
                                              , "subLinks.php", "classes/sort_%s.gif" );
            if( $rs = $linksTable->readRows( "SequenceNum" )) {
               ?>

               <a name='form'></a>
               <form action="subLinks.php" method="POST">
               <table id="links" cellspacing="0" cellpadding="0">

               <?php while( $row = mysql_fetch_array( $rs, MYSQL_ASSOC )) { ?>
                  <tr valign="top">
                     <?php if( $isLoggedIn ) { ?>
                        <td nowrap="1" class="actions">
                           <?php $sortControl->printControls( $row['LinkID'] ); ?>
                        </td>
                     <?php } ?>
                     <td class="address">
                        <a href="http://<?= $row['Address'] ?>/" target="_blank"><?= $row['Address'] ?></a>
                     </td>
                     <td><?= $row['Description'] ?></td>
                  </tr>

               <?php }

               mysql_free_result($rs);

            } // if opened table
         ?>

      </table>

      <?php if( $isLoggedIn ) { ?>
         <hr/><b>Add New Link:</b><br/>
         Address: <input name="address" size="30"><br/>
         Description: <input name="descr" size="40"><br/>
         <input type="submit" value="Submit">
      <?php } ?>

      </form>

   </div> <!-- #body -->

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

</body>

</html>
