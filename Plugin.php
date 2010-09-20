<?php
/*
Plugin Name: Picture Wishlist
Plugin URI: http://www.houseindorset.co.uk/plugins/Wish-Pics
Description: Provides a graphical wishlist displaying a grid of front covers, indicating which books/CDs you have in your collection.
Author: Paul Stuttard
Version: 0.1
Author URI: http://www.houseindorset.co.uk/
License: GPL2

*/

/*  Copyright 2009  P. Stuttard  (email : wordpress_wishpics @ redtom.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/* Syntax:

   <!--wishpics Settings -->

   Where 'Settings' Consists of:

   [id=I][&thumbHeight=Y][&thumbWidth=X][&public=P]

   Where:
         I is an identifier for the wishlist.
         Y is the height of the individual thumbnails
         X is the width of the individual thumbnails
         P indicates if anyone can edit the wishlist (1 = Yes)

*/


if (!class_exists('WishPics_For_WordPress')) {
   class WishPics_For_WordPress {

/*****************************************************************************************/
      /// Settings:
/*****************************************************************************************/
      var $optionList = array(
         'name' => array( 'Name' => "Name", 'Description' => "Name of your Wishlist", 'Default' => 'Wishlist', 'Type' => 'text'),
         'list' => array( 'Name' => "Wishlist", 'Description' => "Which wishpics list to display", 'Default' => 'MrMen', 'Type' => 'wishlist'),
         'thumbWidth' => array( 'Name' => "Thumbnail Width", 'Description' => "Thumbnail Width", 'Default' => '60px', 'Type' => 'text'),
         'thumbHeight' => array('Name' => "Thumbnail Height", 'Description' => "Thumbnail Height", 'Default' => '60px', 'Type' => 'text'),
         'arrayWidth' => array('Name' => "Array Width", 'Description' => "The total width of the Wishlist array", 'Default' => '', 'Type' => 'text'),
         'public' => array('Name' => "Public Access", 'Description' => "Allow anonymous users ability to update the Wishlist.", 'Default' => "1", 'Type' => "checkbox"),
         'accessLevel' => array('Name' => "Access Level", 'Description' => "Set the User Access Level to enable update of the Wishlist", 'Default' => "administrator", 'Type' => "selection", 
                                'Options' => array('Subscriber', 'Contributor','Author','Editor','Administrator')));
			
      // String to insert into Posts to indicate where to insert the Wishlist image map
      var $TagHead          = '<!--wishpics';
      var $TagTail          = '-->';
      var $optionName       = 'WishPicsOptions';
      var $titlesOptionName = 'WishPicsTitles';
      var $itemsOptionName  = 'WishPicsItems';
      var $defaultTag       = 'livpauls-21';

      // Settings
      var $Id = 0;
      var $Settings = array();      // Holds local modified settings (will always be at least defaults)

      var $Opts     = null;         // Holds a copy of the settings for all WishLists
      var $Titles   = array();      // Holds details of all WishPics Lists


/*****************************************************************************************/
      /// Hooks to Initialise and Load the Plugin
/*****************************************************************************************/
      function WishPics_For_WordPress() {
         $this->__construct();
      }

      function __construct() {
         $this->addActions();
         $this->addFilters();
         $this->loadLists();
         $this->URLRoot = plugins_url("", __FILE__);
      }

      function loadLists() {
        $files = glob(dirname(__FILE__) . "/lists/*.php");

         $this->Titles = get_option($this->titlesOptionName);
         if (is_array($files)) {
            foreach($files as $list_filename) {
               include ($list_filename);
            }
         }
      }

      function addActions() {
         add_action('admin_menu', array($this, 'optionsMenu'));
         add_action('init', array($this, 'handlePost'));
      }

      function addFilters() {
         add_filter('the_posts', array($this, 'stylesNeeded'));
         add_filter('the_content', array($this,'contentFilter'));
      }

      function optionsMenu() {
         //                             v- Page Title,    v-Menu Name v- capability, slug, v- function
         $mypage = add_management_page('Manage WishPics', 'WishPics', 8, __FILE__, array($this,'showOptions'));
         add_action( "admin_print_styles-$mypage", array($this,'HeaderContent') );
         add_action( "admin_print_scripts-$mypage", array($this,'HeaderScripts') );
      }

      /// Load styles only on Our Admin page or when Wishpics is displayed...

      function HeaderScripts() {
         $scripts= plugins_url("WishPics.js", __FILE__);
         wp_enqueue_script('wishpics-script', $scripts);
      }

      function HeaderContent() {
         $stylesheet = plugins_url("WishPics.css", __FILE__);
         wp_enqueue_style('wishpics-style', $stylesheet);
      }

      function stylesNeeded($posts){
         if (empty($posts)) return $posts;
 
         foreach ($posts as $post) {
            if (stripos($post->post_content, $this->TagHead)) {
               $this->HeaderContent();
               break;
            }
         }
         return $posts;
      }


/*****************************************************************************************/
      /// Searches through the_content for our 'Tag' and replaces it with the Wishlist image map
/*****************************************************************************************/
      function contentFilter($content) {
         $newContent='';
         $index=0;
         $found = 0;

         while ($found !== FALSE) {
            $found = strpos($content, $this->TagHead, $index);
            if ($found === FALSE) {
               // Add the remaining content to the output
               $newContent = $newContent . substr($content, $index);
               break;
            } else {
               // Need to parse any arguments
               $tagEnd = strpos($content, $this->TagTail, $found);
               $arguments = substr($content, $found + strlen($this->TagHead), ($tagEnd-$found-strlen($this->TagHead)));

               $this->parseArgs($arguments);

               // Generate the Wishlist and add it to the output.
               $output = $this->generateWishPics($this->Id);
               $newContent = $newContent . substr($content, $index, ($found-$index));
               $newContent = $newContent . $output;
               $index = $tagEnd + strlen($this->TagTail);
            }
         }
         return $newContent;
      }

/*****************************************************************************************/
      /// Options
/*****************************************************************************************/

      function getOptions() {
         if (null === $this->Opts) {
            $this->Opts= get_option($this->optionName, array());
         }
         return $this->Opts;
      }

      function saveOptions($Opts) {
         if (!is_array($Opts)) {
            return;
         }
         update_option($this->optionName, $Opts);
         $this->Opts = $Opts;
      }

      function deleteOptions() {
         delete_option($this->optionName);
      }

      function deleteTitle($Title) {
         $Titles = get_option($this->titlesOptionName, array());
         unset($Titles[$Title]);
         unset($this->Titles[$Title]);
         update_option($this->titlesOptionName, $Titles);
      }

      function updateTitle($Title, $Details) {
         if(is_array($Details)) {
            $Titles = get_option($this->titlesOptionName);
            unset($Titles[$Title]);
            unset($this->Titles[$Title]);
            $Titles = array_merge((array)$Titles, $Details);
            update_option($this->titlesOptionName, $Titles);
            $this->Titles = array_merge((array)$this->Titles, $Details);
         }
      }

      function getItems($Id = False) {
         $Items = get_option($this->itemsOptionName, array());
         if ($Id !== False) {
            $Items = $Items[$Id];
         }
         return $Items;
      }

      /*
       * Parse the arguments passed in.
       */
      function parseArgs($arguments) {

         $args = array();
         parse_str(html_entity_decode($arguments), $args);

         // Id should always be given
         $this->Id = (int)(isset($args['id']) ? $args['id'] : 0);

         $Opts = $this->getOptions();
         unset($this->Settings);

         /*
          * Check for each setting, local overides saved option, otherwise fallback to default.
          */
         foreach ($this->optionList as $key => $details) {
            if (isset($args[$key])) {
               $this->Settings[$key] = $args[$key];
            } else if (isset($Opts[$this->Id][$key])) {
               $this->Settings[$key] = $Opts[$this->Id][$key];
            } else if (isset ($details['Default'])) {
               $this->Settings[$key] = $details['Default'];      // Use default
            }
         }

      }

      function checkAccess($Id) {
         $Opts = $this->Settings;
         if (($Opts['public'] == '1') || 
             current_user_can(strtolower($Opts['accessLevel']))) {
            return True;
         } else {
            return False;
         }
      }

/*****************************************************************************************/
      /// Display Content
/*****************************************************************************************/

      function generateWishPics($Id = 0) {
         return include('include/showList.php');
      }

      function showOptions() {

         // Default do nothing.
         $action = isset($_POST[ 'WishPicsAction' ]) ? $_POST[ 'WishPicsAction' ] : 'No Action';

         if ( ($action == "Create List" ) || ($action == "Edit List" ))
         {
            include('include/createList.php');
         } else {
            include('include/showOptions.php');
         }
      }

      function handlePost() {
         include('include/handlePost.php');
      }

/*****************************************************************************************/

      function amazonGetLink($asin)
      {
         if (function_exists('amazon_get_link')) {
            return amazon_get_link("asin=".$asin);
         } else {
            return "http://www.amazon.co.uk/gp/product/". $asin. "?ie=UTF8&tag=" . $this->defaultTag ."&linkCode=as2&camp=1634&creative=6738&creativeASIN=". $asin;
         }
      }

   } /* End of Class */

   $wpfw = new WishPics_For_WordPress;

   /*
    * Wrapper function so we can add the Wishlist image to the template directly
    */
   function display_wish_pics ($args) {
      global $wpfw;
      $wpfw->parseArgs($args);
      echo $wpfw->generateWishPics($wpfw->Id);
   }

} /* End If Exists */

/*****************************************************************************************/

?>