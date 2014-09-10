<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../src/config/database.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

$app['debug'] = true;

// Database
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $databaseConfigs,
));

// Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

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
});

// Save the form's data
$app->post('/', function (Request $request) use ($app)
{
    $story = $request->get('story');
    $author = $request->get('author');

    $shortUrl = base_convert(rand(100, 999) . microtime(true), 10, 36);

    $app['db']->insert('entries', array(
        'shorturl' => $shortUrl,
        'author'   => $author,
        'story'    => $story,
    ));

    return $app->redirect('/' . $shortUrl);
});

// convert to database
$app->get('/convert', function () use ($app)
{
    $files = scandir(__DIR__.'/../data');

    foreach( $files as $fl) {

        if ($fl != '.' && $fl != '..') {

            $data = file_get_contents(__DIR__.'/../data/' . $fl);

            $fl = str_replace('.txt', '', $fl);
            $shortUrl = $fl;

            $entry = $app['db']->fetchAssoc('SELECT * FROM entries WHERE shorturl = :shorturl', array(
                'shorturl' => $shortUrl,
            ));

            if (!$entry) {

                $row = explode('|', $data);

                if (sizeof($row) == 3) {
                    list($story, $author, $created) = explode('|', $data);

                    $app['db']->insert('entries', array(
                        'shorturl' => $shortUrl,
                        'author'   => $author,
                        'story'    => $story,
                    ));
                }
            }
        }
    }
});

// View saved data
$app->get('/{shortUrl}', function ($shortUrl) use ($app)
{
    $entry = $app['db']->fetchAssoc('SELECT * FROM entries WHERE shorturl = :shorturl', array(
        'shorturl' => $shortUrl,
    ));

    if (!$entry) {
        return $app->redirect('/');
    }

    return $app['twig']->render('view.twig', array(
        'story'   => $entry['story'],
        'author'  => $entry['author'],
        'created' => $entry['created'],
    ));
});

$app->run();




