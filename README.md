# jigoshopAtosPlugin
Wordpress plugin that enable Atos payment for Jigoshop.

Install
-------
Depending on your web server, copy the correct binary files on your server.
If you are on Linux, and want to know if you run 32 or 64 bits, just type:

    getconf LONG_BIT

For these binaries, don't forget to add execution rights.

    chmod +x

Put your params files too on your web server.
 
To use the credit cards logos given with this plugin, change images path in your param/pathfile.

```
D_LOGO!/wp-content/plugins/jigoshopAtosPlugin/images/!
```


----------

Test mode
---------
Use these values to test your installation.

Credit card success infos

    Credit card n°: 4974934125497800
    Crypt key: 600
    Expiration date: anything in the future

Credit card failed infos

    Credit cart n°: 4974934125497800
    Crypt key: 655
    Expiration date: anything in the future
