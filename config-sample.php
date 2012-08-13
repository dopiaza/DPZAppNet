<?php

// Edit the lines below to add your app.net client id and secret

$appNetClientId = 'PUT_YOUR_APP_NET_CLIENT_ID_KEY_HERE';
$appNetClientSecret = 'PUT_YOUR_APP_NET_CLIENT_SECRET_HERE';

// You will need to configure the redirect URI over on App.net for this client id. To run this example, you need
// to set it to http://whatever.my.server.is.com/path/to/DPZAppNet/example-auth.php


// The example code needs to know a little bit about how your web server is configured so that it can work out the
// correct URL to generate for redirects.
// If you have a virtual server with this example code running in the document root (with a URL something like
// http://dpzappnet.local/), then you can leave $appNetRedirectPathPrefix set to an empty string.
// If, however, your running in a sub-directory (something like http://my.web.server.com/stuff/dpzappnet/example.php),
// then you need to set this to the path to DPZAppNet (in this example, '/stuff/dpzappnet'). Do not include any
// trailing slashes.

$appNetRedirectPathPrefix = '';