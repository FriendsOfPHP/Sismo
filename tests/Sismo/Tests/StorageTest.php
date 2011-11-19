<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Tests;

use Sismo\Storage;
use Sismo\Commit;
use Sismo\Project;

class StorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * A SQLite3 reference.
     *
     * @var \SQLite3
     */
    private $db;
    private $path;

    public function setUp()
    {
        $app = require __DIR__.'/../../../src/app.php';

        $this->path = sys_get_temp_dir().'/sismo.db';
        @unlink($this->path);

        $this->db = new \SQLite3($this->path);
        $this->db->busyTimeout(1000);
        $this->db->exec($app['db.schema']);
    }

    public function tearDown()
    {
        @unlink($this->path);
    }

    public function testInterfaceIsValid()
    {
        $project = $this->getProject();

        $storage = $this->getStorage();

        $commit = $storage->initCommit($project, '7d78d5', 'fabien', new \DateTime(), 'foo');
        $this->assertInstanceOf('Sismo\Commit', $commit);

        $commit->setMessage('foo, amended with stuff');
        $storage->updateCommit($commit);

        $commit = $storage->getCommit($project, '7d78d5');
        $this->assertInstanceOf('Sismo\Commit', $commit);
    }

    public function testGetCommitReturnsFalseIfNotInDatabase()
    {
        $storage = $this->getStorage();
        $project = $this->getProject();
        $this->assertFalse($storage->getCommit($project, '7d78d5'));
    }

    public function testGetCommitReturnsCommitIfInDatabase()
    {
        $project = $this->getProject();

        $storage = $this->getStorage();
        $storage->initCommit($project, '7d78d5', 'fabien', new \DateTime(), 'foo');

        $commit = $storage->getCommit($project, '7d78d5');
        $this->assertInstanceOf('Sismo\Commit', $commit);
        $this->assertEquals('7d78d5', $commit->getSha());
    }

    public function testUpdateCommit()
    {
        $project = $this->getProject();

        $storage = $this->getStorage();
        $storage->initCommit($project, '7d78d5', 'fabien', new \DateTime(), 'foo');

        $commit = new Commit($project, '7d78d5');
        $commit->setOutput('foo');
        $commit->setStatusCode('success');

        $storage->updateCommit($commit);

        $this->assertEquals('foo', $commit->getOutput());
        $this->assertEquals('success', $commit->getStatusCode());

        $commit = $storage->getCommit($project, '7d78d5');
        $this->assertNotNull($commit->getBuildDate());
    }

    public function testUpdateProject()
    {
        $project = new Project('Twig');

        $storage = $this->getStorage();

        $storage->updateProject($project);
        $this->assertEquals(false, $project->isBuilding());
        $this->assertEquals(array(), $project->getCommits());

        $commit = $storage->initCommit($project, '7d78d5', 'fabien', new \DateTime(), 'foo');
        $storage->updateProject($project);
        $this->assertEquals(true, $project->isBuilding());
        $this->assertEquals(array($commit), $project->getCommits());
    }

    protected function getProject()
    {
        $project = $this->getMockBuilder('Sismo\Project')->disableOriginalConstructor()->getMock();
        $project->expects($this->any())->method('getSlug')->will($this->returnValue('twig'));

        return $project;
    }

    protected function getStorage()
    {
        return new Storage($this->db);
    }
}
