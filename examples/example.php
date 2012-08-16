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

// We need to set up the callback for the authentication process - this must match the redirect URI set up for this
// client id on app.net. For this example, the redirect uri must point at our example-auth.php script.
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

$username = $appNet->getOauthData(AppNet::USER_NAME);
$userId = $appNet->getOauthData(AppNet::USER_ID);

$user = $appNet->call(sprintf('/stream/0/users/%d', $userId));

$avatarUrl = $user->{'avatar_image'}->{'url'};
$avatarWidth = $user->{'avatar_image'}->{'width'};
$avatarHeight = $user->{'avatar_image'}->{'height'};

// are we posting a new message?
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $content = $_POST['post_content'];
    if (!empty($content))
    {
        $post = $appNet->call('/stream/0/posts', array('text' => $content), 'POST');
    }
}

// and get the latest posts to display
$posts = $appNet->call(sprintf('/stream/0/users/%d/posts', $userId));

?>
<!DOCTYPE html>
<html>
    <head>
        <title>DPZAppNet Example</title>
        <link rel="stylesheet" href="example.css">
    </head>
    <body>
        <h1>App.net for <?php echo $user->{'name'} ?> (<?php echo $username ?>)</h1>
        <img class="user-avatar" src="<?php echo $avatarUrl ?>" width="<?php echo $avatarWidth ?>" height="<?php echo $avatarHeight ?>">
        <h2>Recent Posts</h2>
        <ul id="posts">
        <?php $n = 0; ?>
        <?php foreach ($posts as $post) { ?>
            <li class="<?php echo ($n++)%2 ? 'odd' : 'even' ?>">
                <h3><?php echo date('l jS F Y h:i:s A', strtotime($post->{'created_at'})) ?></h3>
                <?php echo $post->{'text'} ?>
            </li>
        <?php } ?>
        </ul>
        <form action="example.php" method="POST">
            <textarea name="post_content" cols="50" rows="4"></textarea>
            <input type="submit" value="Post">
        </form>
    </body>
</html>

