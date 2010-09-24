=== Wish Pics ===
Contributors: paulstuttard
Donate link: http://www.houseindorset.co.uk/plugins/
Tags: Amazon, wishlist, covers, shortcode
Requires at least: 2.9
Tested up to: 3.0.1
Stable tag: 1.0

Displays a wish list in the form of a grid of wanted items (for example CD, DVD or book covers). 

== Description ==

Displays a wish list in the form of a grid of wanted items (for example CD, DVD or book covers). Allowing site visitors to remove items from the list once they have bought them. 

The plugin comes with the Mr Men wish list by default, adding your own lists can be done by copying the file wish-pics/lists/MrMen.php and changing the content to reflect the items you want to display.

The plugin currently relies upon my other plugin [Amazon Link](http://www.houseindorset.co.uk/plugins/amazon-link) being installed to enable the user to generate their own wishlist based upon Amazon searches.
Note these user generated wish lists will link to images on Amazon so may fail to work over time, or the Amazon site is down.

Each wishlist has the following settings:

* Wishlist name & ID - Used to reference the wishlist, ID must be used when inserting the list
* Wishlist - Which template list to use (defaults to MrMen)
* Thumbnail Width - The width of images presented in the wishlist array
* Thumbnail Height - The height of images presented in the wishlist array
* Array width - The total maximum width of the wishlist array
* Public Access - Allow anyone the ability to check items off the list
* Access Level - If not public, then only allow users of this type the ability to check items off the list

If the Amazon Link plugin is installed it is possible to Create new lists based on items for sale on the Amazon Site. Simply select 'Create List' from the Admin page.
Then search for items by 'Title/Author', perform a Search and select items to add to the list by clicking on the check boxes. 
The list will accumulate until you leave this screen, so multiple searches can be performed. Then select 'Create' to show an example of how the finished list will be presented. At this point you can click 'Back to Search Results' to change the list, or select 'Save' to add the list to the database.

Warning! returning to the admin settings screen without saving the list will lose the content of the search!

To create a new wishlist based on an old one or update an exist one, select 'Edit List' from the admin page.

== Installation ==

1. Unzip the attached wish-pics.zip file into your Wordpress '/wp-content/plugins/' directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Enter the Plugin's settings menu to configure your wishlist
1. If you want to create new wishlist content, you must also install the Amazon Link plugin and configure it with your AWS account details.
1. Insert links and wishlists into your content using the <-wish-pics -> tag as described above.

== Frequently Asked Questions ==

= Why can't I create my own wishlists? =

For this to work you must have installed my other plugin 'Amazon Links' and configured it with a valid [Amazon Web Services](http://aws.amazon.com/) account details.

= Sometimes it fails to show details for items when I create a new list? =

Occasionally the Amazon Web Services server fails to return a valid result. It should work when you finally commit to saving the new wishlist.

== Screenshots ==

== Changelog ==

= 1.0 =
First Release

= 1.1 =
Add Internationalisation support.
General tidy up, make plugin name consistent. Move options page into 'Options' section
