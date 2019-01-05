<?php

   /*

      $Header: bb2html.php  Revision:1.2  Sunday, July 03, 2005 8:45:26 AM  JohnB $

      $Log3: C:\Documents and Settings\Owner\My Documents\John\Computer Work\QVCS Archives\MaineBrook\docs\opac\php\bb2html.qiq $

        BBCode to HTML translation function.

      Revision 1.2  by: JohnB  Rev date: 7/3/2005 8:45:26 AM
        Debugged INDENT code, which had not been properly implemented.

      Revision 1.1  by: JohnB  Rev date: 7/2/2005 8:39:02 AM
        Added support for header and list tags, fixed some problems with the
        newline replacement that became apparent when trying to strip them out
        around block tags.

      Revision 1.0  by: JohnB  Rev date: 6/18/2005 4:20:48 PM
        Initial revision.

      $Endlog$

      This module allows you to define your own set of bbcode and translate it
      to HTML, with two restrictions:

         * Tags which take no parameters are assumed to be identical between
           bbcode and HTML, except for the bracket style. For example, [B] to
           <B>, or [I] to <I>.

         * Parameterized tags are limited to one parameter. However, you can
           specify the HTML to use around that parameter, the HTML tag need
           not match the bbcode tag, and the parameter may be (in fact, is
           required to be) passed through a "cleaning" function. For example,
           a "URL" bbcode tag with a single parameter would be translated to
           the "a" HTML tag, with the parameter being used as the value of the
           "href" attribute.

      To configure this module to your linguistic needs:

         * Define tags taking no parameters in the foreach array under the
           comment "Replace parameter-less tags".

         * Tags that take a parameter require the HTML code to replace the
           bbcode starting and ending tag with, and a callback function to use
           to clean up the parameter to guard against malicious input. In the
           starting tag HTML code, use [param] to indicate where in the HTML
           stream to put the parameter. Start by creating the callback
           function to clean the parameter according to your needs, as in the
           examples "cleanURL" and "cleanTitle" below. Then provide the HTML
           definition strings and the name of the callback function in an
           invocation to "replaceParamTag", in the manner of the examples
           present below under the comment "Replace parameterized tags".

      The function also neuters existing HTML by replacing all angle brackets
      with &lt; and &gt; before parsing the bbcode, and replaces all newline
      characters with the <br/> tag.

   */

   function cleanURL( $url ) {
      $output = $url;
      $output = preg_replace( '/[^\w\:\/\-\?\.\#]/', '', $output );
      $output = preg_replace( '/\.\./', '', $output );
      return $output;
   }

   function cleanTitle( $title ) {
      $output = preg_replace( '/[^\w\s\-\.\,\!\?]/', '', $title );
      $output = preg_replace( '/[^\w]/', '_', $output );
      return $output;
   }

   function replaceParamTag( $tag, $input, $replaceStart, $replaceEnd, $cleanFunc ) {
      $output = $input;
      while(( $startPos = stripos( $output, "[$tag=" )) !== FALSE ) {
         $startParam = $startPos + strlen($tag) + 2;
         $endPos = strpos( $output, "]", $startPos );
         $param = substr( $output, $startParam, $endPos - $startParam );
         $param = call_user_func( $cleanFunc, $param );
         $substStart = str_replace( "[param]", $param, $replaceStart );
         $output = substr_replace( $output, $substStart, $startPos, $endPos - $startPos + 1 );
      }
      $output = str_ireplace( "[/$tag]", "</$replaceEnd>", $output );
      return $output;
   }

   function vivifyLinks( $text, $inclEmail = true, $bbCode = false ) {
      $urlRE = '/((http|ftp|https):\/\/|www\.)([\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&:\/~\+#]*[\w\-\@^=%&\/~\+#])?)/';
      $mailRE = '/([\w\-.]+@[\w\-.]+)/';
      if( $bbCode ) {
         $text = preg_replace( $urlRE, "[url=http://$0]$0[/url]", $text );
      }
      else {
         $text = preg_replace( $urlRE, "<a href=\"http://$0\" target=\"_blank\">$0</a>", $text );
         $text = preg_replace( $mailRE, "<a href=\"mailto:$0\">$0</a>", $text );
      }
      $text = preg_replace( '|http://http://|', 'http://', $text );
      return $text;
   }

   function bb2html( $input ) {
      $output = $input;

      // Encode angle brackets to neuter any existing HTML (they were warned)
      $output = str_replace( '<', '&lt;', $output );
      $output = str_replace( '>', '&gt;', $output );

      // Replace all newlines with HTML line breaks
      $output = preg_replace( "/\n\r|\r\n/", "<br/>", $output );
      $output = preg_replace( "/\n|\r/", '', $output );

      // Replace parameter-less tags
      foreach( array( 'B', 'I', 'U', 'H1', 'H2', 'H3', 'H4', 'H5' )
               as $tag ) {
         $output = str_ireplace( "[$tag]", "<$tag>", $output );
         $output = str_ireplace( "[/$tag]", "</$tag>", $output );
      }

      // Replace parameterized tags
      $output = replaceParamTag( "URL", $output
                               , "<a target='_blank' href='[param]'>", "a"
                               , 'cleanURL' );

      $output = replaceParamTag( "ARTICLE", $output
                               , "<a href='/opac/info/kb/[param].php'>", "a"
                               , 'cleanTitle' );

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

      return $output;

   }

   /*
   $test = array( "Hello, world!"
                , "Hello, [B]world[/B]!"
                , "Hello, [i]world[/i]!"
                , "Hello, [U]world[/u]!"
                , "Go to [url=http://www.mainebrook.com/opac]OPAC[/url]!"
                , 'Do you prefer [url=http://www.google.com]Google[/url] or [url=http://www.yahoo.com/]Yahoo[/url]?'
                , "What does it do with this <script language='JavaScript'>document.write( 'potentially malicious code?' );</script>"
                , "A line.\nA second line.\n\nNew paragraph."
                , "Reference to [article=Another Article]Another Article[/ARTICLE]"
                );

   foreach( $test as $s ) {
      print "<p>$s:<br/>\n&nbsp;&nbsp;&nbsp;&nbsp;" . bb2html( $s ) . "</p>\n";
   }
   */

?>
