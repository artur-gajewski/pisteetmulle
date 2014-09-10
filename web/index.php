<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

$app['debug'] = true;

// Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

// Truncate filter for Twig
$app['twig']->addFilter(new Twig_SimpleFilter('truncate', function($string, $size) {
    if(strlen($string) < $size) {
        return $string;
    } else {
        return substr($string, 0, $size) . "...";
    }
}));

// Root page with form
$app->get('/', function () use ($app) {
    return $app['twig']->render('form.twig', array(
    ));
});

// Save the form's data
$app->post('/', function (Request $request) use ($app) {
    $story = $request->get('story');
    $author = $request->get('author');
    $date = date('d.n.Y');

    $id = base_convert(rand(100, 999) . microtime(true), 10, 36);
    $data = $story . '|' . $author . '|' . $date;

    file_put_contents(__DIR__.'/../data/' . $id . '.txt', $data);

    return $app->redirect('/' . $id);
});

// View saved data
$app->get('/{id}', function ($id) use ($app) {
    $fileName = __DIR__.'/../data/' . $id . '.txt';

    if (!file_exists($fileName)) {
        return $app->redirect('/');
    }

    $data = file_get_contents($fileName);

    list($story, $author, $date) = explode('|', $data);

    return $app['twig']->render('view.twig', array(
        'story'  => $story,
        'author' => $author,
        'date'   => $date
    ));
});

$app->run();




