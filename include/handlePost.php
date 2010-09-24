<?php

   // See if the user has posted us some information
   // If they did, this hidden field will be set to 'Y'
   if (isset($_POST[ 'WishPicsAction' ])) {
      $postAction  = $_POST[ 'WishPicsAction' ];
      $nonce = $_POST['_wpnonce'];
      if( $postAction && wp_verify_nonce($nonce, 'update-WishPics-items') ) {
         // Read their posted value
         $postId      = $_POST[ 'WishPicsId' ];
         $postItem    = $_POST[ 'WishPicsItem' ];
         $postComment = $_POST[ 'WishPicsComment' ];
         $Opts = $this->getOptions();
         if ( isset($postId) ) {//&& $this->checkAccess($postId)) {
            $itemSettings = $this->getItems();
            if ($postAction == __('Remove', 'wish-pics')) {
               $itemSettings[$postId][$postItem]['Status'] = '1';
               $itemSettings[$postId][$postItem]['Comment'] = $postComment;
            }  
            if ($postAction == __('Add', 'wish-pics')) {
               $itemSettings[$postId][$postItem]['Status'] = '0';
               $itemSettings[$postId][$postItem]['Comment'] = $postComment;
            }  
            update_option( $this->itemsOptionName, $itemSettings );
         }
      }
   }
?>