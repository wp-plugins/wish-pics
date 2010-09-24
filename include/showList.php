<?php

   $Opts = $this->Settings;

   $itemSettings = $this->getItems($Id);

   $tickWidth  = $Opts['thumbWidth'] - 8;
   $tickHeight = $Opts['thumbHeight'] - 8;
   $tickURL    = $this->URLRoot . "/Images/tick.gif";
   
   $output = '<p><div class="WishPicsWishlist">' . "\n";
   if (isset($Opts['arrayWidth'])) {
      $output = $output . '<div class="WishPicsArray" style="display:table; width:'. $Opts['arrayWidth'] .';">' . "\n";
   } else {
      $output = $output . '<div class="WishPicsArray" style="display:table">' . "\n";
   }
   foreach ($this->Titles[$Opts['list']] as $key => $Title) {
      if (isset($Title['type']) && ($Title['type'] == "Amazon")) {
         $defLink = $this->amazonGetLink($Title['id']);
         $defLinkText= "Available from <a href=\'$defLink\'>Amazon</a>";
         $defLinkTextN= "Available from <a href='$defLink'>Amazon</a>";
      } else if (isset($Title['link'])) {
         $defLink = $Title['link'];
         $defLinkText= "Available from <a href=\'$defLink\'>here</a>";
         $defLinkTextN= "Available from <a href='$defLink'>here</a>";
      } else {
         $defLink = "";
         $defLinkText= "";
         $defLinkTextN= "";
      }

      $defKey         = $key;
      $defComment     = $itemSettings[$key]['Comment'];
      $defDescription = isset($Title['description']) ? $Title['description'] : "";

      // Local Images?
      if (substr($Title['image'],0,5) == "http:") {
         $imageRoot = "";
      } else {
         $imageRoot = $this->URLRoot . "/";
      }

      $defImageL      = $imageRoot . $Title['image'];
      if (isset($Title['thumb'])) {
         $defImage       = $imageRoot .$Title['thumb'];
      } else {
         $defImage       = $imageRoot .$Title['image'];
      }

      $defTitle       = isset($Title['title']) ? $Title['title'] : "";

      $canUpdate = $this->checkAccess($Id);

      if ($canUpdate) {
         $java = "\"document.getElementById('WishPicsItem').value='$key'; ";
         $java = $java ."document.getElementById('WishPicsCommentInput').value ='".addslashes($defComment)."'; ";
      } else {
         $java = "\"document.getElementById('WishPicsComment').innerHTML ='".addslashes($defComment)."'; ";
      }
      $java = $java ."document.getElementById('WishPicsDescription').innerHTML='".addslashes($defDescription)."'; ";
      $java = $java ."document.getElementById('WishPicsTitle').innerHTML='".addslashes($defTitle)."'; ";
      $java = $java ."document.getElementById('WishPicsLink').href='$defLink'; ";
      $java = $java ."document.getElementById('WishPicsLinkText').innerHTML='".addslashes($defLinkText)."'; ";
      $java = $java ."document['WishPicsImage'].src='$defImageL'; ";
 
      $java = $java . "\"";

      if ($itemSettings[$key]['Status'] == "1") {
         $pre = "<div onclick=". $java . " style='background-color: #fafafa; float: left;' id='WishPicsItem" . $key ."'><div style='position:relative; display:block;'><div style='opacity : 0.2; filter: alpha(opacity=40);'>";
      } else {
         $pre = "<div onclick=". $java . " style='float:left' id='WishPicsItem" . $key ."'><div style='position:relative; display:block;'><div>";
      }
      $cover = $pre ."<img height='" . $Opts['thumbHeight'] . "' width='" . $Opts['thumbWidth'] . "'src='" . $defImage . "' alt='" . addslashes($defTitle) . "'></div>\n";
      if ($itemSettings[$key]['Status'] == "1") {
         $cover = $cover. "<div style='position:absolute;left:4px;top:4px; z-index:2'><img width='$tickWidth' height='$tickHeight' src='$tickURL'></div>\n";
      }
      $cover = $cover. "</div></div>\n";
      $output = $output . $cover;
   }
   $output = $output . "</div>\n";
   $details = '<p><div style="width:400px; display: table"><div style="float:left"><a id="WishPicsLink" href="'.$defLink.'" ><img id="WishPicsImage" NAME="WishPicsImage" width="200px" src="'.$defImageL.'"></a></div>'."\n";
   $details = $details. '<div class="WishPicsDetails"><div id="WishPicsTitle">'.addslashes($defTitle).'</div><div id="WishPicsDescription">'.addslashes($defDescription).'&nbsp;</div>';
   $details = $details. '<div id="WishPicsLinkText">'.$defLinkTextN.'</div>';
   if (!$canUpdate) {
      $details = $details .'<div class="WishPicsComment" id = "WishPicsComment">'. $defComment . '</div>';
   } else {
      $form = "<form name='form3' method='post' action='". str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "'>";
      $nonce= wp_create_nonce  ('update-WishPics-items');
      $form = $form. "<input type='hidden' name='_wpnonce' value='$nonce'>";
      $form = $form. "<input class='WishPicsInput' id='WishPicsCommentInput' name='WishPicsComment' type='text' value='". addslashes($defComment) ."' />";
      $form = $form. "<input class='WishPicsButton' type='submit' name='WishPicsAction' value='". __('Remove', 'wish-pics'). "' />";
      $form = $form. "<input class='WishPicsButton' type='submit' name='WishPicsAction' value='". __('Add', 'wish-pics'). "' />";
      $form = $form. "<input name='WishPicsId' type='hidden' value='$Id' />";
      $form = $form. "<input id = 'WishPicsItem' name='WishPicsItem' type='hidden' value='$defKey' size='20' />";
      $form = $form. "</form>\n";
      $details = $details. $form;
   }
   $details = $details. '</div></div></p></br>'. "\n";
   $output = $output . $details;
   $output = $output . '</div></p>';
 
   return $output;
?>