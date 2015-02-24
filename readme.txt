=== JigoshopAtos ===
Contributors: chtipepere
Tags: JigoShop, Payment Gateway, Atos, Cartes Bancaires
Requires at least: 4.0.1
Tested up to: 4.1.1
Stable tag: 1.3
License: Apache License 2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

Handles Atos payment for Jigoshop plugin.

== Description ==

Handles Atos payment in Jigoshop, credit card payment for french banks.

https://github.com/chtipepere/jigoshopAtosPlugin

== Installation ==

Depending on your web server, copy the correct binary files on your server.
If you are on Linux, and want to know if you run 32 or 64 bits, just type:

    getconf LONG_BIT

For these binaries, don't forget to add execution rights.

    chmod +x

Put your params files too on your web server.

To use the credit cards logos given with this plugin, change images path in your param/pathfile.

    D_LOGO!/wp-content/plugins/jigoshopAtosPlugin/images/!

Take a look at the examples atos files provided with this plugin to put the correct values in **YOUR** param files.

**Automatic response**

Create a page that contains shortcode above and fill the automatic_response_url field in admin.

    [jigoshop_atos_automatic_response]

See https://github.com/chtipepere/jigoshopAtosPlugin

== Screenshots ==

1. Backoffice options
2. Gateway choices
3. Pick your card and pay

== Changelog ==

= 1.3 =
* Complies wordpress rules
* Fix translations
* Restrict jigoshop version
* Restrict PHP version

= 1.1 =
* Add merchant name
* Add example files for WebAffaires

= 1.0 =
* Initial version

== Upgrade Notice ==

= 1.2 =
Use shortcode for autoresponse page

= 1.1 =
You can now manage the shop name displayed on payment page.

== Jigoshop Compatibility ==

Requires at least: 1.8
Tested up to: 1.15

== Credits ==

Based on http://thomasdt.com