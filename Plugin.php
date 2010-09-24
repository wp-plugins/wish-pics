<?php
/*
Plugin Name: Wish Pics
Plugin URI: http://www.houseindorset.co.uk/plugins/wish-pics
Description: Provides a graphical wishlist displaying a grid of front covers, indicating which books/CDs you have in your collection.
Author: Paul Stuttard
Version: 1.1
Text Domain: wish-pics
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
      var $optionList = array();    // Will hold the plugin options
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
         $this->base_name  = plugin_basename( __FILE__ );
         $this->plugin_dir = dirname( $this->base_name );
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
         add_action('init', array($this, 'loadLang'));
      }

      function addFilters() {
         add_filter('plugin_row_meta', array($this, 'registerPluginLinks'),10,2);
         add_filter('the_posts', array($this, 'stylesNeeded'));
         add_filter('the_content', array($this,'contentFilter'));
       }

      function registerPluginLinks($links, $file) {
         if ($file == $this->base_name) {
            $links[] = '<a href="options-general.php?page=' . $this->base_name .'">' . __('Settings','wish-pics') . '</a>';
         }
         return $links;
      }

      function optionsMenu() {
         $my_page = add_options_page(__('Manage WishPics', 'wish-pics'), __('Wish Pics', 'wish-pics'), 'manage_options', __FILE__, array($this, 'showOptions'));
         add_action( "admin_print_styles-$my_page", array($this,'HeaderContent') );
         add_action( "admin_print_scripts-$my_page", array($this,'HeaderScripts') );
      }

      function loadLang() {
         /* load localisation  */
         load_plugin_textdomain('wish-pics', $this->plugin_dir . '/i18n', $this->plugin_dir . '/i18n');

         /* Move Option List construction here so we can localise the strings */
         $this->optionList = array(
            'name' => array( 'Name' => __('Name', 'wish-pics'), 'Description' => __('Name of your Wishlist', 'wish-pics'), 'Default' => __('Wishlist', 'wish-pics'), 'Type' => 'text'),
            'list' => array( 'Name' => __('Wishlist', 'wish-pics'), 'Description' => __('Which wishpics list to display', 'wish-pics'), 'Default' => 'MrMen', 'Type' => 'wishlist'),
            'thumbWidth' => array( 'Name' => __('Thumbnail Width', 'wish-pics'), 'Description' => __('Thumbnail Width', 'wish-pics'), 'Default' => '60px', 'Type' => 'text'),
            'thumbHeight' => array('Name' => __('Thumbnail Height', 'wish-pics'), 'Description' => __('Thumbnail Height', 'wish-pics'), 'Default' => '60px', 'Type' => 'text'),
            'arrayWidth' => array('Name' => __('Array Width', 'wish-pics'), 'Description' => __('The total width of the Wishlist array', 'wish-pics'), 'Default' => '', 'Type' => 'text'),
            'public' => array('Name' => __('Public Access', 'wish-pics'), 'Description' => __('Allow anonymous users ability to update the Wishlist.', 'wish-pics'), 'Default' => '1', 'Type' => 'checkbox'),
            'accessLevel' => array('Name' => __('Access Level', 'wish-pics'), 'Description' => __('Set the User Access Level to enable update of the Wishlist', 'wish-pics'), 'Default' => __('administrator', 'wish-pics'), 'Type' => 'selection', 
                                'Options' => array(__('Subscriber', 'wish-pics'), __('Contributor', 'wish-pics'), __('Author', 'wish-pics'), __('Editor', 'wish-pics'), __('Administrator', 'wish-pics') )));
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

         if ( ($action == __('Create List', 'wish-pics') ) || ($action == __('Edit List', 'wish-pics') ))
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