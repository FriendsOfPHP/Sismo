<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Silex\WebTestCase;
use Sismo\Project;
use Symfony\Component\HttpKernel\Util\Filesystem;

class AppTest extends WebTestCase
{
    protected $baseDir;

    public function createApplication()
    {
        $app = require __DIR__.'/../src/bootstrap.php';

        $this->baseDir = realpath(sys_get_temp_dir()).'/sismo';
        $fs = new Filesystem();
        $fs->mkdir($this->baseDir);
        $fs->mkdir($this->baseDir.'/config');
        $app['data.path'] = $this->baseDir.'/db';
        $app['config.file'] = $this->baseDir.'/config.php';

        @unlink($this->app['db.path']);
        file_put_contents($app['config.file'], '<?php return array();');

        require __DIR__.'/../src/app.php';

        return $app;
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->app['storage']->close();

        $fs = new Filesystem();
        $fs->remove($this->baseDir);
    }

    public function testGetProjectsEmpty()
    {
        $crawler = $this->createClient()->request('GET', '/');

        $this->assertEquals(1, count($crawler->filter('p:contains("No project yet.")')));
    }

    public function testPages()
    {
        $sismo = $this->app['sismo'];
        $storage = $this->app['storage'];

        $sismo->addProject(new Project('Twig'));

        $sismo->addProject($project1 = new Project('Silex1'));
        $commit = $storage->initCommit($project1, '7d78d5', 'fabien', new \DateTime(), 'foo');
        $storage->updateProject($project1);

        $sismo->addProject($project2 = new Project('Silex2'));
        $commit = $storage->initCommit($project2, '7d78d5', 'fabien', new \DateTime(), 'foo');
        $commit->setStatusCode('success');
        $storage->updateCommit($commit);
        $storage->updateProject($project2);

        $sismo->addProject($project3 = new Project('Silex3'));
        $commit = $storage->initCommit($project3, '7d78d5', 'fabien', new \DateTime(), 'foo');
        $commit->setStatusCode('failed');
        $storage->updateCommit($commit);
        $storage->updateProject($project3);

        // projects page
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertEquals(array('Twig', 'Silex1', 'Silex2', 'Silex3'), $crawler->filter('ul#projects li a')->each(function ($node) { return trim($node->nodeValue); }));
        $this->assertEquals(array('not built yet', 'building', 'succeeded', 'failed'), $crawler->filter('ul#projects li div')->each(function ($node) { return trim($node->nodeValue); }));
        $this->assertEquals(array('no_build', 'building', 'success', 'failed'), $crawler->filter('ul#projects li')->extract('class'));

        $links = $crawler->filter('ul#projects li a')->links();

        // project page
        $crawler = $client->click($links[0]);
        $this->assertEquals(1, count($crawler->filter('p:contains("Never built yet.")')));

        $crawler = $client->click($links[1]);
        $this->assertEquals('#7d78d5 building', trim($crawler->filter('div.commit')->text()));

        $crawler = $client->click($links[2]);
        $this->assertEquals('#7d78d5 succeeded', trim($crawler->filter('div.commit')->text()));

        $crawler = $client->click($links[3]);
        $this->assertEquals('#7d78d5 failed', trim($crawler->filter('div.commit')->text()));

        // sha page
        $crawler = $client->request('GET', '/silex2/7d78d5');
        $this->assertEquals('#7d78d5 succeeded', trim($crawler->filter('div.commit')->text()));

        // cc tray
        $crawler = $client->request('GET', '/dashboard/cctray.xml');
        $this->assertEquals(4, count($crawler->filter('Project')));

        $this->assertEquals(array('Unknown', 'Unknown', 'Success', 'Failure'), $crawler->filter('Project')->extract('lastBuildStatus'));
        $this->assertEquals(array('Sleeping', 'Building', 'Sleeping', 'Sleeping'), $crawler->filter('Project')->extract('activity'));
    }

    public function testGetNonExistentProject()
    {
        $crawler = $this->createClient()->request('GET', '/foobar');

        $this->assertEquals('Project "foobar" not found.', $crawler->filter('p')->text());
    }

    public function testGetNonExistentProjectOnShaPage()
    {
        $crawler = $this->createClient()->request('GET', '/foo/bar');

        $this->assertEquals('Project "foo" not found.', $crawler->filter('p')->text());
    }

    public function testGetNonExistentSha()
    {
        $sismo = $this->app['sismo'];
        $sismo->addProject(new Project('Twig'));

        $crawler = $this->createClient()->request('GET', '/twig/bar');

        $this->assertEquals('Commit "bar" for project "twig" not found.', $crawler->filter('p')->text());
    }
}
