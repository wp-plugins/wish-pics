<?php

   $optionList = array(
         'Id' => array( 'Name' => "Id", 'Type' => 'hidden'),
         'name' => array( 'Name' => "Name", 'Description' => "Name of your Wishlist", 'Default' => 'Wishlist', 'Type' => 'text'),
         'index' => array( 'Name' => "Product Index", 'Description' => "Which Amazon Product Index to Search through", 'Default' => 'Books', 'Type' => 'selection', 
                           'Options' => array ( 'Apparel', 'Baby','Beauty','Blended','Books','Classical','DigitalMusic','DVD','Electronics','ForeignBooks','GourmetFood','HealthPersonalCare','HomeGarden',
                                               'Jewelry','Kitchen','Magazines','Merchants','Miscellaneous','Music','MusicalInstruments','MusicTracks','OfficeProducts','OutdoorLiving','PCHardware',
                                               'Photo','Restaurants','Software','SoftwareVideoGames','SportingGoods','Tools','Toys','VHS','Video','VideoGames','Wireless','WirelessAccessories') ),
         'author' => array('Name' => "Author", 'Description' => "Author or Artist to search for", 'Type' => 'text', 'Default' => ''),
         'title' => array('Name' => "Title", 'Description' => "Items Title to search for", 'Type' => 'text', 'Default' => ''),
         'page' => array('Name' => "Page", 'Description' => "Page of Search Results", 'Default' => "1", 'Type' => 'text'),
         'items' => array('Name' => "Items", 'Description' => "Number of Items so Far Selected", 'Type' => 'text', 'Default' => '0'));

   $listAction = isset($_POST['CreateListAction']) ? $_POST['CreateListAction'] :'No Action';

   if (($listAction == "No Action") && check_admin_referer( 'update-WishPics-options')) {
      
      $Opts['Id'] = $_POST['WishPicsId'];
      $Opts['name'] = stripslashes($_POST['list']);
      // First Entry into Page, clear options
      if ($action == "Edit List") {

         // Retrieve the list content
         foreach ($this->Titles[$Opts['name']] as $defTag => $Details) {
            $AmazonItem[$Details['id']] = '1';
         }
         $Opts['items'] = count($AmazonItem);
      }

   } else {

      // Subsequent entry, retrieve previous data
      // Update Current Wishlist settings

      foreach ($optionList as $optName => $optDetails) {
         if (isset($optDetails['Name'])) {
            // Read their posted value
            $Opts[$optName] = stripslashes($_POST[$optName]);
         }
      }
      if ($listAction  == "Search") {
         $Opts['page'] = 1;
      }
      if ($listAction  == "Next") {
         $Opts['page'] += 1;
      }
      if (($listAction == "Previous") && ($Opts['page'] > 1)) {
         $Opts['page'] -= 1;

      }
      $AmazonItem=$_POST['AmazonItem'];
      $Opts['items'] = count($AmazonItem);

      if (($listAction  == "Save")) {
         $Titles = array();
         $defKey=0;
         foreach ($AmazonItem as $ASIN => $key) {

            $attempts=4;
            $request = array("Operation"=>"ItemLookup","ItemId"=>$ASIN,"ResponseGroup"=>"Small,Images","IdType"=>"ASIN","MerchantId"=>"Amazon");
            do {
               $pxml = amazon_query($request);
               if ($pxml == False) {
                  $attempts--;
                  usleep(200); echo "<PRE>+</PRE>";
               }
            } while (($pxml == False) && ($attempts >0));

            $result = $pxml['Items']['Item'];
            $defTitle       = $result['ItemAttributes']['Title'];
            $r_artist = isset($result['ItemAttributes']['Artist']) ? $result['ItemAttributes']['Artist'] :
                        (isset($result['ItemAttributes']['Author']) ? $result['ItemAttributes']['Author'] :
                         (isset($result['ItemAttributes']['Creator']) ? $result['ItemAttributes']['Creator'] : '-'));
            $r_manufacturer = isset($result['ItemAttributes']['Manufacturer']) ? $result['ItemAttributes']['Manufacturer'] : '-';

            if (isset($result['MediumImage']))
               $defImage       = $result['MediumImage']['URL'];
            else
               $defImage       = "http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif";

           if (isset($result['LargeImage']))
               $defImageL       = $result['LargeImage']['URL'];
            else
               $defImageL       = "http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif";

            $defDescription ="by " .$r_artist . " [" . $r_manufacturer . "]";

            // Make Short Form Name:
            $words = str_word_count($defTitle, 2);
            $words = array_slice( $words, 0, 3);
            $defTag = implode('', $words).$defKey;

            $Titles[$Opts['name']][$defTag] = array( 'title' => $defTitle, 'type' => 'Amazon', 'id' => $ASIN,
                                                     'image' => $defImageL, 'thumb' => $defImage, 'description' => $defDescription);
            $defKey++;
         }

         $this->updateTitle($Opts['name'], $Titles);
      }
   }

   /*
    * If first run need to create a default options
    */

   foreach ($optionList as $optName => $optDetails) {
      if(!isset($Opts[$optName]) && isset($optDetails['Default'])) {
         $Opts[$optName] = $optDetails['Default'];
      }
   }

?>
<div class="wrap">
 <h2>Wish Pics List Creation Tool</h2>
 <form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">

<?php wp_nonce_field('update-WishPics-options'); ?>

<?php

   /////////////////////////////////////////////////////////////////////////////////////////////////////

   // Display Search Options
   if (($listAction != "Create") && check_admin_referer( 'update-WishPics-options')) {

?>
  <table class="form-table">
   <tr valign="top">
    <td>
      <input type="hidden" name="WishPicsAction" value="Create List" />
      <input class="button-secondary" type="submit" name="WishPicsAction" value="Back to Settings" />
    </td>
   <tr>

<?php 

   // Loop through the options table, display a row for each.
   foreach ($optionList as $optName => $optDetails) {
   if ($optDetails['Type'] == "checkbox") {

   // Insert a Check Box Item

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
      } else if ($optDetails['Type'] == "selection") {

      // Insert a Dropdown Box Item

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
   } else if ($optDetails['Type'] == "hidden") {

   // Insert hidden item
?>
   <input name="<?php echo $optName; ?>" type="hidden" value="<?php echo $Opts[$optName]; ?>" />
<?php

   } else {

   // Insert a Text Item
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

   <tr valign="top">
    <th scope="row">
   <p class="submit">
    <input type="submit" class="button-primary" name="CreateListAction" value="Search" />
   </p>
  </td></tr>
 </table>

<?php

   /////////////////////////////////////////////////////////////////////////////////////////////////////

   // Display page of items to consider
   if (($Opts['title'] != "") || ($Opts['author'] != "") || ($Opts['items'] > 0)) {

      if ($Opts['index'] == 'Books') {
         $Term = "Author";
      } else if ($Opts['index'] == 'Music') {
         $Term = "Artist";
      } else if ($Opts['index'] == 'DVD') {
         $Term = "Publisher";
      } else {
         $Term = "Manufacturer";
      }

      // First create query to first 10 matching items
      $request = array("Operation" => "ItemSearch",
                       "ResponseGroup" => "Images,Small",
                       $Term=>$Opts['author'],
                       "Title"=>$Opts['title'],
                       "SearchIndex"=>$Opts['index'],
                       "Sort"=>"salesrank",
                       "MerchantId"=>"Amazon",
                       "ItemPage"=>$Opts['page'],
                       "AssociateTag"=>"livpaul-21");

      $pxml = amazon_query($request);

      if (($pxml === False) || !isset($pxml['Items']['Item'])) {
         echo "<PRE>Search returned no results.</PRE>";
         $Items = array();
      } else {
         $Items=$pxml['Items']['Item'];
      }
?>

   <input type="checkbox" onclick="myselectcb('AmazonItems','AmazonItem', 'checked')">
   <div id="AmazonItems">

<?php

      for ($counter = 0; $counter < count($Items) ; $counter++) {
         $ASIN = $Items[$counter]['ASIN'];
         $request = array("Operation"=>"ItemLookup","ItemId"=>$ASIN,"ResponseGroup"=>"Small,Reviews,Images,Offers,SalesRank","IdType"=>"ASIN","MerchantId"=>"Amazon");

         $pxml = amazon_query($request);

         $result = $pxml['Items']['Item'];
         $r_title  = $result['ItemAttributes']['Title'];
         $r_artist = isset($result['ItemAttributes']['Artist']) ? $result['ItemAttributes']['Artist'] :
                     (isset($result['ItemAttributes']['Author']) ? $result['ItemAttributes']['Author'] :
                      (isset($result['ItemAttributes']['Creator']) ? $result['ItemAttributes']['Creator'] : '-'));
         $r_manufacturer = isset($result['ItemAttributes']['Manufacturer']) ? $result['ItemAttributes']['Manufacturer'] : '-';

         if (isset($result['MediumImage']))
           $r_s_url  = $result['MediumImage']['URL'];
         else
           $r_s_url  = "http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif";

         $r_url    = $result['DetailPageURL'];
         $r_rank   = $result['SalesRank'];
         $r_rating = isset($result['CustomerReviews']['AverageRating']) ? $result['CustomerReviews']['AverageRating'] : '-';
         $r_price  = utf8_decode(substr($result['Offers']['Offer']['OfferListing']['Price']['FormattedPrice'],0));

?>
<div style='width:100%;height:130px; margin: 3px; border-bottom: 1px dashed;' class='amazon_prod'>
 <div style='height:7em;float:right;border:1px dotted; padding:5px;margin-right:10px; width:7em'>
  <A style='text-align:center;' href='<?php echo $r_url; ?>'>
   <IMG style='margin-left:auto; margin-right:auto; display: block; height:7em' class='amazon_pic' src='<?php echo $r_s_url; ?>'>
  </a>
 </div>
 <div style='width:65%; float:left'>
  <p style='margin:0; line-height: 1em;'>
   <input id="AmazonItem" name="AmazonItem[<?php echo $ASIN; ?>]" type="checkbox" value="1" <?php checked(isset($AmazonItem[$ASIN])); ?>/>
   <a href='<?php echo $r_url ."'>" . $r_title; ?></a>
  </p>
  <p style='margin:0; line-height: 1em;'>by <?php echo $r_artist . " [" . $r_manufacturer; ?>]</p>
  <p style='margin:0; margin-top:4.5em; line-height: 1em;'>Rank/Rating: <?php echo $r_rank ."/". $r_rating; ?></p>
  <p style='margin:0; line-height: 1em;'><b>Price <span style='color:red;'><?php echo $r_price; ?></span></b></p>
 </div>
</div>
<?php
         unset($AmazonItem[$ASIN]);
      }
?>
    </div>
<?php 
      /////////////////////////////////////////////////////////////////////////////////////////////////////

      // Create list of Items already selected
      if (is_array($AmazonItem)) {
         foreach ($AmazonItem as $Item => $key) {
      ?>
      <input type='hidden' name='AmazonItem[<?php echo $Item; ?>]' value = '1'>
      <?php
         }
      }
?>
   <p class="submit">
    <input type="submit" class="button-primary" name="CreateListAction" value="Previous" />
    <input type="submit" class="button-primary" name="CreateListAction" value="Next" />
    <input type="submit" class="button-primary" name="CreateListAction" value="Create" />
   </p>

<?php
      }

   } else {

   /////////////////////////////////////////////////////////////////////////////////////////////////////
 
   // Display sample Wishlist
?>
   <table class="form-table">
    <tr valign="top">
     <td colspan="2">
      <p class="submit">
       <input type="hidden" name="WishPicsAction" value="Create List" />
       <input class="button-secondary" type="submit" name="CreateListAction" value="Back to Search Results" />
       <input type="submit" class="button-primary" name="CreateListAction" value="Save" />
      </p>
     </td>
    <tr>
   </table>
<?php

      $Options = $this->getOptions();
      $Options = $Options[$Opts['Id']];

      // Show Sample Wish Pics
      // Need Small Image, Link, Title, Tag
?>
   <div class="WishPicsArray" style="display:table">
<?php

      if (is_array($AmazonItem)) {
         $defKey=0;
         foreach ($AmazonItem as $ASIN => $key) {

            $request = array("Operation"=>"ItemLookup","ItemId"=>$ASIN,"ResponseGroup"=>"Small,Images","IdType"=>"ASIN","MerchantId"=>"Amazon");
            $pxml = amazon_query($request);

            $result = $pxml['Items']['Item'];
            $defTitle       = $result['ItemAttributes']['Title'];
            $r_artist = isset($result['ItemAttributes']['Artist']) ? $result['ItemAttributes']['Artist'] :
                        (isset($result['ItemAttributes']['Author']) ? $result['ItemAttributes']['Author'] :
                         (isset($result['ItemAttributes']['Creator']) ? $result['ItemAttributes']['Creator'] : '-'));
            $r_manufacturer = isset($result['ItemAttributes']['Manufacturer']) ? $result['ItemAttributes']['Manufacturer'] : '-';

            if (isset($result['MediumImage']))
               $defImage       = $result['MediumImage']['URL'];
            else
               $defImage       = "http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif";

           if (isset($result['LargeImage']))
               $defImageL       = $result['LargeImage']['URL'];
            else
               $defImageL       = "http://images-eu.amazon.com/images/G/02/misc/no-img-lg-uk.gif";

            $defLink    = $result['DetailPageURL'];
            $defDescription ="by " .$r_artist . " [" . $r_manufacturer . "]";
            // Make Short Form Name:
            $words = str_word_count($defTitle, 2);
            $words = array_slice( $words, 0, 3);
            $defTag = implode('', $words).$defKey;
            $defComment     ="$ASIN - $defTag";

            $defLinkText= "Available from <a href=\'$defLink\'>Amazon</a>";

            $java = "\"document.getElementById('WishPicsComment').innerHTML ='".addslashes($defComment)."'; ";
            $java = $java ."document.getElementById('WishPicsDescription').innerHTML='".addslashes($defDescription)."'; ";
            $java = $java ."document.getElementById('WishPicsTitle').innerHTML='".addslashes($defTitle)."'; ";
            $java = $java ."document.getElementById('WishPicsLink').href='$defLink'; ";
            $java = $java ."document.getElementById('WishPicsLinkText').innerHTML='".addslashes($defLinkText)."'; ";
            $java = $java ."document['WishPicsImage'].src='$defImageL'; ";
            $java = $java . "\"";

            $pre = "<div onclick=". $java . " style='float:left' id='WishPicsItem" . $key ."'><div style='position:relative; display:block;'><div>";
            $cover = $pre ."<img height='". $Options['thumbHeight'] ."' width='". $Options['thumbWidth'] ."' src='" . $defImage . "' alt='" . $defTitle . "'></div>\n";
            $cover = $cover. "</div></div>\n";
            echo $cover;
            $defKey++;
         }

?>

   </div>
   <p>
    <div style="width:400px; display: table">
     <div style="float:left">
      <a id="WishPicsLink" href="" ><img id="WishPicsImage" NAME="WishPicsImage" width="200px" src=""></a>
     </div>
     <div class="WishPicsDetails">
      <div id="WishPicsTitle"></div>
      <div id="WishPicsDescription"></div>
      <div id="WishPicsLinkText"></div>
      <div class="WishPicsComment" id = "WishPicsComment"></div>
     </div>
    </div>
   </p>
<?php
      }

      // Keep settings from search form
      foreach ($optionList as $optName => $optDetails) {
?>
  <input type='hidden' name='<?php echo $optName; ?>' value = '<?php echo $Opts[$optName]; ?>'>
<?php
      }
  
      // Create list of Items already selected
      if (is_array($AmazonItem)) {
         foreach ($AmazonItem as $Item => $key) {
?>
  <input type='hidden' name='AmazonItem[<?php echo $Item; ?>]' value = '1'>
<?php
         }
      }

   }
   /////////////////////////////////////////////////////////////////////////////////////////////////////

?>
 </form>
</div>
