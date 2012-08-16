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

spl_autoload_register(function($className)
{
    $className = str_replace ('\\', DIRECTORY_SEPARATOR, $className);
    include (dirname(__FILE__) . '/../src/' . $className . '.php');
});

/**
 * Or use this:
 * `./composer.phar install`
 * require_once dirname(__DIR__) . '/vendor/autoload.php';
 */

use \DPZ\AppNet;

// Build the URL for our callback
$callback = sprintf('%s://%s%s%s/example-auth.php',
    (@$_SERVER['HTTPS'] == "on") ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    ($_SERVER['SERVER_PORT'] == 80) ? '' : ':' . $_SERVER['SERVER_PORT'],
    $appNetRedirectPathPrefix
    );

$appNet = new AppNet($appNetClientId, $appNetClientSecret, $callback);

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