<?php

$configFile = dirname(__FILE__) . '/config.php';

if (file_exists($configFile))
{
    include $configFile;
}
else
{
    die("Please rename the config-sample.php file to config.php and add your App.net client id and secret to it\n");
}

require_once dirname(__FILE__) . '/DPZAppNet.php';

$appNet = new DPZFAppNet($appNetClientId, $appNetClientSecret);

$appNet->signout();

?>
<!DOCTYPE html>
<html>
    <head>
        <title>DPZAppNet Signout Example</title>
        <link rel="stylesheet" href="example.css">
    </head>
    <body>
        <h1>Signed out</h1>
        <p>You have now signed out of this App.net session. <a href="example.php">Sign in</a>.</p>
    </body>
</html>