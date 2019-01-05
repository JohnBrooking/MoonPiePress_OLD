<?php

require_once("widget.php");
require_once("Authors.php");

class Books extends dataWidget {

   protected $tableName = 'books';
   protected $keyFieldName = 'BookID';
   protected $arrTableDef = array( 'BookID'        => 'TINYINT(4) UNSIGNED PRIMARY KEY AUTO_INCREMENT'
                                 , 'Title'         => 'VARCHAR(50) NOT NULL'
                                 , 'AuthorID'      => 'TINYINT(4) UNSIGNED REFERENCS authors ( AuthorID )'
                                 , 'AuthorListed'  => 'TINYINT(1) UNSIGNED DEFAULT 1'
                                 , 'AltAuthorName' => 'VARCHAR(80)'
                                 , 'ExtraText'     => 'VARCHAR(80)'
                                 , 'Copyright'     => 'MEDIUMINT(4) UNSIGNED'
                                 , 'Price'         => 'DECIMAL(4,2)'
                                 , 'PriceDescr'    => 'VARCHAR(50)'
                                 , 'ISBN'          => 'VARCHAR(20)'
                                 , 'imageURL'      => 'VARCHAR(255) NOT NULL'
                                 , 'thumbnailURL'  => 'VARCHAR(255) NOT NULL'
                                 , 'Entered'       => 'TIMESTAMP'
                                 , 'OnlinePurchase' => 'TINYINT(1) UNSIGNED DEFAULT 1'
                                 );

   protected $addDateName  = 'Entered';

   protected $name = 'Books';

   public $currBookID = 0;    // When displaying focus book, these are set
   public $currTitle = '';    // so caller can find out what it was, if
                              // not being set by $_GET
   private $sortParam = 'Title';

   // Optional array of SQL statements corresponding to multiple views
   // Define as key'd array with view names as keys, statements as values
   protected $viewSQL = array();

   public $widthThumbnail = 75;
   public $widthPrimary = 275;

   function __construct() {
      parent::__construct();

      $baseSQL = "SELECT B.*, IF( ISNULL( B.AuthorID )
                               , B.AltAuthorName
                               , CONCAT( A.nameFirstMI, ' ', A.nameLast )
                               ) AS AuthorDisplay
                           , IF( ISNULL( B.AuthorID )
                               , 'ZZZZ'
                               , CONCAT( A.nameLast, A.nameFirstMI )
                               ) AS AuthorSort
                           , A.nameFirstMI, A.nameLast
                 FROM books B
                   LEFT JOIN authors A
                   ON A.authorID = B.authorID";

      $this->viewSQL = array( 'detailList'   => "$baseSQL ORDER BY BookID"
                            , 'catalogList'  => "$baseSQL ORDER BY \$1"
                            , 'focusByID'    => "$baseSQL WHERE BookID = \$1"
                            , 'focusLatest'  => "$baseSQL ORDER BY BookID DESC LIMIT 1"
                            , 'catalogFocus' => "$baseSQL ORDER BY BookID DESC LIMIT 1"
                            );
   }

   public function printAddToCart( $title, $price ) {
   ?>
      <form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
         <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but22.gif" border="0" name="submit"
            title="Make payments with PayPal - it's fast, free and secure!" alt="[Add to Cart]">
         <input type="hidden" name="add" value="1">
         <input type="hidden" name="cmd" value="_cart">
         <input type="hidden" name="business" value="moonpiepress@yahoo.com">
         <input type="hidden" name="item_name" value="<?= $title ?>">
         <input type="hidden" name="amount" value="<?= $price ?>">
         <input type="hidden" name="no_note" value="1">
         <input type="hidden" name="currency_code" value="USD">
         <input type="hidden" name="lc" value="US">
         <input type="hidden" name="bn" value="PP-ShopCartBF">
      </form>
      <?php
   }

   public function printViewCart() {
   ?>
      <form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
         <input type="hidden" name="cmd" value="_cart">
         <input type="hidden" name="business" value="moonpiepress@yahoo.com">
         <input type="image" src="https://www.paypal.com/images/view_cart.gif" border="0" name="submit"
           title="Make payments with PayPal - it's fast, free and secure!" alt="[View Cart]">
         <input type="hidden" name="display" value="1">
      </form>
   <?php
   }

   public function setSortParam( $param ) {
      $this->sortParam = $param;
   }

   protected function printData( $viewName, $invokedFor, $row = FALSE ) {
      static $rowNum = 0;

      $jumpName = '';
      if( $invokedFor == DataWidget::DATA_ROW ) {
         if( trim( $row['nameLast'] )) {
            $jump = Authors::jumpLabel( $row['nameFirstMI'], $row['nameLast'] );
            $name = stripslashes( $row['AuthorDisplay'] );
            $jumpName = "<a href='writers_artists.htm#$jump'>$name</a>";
         }
         else {
            $jumpName = stripslashes( $row['AuthorDisplay'] );
         }
      }

      switch( $viewName ) {

         case 'detailList':

            switch( $invokedFor ) {

               case DataWidget::DATA_BEGIN:
                  $rowNum = 0;
                  ?>
                     <table id='books' border='0' cellspacing='0' cellpadding='0'>
                        <tr valign='top'>
                  <?php
                  break;

               case DataWidget::DATA_ROW:
                  ?>
                     <td width='50%' class='col<?= $rowNum ?>'>
                        <a href='<?= $_SERVER['SCRIPT_NAME'] ?>?BookID=<?= $row['BookID'] ?>'>
                           <img src='<?= $row['thumbnailURL'] ?>'
                                width='<?= $this->widthThumbnail ?>'
                                alt="<?= $row['Title'] ?><?= $authText ?>"
                                title="<?= $row['Title'] ?><?= $authText ?>"
                        ></a><br/>
                        <a href='<?= $_SERVER['SCRIPT_NAME'] ?>?BookID=<?= $row['BookID'] ?>'><b><?= $row['Title'] ?></b>, by <?= $row['AuthorDisplay'] ?></a>
                     </td>
                  <?php
                  if(( $rowNum = ( $rowNum + 1 ) % 2 ) == 0 ) {
                     print "</tr><tr valign='top'>";
                  }
                  break;

               case DataWidget::DATA_END:
                  print "</tr></table>\n";
                  break;

            } // default view, switch row type

            break;

         case 'focusByID':
         case 'focusLatest':

            if( $invokedFor == DataWidget::DATA_ROW ) {

               ?>
                  <div class='drop-shadow'>
                     <img src='<?= $row['imageURL'] ?>' width='<?= $this->widthPrimary; ?>'
                          alt='<?= $row['Title'] ?>' title='<?= $row['Title'] ?>'
                     >
                  </div>

                  <div id='focus_details'>
               <?php

               $this->currBookID = $row['BookID'];
               $this->currTitle = $row['Title'];
               if( trim($row['nameLast']) ) {
                  $authText = "by $jumpName";
               }
               else {
                  $authText = $row['AuthorDisplay'];
               }

               print "<p id='title'>$row[Title]</p>\n<p>";
               if( trim( $row['nameLast'] )) { print 'by '; }
               print $jumpName;
               if( $row['Copyright'] ) { print " &#8211; copyright $row[Copyright]"; }
               print "</p>\n";

               if( $row['ISBN'] ) { print "<p>ISBN $row[ISBN]</p>"; }
               if( $row['ExtraText'] ) { print "<p>$row[ExtraText]</p>"; }

               print "<p>$row[PriceDescr]</p>";

               if( $row['Price'] && $row['OnlinePurchase'] ) {
                  $this->printAddToCart( $row['Title'], $row['Price'] );
                  $this->printViewCart();
               }

               print "</div> <!-- id='focus_details' -->";

            } // if $invokedFor = data
            break;

         case 'catalogFocus':

            switch( $invokedFor ) {

               case DataWidget::DATA_BEGIN: print "<div id='focus'>\n"; break;

               case DataWidget::DATA_ROW:
                  ?>
                     <img src='<?= $row['thumbnailURL'] ?>' align='left'>

                                 <p style='margin-top: 0;'><span style='font-size: larger;'><?= $row['Title'] ?></span><br>
                                 by <?= $jumpName ?></p>

                                 <p>Read Reviews and a sample
                                    <a href='catalog.php?BookID=<?= $row['BookID'] ?>#details'>here</a>
                                 </p>

                  <?php
                  if( $row['OnlinePurchase'] ) $this->printAddToCart( $row['Title'], $row['Price'] );

                  break;

               case DataWidget::DATA_END: print "</div>\n"; break;

            }
            break;

         case 'catalogList':

            switch( $invokedFor ) {

               case DataWidget::DATA_BEGIN:

                  if( substr( $sortParamCmp = $this->sortParam, 0, 1 ) == '-' ) {
                     $dirImage = 'images/catalog_desc.gif';
                     $changePrefix = '';
                     $sortParamCmp = substr( $sortParamCmp, 1 );
                  }
                  else {
                     $dirImage = 'images/catalog_asc.gif';
                     $changePrefix = '-';
                  }

                  print "<table id='catalog' cellspacing='0'><thead><tr>\n";
                  foreach( array( 'Title', 'Author', 'Published' ) as $header ) {
                     $align = (( $header == 'Title' || $header == 'Author' ) ? 'left' : 'center' );
                     if( $header == $sortParamCmp ) {
                        print "<th align='$align' class='sortColumn'><a href='$_SERVER[SCRIPT_NAME]?sort=$changePrefix$header'>$header&nbsp;<img src='$dirImage'></th>";
                     }
                     else {
                        print "<th align='$align' class='nonSortColumn'><a href='$_SERVER[SCRIPT_NAME]?sort=$header'>$header</th>";
                     }
                  }
                  print "<th>Order It</th></tr></thead>\n<tbody>";
                  $rowNum = 0;
                  break;

               case DataWidget::DATA_ROW:
                  ?>
                     <tr class='r<?= $rowNum ?>'>
                        <td><a href='catalog.php?BookID=<?= $row['BookID'] ?>#details'><?= $row['Title'] ?></a></td>
                        <td><?= $jumpName ?></td>
                        <td align='center'><?= $row['Copyright'] ?></td>
                        <td align='center'>
                           <?php
                              if( $row['OnlinePurchase'] )
                                 $this->printAddToCart( $row['Title'], $row['Price'] );
                              else
                                 print "(see <a href='catalog.php?BookID=$row[BookID]#details'>book detail</a>)";
                           ?>
                        </td>
                     <tr>
                  <?php
                  $rowNum = ( $rowNum + 1 ) % 2;
                  break;

               case DataWidget::DATA_END:
                  print "</tbody></table>\n";
                  break;

            } // switch $invokedFor
            break;

      } // switch $viewName

   } // printData method


   protected function printForm( $row, $fk ) {
      // Form fields here, not including form opening and closing tags.
      // Also not including action buttons.
      //
   } // printForm method


   protected function buildSubmitArray() {
      // Check and build return array of $_REQUEST values
   } // buildSubmitArray method


};

?>
