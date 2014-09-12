<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../src/config/database.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

// Dev mode, not for production
//$app['debug'] = true;

// Database
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $databaseConfigs,
));

// Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

// URL generator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

// Truncate filter for Twig
$app['twig']->addFilter(new Twig_SimpleFilter('truncate', function($string, $size)
{
    if(strlen($string) < $size) {
        return $string;
    } else {
        return substr($string, 0, $size) . "...";
    }
}));

// Root page with form
$app->get('/', function () use ($app)
{
    return $app['twig']->render('form.twig', array(
    ));
})->bind('homepage');

// Save the form's data
$app->post('/', function (Request $request) use ($app)
{
    $story = $request->get('story');
    $author = $request->get('author');
    $public = $request->get('public') ? 1 : 0;

    $shortUrl = base_convert(rand(100, 999) . microtime(true), 10, 36);

    $app['db']->insert('entries', array(
        'shorturl' => $shortUrl,
        'author'   => $author,
        'story'    => $story,
        'public'   => $public,
    ));

    return $app->redirect('/' . $shortUrl);
});

// Get list of latest entries
$app->get('/uusimmat', function () use ($app)
{
    $entries = $app['db']->query('SELECT * FROM entries WHERE author != "" AND story != "" AND public = true ORDER BY created DESC LIMIT 20');

    return $app['twig']->render('list.twig', array(
        'entries'    => $entries,
        'page_title' => 'Uusimmat'
    ));
})->bind('latest');

// Get list of TOP entries
$app->get('/top', function () use ($app)
{
    $entries = $app['db']->query('SELECT * FROM entries WHERE author != "" AND story != "" AND public = true ORDER BY views DESC LIMIT 20');

    return $app['twig']->render('list.twig', array(
        'entries'    => $entries,
        'page_title' => 'TOP 20'
    ));
})->bind('top');

// View saved data
$app->get('/{shortUrl}', function ($shortUrl) use ($app)
{
    $entry = $app['db']->fetchAssoc('SELECT * FROM entries WHERE shorturl = :shorturl', array(
        'shorturl' => $shortUrl,
    ));

    if (!$entry) {
        return $app->redirect('/');
    }

    $views = (int) $entry['views'] + 1;
    $app['db']->executeUpdate('UPDATE entries SET views = :views WHERE shorturl = :shorturl', array(
        'views'    => $views,
        'shorturl' => $shortUrl
    ));

    return $app['twig']->render('view.twig', array(
        'shortUrl'  => $entry['shorturl'],
        'story'     => $entry['story'],
        'author'    => $entry['author'],
        'created'   => $entry['created'],
    ));
})->bind('view');

$app->run();




