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

// Build the URL for our callback
$callback = sprintf('%s://%s%s%s/example-auth.php',
    (@$_SERVER['HTTPS'] == "on") ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'],
    $appNetRedirectPathPrefix
    );

$appNet = new DPZFAppNet($appNetClientId, $appNetClientSecret, $callback);

if (!$appNet->authenticate('stream write_post'))
{
    die("Hmm, something went wrong...\n");
}

// Looks like we authenticated OK, let's go back to our main page

$redirectTo = sprintf('%s://%s%s%s/example.php',
    (@$_SERVER['HTTPS'] == "on") ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'],
    $appNetRedirectPathPrefix
    );

header("Location: $redirectTo");

exit(0);