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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app->error(function (\Exception $e) use ($app) {
    $error = null;
    if ($e instanceof NotFoundHttpException || in_array($app['request']->server->get('REMOTE_ADDR'), array('127.0.0.1', '::1'))) {
        $error = $e->getMessage();
    }

    return new Response(
        $app['twig']->render('error.twig', array('error' => $error)),
        $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500
    );
});

$app->get('/', function() use ($app) {
    return $app['twig']->render('projects.twig', array('projects' => $app['sismo']->getProjects()));
})->bind('projects');

$app->get('/{slug}', function($slug) use ($app) {
    if (!$app['sismo']->hasProject($slug)) {
        throw new NotFoundHttpException(sprintf('Project "%s" not found.', $slug));
    }

    $project = $app['sismo']->getProject($slug);
    $commits = $project->getCommits();
    $latest = array_shift($commits);

    return $app['twig']->render('project.twig', array(
        'project' => $project,
        'commit'  => $latest,
        'commits' => $commits,
    ));
})->bind('project');

$app->get('/{slug}/{sha}', function($slug, $sha) use ($app) {
    if (!$app['sismo']->hasProject($slug)) {
        throw new NotFoundHttpException(sprintf('Project "%s" not found.', $slug));
    }

    $project = $app['sismo']->getProject($slug);

    if (!$commit = $app['storage']->getCommit($project, $sha)) {
        throw new NotFoundHttpException(sprintf('Commit "%s" for project "%s" not found.', $sha, $slug));
    }

    return $app['twig']->render('project.twig', array(
        'project' => $project,
        'commit'  => $commit,
    ));
})->bind('commit');

$app->get('/dashboard/cctray.xml', function() use ($app) {
    $content = $app['twig']->render('ccmonitor.twig.xml', array('projects' => $app['sismo']->getProjects()));

    return new Response($content, 200, array('content-type' => 'text/xml'));
})->bind('ccmonitor');

return $app;
