<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Sismo\Sismo;
use Sismo\Project;
use Sismo\Storage\Storage;
use Sismo\Builder;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;

$app = new Application();
$app->register(new UrlGeneratorServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));
$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    $twig->setCache($app['twig.cache.path']);
    $twig->addGlobal('ansi_to_html', new AnsiToHtmlConverter());

    return $twig;
}));

$app['data.path']   = getenv('SISMO_DATA_PATH') ?: getenv('HOME').'/.sismo/data';
$app['config.file'] = getenv('SISMO_CONFIG_PATH') ?: getenv('HOME').'/.sismo/config.php';
$app['config.storage.file'] = getenv('SISMO_STORAGE_PATH') ?: getenv('HOME').'/.sismo/storage.php';
$app['build.path']  = $app->share(function ($app) { return $app['data.path'].'/build'; });
$app['db.path']     = $app->share(function ($app) {
    if (!is_dir($app['data.path'])) {
        mkdir($app['data.path'], 0777, true);
    }

    return $app['data.path'].'/sismo.db';
});
$app['build.token']     = getenv('SISMO_BUILD_TOKEN');
$app['twig.cache.path'] = $app->share(function ($app) { return $app['data.path'].'/cache'; });
$app['git.path']        = getenv('SISMO_GIT_PATH') ?: 'git';
$app['git.cmds']        = array();
$app['db.schema']       = <<<EOF
CREATE TABLE IF NOT EXISTS project (
    slug        TEXT,
    name        TEXT,
    repository  TEXT,
    branch      TEXT,
    command     BLOB,
    url_pattern TEXT,
    PRIMARY KEY (slug)
);

CREATE TABLE IF NOT EXISTS `commit` (
    slug          TEXT,
    sha           TEXT,
    date          TEXT,
    message       BLOB,
    author        TEXT,
    status        TEXT,
    output        BLOB,
    build_date    TEXT DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (slug, sha),
    CONSTRAINT slug FOREIGN KEY (slug) REFERENCES project(slug) ON DELETE CASCADE
);
EOF;

$app['db'] = $app->share(function () use ($app) {
    $db = new \SQLite3($app['db.path']);
    $db->busyTimeout(1000);
    $db->exec($app['db.schema']);

    return $db;
});

$app['storage'] = $app->share(function () use ($app) {
    if (is_file($app['config.storage.file'])) {
        $storage = require $app['config.storage.file'];
    } else {
        $storage = new Storage($app['db']);
    }

    return $storage;
});

$app['builder'] = $app->share(function () use ($app) {
    $process = new Process(sprintf('%s --version', $app['git.path']));
    if ($process->run() > 0) {
        throw new \RuntimeException(sprintf('The git binary cannot be found (%s).', $app['git.path']));
    }

    return new Builder($app['build.path'], $app['git.path'], $app['git.cmds']);
});

$app['sismo'] = $app->share(function () use ($app) {
    $sismo = new Sismo($app['storage'], $app['builder']);
    if (!is_file($app['config.file'])) {
        throw new \RuntimeException(sprintf("Looks like you forgot to define your projects.\nSismo looked into \"%s\".", $app['config.file']));
    }
    $projects = require $app['config.file'];

    if (null === $projects) {
        throw new \RuntimeException(sprintf('The "%s" configuration file must return an array of Projects (returns null).', $app['config.file']));
    }

    if (!is_array($projects)) {
        throw new \RuntimeException(sprintf('The "%s" configuration file must return an array of Projects (returns a non-array).', $app['config.file']));
    }

    foreach ($projects as $project) {
        if (!$project instanceof Project) {
            throw new \RuntimeException(sprintf('The "%s" configuration file must return an array of Project instances.', $app['config.file']));
        }

        $sismo->addProject($project);
    }

    return $sismo;
});

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    $error = 404 == $code ? $e->getMessage() : null;

    return new Response($app['twig']->render('error.twig', array('error' => $error)), $code);
});

return $app;
