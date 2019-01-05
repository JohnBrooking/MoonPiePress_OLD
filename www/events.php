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
   include("functions/bb2html.php");
   include("functions/dateUtils.php");

   $events = new Events();
   $events->setUser( $userName );
   $events->setAdmin( $isLoggedIn );

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>

   <meta name="Author" content="John Brooking">
   <meta name="Keywords" content="Maine, poetry, chapbooks, Alice Persons, Nancy Henry, reviews, poems">

   <title>Moon Pie Press - Readings / Events</title>

   <link href="site.css" rel="stylesheet" type="text/css">

   <style type="text/css">

      /* Page Overrides */

      body {
         background-image: url('images/headerEvents.gif');
      }

      #header {
         background-color: rgb(167,185,124);
      }

      #navbar tr td.current {
         color: rgb(167,185,124);
         border-right: none;
      }

      #past, #future {
         margin-bottom: 1em;
      }

      #past {
         background-color: #EEE;
         padding: 5px;
      }

      #past .header {
         font-weight: bold;
         color: #888;
         font-size: larger;
         margin: 0;
      }

      #future .header {
         font-weight: bold;
         color: rgb(167,185,124);
         font-size: larger;
         margin-top: 2em;
      }

      .event {
         margin-top: 1em;
      }

      .event p {
         margin: 0;
      }

      .event p.date {
         font-weight: bold;
         color: rgb(0,102,153);
      }

      .event p.title {
         font-style: italic;
         margin-bottom: .5em;
      }

   </style>

</head>

<body>

   <div id="header"></div>

   <!-- Navigation -->

   <table id="navbar" cellspacing="0" cellpadding="0">
      <tr class="row1">
         <td><a href="index.htm"                   >HOME            </a></td>
         <td><a href="about.htm"                   >ABOUT US        </a></td>
         <td><a href="writers_artists.htm"         >POETS           </a></td>
         <td><a href='artwork.htm'                 >ARTISTS         </a></td>
         <td><a href="catalog.php"                 >CATALOG         </a></td>
         <td><a href="events.php"                >READINGS/EVENTS</a></td>
         <td class='last'><a href='TakeHeart.htm'>TAKE HEART     </a></td>
      </tr>
   </table>

   <!-- Content -->

   <div id="body">

      <?php

         // Display error message, if there is one
         if( isset( $G_GET['err'] ) && $_GET['err'] ) {
            print "<p class='error'>$_GET[err]</p>";
         }

         // UPCOMING EVENTS

         ?>
            <div id='future'>
            <p class='header'>UPCOMING EVENTS</p>
         <?php

         $rs = $events->readUpcoming();
         while( $row = mysql_fetch_array( $rs, MYSQL_ASSOC )) {
            $dateStr = db2Out( $row['DateTime'] );
            $descr = bb2html( $row['Description'] );
            $descr = vivifyLinks( $descr );
            ?>
            <div class='event'>
               <p class='date'><?= $dateStr ?></p>
               <p class='title'><?= $row['Title'] ?></p>
               <p class='descr'>
                  <?php if( $isLoggedIn ) { ?>
                     <a href='events.php?cid=<?= $row['EventID'] ?>#form'><img src='/images/edit.gif' alt='edit' title='edit' border='0'></a>
                     <a href='subEvents.php?del=<?= $row['EventID'] ?>'
                        onClick="return confirm( 'Are you sure you want to delete this event?' );"
                     >
                        <img src='/images/CutX.gif' alt='delete' title='delete' border='0'>
                     </a>
                  <?php } ?>
                  <?= $descr ?>
               </p>
            </div>
            <?php
         } // while $row
         mysql_free_result($rs);
         print '</div>';

         // PAST EVENTS

         ?>
            <div id='past'>
            <p class='header'>RECENT EVENTS</p>
         <?php

         $rs = $events->readPastN(90);
         while( $row = mysql_fetch_array( $rs, MYSQL_ASSOC )) {
            $dateStr = db2Out( $row['DateTime'] );
            $descr = bb2html( $row['Description'] );
            $descr = vivifyLinks( $descr );
            ?>
            <div class='event'>
               <p class='date'><?= $dateStr ?></p>
               <p class='title'><?= $row['Title'] ?></p>
               <p class='descr'>
                  <?php if( $isLoggedIn ) { ?>
                     <a href='events.php?cid=<?= $row['EventID'] ?>#form'><img src='/images/edit.gif' alt='edit' title='edit' border='0'></a>
                     <a href='subEvents.php?del=<?= $row['EventID'] ?>'
                        onClick="return confirm( 'Are you sure you want to delete this event?' );"
                     >
                        <img src='/images/CutX.gif' alt='delete' title='delete' border='0'>
                     </a>
                  <?php } ?>
                  <?= $descr ?>
               </p>
            </div>
            <?php
         } // while $row
         mysql_free_result($rs);
         print '</div>';

         if( $isLoggedIn ) { ?>

         <a name='form'></a>
         <form action="subEvents.php" method="POST">
            <hr/>

            <b><?= ( isset( $_GET['cid'] ) && is_numeric($_GET['cid'])) ? 'Update This' : 'Add New' ?> Event:</b><br/>

            <?php
               $title = '';
               $datetime = '';
               $descr = '';

               if(( isset( $_GET['cid'] ) && is_numeric( $_GET['cid'] ))) {

                  print "<input type='hidden' name='id' value='$_GET[cid]'>";

                  $row = $events->readSingle( '', $_GET['cid'] );
                  $title = $row['Title'];
                  $descr = $row['Description'];
                  $datetime = db2Inp( $row['DateTime'] );
               }

            ?>

            Title: <input name="title" size="50" maxlength="127" value="<?= $title ?>"><br/>
            Date & Time: <input name="datetime" size="30" value="<?= $datetime ?>"> (m/d/yy h:mm AM/PM)<br/>
            Description:<br/>
            <textarea name="descr" rows="4" cols="50"><?= $descr ?></textarea><br/>
            <input type="submit" value="<?= ( isset( $_GET['cid'] ) && $_GET['cid'] ) ? 'Save Changes' : 'Submit' ?>">
            <?php
               if( isset( $_GET['cid'] ) && is_numeric( $_GET['cid'] )) {
                  print "<a href='events.php'>Cancel</a>";
               }
            ?>
         </form>

      <?php } ?>

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

      unset($events);
   ?>

</body>

</html>
