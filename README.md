# Muximux - Lightweight portal to your webapps

[![Join the chat at https://gitter.im/mescon/Muximux](https://badges.gitter.im/mescon/Muximux.svg)](https://gitter.im/mescon/Muximux?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge) [![Docker Automated buil](https://img.shields.io/docker/automated/linuxserver/docker-muximux.svg)](https://hub.docker.com/r/linuxserver/muximux/) [![GitHub stars](https://img.shields.io/github/stars/mescon/Muximux.svg)](https://github.com/mescon/Muximux/stargazers) [![GitHub forks](https://img.shields.io/github/forks/mescon/Muximux.svg)](https://github.com/mescon/Muximux/network)

This is a lightweight portal to view & manage your webapps without having to run anything more than a PHP enabled webserver.
With Muximux you don't need to keep multiple tabs open, or bookmark the URL to all of your apps.

![Desktop screenshot](https://i.imgur.com/LLsHzxX.png)
[More screenshots](#screenshots)

## Major features
* Add, remove and rearrange your owns apps without touching any code - it's all in the settings menu!
* A shiny new dropdown menu (top right) where you can put items you don't use that often!
* Change or replace icons by just clicking the icon you think looks good.
* Enable or disable a landingpage for each app (landingpages prevent you from being bombarded with login-prompts, and reduces load on your browser).
* All menu items move to the dropdown when you access Muximux from your mobile phone or tablet!
* Refresh button - when you click it, only the app you are looking at will be reloaded - not EVERY app inside your browser. You can also double click the item in the menu.

### Behind the scenes features
* Deferred loading of apps - each app only opens when you first click it. Loading time of Muximux is very fast!
* Security token generated on each page load. To execute specific functions of Muximux you can not do it without this token - a token that changes when the user leaves the page, effectively making commands to Muximux not function if you are not a valid user of the Muximux app currently browsing it.
* API calls to Github to look up commit history/changelog are cached and only called *once* when Muximux is loaded.
* No HTTP requests to external servers. *Muximux fonts, icons and other resources: Google, Bootstrap, jQuery and Font-Awesome do not need to know you are hosting a server!*
* Custom versions of minified javascript libraries that removes some of the unnecessary functions we're not using, which result in less javascript overhead and faster loading times.

## Setup

**Requirements:** A webserver (nginx, Apache, IIS or any other webserver) configured with PHP5 support.
`` parse_ini_file `` must be allowed in php.ini (default is allowed!)

- To set it up, clone this repository:
`` git clone https://github.com/mescon/Muximux `` or [download the ZIP-file](https://github.com/mescon/Muximux/archive/master.zip). If you install by downloading the zip-file, Muximux will still be able to notify you of updates and install them for you (given the right directory permissions!)

- Place all files on a publicly accessible webserver, either in a subdirectory called (for example) ``muximux`` or directly in the root directory of your webserver (such as ``/var/www``, ``/var/html``, ``C:\Inetpub\wwwroot`` or wherever your webserver serves files from by default).

- [Read this note](#security) about securing Muximux, and [read this note](#important-note-regarding-https) about what happens if you are using HTTPS. Just do it.

- Make sure that the directory where you place Muximux is [writable by the process that is running your webserver](http://lmgtfy.com/?q=how+to+make+a+directory+writable+by+my+webserver). *(i.e www-data, www-user, apache, nginx or whatever the user is called)*
  - Example: ``chown -R www-data.www-data /var/www/muximux``


## Docker Setup

The fine people of [LinuxServer.io](https://www.linuxserver.io) takes care of Docker builds for Muximux.
You can find [their instructions here](https://hub.docker.com/r/linuxserver/muximux/).


## Usage
- Navigate to ``http://<host>/muximux`` where ``<host>`` is either the IP or hostname of your webserver. *Obviously if you put the files in the root directory of your webserver, there is no need to append ``/muximux``*

- Remove the default apps (or just change the URL:s of them if you want to keep them), add your own apps by clicking in the top right corner and then click "Settings".

- Under Settings, rearrange your apps with drag'n'drop - just drag an item under another item to move it it.

- To reload an app, double click it in the menu, or press the refresh button in the top right bar.

- If you want to bookmark a specific service - or just quickly go to a service you've set up - use ``https://<host>/muximux/#service_name``. For instance, ``https://myserver.com/muximux/#couchpotato`` which will load Muximux and automatically load my Couchpotato tab. Replace spaces with underscore.

> There is no longer any need to edit config.ini.php or any file at all. In fact, we recommend you don't!

### Security
**It is strongly recommended that you secure any exposed applications with Basic Auth (``.htpasswd / .htaccess``)**

Read instructions for [Nginx](https://www.digitalocean.com/community/tutorials/how-to-set-up-password-authentication-with-nginx-on-ubuntu-14-04), [Apache](https://www.digitalocean.com/community/tutorials/how-to-set-up-password-authentication-with-apache-on-ubuntu-14-04) and [Microsoft IIS](http://serverfault.com/a/272292).

If you decide not to, Muximux disallows search engines from indexing your site, however, Muximux itself does not password protect your *services*, so you have to secure each of your applications properly (which they already should be!).
Muximux is NOT a proxy server, and as such can not by itself secure your separate applications.
However, you can password protect the Muximux application itself in the "Settings" menu. If you don't want your other services exposed to the world you *must* make sure that you have Basic Auth or other means of security enabled on your server.

### Important note regarding HTTPS
 If you are serving Muximux from a HTTPS-enabled webserver (i.e``https://myserver.com/muximux``), all of your services *must* *also* be secured via HTTPS.
 Any URL that does *not* start with ``https://`` (such as ``http://myserver.com:5050``) will be blocked by your web-browser!

 If you can, try serving Muximux from a non-secure (HTTP) webserver instead.
 If the apps you have configured are using HTTPS, communication with them will still be encrypted.

 The only known workaround is for Chrome, Opera and Chromium-based webbrowsers.

 Install the plugin "[Ignore X-Frame headers](https://chrome.google.com/webstore/detail/ignore-x-frame-headers/gleekbfjekiniecknbkamfmkohkpodhe)" which disables the blocking of non-secure content.

### Using with PFSense
Please refer to the following:
https://forum.pfsense.org/index.php?topic=47167.msg248336#msg248336
then check this box:
Browser HTTP_REFERER enforcement - Disable HTTP_REFERER enforcement check
Now pfsense works in muximux :D
(Thanks to nullredvector for the tip)

NOTE FROM tserversbfs:

The changes I have made to the git are for me and my site. I am NOT a php
programmer. Obviously. Many changes I made are hardcoded into the actual php files
as I am not able to add them into a configureation file.
Synopsys of changes: (more or less)
1: Index.php  - Replaced the rssfeed section on the left.
          - Added the 'warning_object' section on the right. More later.
2: muximux.php  - Added the ability to stack custom muximux menus
            - Added the ability to have custom menus for each user.
3: rssdog_iframe.html - Added custom rss feed replacing broken feed.
4: warning_object...  - Added a right menubar to allow Admin and System messages
          that fade after specific time. An expiry date is selected and these messages
          fade at that time. These messages are sorted by expiry date. DO NOT edit
          these files by hand. See the example bash file: mmWarningObject.sh file.

I am willing to discuss these changes with interested persons. As I learn php I will remove the hard coding and add files to the configuration files. I would also like to fix some of the parts of this repository that are broken. Confidence is low.


## Screenshots
#### Desktop screenshot (modern theme)
![Desktop screenshot, modern theme](https://i.imgur.com/LLsHzxX.png)

#### Desktop screenshot (classic theme)
![Desktop screenshot, classic theme](https://i.imgur.com/MeMfrI4.png)

#### Splash screen (modern theme) - shown on startup, accessible in the top right corner if you close it
![Splash screen](https://i.imgur.com/q6gw45q.png)

#### Mobile screenshot (modern theme) - dropdown menu hidden
![Mobile screenshot - dropdown menu hidden](https://i.imgur.com/smua7bw.png)

#### Mobile screenshot (modern theme) - dropdown menu shown
![Mobile screenshot - dropdown menu shown](https://i.imgur.com/8cDGN7A.png)

#### Settings: Drag & Drop items to re-arrange them in your menu
![Drag & Drop items to re-arrange them in your menu](https://i.imgur.com/7m0k6qB.png)

#### Settings: Pick and choose from over 2600 icons and choose colors for each tab
![Pick and choose from over 500 icons](https://i.imgur.com/NyUmzX7.png)

> This is a PHP enabled fork of (the simpler and more lightweight) "Managethis" found here:
> https://github.com/Tenzinn3/Managethis

> If you prefer a NodeJS version with a built-in webserver, a rewrite of Muximux (though not up to date) is available at
> https://github.com/onedr0p/manage-this-node
