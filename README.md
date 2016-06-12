Slack Linkit
============

This documentation is incomplete.

**Slack Linkit** is a Wordpress plugin that allows you to save links directly from Slack to a Wordpress blog to share with the world, or for later reading.

Usage
-----

    /linkit https://en.wikipedia.org/wiki/The_Twilight_Zone

Installtion (Ubuntu 14.04)
--------------------------

To install Slack Linkit on your own servers ensure you have the following installed:

* **php5-curl**: `apt-get install php5-curl` (Don't forget to restart apache/nginx/php-fpm after install.)
* [Wordpress](https://github.com/WordPress/WordPress) 
* PhantomJS

Navigate to the directory where you have Wordpress installed and run the following commands

    cd /wp-content/plugins/
    git clone https://github.com/JamesTheHacker/slack-linkit
    cd slack-linkit

Next we download composer and install dependencies ...

    curl -sS https://getcomposer.org/installer | php
    php composer.phar install

One of the dependencies is bundled with a copy of PhantomJS. We need to make it executable ...

    chmod +x vendor/microweber/screen/bin/phantomjs
    
Finally we need to make a couple of folders writable:

    chmod 755 vendor/microweber/scree/jobs
    chmod 755 -R ../../uploads

Enable the plugin in the Wordpress plugins page

Configuration
-------------

**Add a Slack token**

The `SLACK_TOKEN` is a unique token provided by Slack to validate the source of a message. When you create your application this token is provided. You **must** update this. To do so open `slack-linkit.php` and modify:

    define('SLACK_TOKEN', 'YOUR-TOKEN-GOES-HERE');

**Setting the PhantomJS path**

If you're using a non Debian OS you may need to install PhantomJS and change the `PHANTOM_PATH` variable in `slack-linkit.php`. This is because the executable that is provided was compiled on a Debian based operating system.

     define('PHANTOM_PATH', '/usr/local/bin/');

