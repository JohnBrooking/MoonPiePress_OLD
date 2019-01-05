<?php

   /*
      $Header: mySqlTable.php  Revision:1.22  Tuesday, December 08, 2009 10:16:09 PM  JohnB $

      $Log3: C:\Documents and Settings\jwbrooking\My Documents\Pers\Computer Work\QVCS Archives\CodeLib\PHP\mySqlTable.qiq $
      
        An abstract base class for mySQL tables, defining basic functions for
        table creation, data insertion, updating, and deletion, plus some
        fancier extras as well. The minimum required to use this class is to
        derive from it and define the three protected variables at the top,
        including an array of the field definitions.
      
      Revision 1.22  by: jwbrooking  Rev date: 12/8/2009 10:16:19 PM
        SetActionCondition also needs to recognize plain < or >.
      
      Revision 1.21  by: jwbrooking  Rev date: 4/12/2009 6:01:21 PM
        * Fix bug in getField Type 
        * SetActionCondition now recognizes <> as well as = and IN for
        complete WHERE clause. 
        * Changes to InsertRow and UpdateRow to quote date as well as string
        values, and handle NULL correctly for both.
      
      Revision 1.20  by: JohnB  Rev date: 5/26/2008 2:42:11 PM
        Optional ORDER BY added to getSelectList.
      
      $Endlog$
   */

abstract class mySqlTable {

   private $isAdmin = FALSE;     // Indicates if action SQL may be executed
   private $loggedInUser = '';   // Name of logged in user

   // REQUIRED INTERNAL MEMBERS

   protected $tableName;      // Name of the table
   protected $arrTableDef;    // Array of field names and definitions

   // OPTIONAL INTERNAL MEMBERS

   protected $keyFieldName;   // Name of the single primary key field
   protected $addDateName;    // Name of TIMESTAMP field for creation timestamp
   protected $modDateName;    // Name of TIMESTAMP field for modication timestamp
   protected $addByName;      // Name of field recording AddBy user
   protected $modByName;      // Name of field recording ModBy user

   // PUBLIC MEMBERS (for information only)

   public $lastSQL = "";      // The last SQL statement executed
   public $affectedRows;      // Rows affected by last action query
   public $lastNewID;         // Value of AUTO_INCREMENT field for last new row

   // SETTABLE PUBLIC MEMBERS

   public $defDateFmt = 'n/d/y';  // Default format for fmtDateTime method, per PHP "date" function

   // CONSTANTS (used internally)

   const TYPE_STRING = 0;
   const TYPE_NUMERIC = 1;
   const TYPE_DATETIME = 2;

   public function setAdmin( $isAdmin = FALSE ) {
      $this->isAdmin = $isAdmin;
   }

   public function setUser( $userName ) {
      $this->loggedInUser = $userName;
   }

   public function isAdmin() { return $this->isAdmin; }

   public function User() { return $this->loggedInUser; }

   // ------------------------------------------------------------------------
   // Clean up strings for embedded apostrophes
   // ------------------------------------------------------------------------
   private function cleanApos( $s ) {
      // Do this only if "magic quotes" option off for GET/POST/COOKIE data
      $value = $s;
      if(!get_magic_quotes_gpc()) {
         $value = str_replace( "'", "\\'", $value );
         $value = preg_replace( "/\\+'/", "\\'", $value );
      }
      return $value;
   }


   // ------------------------------------------------------------------------
   // A private function that reads the field definitions and returns what
   // general type of field the named field is (string, numeric, date).
   // ------------------------------------------------------------------------

   private function getFieldType( $fldName ) {
      $dbType = $this->arrTableDef[$fldName];
      $returnVal = -1;

      $arrNumKeywords = array( 'bit', 'int', 'bool', 'float', 'double', 'dec', 'fixed' );
      $arrDateKeywords = array( 'date', 'time', 'year' );

      // Check the column type for numeric keywords
      foreach( $arrNumKeywords as $keywd ) {
         if( stristr( $dbType, $keywd ) !== FALSE ) { $returnVal = mySqlTable::TYPE_NUMERIC; }
         if( $returnVal != -1 ) break;
      }

      // Check the column type for date/time keywords
      if( $returnVal == -1 ) {
         foreach( $arrDateKeywords as $keywd ) {
            if( stristr( $dbType, $keywd ) !== FALSE ) { $returnVal = mySqlTable::TYPE_DATETIME; }
            if( $returnVal != -1 ) break;
         }
      }

      return $returnVal == -1 ? mySqlTable::TYPE_STRING : $returnVal;
   }

   // ------------------------------------------------------------------------
   // Get a bunch of properties about the field, more exact than getFieldType.
   // If display length is unspecified, 0 is returned. Unsigned is undefined
   // for non-numeric columns.
   // ------------------------------------------------------------------------

   private function getFieldProps( $fldName, &$typeWord, &$dispLength, &$unsigned ) {
      $dbSpec = strtoupper( $this->arrTableDef[$fldName] );
      $dispLength = 0;

      if( preg_match( '/^\s*(\w+)\s*\(\s*(\d+)/', $dbSpec )) {
         $typeWord = $matches[1];
         $dispLength = $matches[2];
      }
      else if( preg_match( '/^\s*(\w+)\s/', $dbSpec )) {
         $typeWord = $matches[1];
      }
      // Better match one of those, if it is a valid spec!

      $unsigned = ( strstr( $dbSpec, ' UNSIGNED' ) !== FALSE );
   }

   // ------------------------------------------------------------------------
   // Returns a keystroke filter regexp for the indicated field
   // ------------------------------------------------------------------------

   private function reKeypressForField( $fldName ) {
      $this->getFieldProps( $fldName, $typeWord, $dispLength, $unsigned );
      /*
      switch( $typeWord ) {

         case "BIT":
         case "BOOL":
         case "BOOLEAN":
            $returnRE = '/^[01]$/'; break;   // equate to TINYINT(1) UNSIGNED

         case "TINYINT":
         case "MEDIUMINT":
      }
      */
   }

   /*
   ------------------------------------------------------------------------

      A private function to set a where clause when the possible values
      given could be an explicit WHERE clause (without the keyword WHERE), a
      single key value, or a list of key values. The latter two cases
      require the class' $keyFieldName member to be defined. The function
      determines which style of condition has been passed by detecting the
      presence of '=', '<>', or ' IN' to signal an explicit clause, or a
      comma to indicate a value list. If none of these is found, it is
      assumed to be a single value. If the single value is a string, the
      surrounding apostrophes are optional; otherwise (for a list or
      explicit clause), they are required. An empty string is returned if a
      single or list of key values is indicated, but the class has not
      defined $keyFieldName.

   ------------------------------------------------------------------------
   */

   private function setActionCondition( $condition ) {
      $where = '';
      $condition = str_replace( ';', '', $condition );

      if( strpos( $condition, '=' ) || strpos( $condition, ' IN' ) || strpos( $condition, '<' ) || strpos( $condition, '>' )) {
         $where = $condition;                   // Explicit condition
      }

      elseif( $this->keyFieldName ) {  // Only if a key field is defined

         if( strpos( $condition, ',' )) {
            $where = " IN ( $condition )";     // List of key values
         }
         else {                                 // Single key value
            if( strpos( $condition, "'" )) {
               $where = " = $condition";
            }
            else {
               $where = $this->getFieldType( $this->keyFieldName ) == mySqlTable::TYPE_STRING
                      ? " = '$condition'" : " = $condition";
            }
         } // single or list
         $where = $this->keyFieldName . $where;

      } // which method

      return $where;
   } // function setActionCondition


   // ------------------------------------------------------------------------
   // Executes an update statement, returns the true/false result, but also
   // sets the internal lastSQL string to the SQL statement it was passed.
   // ------------------------------------------------------------------------

   protected function executeAction( $sql ) {
      $this->lastSQL = $sql;
      if( $result = mysql_query( $sql )) {
         $this->affectedRows = mysql_affected_rows();
         $this->lastNewID = mysql_insert_id();
      }
      return $result;
   }


   public static function version() {
   /* --------------------------------------------------------------

      PURPOSE: Return the mySqlTable module version

      RETURNS: A version number of the form m.n[.x.y]

      Returns the version of the mSqlTable object, as given by the
      source code control software used by the author.

   ------------------------------------------------------------------ */
      $matches = array();
      preg_match( '/\x24Revision:\s([\d\.]+)\s\$/', '$Revision: 1.22 $', $matches );
      return $matches[0];
   }


   public function fmtDateTime( $dbValue, $dispFormat = '' ) {
   /* --------------------------------------------------------------

      PURPOSE: Convert date/time from db format to any display format

      RETURNS: The converted string

      Pass the value as it comes out of the database, and an optional
      format string in the style of the PHP "date" function. If the
      format is omitted, the format set by this class' public member
      $defDateFmt is used.

   ------------------------------------------------------------------ */
      $dateTime = explode( ' ', $dbValue );
      $datePart = explode( '-', $dateTime[0] );
      $timePart = isset( $dateTime[1] )
                  ? explode( ':', $dateTime[1] )
                  : array( 0, 0, 0 );
      $time = mktime( $timePart[0], $timePart[1], $timePart[2]
                    , $datePart[1], $datePart[2], $datePart[0]
                    );
      return date( $dispFormat ? $dispFormat : $this->defDateFmt, $time );
   }

   public static function cvtDateTime( $mmddyy ) { // convert "mm/dd/yy hh[:mm] AM/PM"
   /* --------------------------------------------------------------

      PURPOSE: Convert date/time from standard US format to mySQL required format

      RETURNS: The converted string

      This function converts from "mm/dd/[yy]yy[ hh[:mm][ *AM|PM]]" to the mySQL
      standard "yyyy-mm-dd[ hh:mm]", where the mySQL is always 24-hour.

   ------------------------------------------------------------------ */

      $dateTimeRE = '[(\d{1,2})/(\d{1,2})/(\d{2,4})(\s+(\d{1,2})(:\d{1,2})?(\s*(A|P))?)?]';
      $retVal = FALSE;
      if( preg_match( $dateTimeRE, $mmddyy, $matches )) {
         //print_r($matches);
         if(( $year = $matches[3] ) < 100 ) {
            if( "20$year" > date("Y") + 50 ) {
               $year += 1900;
            }
            else {
               $year += 2000;
            }
         }
         if( isset( $matches[4] )) {
            $min = ( $matches[6] ? substr( $matches[6], 1, 2 ) : 0 );
            $hour = $matches[5];
            if( strtoupper($matches[8]) == 'P' ) {
               if( $hour < 12 ) { $hour += 12; }
            }
            elseif( $matches[8] == 'A' && $hour == 12 ) {
               $hour = 0;
            }
            $retVal = sprintf( '%d-%02d-%02d %02d:%02d'
                             , $year, $matches[1], $matches[2]
                             , $hour, $min
                             );
         }
         else {
            $retVal = sprintf( '%d-%02d-%02d', $year, $matches[1], $matches[2] );
         }
      }
      return $retVal;
   }


   public function createTable( $drop = 0 ) {
   /* --------------------------------------------------------------

      PURPOSE: Create the table

      RETURNS: A true value if succeeded, false if not

      Executes a CREATE TABLE statement using the fields specified in
      the $arrTableDef property, to a table named by the $tableName
      property. If $drop is a true value, performs a DROP TABLE
      statement first. Dropping a non-existant table is not considered
      an error in the context of this function, and does not prevent the
      table from being created afterward. However, attempting to create
      a table which already exists without first dropping it is an
      error, and will not overwrite the old table. (You can safely
      ignore the failure if you know the table already exists, but of
      course leaving a call to createTable in your code is not a good
      habit.)

   ------------------------------------------------------------------ */

      if(!$this->isAdmin) { return FALSE; }

      // Use the field definitions to create the CREATE TABLE statement
      $arrFieldDefs = array();
      foreach( array_keys($this->arrTableDef) as $field ) {
         $arrFieldDefs[] = "$field " . $this->arrTableDef[$field];
      }
      $createSql = "CREATE TABLE " . $this->tableName . " ( "
                 . implode( ',', $arrFieldDefs ) . " )";

      // Execute the create statement, with drop if indicated
      if( $drop ) {
         if( ! $this->executeAction( "DROP TABLE " . $this->tableName )) {
            if( mysql_errno() != 1051 ) { // 1051 = Unknown table, that's okay
               return FALSE;
            }
         }
      }
      return $this->executeAction( $createSql );
   }


   public function insertRow( $arrGivenValues ) {
   /* --------------------------------------------------------------

      PURPOSE: Insert a row into the table

      RETURNS: A true value if succeeded, false if not

      Insert a row into this table, by building a dynamic INSERT statement
      using the key/value array passed to it. The input parameter is an
      array whose keys are fieldnames in the table, and whose values are
      the values to use for those fields. Text fields are will have
      embedded apostrophes automatically escaped (if that option is not
      already enabled in your PHP installation). Of course the data must
      satisfy any constraints you have created on the table, such as non-
      null fields and primary keys (if not auto- populated)

   ------------------------------------------------------------------ */

      if(!$this->isAdmin) { return FALSE; }

      $arrNames = array();
      $arrValues = array();

      // Build up list of name and values to insert, only those included

      foreach( array_keys($this->arrTableDef) as $field ) {
         if( isset( $arrGivenValues[$field] )) { // if field included
            $arrNames[] = $field;  // That's the easy one
            $fieldType = $this->getFieldType( $field );
            if( $fieldType == mySqlTable::TYPE_STRING || $fieldType == mySqlTable::TYPE_DATETIME ) {
               $value = $this->cleanApos( $arrGivenValues[$field] );
               if( $value != 'NULL' ) $value = "'$value'";
               $arrValues[] = $value;
            }
            else {
               $arrValues[] = $arrGivenValues[$field];
            }
         }
      }
      //print_r( $fields );

      // Include addDate and/or addBy, if column names specified
      if( $this->addDateName ) {
         $arrNames[] = $this->addDateName;
         $arrValues[] = 'NOW()';
      }
      if( $this->addByName ) {
         $arrNames[] = $this->addByName;
         $arrValues[] = $this->loggedInUser;
      }

      $sql = "INSERT INTO " . $this->tableName . " ( "
           . implode( ',', $arrNames )
           . ") VALUES ( "
           . implode( ',', $arrValues )
           . ")";

      return $this->executeAction( $sql );

   } // function insertRow


   public function updateRow( $condition, $arrChangedValues ) {
   /* --------------------------------------------------------------

      PURPOSE: Update a row in the table

      RETURNS: A true value if succeeded, false if not

      Updates one or more rows identified by the $condition parameter,
      as deteremined by setActionCondition above.

      The $arrChangedValues parameter is an array whose keys are the
      fieldnames to be changed, and whose values are the values to use for
      those fields. Text fields are will have embedded apostrophes
      automatically escaped (if that option is not already enabled in your
      PHP installation). Of course the data must satisfy any constraints
      you have created on the table, such as non-null fields and primary
      keys (if not auto-populated)

   ------------------------------------------------------------------ */

      if(!$this->isAdmin) { return FALSE; }

      $where = $this->setActionCondition( $condition );
      $fields = array();

      // Build up list of field updates, only those included
      foreach( array_keys($this->arrTableDef) as $field ) {
         // To see if field included, even blanks, search for presence of key
         if( array_search( $field, array_keys( $arrChangedValues )) !== FALSE ) {
            $fieldType = $this->getFieldType( $field );
            if( $fieldType == mySqlTable::TYPE_STRING || $fieldType == mySqlTable::TYPE_DATETIME ) {
               $value = $this->cleanApos( $arrChangedValues[$field] );
               if( $value != 'NULL' ) $value = "'$value'";
               $fields[] = "$field = $value";
            }
            else {
               $fields[] = "$field = $arrChangedValues[$field]";
            }
         }
      }

      // Include modDate and/or modBy, if column names specified
      if( $this->modDateName ) {
         $fields[] = "{$this->modDateName} = NOW()";
      }
      if( $this->modByName ) {
         $fields[] = "{$this->modByName} = '{$this->loggedInUser}'";
      }

      // Execute the action and return
      $sql = "UPDATE " . $this->tableName . " SET "
           . implode( ',', $fields )
           . " WHERE $where";

      return $this->executeAction( $sql );

   } // function updateRow


   public function deleteRow( $idList ) {
   /* --------------------------------------------------------------

      PURPOSE: Delete a row in the table

      RETURNS: A true value if succeeded, false if not

      Deletes one or more rows identified by the condition, which is used
      the same way as in the updateRow method above.

   ------------------------------------------------------------------ */
      if(!$this->isAdmin) { return FALSE; }
      $sql = "DELETE FROM " . $this->tableName . " WHERE " . $this->setActionCondition( $idList );
      return $this->executeAction( $sql );
   }


   public function readRows( $orderby = '', $where = '', $fieldList = '*' ) {
   /* --------------------------------------------------------------

      PURPOSE: Select data into a recordset

      RETURNS: A recordset object if successful, or a false value if not

      Returns a recordset object for rows matching the given optional $where
      filter, which is just a WHERE clause without the keyword. If this is not
      included, all rows are returned. The rows are ordered by the optional
      $orderby argument, which is just the ORDER BY clause without the
      keyword. If this is not included, rows are ordered by the primary key
      value if a primary is defined in the $keyFieldName member, or in an
      undefined order if not. A comma-separated list of fields to be returned
      is also optional, all fields by default.

   ------------------------------------------------------------------ */
      $orderby = str_replace( ';', '', $orderby );
      $where = str_replace( ';', '', $where );
      $fieldList = str_replace( ';', '', $fieldList );
      if( $orderby == "" && $this->keyFieldName ) { $orderby = $this->keyFieldName; }
      if( $where != "" ) { $where = "WHERE $where"; }
      if( $orderby != "" ) { $orderby = "ORDER BY $orderby"; }
      $this->lastSQL = "SELECT $fieldList FROM " . $this->tableName . " $where $orderby";
      return mysql_query( $this->lastSQL );
   }


   public function readSingle( $orderby = '', $where = '', $fieldList = '*' ) {
   /* --------------------------------------------------------------

      PURPOSE: Return a single row dataset as an associative array

      RETURNS: The array, or false if not successful

      This method is similar to readRows, except only the first row is
      returned, and as an associative array, not a recordset. The recordset is
      deallocated before the function returns. This is just for convenience,
      if you know that you are requesting just one row. (Or you could use it
      to get the row where some field is the max or min of a set of rows.)

   ------------------------------------------------------------------ */
      $row = false;
      if( $rs = $this->readRows( $orderby, $this->setActionCondition( $where ), $fieldList )) {
         $row = mysql_fetch_array( $rs, MYSQL_ASSOC );
         mysql_free_result($rs);
      }
      return $row;
   }


   public function readValue( $selectField, $where = "" ) {
   /* --------------------------------------------------------------

      PURPOSE: Read a single value from the table

      RETURNS: The value requested if succeeded, FALSE if not

      Directly returns the result of selecting $selectField with the indicated
      $where clause. If the database returns multiple rows, only the first one
      is returned to the caller. If the database returns no rows, or has an
      error, false is returned. Note that $selectField is not limited to field
      names. For example, you could get the number of records or a maximum id
      value with functions "COUNT(*)" or "MAX(id)".

   ------------------------------------------------------------------ */
      $retVal = FALSE;
      if( $where != "" ) { $where = "WHERE $where"; }
      $this->lastSQL = "SELECT $selectField FROM " . $this->tableName . " $where";
      $rs = mysql_query( $this->lastSQL );
      if($rs) {
         if(( $row = mysql_fetch_array( $rs, MYSQL_NUM )) !== FALSE )
            $retVal = $row[0];
         mysql_free_result($rs);
      }
      return $retVal;
   }


   public function getSelectList( $descFieldName, $currValue = '', $where = '', $idFieldName = '', $orderBy = '' ) {
   /* --------------------------------------------------------------

      PURPOSE: Returns HTML for a list of code/label fields inside &lt;OPTION&gt; tag pairs

      RETURNS: The HTML string if succeeded, or boolean false value if not

      Returns (NOT prints) a list of values and corresponding labels formatted
      as a list of HTML "OPTION" tags, ordered as specified in $orderBy, or in
      ascending order of the label by default. If $idFieldName is omitted, the
      table's $keyFieldName is used if defined, otherwise the label field is
      also used as the option value. Passing a non-blank $idFieldName causes
      that field to be used regardless of the presence of a key field. Note
      that only the OPTION tags are included; the SELECT wrapper is left to
      the caller, as are any leading option tags for blank or prompting
      options. If $currValue is included, that option will be pre-selected (by
      outputting " selected" after the value).

   ------------------------------------------------------------------ */
      $retVal = false;
      $output = '';

      // Set the ID field's name: given, or key field, or same as label field
      if( !$idFieldName ) {
         if ( $this->keyFieldName ) {
            $idFieldName = $this->keyFieldName;
         }
         else {
            $idFieldName = $descFieldName;
         }
      }

      // Override default ORDER BY if indicated
      if( $orderBy == '' ) $orderBy = $descFieldName;

      if( $rs = $this->readRows( $orderBy, $where, "$idFieldName, $descFieldName" )) {
         while( $row = mysql_fetch_array( $rs, MYSQL_NUM )) {
            $output .= "<option value='$row[0]'";
            if( $row[0] == $currValue ) { $output .= " selected"; }
            $output .= ">$row[1]</option>\n";
         }
         mysql_free_result($rs);
         $retVal = $output;
      }
      return $retVal;
   }


   public function validateField( $fieldName, $value ) {
   /* --------------------------------------------------------------

      PURPOSE: Validate a value for the field given

      RETURNS: True if the value validates, false if not.

      This method uses the type and length of the field definition in the
      table definition to evaluate whether or not the given value is
      appropriate for it.

   ------------------------------------------------------------------ */
   }


   public function inputField( $fieldName, $onKeyPress = '', $onBlur = '', $class = '' ) {
   /* --------------------------------------------------------------

      PURPOSE: Return an HTML string representing an HTML form input field

      RETURNS: The HTML string

      This method returns a "<input ...>" type HTML field, based on the field
      name given. The name attribute of the HTML field is identical to the
      name of the table field, and JavaScript validation routines are provided
      for key filtering and field validation, based by default on the type and
      length of the field. Finally, the field is also given a CSS style class
      name, default 'mySqlTableField'.

      One or both default script may be overridden by passing a non-empty
      string of JavaScript for the $onKeyPress or $onBlur variables, which is
      applied to the events of those names. Pass $onKeyPress a script to
      approve each new character that is typed, while $onBlur is applied only
      when the focus leaves the field. Failing the $onBlur test does NOT
      prevent the focus from leaving the field, but will cause an error
      message to display.

   ------------------------------------------------------------------ */
   }


   public function getEnumValues( $fieldName ) {
   /* --------------------------------------------------------------

      PURPOSE: Return array of possible enum values

      RETURNS: An array if successful, or FALSE on error

      Note: This code lifted from posting by Willem-Jan van Dinter
      on May 24 2003 at http://mysql.com/doc/refman/5.0/en/enum.html.

   ------------------------------------------------------------------ */

      $retValue = FALSE;
      $query = "SHOW COLUMNS FROM {$this->tableName} LIKE '$fieldName'";
      $result = mysql_query( $query );
      if( mysql_num_rows( $result ) > 0 ){
         $row = mysql_fetch_row( $result );
         $retValue = explode( "','"
                   , preg_replace( "/(enum|set)\('(.+?)'\)/","\\2", $row[1] )
                   );
      }

      return $retValue;
   }

   public function getSelectEnum( $fieldName, $currValue = '' ) {
   /* --------------------------------------------------------------

      PURPOSE: Returns HTML for a list of enumerated field values inside &lt;OPTION&gt; tag pairs

      RETURNS: The HTML string if succeeded, or boolean false value if not

      Returns a list of values from an enumerated type field formatted as a
      list of HTML "OPTION" tags, ordered in the order given in the field
      definition. The type values are used for the OPTION label, with an
      undeclared value so the label is also used. Note that only the OPTION
      tags are output; the SELECT wrapper is left to the caller, as are any
      leading option tags for blank or prompting options. If $currValue is
      included, that option will be pre-selected (by outputting " selected"
      after the value).

   ------------------------------------------------------------------ */
      $retVal = FALSE;
      $output = '';

      if( $arrEnums = $this->getEnumValues( $fieldName )) {
         foreach( $arrEnums as $enumValue ) {
            $output .= '<option';
            if( $enumValue == $currValue ) { $output .= ' selected'; }
            $output .= ">$enumValue</option>";
         }
         $retVal = $output;
      }
      return $retVal;
   }

   public function errorMsg( $action
                           , $template = '<p class="mySqlTableError"><b>There was a problem %a.</br>
                                         Please notify your webmaster with the following technical information:</b><br/>
                                         SQL Error %n: %s<br/>
                                         SQL Statement: %t</p>'
                           ) {
   /* --------------------------------------------------------------

      PURPOSE: Return an error message with contextual information filled in.

      RETURNS: The error message string

      This method returns a error message using a default or customized
      template into which values are filled in at run-time. Pass $action as a
      description of what was being attempted (i.e. "inserting the record"),
      and an optional template string (or omit for a default).

      In the template string, use %a to indicate where to insert the action
      description you supplied, %n to indicate the SQL error number, %s to
      indicate the SQL error description, and %t to insert the full SQL
      statement being attempted at the time. The template does not need to use
      all of these, and may also use them more than once.

      The default template outputs the information inside a paragraph tag with
      a CSS class name 'mySqlTableError', so you may customize its appearance
      by defining styles for this CSS class name while still using the default
      message. (Of course if you pass your own message template, you can style
      it however you want.)

   ------------------------------------------------------------------ */

      $s = "SQL error called on $action, but there is no error reported. Perhaps user is not logged in.";
      if(mysql_errno()) {
         $s = $template;
         $s = str_replace( '%a', $action, $s );
         $s = str_replace( '%n', mysql_errno(), $s );
         $s = str_replace( '%s', mysql_error(), $s );
         $s = str_replace( '%t', $this->lastSQL, $s );
      }
      return $s;
   }

} // class mySqlTable

?>
