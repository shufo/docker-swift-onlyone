<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

$app = new \Slim\Slim(array(
    'debug' => false
));

$env = $app->environment();
$env['SWIFT_ADDR']     = getenv('SWIFT_PORT_8080_TCP_ADDR');
$env['SWIFT_PORT']     = getenv('SWIFT_PORT_8080_TCP_PORT');
$env['SWIFT_USER']     = getenv('SWIFT_ENV_SWIFT_USER');
$env['SWIFT_PASSWORD'] = getenv('SWIFT_ENV_SWIFT_SET_PASSWORDS');
$env['REDIS_ADDR']     = getenv('REDIS_PORT_6379_TCP_ADDR');
$env['REDIS_PORT']     = getenv('REDIS_PORT_6379_TCP_PORT');

$app->get('/ping', function() {
      echo 'pong';
});

$app->get('/api/v1/image/:name+', function ($name) use ($app, $redis) {

    $env  = $app->environment();
    $name = implode('/', $name);

    $redis = new Redis();
    $redis->connect($env['REDIS_ADDR'], $env['REDIS_PORT']);

    $app->url   = $redis->get('storage_url');
    $app->token = $redis->get('storage_token');

    $client = new Client([
        'base_uri' => 'http://' . $env['SWIFT_ADDR'] . ':' . $env['SWIFT_PORT'],
        'timeout'  => '10.0',
        'verify'   => false,
    ]);

    $request = function($name) use ($client, $app) {
        $object = function ($path) use ($client, $app) {
            $headers = [
               'X-Auth-Token' => $app->token,
            ];
            return $client->get($app->url . $path, compact('headers'));
        };

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $body     = $object('/image/' . $name)->getBody();
        $mimetype = finfo_buffer($finfo, $body);
        $app->response->headers->set('Content-Type', $mimetype);

        return $body;
    };

    try {
        echo $request($name);

    } catch (ClientException $e) {

        $headers = [
            'X-Storage-User' => $env['SWIFT_USER'],
            'X-Storage-Pass' => $env['SWIFT_PASSWORD'],
        ];

        $response   = $client->get('/auth/v1.0', compact('headers'));
        $app->url   = $response->getHeader('X-Storage-Url')[0];
        $app->token = $response->getHeader('X-Auth-Token')[0];
        $redis->set('storage_token', $app->token);
        $redis->set('storage_url', $app->url);

        echo $request($name);
    }

});

$app->put('/api/v1/image/:name+', function ($name) use ($app) {

    $env  = $app->environment();
    $name = implode('/', $name);

    $redis = new Redis();
    $redis->connect($env['REDIS_ADDR'], $env['REDIS_PORT']);

    $app->url   = $redis->get('storage_url');
    $app->token = $redis->get('storage_token');

    $client = new Client([
        'base_uri' => 'http://' . $env['SWIFT_ADDR'] . ':' . $env['SWIFT_PORT'],
        'timeout'  => '10.0',
        'verify'   => false,
    ]);

    $object = function ($path) use ($client, $app) {
        $body = file_get_contents("php://input");

        $headers = [
            'X-Auth-Token' => $app->token,
        ];

        $client->put($app->url . '/image', compact('headers'));
        return $client->put($app->url . $path, compact('headers', 'body'));
    };


    try {
        $object('/image/' . $name);
    } catch (ClientException $e) {

        $headers = [
              'X-Storage-User' => $env['SWIFT_USER'],
              'X-Storage-Pass' => $env['SWIFT_PASSWORD'],
        ];

        $response   = $client->get('/auth/v1.0', ['headers' => $headers]);
        $app->url   = $response->getHeader('X-Storage-Url')[0];
        $app->token = $response->getHeader('X-Auth-Token')[0];
        $redis->set('storage_token', $app->token);
        $redis->set('storage_url', $app->url);

        echo $object('/image/' . $name)->getBody();
    }
});

$app->run();
