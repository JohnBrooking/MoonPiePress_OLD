<?php

   /*
      $Header$

      $Log3$

   */

   class htmlOutput {


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
            $url = htmlOutput::cleanURL( $matches[1] );
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

         $output = htmlOutput::vivifyLinks( $output );   // NOT TESTED WELL HERE YET
         $output = htmlOutput::cleanHTML( $output );

         return $output;

      } // method output


   } // class

?>
