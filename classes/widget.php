<?php

   /*

      $Header: widget_2_3.php  Revision:2.3  Saturday, November 10, 2007 10:09:10 AM  JohnB $

      $Log3: C:\Documents and Settings\jwbrooking\My Documents\Pers\Computer Work\QVCS Archives\CodeLib\PHP\widget.qiq $
      
        An abstract class implementing generic "skeleton code" for using the
        mySqlTable object in the context of a web page.
      
      Revision 2.3  by: JohnB  Rev date: 11/10/2007 10:09:10 AM
        Bugfix: Don't try to check required parameters if they are not defined.
      
      Revision 2.2  by: JohnB  Rev date: 11/10/2007 9:23:07 AM
        Added $viewSQL member to allow multiple views to have SQL defined in
        the object rather than in the caller.
      
      Revision 2.1  by: JohnB  Rev date: 9/27/2007 6:52:15 PM
        Added ability to automatically check input by some pre-set regular
        expressions, and finally fixed up consistent error handling. The
        latter includes a new function to reset a value to its $_SESSION
        counterpart, if present.
      
      $Endlog$

   */

   /* MBDOC

      The dataWidget class is an abstract class implementing generic "skeleton
      code" for using the mySqlTable object in the context of a web page.
      While that module provides the database layer code for a particular type
      of "thing" (announcement, event, whatever you want to use it for), it
      does not (and should not, and in some cases cannot) include all the
      presentation and business logic code: Displaying the object, displaying
      a form for adding or editing, and the logic necessary to handle adds,
      edits, and deletes from POST varibles or querystrings.

      While presentation-layer functionality is specific to the table and the
      page it is used on, there is some code, such as retrieving the dataset,
      that is common to all implementations. The dataWidget abstract class is
      an attempt to (1) encapsulate all the generic presentation-level code,
      leaving only the code that really is specific to the object to be
      implemented by the child class, thus (2) creating the foundation for
      child classes that completely encapsulate their behavior and may be
      dropped into any page anywhere with just a few lines of code.

      The dataWidget class extends the mySqlTable object class. To use it, you
      must in turn define a class extending the dataWidget, and provide all
      the required definitions for both the mySqlTable and the dataWidget
      classes. See the mySqlTable for information on what it requires to be
      defined. For the dataWidget class, you need to define at least the
      protected $name member, and if you are using the file upload feature,
      the $uploadStoragePath and $usesUploadFile as well. In either case, you must
      then overload all the abstract methods discussed in more detail below,
      using code that is particular to the object you are defining. The
      overloaded abstract methods are called at runtime by the public methods,
      which are the ones actually called by the outside page. You also need to
      need to call the widget-level constructor from the child constructor,
      using the parent::__constructor convention.

      The primary public methods called by the presentation page are
      displayData, displayForm, and handleSubmit. These handle all the
      standard housekeeping, and in turn call the abstract functions
      printData, printForm, and buildSubmitArray to do the implemantation-
      specific bits. There are additional functions for utility.

      Here's an example skeleton for a page using a data widget subclass
      named childWidget:

      &lt;?php
         require 'dbConnect.php';               // Take care to use proper paths
         require 'ChildWidget.php';             // Whatever the name of your file is
         $childWidget = new ChildWidget();      // Your constructor may vary

         if( $_REQUEST['update'] == $childWidget->name ) {
            $childWidget->handleSubmit(); // redirect back here afterwards by default
         }
         else { // otherwise, do rest of page
      ?>

      &lt;html>

         &lt;head>

            &lt;!-- ... -->

         &lt;/head>

         &lt;body>

            &lt;!-- ... -->

            &lt;!-- If error message from a previous update, print it here -->
            &lt;?php $childWidget->checkError(); ?>

            &lt;!-- ... -->

            &lt;!-- Display the widget data here; your call may vary -->
            &lt;?php $childWidget->displayData(); ?>

            &lt;!-- ... -->
            &lt;!-- Display the add/edit form here, if user is an admin -->
            &lt;?php
               if($childWidget->isAdmin()) {
                  $childWidget->displayForm( "&lt;h2>%s Widget&lt;/h2>\n" );
               }
            ?>

            &lt;!-- ... -->

         &lt;/body>

      &lt;/html>
      &lt;?php
         } // close "if" way up at top
         unset($childWidget);
      ?>

   */

   require_once "mySqlTable.php";

   abstract class dataWidget extends mySqlTable {

      private $debug = FALSE;

      protected $name;        // Override this with name of widget (used to identify the form)

      public $scriptName;     // The name of the current script

      public $NoDataMsg = '';

      // Optional array of SQL statements corresponding to multiple views
      // Define as key'd array with view names as keys, statements as values
      protected $viewSQL = array();

      // Array of field names to check for their presence.
      protected $arrReqParams = FALSE;

      /*

         Array of filters to apply on submit. Use by setting $arrSubmitFilters
         to an array of key/value pairs, where the key is the name of an
         incoming parameter, and value is a regular expression that it must
         pass. Constants RE_TEST_{NAME,EMAIL,PHONE,URL} may be used as the RE
         argument, or of course the provide can provide its own.

         The incoming parameters are matched against their regular expressions
         just prior to the call to buildSubmitData. Failure to match results
         in the submission being halted, with error handling invoked using the
         message "Invalid characters in field 'fieldname'".

      */
      protected $arrSubmitFilters = FALSE;

      const RE_TEST_NAME  = '/^[\s\w\.\-,\/]+$/';
      const RE_TEST_EMAIL = '/^[\w\.\-_]+@([\w\-_]+\.)+[a-zA-Z]{2,4}$/';
      const RE_TEST_PHONE = '/^[\s\d\()\-\.\/\+]+$/';
      const RE_TEST_URL   = '/^(((https?)|ftp):\/\/)?([\w\-]+\.)+[a-zA-Z]{2,4}(:\d{1,4})?(\/([\w\-\.]|%[a-fA-F\d]{2})+)*\/?(\?\w+=([\w\-\.]|%[a-fA-F\d]{2})+(&\w+=([\w\-\.]|%[a-fA-F\d]{2})+)*)?(#\w+)?$/';

      /*
         The URL RE broken down:

            (((https?)|ftp)://)?
            Optional leading protocol of http, https, or ftp only

            ([\w\-]+\.)+[a-zA-Z]{2,4}(:\d{1,4})?
            Optional domain name, with or without port number

            (\/([\w\-\.]|%[a-fA-F\d]{2})+)*\/?
            Optional path, which is zero or more instances a leading slash
            followed by a "path segment", each character of which can be a
            word character, dash, dot, or hex encoding. The whole thing may
            also be followed by a closing slash. Note that this means a
            closing filename and extension is counted as a final path segment
            without a closing slash, but that's okay, we're just validating
            syntax, not using it, so semantics doesn't matter.

            (
              \?\w+=([\w\-\.]|%[a-fA-F\d]{2})+
              (&\w+=([\w\-\.]|%[a-fA-F\d]{2})+)*
            )?

            Optional url parameters, first starting with ?, zero or more
            subsequent starting with &. Each parameter is in the form
            word=value, where the values consist of word character, dash, dot,
            or hex encodings.

            (#\w+)?
            Optional bookmark

         Test cases:

            $passingTests = array( 'www.microsoft.com'
                                 , 'www.abc.def.ghi'
                                 , 'http://www.microsoft.com'
                                 , 'www.microsoft.com:8080'
                                 , 'http://www.microsoft.com:8080/'
                                 , 'http://www.microsoft.com/path/to/page'
                                 , 'http://www.microsoft.com:80/path/to/page'
                                 , 'www.microsoft.com/path/to/page'
                                 , 'www.microsoft.com/path/to/page/'
                                 , 'www.microsoft.com/path/to/page/page.php'
                                 , 'www.microsoft.com/path.with.dot/to/page'
                                 , 'www.microsoft.com/path%20with%20space/to/page'
                                 , 'www.microsoft.com/path/to/page/page.php?foo=5'
                                 , 'www.microsoft.com/path/to/page/page.php?foo=54&bar=%20hello%2cworld%21'
                                 , 'https://www.microsoft.com:5064/path/to/page/page.php?foo=54&bar=%20hello%2cworld%21#anchor'
                                 , 'page.php'
                                 , 'page.php?foo=2&bar=%20%2e%20'
                                 );

            $failingTests = array( ''                 // Empty string should fail
                                 , '      '           // Space-only string should fail
                                 , 'www.microsoft.com:8080/path/to/page/page.php&foo=5'   // Parameter list must start with ?
                                 , '?foo=54&bar=10'   // Parameter list must be attached to a page
                                 , 'page.php?foo=54&bar=%20Hello%2gWorld%21'     // Invalid hex %2G
                                 , 'www.abc.def.toolong'    // Top-level domain must be <= 4 characters
                                 , '#anchor'          // Anchor must be attached to a page
                                 , '%2c'              // Hex-only string should fail
                                 );

         The email RE is simple enough to not have to explain it in detail,
         but for the sake of having them, here are some test cases for it:

            $passingTests = array( 'joe@mydomain.com'
                                 , 'joe-bob-smith@mydomain.com'
                                 , 'joe@na.mydomain.com'
                                 , 'joe-otool@abc.def.com'
                                 );

            $failingTests = array( ''                       // Empty string should fail
                                 , '      '                 // Space-only string should fail
                                 , 'joe@domain.a'           // Top-level domain too short
                                 , 'joe@domain.toolong'     // Top-level domain too long
                                 , "joe-o'tool@abc.def.com" // Apostrophe not allowed
                                 , 'joe <joe@domain.org>'   // This format not allowed
                                 , 'joe@microsoft'          // Missing top-level domain
                                 );

      */


      /* File upload related specs */

      protected $uploadInputName = '';    // Non-blank indicates upload in use
      // For multiple files, use differently named fields and store here
      // separated by commas
      protected $uploadDBField = '';      // Same usage as $uploadInputName
      protected $uploadStoragePath;       // local system path
      protected $uploadMaxFileSize = 5000000;  // MAX_FILE_SIZE on forms
      public $justUploaded = FALSE;       // Set by widget to indicate upload

      // Provide an accessor for $uploadStoragePath, since it is likely to
      // be dependent on the environment.
      public function uploadStoragePath( $path = '' ) {
         if( $path ) { $this->uploadStoragePath = $path; }
         return $this->uploadStoragePath;
      }

      // Names of images to use for editing buttons

      protected $btnEdit   = 'images/pencilpaper.gif';
      protected $btnDelete = 'images/CutX.gif';
      protected $btnTop    = 'images/move_top.gif';
      protected $btnUp     = 'images/move_up.gif';
      protected $btnDown   = 'images/move_down.gif';
      protected $btnBottom = 'images/move_bottom.gif';

      // If using sequencing, set field name here in child class
      protected $sequenceFieldName = '';

      // Constructor - Set the script name

      public function __construct() {
         $this->scriptName = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
      }

      public function debug( $d = TRUE ) { $this->debug = $d; }

      // Abstract methods - functionality to be overridden for each widget.
      // These are in turn called by the public methods below.

      // Called to print data rows: At start, each row, and at end
      const DATA_BEGIN = 0;
      const DATA_ROW = 1;
      const DATA_END = 2;
      abstract protected function printData( $viewName, $invokedFor, $row = FALSE );
      /* --------------------------------

         PURPOSE: The subclass-defined method for printing data

         RETURNS: Nothing

         This function is where you tell the dataWidget how to print this type
         of object. It is called multiple times from the public function
         displayData. $viewName is a string passed through displayData from
         the original caller, and may be used if your object has more than one
         possible view. How and if you use it is totally up to you.
         $invokedFor is one of the three constants DATA_BEGIN, DATA_ROW, and
         DATA_END. This function is called once at the beginning of the
         dataset with $invokedFor = DATA_BEGIN, once at the end with
         $invokedFor = DATA_END, and once for each data row with $invokedFor =
         DATA_ROW. In the latter case, $row is an array of the column values,
         keyed by field names. You will probably have a case statement on
         $invokedFor, so that you can output the HTML for the beginning and
         end of the list (table, etc.) as well as each row (list item, etc.)

      --------------------------------- */


      abstract protected function printForm( $row, $fkValues );
      /* --------------------------------

         PURPOSE: The subclass-defined method for printing the input fields in the add/edit form

         RETURNS: Nothing

         This function is where you tell the dataWidget how to print the
         add/edit form for this object. This is called from the public
         function displayForm, which outputs actual FORM and /FORM tags
         itself, as well as some hidden variables appropriate to the context.
         Therefore, all that this function needs to write out is the actual
         INPUT fields (or SELECT, TEXTAREA, or whatever controls are
         appropriate). If the form is being called to edit an existing row,
         the $row parameter will contain current values for that row. If the
         form is being printed as an Add form, $row will be an empty array.
         However, due to PHP's relaxed attitude towards printing undefined
         values, you can still use $row['fieldname'] in value attributes of
         tags without checking if it has a value or not, so the same code will
         work both ways. If file uploading is being used, the FILE input tag
         *must* be named "uploadfile".

      --------------------------------- */


      abstract protected function buildSubmitArray();
      /* --------------------------------

         PURPOSE: The subclass-defined method for adding/modifying data on submission

         RETURNS: An array of field names and values to add or change (not including the id in the case of change)

         The public function handleSubmit does everything necessary to process
         an add or change request to the database, except of course gather the
         specific POST or Querystring parameters from the submitting page. It
         calls this function to find that out. This function should return an
         array having field names as the keys and the values recieved from the
         calling page as the array values. The handleSubmit method uses this
         array directly in the mySqlTable functions insertRow or updateRow (as
         appropriate).

      --------------------------------- */


      public function modifyURL( $actions, $url = '' ) {

         $arrOldParams = array();
         $arrNewParams = array();
         $arrActions = array();
         $delMark = '_____';

         // Default URL is the current script
         if( strlen( $url ) == 0 ) { $url = $this->scriptName; }

         // First, remove any fragment (will reattach later unless told not to)
         $frag = '';
         $removeFrag = false;
         $arrFrags = explode( '#', $url );
         $url = $arrFrags[0];
         $frag = isset( $arrFrags[1] ) ? $arrFrags[1] : '';

         // Now explode the parameters (if any) into the 'old' array
         if( $pos = strpos( $url, '?' )) {
            $before = substr( $url, 0, $pos );
            $arrOldParams = explode( '&', substr( $url, $pos + 1 ));
         }
         else {
            $before = $url;
         }

         // If only a single string was passed, turn it into an array for use here
         if( is_array( $actions )) {
            $arrActions = $actions;
         }
         else {
            $arrActions[] = $actions;
         }

         // And iterate the action array (there's probably a more intelligent
         // way to process this than to iterate the whole URL parameter list
         // for every action item, but the limited array sizes required for a
         // URL is not worth the coding complexity)

         foreach( $arrActions as $action ) {

            // Set the action code, and new parameter name and (maybe) value
            $actCode = substr( $action, 0, 1 );
            $arrPair = explode( '=', substr( $action, 1 ));
            $actName = $arrPair[0];
            $actValue = isset( $arrPair[1] ) ? $arrPair[1] : '';

            switch( $actCode ) {

               // If add OR remove, remove all existing first (mark for deletion)
               case '+':
               case '-':
                  foreach( $arrOldParams as $idx => $param ) {
                     if( strpos( $param, "$actName=" ) !== false ) {
                        $arrOldParams[$idx] = str_replace( $actName, $delMark, $arrOldParams[$idx] );
                     }
                  }

                  // Then if adding, add it back in with this value
                  if( $actCode == '+' ) {
                     $arrOldParams[] = "$actName=$actValue";
                  }
                  break;

               // If rename, rename all
               case '>':
                  foreach( $arrOldParams as $idx => $param ) {
                     if( strpos( $param, "$actName=" ) !== false ) {
                        $arrOldParams[$idx] = preg_replace( '/(.+)=(.+)/', "$actValue=$2", $param );
                     }
                  }
                  break;

              // If removing fragment, set the flag for later
              case '#':
                  $removeFrag = true;
                  break;

            } // switch on action flag

         } // iterate action array

         // Now put them all back together, excluding deleted ones
         foreach( $arrOldParams as $param ) {
            if( substr( $param, 0, strlen( $delMark )) != $delMark ) {
               $arrNewParams[] = $param;
            }
         }
         if( array_count_values( $arrNewParams )) {   // might not be any left
            $url = "$before?" . implode( '&', $arrNewParams );
         }
         else {
            $url = $before;
         }

         // Add fragment back on unless told to remove
         if( $frag && !$removeFrag ) {
            $url .= "#$frag";
         }

         return $url;
      } // function modifyURL


      public function editLink( $id, $page = '' ) {
      /* ------------------------

         PURPOSE: Return a URL suitable for editing the current record

         RETURNS: A URL to edit the current record

         When displaying records from a recordset (such as within the
         printData method), you may use this function to return a URL which
         will display the same page (unless overriden by the $page argument),
         but cause the add/edit form to be filled in for editing the current
         record. It simply returns the current URL (or $page) with two
         additional querystring parameters: edit=(name of widget), to indicate
         editing; and id=(id of record).

      ------------------------- */
         return $this->modifyURL( array( "+edit={$this->name}", "+id=$id" ), $page )
                . "#edit_{$this->name}";

      }

      public function deleteLink( $id, $page = '' ) {
      /* ------------------------

         PURPOSE: Return a URL for deleting the current record

         RETURNS: A URL to delete the current record

         When displaying records from a recordset (such as within the
         printData method), you may use this function to call the update page
         (current page unless overridden by the $page argument) with the
         proper HTTP querystring to delete the current record. This is the
         current (or overridden) page plus querystring parameters update=(name
         of widget), to confirm the widget being used; and del=(id of record).

      ------------------------- */
         return $this->modifyURL( array( "+update={$this->name}", "+del=$id" ), $page );
      }

      public function displayEditButtons( $modifyScript, $id, $useDeleteJS = FALSE ) {
      /* --------

         PURPOSE: Display the editing buttons for admins

         RETURNS: Nothing

      -------- */

         if( !$modifyScript ) { $modifyScript = $this->scriptName; }
         ?>
            <a href='<?= $this->editLink($id) ?>'><img alt='Edit Record' title='Edit Record' src='<?= $this->btnEdit ?>' border='0'></a>
            <?php if( strlen( $this->sequenceFieldName )) { ?>
               <a href='<?= $modifyScript ?>?update=<?= $this->name ?>&id=<?= $id ?>&move=top'><img alt='Move to Top' title='Move to Top' src='<?= $this->btnTop ?>' border='0'></a>
               <a href='<?= $modifyScript ?>?update=<?= $this->name ?>&id=<?= $id ?>&move=up'><img alt='Move Up' title='Move Up' src='<?= $this->btnUp ?>' border='0'></a>
               <a href='<?= $modifyScript ?>?update=<?= $this->name ?>&id=<?= $id ?>&move=down'><img alt='Move Down' title='Move Down' src='<?= $this->btnDown ?>' border='0'></a>
               <a href='<?= $modifyScript ?>?update=<?= $this->name ?>&id=<?= $id ?>&move=bottom'><img alt='Move to Bottom' title='Move to Bottom' src='<?= $this->btnBottom ?>' border='0'></a>
            <?php }
            $deleteLink = $this->deleteLink( $id, $modifyScript );
            if( $useDeleteJS ) {
               $deleteLink = "javascript: if( confirm( 'Are you sure you to delete this data?' )) window.location = '$deleteLink';";
            }
            ?><a href="<?= $deleteLink ?>" title='Delete Record'><img alt='Delete Record' title='Delete Record' src='<?= $this->btnDelete ?>' border='0'></a>
         <?php

      } // function displayEditButtons


      public static function outputEML( $linkText, $email ) {
      /* ------------------------

         PURPOSE: Print a disguised "mailto" link

         RETURNS: Nothing

         This static function takes text and an email address, and creates a
         disguised mailto link in the form of a JavaScript call to the "EML"
         function, also available from this code library.

         --------------------- */

         $emailParts = explode( '@', $email );
         $domainParts = explode( '.', $emailParts[1] );

         print "<script type='text/javascript'>\n";
         print "   eml( '$linkText', '$emailParts[0]', '";
         print implode( "', '", $domainParts );
         print "', '@";
         print str_repeat( '.', count( $domainParts ) - 1 );
         print "' );\n</script>\n";
         print "<noscript>\n";
         $noScriptAddr = "$emailParts[0] (at) " . implode( ' (dot ) ', $domainParts );
         if( $linkText ) {
            print "$linkText < $noScriptAddr >";
         }
         else {
            print $noScriptAddr;
         }
         print "\n</noscript>\n";
      }

      public static function indicateBoolean( $value ) {
      /* ------------------------

         PURPOSE: Returns a character indicating a boolean value

         RETURNS: Nothing

         This static function takes a value which it interprets as boolean,
         and returns some kind of character indicating yes or no.

         --------------------- */

         return $value ? 'Y' : '-';
      }

      public function printCategoryInput( $colName = 'category' ) {
      /* ------------------------

         PURPOSE: Print a set of controls to allow category selection

         RETURNS: Nothing

         This is a special public function to assist in widgets that utilize
         categorization. It queries the table for distinct values of a given
         text field name (default 'category'), sorts them, and prints them in
         a drop-down selection control. It then prints "or new: " with a text
         field. The select is named after the field name exactly, while the
         text field is the column name followed by "_new".

         If you use this, note that your implementation of buildSubmitArray
         must have the logic to assign the data value from whichever control
         is appropriate. You probably want to use $_REQUEST['fieldname_new']
         if it has a value, or $_REQUEST['fieldname'] if not.

         --------------------- */

         if( $rs = $this->readRows( $colName, '', "DISTINCT $colName" )) {
            $count = 0;
            while( $catRow = mysql_fetch_array( $rs )) {
               if( $count == 0 ) {
                  print "<select name='$colName' id='$colname'>\n";
               }
               print "<option";
               if( $catRow[$colName] == $row[$colName] ) {
                  print " selected";
               }
               print ">$catRow[$colName]</option>";
               ++$count;
            }
            mysql_free_result($rs);
         }
         if( $count ) { print "</select> or new: "; }
         print "<input name='{$colName}_new' id='{$colName}_new' size='10'/>\n";
      }


      public function displayData( $viewName = '', $order = '', $where = '' ) {
      /* --------------------------------

         PURPOSE: The public entry point for displaying data from the object

         RETURNS: Nothing

         This is the public entry point for printing the object's data. It can
         be called a myriad of ways, as follows.

         1) Simple SQL from a single table

         Pass an emply string for $viewName, and an optional ORDER BY and/or
         WHERE clause (minus the keywords) to select all fields with the
         indicated filter (default none) and sort (default primary key if
         defined, no particular order if no primary key).

         The $viewName is always passed to printData in any case, so you can
         use anyway it if you want to for some reason. But it is most useful
         in the third method listed below.

         2) Override full SQL statement

         Regardless of the use of $viewName, you can also provide a full SQL
         statement in the $order argument. This is most often handy if you
         need to join in some fields from another table. However, especially
         if multiple views are used, you can do the same using the following
         third method.

         3) Multiple views

         If you have multiple views, you can define the SQL statements
         associated with each by overriding the protected $viewSQL array in
         the Widget class. The keys will be view names that correspond to the
         view names you will pass from the page. The values are full SQL
         statements.

         The statements may contain replaceable parameters, denoted as $0, $1,
         etc. up to 9. In this case, pass in the run-time values as a numbered array in
         place of the $order parameter. Otherwise, omit $order and $where.

      --------------------------------- */
         $retVal = FALSE;

         if( is_string( $order ) && strpos( $order, 'SELECT ' )) {

            // Full statement override provided, just use it
            $rs = mysql_query( $order );

         }
         else {

            // No override, use view def'n if provided, or default if not
            if( strlen( $viewName ) && isset( $this->viewSQL[$viewName] )) {

               $sql = $this->viewSQL[$viewName];
               if( is_array( $order )) {
                  for( $i=0; $i<9; ++$i ) {
                     if( isset( $order[$i] )) $sql = str_replace( '$1', $order[$i], $sql );
                  }
               }
               $rs = mysql_query( $sql );

            }
            else {
               $rs = $this->readRows( $order, $where );
            }
         }

         if( $rs ) {
            if( $row = mysql_fetch_array( $rs, MYSQL_ASSOC )) {
               $this->printData( $viewName, self::DATA_BEGIN );
               do {
                  $this->printData( $viewName, self::DATA_ROW, $row );
               } while( $row = mysql_fetch_array( $rs, MYSQL_ASSOC ));
               $this->printData( $viewName, self::DATA_END );
            }
            else {
               print "<p class='widgetNoDataMsg'>{$this->NoDataMsg}</p>";
            }
            mysql_free_result($rs);
            $retVal = TRUE;
         }
         return $retVal;
      } // function displayData


      public function displayForm( $submitTo = '', $headText = '', $addText = 'New', $editText = 'Edit', $fkValues = FALSE ) {
      /* --------------------------------

         PURPOSE: The public entry point for printing an add/edit form

         RETURNS: Nothing

         This is the public entry point for printing the object's add/edit
         form. It will print the add style of the form unless $_GET parameters
         'edit' and 'id' are present, 'edit' is the name of this widget, and
         id is numeric. In that case, the edit form is presented with the
         existing data for that id filled in. The method prints the FORM
         opening and closing tags (including the appropriate enctype attribute
         if file uploading is being used), as well as hidden tags for the
         primary key value (if editing) and an "update" field whose values is
         $this->name. (This is so that the handleSubmit method can verify that
         its getting its own data.) In the case of editing, a Cancel link is
         also printed which redirects to the same page but without the primay
         key indicator.

      --------------------------------- */
         if($this->isAdmin()) {
            $id = '';
            if( isset( $_GET['id'] ) && !$this->justUploaded ) { $id = trim($_GET['id']); }
            $editFlag = ( is_numeric($id) && $_GET['edit'] == $this->name );

            // Read the row if editing, or if not, give an array with all
            // columns blank for convenience, so caller can use them without
            // undefined index warnings
            if( $editFlag ) {
               $row = $this->readSingle( '', $id );
            }
            else {
               // or give an array with all columns blank to prevent warning msgs
               $row = array();
               foreach( array_keys( $this->arrTableDef ) as $key ) {
                  $row[$key] = '';
               }
            }

            // Begin printing the form
            print "\n<a name='edit_{$this->name}'></a>\n";
            print "<form id='{$this->name}' class='widget_form' method='POST'";
            if( $submitTo ) { print " action='$submitTo'"; }
            if( $this->uploadInputName ) { print " enctype='multipart/form-data'"; }
            print ">\n";

            // Print error message, if any
            $this->checkError();


            // Print the header, if indicated
            if( $headText ) {
               print sprintf( $headText, $editFlag ? $editText : $addText );
            }

            print "<input type='hidden' name='id' id='id' value='"
                .  ( $editFlag ? $id : '' ) . "'/>\n";
            print "<input type='hidden' name='update' id='update' value='{$this->name}'/>\n";
            print "<input type='hidden' name='MAX_FILE_SIZE' value='" . $this->uploadMaxFileSize . "' />";

            // Call the callback to print the meat of it
            $this->printForm( $row, $fkValues );

            // Finish printing the form
            if( $id ) {
               print "<input type='submit' value='Save'> ";
               $url = $this->modifyURL( array( '-id', '-edit' ));
               print "<a href='$url'>Cancel</a>\n";
            } else {
               print "<input type='submit' value='Add'>";
            }
            print "</form>\n";
         }
      } // function displayForm


      public function onSubmit( $actionFlag, $arrData, $uploadedNames ) {
      /* --------------------------------

         PURPOSE: A generic function called on a successful submit

         RETURNS: Blank string if successful, error message if not

         This function is always called on a successful submit, immediately
         before the redirect. By default, it does nothing but return a blank
         string, but is meant to be overriden by a child object if needed to
         accomplish something between the update and the redirect. If a non-
         blank string is returned, the submission is considered to have failed
         and takes the error redirect with the returned string as the error
         message. The function is provided with the action that was performed
         on the record (I, U, or D), the data (for I and U), and an array of
         uploaded filenames, not including path.

      --------------------------------- */
         return '';
      }

      private static function standardizeUploadFileName( $name ) {
         // Anything in file name not alphanumeric or '.' translates to _
         return preg_replace( '/[^\w\.]/', '_', $name );
      }

      public function handleSubmit( $redirect = '', $redirError = '' ) {
      /* --------------------------------

         PURPOSE: The public entry point for handling form submissions

         RETURNS: Nothing (redirects to indicated URLs)

         This function is the one called by the application page to handle an
         add/change/delete request. It redirects to $redirect on success or
         $redirError on error. If $redirError is omitted, the value of
         $redirect is used. If both are omitted, the current page name is used
         for both (a "reentrant" form). The method does something only if (1)
         the request was from an administrator (determined by mySqlTable
         isAdmin method), and (2) there is an "update" parameter (POST or GET)
         matching $this->name.

         If an error is encountered, the function redirects to $redirError
         with session variable $_SESSION['errMsg'] set. The public function
         checkError may be called from the redirected page to check for and
         print this message. If no errors are encountered, the function
         redirects to $redirect.

      --------------------------------- */

         $okay = FALSE;
         $errMsg = '';
         $IDtoUse = 0;
         $action = '';     // For error messages

         // If URL includes id, then probably fill it in by REQUEST[id]
         if( $useID = preg_match( '/[\?&]id=/', $redirect )) {
            $IDtoUse = dataWidget::nvlParam('id');
         }


         // Save parameters in session variables for error handling
         foreach( $_REQUEST as $pkey => $pvalue ) {
            $_SESSION[$pkey] = $_REQUEST[$pkey];
         }

         // Main processing

         if( $this->debug ) { print "<p>Starting handleSubmit, " . print_r($_REQUEST) . "</p>"; }
         if( $this->isAdmin() && $_REQUEST['update'] && $_REQUEST['update'] == $this->name ) {
            $okay = TRUE;

            // Determine the action to be taken, and build initial submit array

            if( dataWidget::nvlParam('del') && is_numeric( $_REQUEST['del'] )) {
               $actionFlag = 'D';
               //print '<p>D</p>';
            }
            elseif( dataWidget::nvlParam('id') && is_numeric( $_REQUEST['id'] )) {
               $actionFlag = 'U';
               ///print '<p>U</p>';
            }
            else {
               $actionFlag = 'I';
               //print "<p>I, $actionFlag</p>";
            }
            //print "<p>$actionFlag</p>";

            $seqFlag = ( $actionFlag == 'U' && preg_match( '/up|down|top|bottom/', dataWidget::nvlParam( 'move' ))
                       ? $_REQUEST['move'] : '' );

            // On Insert, or Update not including requesencing, check data

            if( $actionFlag == 'I' || ( $actionFlag == 'U' && $seqFlag == '' )) {
               $okay = TRUE;

               // Check that required request params are present

               if( $this->arrReqParams !== FALSE ) {
                  foreach( $this->arrReqParams as $reqField ) {
                     if( !dataWidget::nvlParam( $reqField )) {
                        $okay = FALSE;
                        $errMsg = "Missing required value for field $reqField";
                        break;
                     }
                  }
               }

               // Check filters, if defined

               if( $okay ) {
                  if( $this->arrSubmitFilters !== FALSE ) {
                     foreach( $this->arrSubmitFilters as $filterKey => $filter ) {
                        if( !preg_match( $filter, $_REQUEST[$filterKey] )) {
                           $okay = FALSE;
                           $errMsg = "Invalid characters in field $filterKey ('$_REQUEST[$filterKey]')";
                        }
                     }
                  }
               }

               // Call child's buildSubmitArray to build the data array

               if( $okay ) {
                  $okay = (( $data = $this->buildSubmitArray()) !== FALSE );
                  if( !$okay ) {
                     $errMsg = 'Child object returned no data to submit';
                  }
               }

            } // On Insert, or Update not including requesencing, check data

            if( $this->debug ) {
               print "<p>actionFlag = $actionFlag, seqFlag = $seqFlag</p>";
               print_r($data);
               print_r($_SESSION);
            }

            // Handle any file uploads (this assumes not delete action)

            if( $this->debug ) { print "<p>About to upload files, \$okay = '$okay'</p>"; }
            $uploadedBaseNames = Array();
            if( $okay && $this->uploadInputName ) {

               $action = 'uploading files';
               if( $this->debug ) { print_r($_FILES); }
               $formNames = explode( ',', $this->uploadInputName );
               $dbFieldNames = explode( ',', $this->uploadDBField );
               $fieldNo = 0;
               foreach( $formNames as $formName ) {
                  if( !$okay ) break;
                  if( isset( $_FILES[$formName] ) && strlen( $_FILES[$formName]['name'] )) {
                     if( $_FILES[$formName]['error'] == UPLOAD_ERR_OK ) {
                        if( $this->debug ) { print "<p>Trying " . $_FILES[$formName]['name'] . ", \$okay = $okay</p>"; }
                        $uploadedBaseNames[]
                           = $basename
                           = dataWidget::standardizeUploadFileName( basename( $_FILES[$formName]['name'] ));
                        if( $this->debug ) { print "<p>Basename: $basename, fullname = '" . $this->uploadStoragePath . "/$basename', tmpname = " . $_FILES[$formName]['tmp_name'] . "</p>"; }
                        $okay = move_uploaded_file( $_FILES[$formName]['tmp_name']
                                                  , $this->uploadStoragePath . "/$basename"
                                                  );
                        if( $this->debug ) { print  "<p>Moved, \$okay = $okay</p>"; }
                        // record name in DB data array
                        $data[$dbFieldNames[$fieldNo++]] = $basename;
                     }
                     else {
                        $okay = FALSE;
                     }
                  } // if there is a file
               } // for each file

               $this->justUploaded = $okay;

            } // if any uploaded files

            if( $this->debug ) { print "<p>Finished uploading files, \$okay = '$okay'</p>"; }

            // Update the database row: Ins, Upd, or Del

            if( $this->debug ) { print "<p>Action '$actionFlag'</p>"; }
            if( $this->debug ) { print_r( $data ); }

            if( $okay) {

               switch( $actionFlag ) {

                  case 'I':   // Insert
                     if( $data !== FALSE ) {
                        if( count( $data )) {
                           $action = 'inserting a row';
                           // If sequencing being used, assign seq # now
                           if( $this->sequenceFieldName ) {
                              $data[$this->sequenceFieldName] =
                              $this->readValue( "MAX({$this->sequenceFieldName}) + 1" );
                           }
                           if( $this->debug ) { print_r( $data ); }
                           $okay = $this->insertRow( $data );
                        } // if array contains data
                     } // if array okay
                     else {
                        $action = 'building data array for insertion';
                        $okay = FALSE;
                     }
                  break;

                  case 'U':   // Update
                     if( $seqFlag == '' ) {
                        if( $data !== FALSE ) {
                           if( count( $data )) {
                              $action = 'modifying a row';
                              $okay = $this->updateRow( $_REQUEST['id'], $data );
                              //print "<p>{$this->lastSQL}</p>";
                           }
                        }
                        else {
                           $action = 'building data array for update';
                           $okay = FALSE;
                        }
                     }
                  break;

                  case 'D':   // Delete
                     $action = 'deleting a row';
                     $okay = $this->deleteRow( $_REQUEST['del'] );
                  break;

               } // switch: Ins, Upd, Del

            } // if was okay up to this point


            // Now handle sequencing, if indicated
            if( $seqFlag && $okay ) {
               $action = 'resequencing records';
               $okay = $this->resequence( $_REQUEST['id'], $seqFlag );
            }

            // Now call the user defined onSubmit
            if( $okay ) {
               if( $this->debug ) { print "<p>About to call user submit, \$okay = $okay</p>"; }
               $submitMsg = $this->onSubmit( $actionFlag, $data, $uploadedBaseNames );
               if( $this->debug ) { print "<p>User onSubmit returned '$submitMsg'</p>"; }
               if( $submitMsg ) {
                  $okay = FALSE;
                  $errMsg = $submitMsg;
               }
               if( $this->debug ) { print "<p>Called user submit, \$okay = $okay</p>"; }
            }

         } // if administrator, and update parameter set appropriately


         // Redirect afterwards

         if( $okay ) {

            if( !$redirect ) { $redirect = $this->scriptName; }
            $redirect= $this->modifyURL( array( '-update', '-del', '-id' ), $redirect );
            if( $useID ) {
               $redirect = $this->modifyURL( "+id=$IDtoUse", $redirect );
            }

            if( $this->debug ) {
               print "<p>Location: $redirect</p>";
            }
            else {
               header( "Location: $redirect" );
            }
         } else {

            if( !$redirError ) {
               $redirError = ( $redirect ? $redirect : $this->scriptName );
            }
            if( $actionFlag == 'U' ) {
               $id = $_REQUEST['id'];
               $redirError = $this->modifyURL( array( "+edit={$this->name}"
                                                    , "+id=$id"
                                                    )
                                              , $redirError
                                              );
               $redirError = "$redirError#edit_{$this->name}";
            }

            $_SESSION['errMsg'] = "Error: $action ";
            if( $errMsg ) $_SESSION['errMsg'] .= $errMsg;
            if( $this->debug ) {
               print "<p>Error Message: $_SESSION[errMsg]</p>";
               print "<p>Location: $redirError</p>";
            }
            else {
               header( "Location: $redirError" );
            }
         }

         print "<p>Ending onSubmit, \$okay = '$okay'</p>";
      } // function handleSubmit


      public function resequence( $recID, $dir ) {
      /* -----

         PURPOSE: Move an record up or down in the sequence

         RETURNS: TRUE if successful, FALSE if not

         This method moves resequences the records by reassigning values
         in the $sequenceFieldName field. Indicate what to do by passing
         the record ID to be reassigned, and a direction to make it go
         (top, up, down, bottom).

      ----- */

      /* Sequencing Action Chart

         ID  Seq  Top                              Up                              Down                            Bottom
         --  ---  -------------------------------  ------------------------------  -------------------------       -------------------------------
         A    1   SET seq = seq + 1 WHERE seq < 1  SET seq = <null> WHERE key = A  SET seq = 2 WHERE key = A       SET seq = seq - 1 WHERE seq > 1
                  SET seq = 1 WHERE key = A        SET seq = 1 WHERE key = <null>  SET seq = 1 WHERE key = B       SET seq = 4 WHERE key = A
         --  ---  -------------------------------  ------------------------------  -------------------------       -------------------------------
         B    2   SET seq = seq + 1 WHERE seq < 2  SET seq = 1 WHERE key = B       SET seq = 3 WHERE key = B       SET seq = seq - 1 WHERE seq > 2
                  SET seq = 1 WHERE key = B        SET seq = 2 WHERE key = A       SET seq = 2 WHERE key = C       SET seq = 4 WHERE key = B
         --  ---  -------------------------------  ------------------------------  -------------------------       -------------------------------
         C    3   SET seq = seq + 1 WHERE seq < 3  SET seq = 2 WHERE key = C       SET seq = 4 WHERE key = C       SET seq = seq - 1 WHERE seq > 3
                  SET seq = 1 WHERE key = C        SET seq = 3 WHERE key = B       SET seq = 3 WHERE key = D       SET seq = 4 WHERE key = C
         --  ---  -------------------------------  ------------------------------  -------------------------       -------------------------------
         D    4   SET seq = seq + 1 WHERE seq < 4  SET seq = 3 WHERE key = D       SET seq = <null> WHERE key = D  SET seq = seq - 1 WHERE seq > 4
                  SET seq = 1 WHERE key = D        SET seq = 4 WHERE key = C       SET seq = 4 WHERE key = <null>  SET seq = 4 WHERE key = D

         Note exceptions in moving top sequence up and bottom down. Also note
         that moving top to top and bottom to bottom is unnecessary, but not
         harmful.

      */

         $retVal = FALSE;
         $seqName = $this->sequenceFieldName;  // convenience
         $tbName = $this->tableName;           // convenience
         $keyName = $this->keyFieldName;       // convenience
         $currSeq = $this->readValue( $seqName, "$keyName = $recID" );

         if( $this->debug ) {
            print "<p>Resequencing ID $recID (seq $currSeq) $dir</p>";
         }

         switch( $dir ) {

            case 'top':
            case 'bottom':
               $offset =   ( $dir == 'top' ? 1   : -1  );
               $relation = ( $dir == 'top' ? '<' : '>' );
               $otherSeq = ( $dir == 'top' ? 1   : $this->readValue( 'MAX(sequence)' ));
               $sql1 = "UPDATE $tbName SET $seqName = $seqName + $offset WHERE $seqName $relation $currSeq";
               $sql2 = "UPDATE $tbName SET $seqName = $otherSeq WHERE $keyName = $recID";
               break;

            case 'up':
            case 'down':
               $relation = ( $dir == 'up' ? '<' : '>' );
               $func = ( $dir == 'up' ? 'MAX' : 'MIN' );

               $otherSeq = $this->readValue( "$func($seqName)", "$seqName $relation $currSeq" );
               if( $this->debug ) {
                  print "<p>Searched for $func($seqName) WHERE $seqName $relation $currSeq, found ";
                  print ( $otherSeq === FALSE ? '<FALSE>' : "'$otherSeq'" );
                  print "<br>{$this->lastSQL}</p>";
                }

               if( $otherSeq ) {   // moving up at top or down at bottom
                  $switchRow = $this->readSingle( "$keyName, $seqName"
                                  , "$keyName = ( SELECT $keyName
                                                  FROM $tbName
                                                  WHERE $seqName = $otherSeq
                                                )"
                                  , "$keyName, $seqName"
                                );
                  if( $this->debug ) { print "<p>{$this->lastSQL}</p>"; }
                  $key2 = $switchRow[$keyName];
                  $seq2 = $switchRow[$seqName];
                  $sql1 = "UPDATE $tbName SET $seqName = $seq2 WHERE $keyName = $recID";
                  $sql2 = "UPDATE $tbName SET $seqName = $currSeq WHERE $keyName = $key2";
               }
               break;

         }

         if( isset( $sql1 )) {
            if( $this->debug ) { print "<p>SQL1: $sql1<br>SQL2: $sql2</p>"; }
            $retVal = $this->executeAction($sql1) && $this->executeAction($sql2);
         }
         else {
            if( $this->debug ) { print '<p>Requence bypassed</p>'; }
            $retVal = TRUE;   // Nothing needed to be done
         }

         return $retVal;
      }


      public static function nvlParam( $name ) {
         return ( isset( $_REQUEST[$name] ) ? $_REQUEST[$name] : '' );
      }


     public static function cleanURL( $url ) {
      /* --------------------------------------------------------------

         PURPOSE: "Clean up" a supposed URL string

         RETURNS: The input with any malicious characters stripped

         This static function "cleans up" a URL by removing any characters that
         are not in the set ( alphanumeric :/-?&=.# ), and also removes any "..".

      ----------------------------------------------------------------- */
         $output = $url;
         $output = preg_replace( '/[^\w\:\/\-\?&=\.\#]/', '', $output );
         $output = preg_replace( '/\.\./', '', $output );
         return $output;
      }


      public static function cleanHTML( $text ) {
      /* --------------------------------------------------------------

         PURPOSE: Clean up text with potential HTML for display

         RETURNS: The input with any malicious HTML neutralized

         This static function removes potentially harmful HTML from a string
         to prevent cross-scripting (XSS) attacks. It works by allowing a
         certain subset of HTML given by an array inside the function,
         basically display- type tags plus links. It does this by first
         converting all tag characters, plus certain questionable puncuation,
         into HTML entities. Then it converts back just the allowable tags.
         The following tags are allowed: B, BR, I, U, A, FONT, P, and SPAN.

      ------------------------------------------------------------------ */

         $disallow_punct = array( '&' => '&#38;'
                                , '<' => '&lt;'
                                , '>' => '&gt;'
                                , '(' => '&#40;' // these + & recommended by
                                , ')' => '&#41;' // http://www.cgisecurity.com
                                );               //    /articles/xss-faq.shtml
                                                 // Not sure why, honestly.
         $allow_tags = array( 'b', 'br', 'i', 'u', 'a', 'font', 'p', 'span' );

         foreach( $disallow_punct as $bad => $okay ) {
            $text = str_replace( $bad, $okay, $text );
            //print "<p>Replaced $bad: $text</p>";
         }

         // this also recommended, but cannot be done quite as simply
         $text = preg_replace( '/[^&]#/', '&#35;', $text );

         foreach( $allow_tags as $tag ) {

            // First, bring back the closing tag (or combined), being easy
            $text = str_ireplace( "&lt;/$tag&gt;", "</$tag>", $text ); // See?
            $text = str_ireplace( "&lt;$tag/&gt;", "<$tag/>", $text );

            // Now take care of attributeless opening tags, just as easy.
            $text = str_ireplace( "&lt;$tag&gt;", "<$tag>", $text );

            // Now get back the opening tags with attributes, where the end of the
            // tag may be an arbitrary distance from the start.
            $start = 0;
            while(( $start = strpos( $text, "&lt;$tag ", $start )) !== FALSE ) {
               if(( $end = strpos( $text, "&gt;", $start )) !== FALSE ) {
                  $replace = '<' . substr( $text, $start + 4, $end - ( $start + 4 )) . '>';
                  $text = substr_replace( $text, $replace, $start, $end - $start + 4 );
               }
            }

         } // for each allowable tag

         return $text;
      }


      public static function vivifyLinks( $text, $inclEmail = true ) {
      /* --------------------------------------------------------------

         PURPOSE: Create a link from URL's and mailto:'s embedded in the text

         RETURNS: The input with links vivified

         This static function "vivifies" URL and (optionally) mailto links by
         surrounding them with an anchor tag.

      ----------------------------------------------------------------- */

         $urlRE = '/[^\'"]((http|ftp|https):\/\/|www\.)([\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&:\/~\+#]*[\w\-\@^=%&\/~\+#])?)[^\'"]/';
         $mailRE = '/[^\'"]([\w\-.]+@[\w\-.]+)[^\'"]/';
         $text = preg_replace( $urlRE, "<a href='http://$0' target='_blank'>$0</a>", $text );
         if( $inclEmail ) {
            $text = preg_replace( $mailRE, "<a href='mailto:$0'>$0</a>", $text );
         }
         $text = preg_replace( '|http://http://|', 'http://', $text );
         return $text;
      }


      public static function output( $input ) {
      /* --------------------------------------------------------------

         PURPOSE: Output HTML string from string containing bbCode and allowed HTML tags

         RETURNS: The input with bbCode converted to HTML, and legal HTML retained

         This function accepts a string that may include bbCode and/or HTML,
         converts the allowed bbCode to HTML, allows only safe HTML through,
         turns all newlines into BR tags, and vivifies any URLs and mailto
         tags not already in an A tag. Non-safe HTML tags are neutralized by
         having their angle brackets turned into the HTML angle bracket
         entities. (BEWARE of this if you are processing it further!)
         Supported bbCode tags are B, I, U, H1, H2, H3, H4, H5, URL, LIST and
         INDENT. Supported HTML is that documented for the cleanHTML method of
         this object.

      ------------------------------------------------------------------ */

         $output = $input;

         // Replace all newlines with HTML line breaks
         $output = preg_replace( "/\n\r|\r\n/", "<br/>", $output );
         $output = preg_replace( "/\n|\r/", '', $output );

         // Replace parameter-less tags
         foreach( array( 'B', 'I', 'U', 'H1', 'H2', 'H3', 'H4', 'H5' )
                  as $tag ) {
            $output = str_ireplace( "[$tag]", "<$tag>", $output );
            $output = str_ireplace( "[/$tag]", "</$tag>", $output );
         }

         // Replace parameterized tag URL
         while( preg_match( '/\[url=(.*?)\]/i', $output, $matches )) {
            $url = dataWidget::cleanURL( $matches[1] );
            $output = preg_replace( '/\[url=(.*)\]([\w\d\s]+)\[\/url\]/i'
                                  , "<a href='$url'>$2</a>", $output, 1
                                  );
         }

         // Miscellaneous replacements not fitting those two patterns

         // List tags
         $output = str_ireplace( "[LIST][*]", "<ul class='bullet'><li>", $output );
         $output = str_ireplace( "[LIST=1][*]", "<ul class='number'><li>", $output );
         $output = str_ireplace( "[LIST=A][*]", "<ul class='letter'><li>", $output );
         $output = str_ireplace( "[/LIST]", "</ul>", $output );
         $output = str_replace( "[*]", "</li><li>", $output );

         // Indent codes become divs of indent class
         $output = str_ireplace( "[INDENT]", "<div class='indent'>", $output );
         $output = str_ireplace( "[/INDENT]", "</div>", $output );

         // Remove newlines around block elements
         foreach( array( 'H1', 'H2', 'H3', 'H4', 'H5', 'UL', 'LI', 'DIV' ) as $tag ) {
            while( stristr( $output, "<br/><$tag" ) !== FALSE ) {
               $output = str_ireplace( "<br/><$tag", "<$tag", $output );
            }
            while( stristr( $output, "<br/></$tag>" ) !== FALSE ) {
               $output = str_ireplace( "<br/></$tag>", "</$tag>", $output );
            }
            while( stristr( $output, "</$tag><br/>" ) !== FALSE ) {
               $output = str_ireplace( "</$tag><br/>", "</$tag>", $output );
            }
         }

         $output = dataWidget::vivifyLinks( $output );   // NOT TESTED WELL HERE YET
         $output = dataWidget::cleanHTML( $output );

         return $output;

      } // method output


      public function checkError( $reset = TRUE ) {
      /* -----------------

         PURPOSE: Print an error message if one was indicated

         RETURNS: Nothing

         Error handling in the widget is accomplished by writing a message to
         $_SESSION['errMsg']. This function may be called from that page to
         print out the err parameter if it exists, inside a P tag with CSS
         class 'error'. Unless a parameter of FALSE is passed, the session
         variable is then cleared. The text is cleansed of non-presentational
         HTML tags. Note that the error handling page does not have to use
         this function, but if it does not, it should take care to do a
         similar cleaning of the parameter to guard against cross-site
         scripting attacks.

      -------------------- */

         if( isset( $_SESSION['errMsg'] )) {
            print "<p class='error'>$_SESSION[errMsg]</p>";
            if( $reset ) { unset( $_SESSION['errMsg'] ); }
         }
      }

      static public function SessionOverride( $inputName, $dataRow ) {
         return isset( $_SESSION[$inputName] ) ? $_SESSION[$inputName]
                  : ( isset( $dataRow[$inputName] ) ? $dataRow[$inputName] : '' );
      }

   } // class dataWidget

?>