<?php
/*****************************************************************************************/

/*
 * Admin Panel Supporting Functions
 *
 * Require settings for:
 *                      selecting instance of wishlist
 *                      selecting which items have been ticked off
 *                      Changing the presentation of the wishlist

Array containing items matching globals:

Settings['0'] => array('MrMenThumbWidth' => X, 'MrMenThumbHeight' => Y,
                  'MrMenItems' => array( 'Happy' => '1', 'Strong' => '1' ));

On creation loop through static array at top to create MrMenItems.
On subsequent access on read must check isset.

 */

   $Opts = $this->getOptions();
   unset($Opts['Temp']);
   $NewId = is_array($Opts) ? array_pop(array_keys($Opts)) + 1 : 0;
   $itemSettings = $this->getItems();

   // Get the Wish List ID if selected.
   if (isset($_POST['WishPicsId'])) {
      $Id=$_POST['WishPicsId'];
   } else {
      // Default to first wishlist in array.
      $Id = is_array($Opts) ? array_shift(array_keys($Opts)) : 0;
   }

/*****************************************************************************************/

   // User Input, grab settings.
   if (($action == __('Update Options', 'wish-pics') ) || ($action == __('New', 'wish-pics') )) {
       foreach ($this->optionList as $optName => $optDetails) {
         // Read their posted value
         $Opts[$Id][$optName] = stripslashes(isset($_POST[$optName]) ? $_POST[$optName] : null);
      }
   }

   // See if the user has posted us some information
   // If they did, the admin Nonce should be set.
   $NotifyUpdate = False;
   if( ($action == __('Update Options', 'wish-pics')) && check_admin_referer( 'update-WishPics-options')) {

      // Update Current Wishlist settings
      $this->saveOptions($Opts);
      $NotifyUpdate = True;
   } elseif (($action == __('New', 'wish-pics')) && check_admin_referer( 'update-WishPics-options')) {

      // Create new list based on the previous one.
      foreach ($this->optionList as $optName => $optDetails) {
         // Read their posted value
         $Opts[$NewId][$optName] = $Opts[$Id][$optName];
      }
      $Id = $NewId;

      // Tweak the name.
      $Opts[$Id]['name'] = __('New', 'wish-pics') . ' ' . $Opts[$Id]['name'];

      $this->saveOptions($Opts);

      $NotifyUpdate = True;
   } elseif (($action == __('Delete', 'wish-pics')) && check_admin_referer( 'update-WishPics-options')) {

      // Delete the selected wishlist and drop back to the first.

      unset($Opts[$Id]);
      unset($itemSettings[$Id]);
      $this->saveOptions($Opts);
      update_option( 'WishPicsItems', $itemSettings );
      $Id=0;
   } elseif (($action == __('Delete List', 'wish-pics')) && check_admin_referer( 'update-WishPics-options')) {
 
      // Delete the Wish list template
      $this->deleteTitle($_POST['list']);
      if ($Opts[$Id]['list'] == $_POST['list']) {
         unset($Opts[$Id]['list']);  // Force fallback to default.
      }
   }

/*****************************************************************************************/

   /*
    * If first run or just deleted the last wishlist, need to create a default one
    */
   $Update=False;
   foreach ($this->optionList as $optName => $optDetails) {
      if(!isset($Opts[$Id][$optName])) {
         $Opts[$Id][$optName] = $optDetails['Default'];
         $Update=True;
      }
   }
   if ($Update && current_user_can('manage_options'))
      $this->saveOptions($Opts);


   // Set up defaults for this wishlist
   $Update=False;
   foreach ($this->Titles[$Opts[$Id]['list']] as $item => $itemDetails) {
      if ( !isset($itemSettings[$Id][$item])) {
         // Settings don't exist
         $itemSettings[$Id][$item]['Status'] = '0'; // Available 
         $itemSettings[$Id][$item]['Comment'] = ''; // No comment
         $Update=True;
      }
   }
   if ($Update && current_user_can('manage_options'))
      update_option( 'WishPicsItems', $itemSettings );



/*****************************************************************************************/

   if ($NotifyUpdate) {
      // **********************************************************
      // Put an options updated message on the screen
?>

<div class="updated">
 <p><strong><?php _e('Options saved.', 'wish-pics' ); ?></strong></p>
</div>

<?php
   }

/*****************************************************************************************/

   // **********************************************************
   // Now display the options editing screen


?>

<div class="wrap">
 <h2><?php _e('Wish Pics Plugin Options', 'wish-pics')?></h2>
 <form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">

<?php wp_nonce_field('update-WishPics-options'); ?>

  <table class="form-table">

   <tr valign="top">
    <th scope="row"><label for="WishPicsId">Wishlist</label></th>
    <td>
     <div>
      <div style="float:left; width:210px">
       <select style="width:200px;" name="WishPicsId" id="WishPicsId" class='postform'>

<?php

   foreach ($Opts as $key => $Details) {
      echo "<option value='$key' " . selected($key == $Id) .">". $Details['name'] . " [Id = $key]</option>";
   }
?>
       </select>
      </div>
      <input class="button-secondary" type="submit" name="WishPicsAction" value="<?php _e('Select', 'wish-pics')?>" />
      <input class="button-secondary" type="submit" name="WishPicsAction" value="<?php _e('Delete', 'wish-pics')?>" />
      <input class="button-secondary" type="submit" name="WishPicsAction" value="<?php _e('New', 'wish-pics')?>" />
     </div>
    </td>
   </tr>

<?php 

   $Opts= $Opts[$Id];

   // Loop through the options table, display a row for each.
   foreach ($this->optionList as $optName => $optDetails) {
   if ($optDetails['Type'] == 'checkbox') {

   // Insert a Check Box Item
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
   <tr valign="top">
    <th scope="row"><label for="<?php echo $optName; ?>"><?php echo $optDetails['Name']; ?></label></th>
    <td>
     <input name="<?php echo $optName; ?>" type="checkbox" value="1" <?php checked($Opts[$optName] == "1") ?>/>
     <br />
     <?php echo $optDetails['Description']?>

    </td>
  </tr>

<?php
      } else if ($optDetails['Type'] == 'selection') {

   // Insert a Dropdown Box Item
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
   <tr valign="top">
    <th scope="row"><label for="<?php echo $optName; ?>"><?php echo $optDetails['Name']; ?></label></th>
    <td>
     <select style="width:200px;" name="<?php echo $optName; ?>" id="<?php echo $optName; ?>" class='postform'>

<?php
   foreach ($optDetails['Options'] as $key => $Details) {
      echo "<option value='$Details' ". selected( $Opts[$optName] == $Details ). " >" . $Details . "</option>";
   }
?>
      </select>
     <br />
     <?php echo $optDetails['Description']; ?>
    </td>
  </tr>
<?php
      } else if ($optDetails['Type'] == 'wishlist') {

   // Insert Wishlist Selector
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
   <tr valign="top">
    <th scope="row"><label for="<?php echo $optName; ?>"><?php echo $optDetails['Name']; ?></label></th>
    <td>
     <div>
      <div style="float:left; width:210px">
       <select style="width:200px;" name="<?php echo $optName; ?>" id="<?php echo $optName; ?>" class='postform'>

<?php
   foreach ($this->Titles as $listName => $Details) {
      echo "<option value='$listName' ". selected( $Opts[$optName] == $listName ). " >" . $listName. "</option>";
   }
?>
      </select>
      </div>
<?php
   
   if (class_exists('AmazonWishlist_For_WordPress')) {
?>
      <input class="button-secondary" type="submit" name="WishPicsAction" value="<?php _e('Create List', 'wish-pics')?>" />
      <input class="button-secondary" type="submit" name="WishPicsAction" value="<?php _e('Edit List', 'wish-pics')?>" />
      <input class="button-secondary" type="submit" name="WishPicsAction" value="<?php _e('Delete List', 'wish-pics')?>" />
<?php
   }
?>
     </div><div style="clear:both">
     <?php echo $optDetails['Description']; ?></div>
    </td>
  </tr>
<?php
   } else {

   // Insert a Text Item
   //////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
   <tr valign="top">
    <th scope="row"><label for="<?php echo $optName; ?>"> <?php echo $optDetails['Name']; ?></label></th>
    <td>
     <input name="<?php echo $optName; ?>" type="text" value="<?php echo $Opts[$optName]; ?>" size="20" />
     <br />
<?php echo $optDetails['Description']?>
    </td>
   </tr>

<?php
      }
   }
?>

  </table>

  <p class="submit">
   <input type="submit" class="button-primary" name="WishPicsAction" value="<?php _e('Update Options', 'wish-pics')?>" />
  </p>
 </form>
</div>

<?php 
 $this->parseArgs("id=$Id&public=1");
 echo $this->generateWishPics($Id);

?>