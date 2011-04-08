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

use Sismo\Project;
use Sismo\Commit;

class CommitTest extends \PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $this->assertEquals('Twig@7d78d5', (string) $commit);
    }

    public function testSha()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $this->assertEquals('7d78d5f7a8c039059046d6c5e1d7f66765bd91c7', $commit->getSha());
        $this->assertEquals('7d78d5', $commit->getShortSha());
    }

    public function testStatus()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');

        $this->assertEquals('building', $commit->getStatusCode());
        $this->assertEquals('building', $commit->getStatus());

        $commit->setStatusCode('failed');
        $this->assertEquals('failed', $commit->getStatusCode());
        $this->assertEquals('failed', $commit->getStatus());

        $commit->setStatusCode('success');
        $this->assertEquals('success', $commit->getStatusCode());
        $this->assertEquals('succeeded', $commit->getStatus());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetStatus()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $commit->setStatusCode('weird');
    }

    public function testIsBuilding()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $this->assertTrue($commit->isBuilding());

        $commit->setStatusCode('failed');
        $this->assertFalse($commit->isBuilding());
    }

    public function testIsBuilt()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $this->assertFalse($commit->isBuilt());

        $commit->setStatusCode('failed');
        $this->assertTrue($commit->isBuilt());

        $commit->setStatusCode('success');
        $this->assertTrue($commit->isBuilt());
    }

    public function testIsSuccessful()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $this->assertFalse($commit->isSuccessful());

        $commit->setStatusCode('failed');
        $this->assertFalse($commit->isSuccessful());

        $commit->setStatusCode('success');
        $this->assertTrue($commit->isSuccessful());
    }

    public function testOutput()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $commit->setOutput('foo');
        $this->assertEquals('foo', $commit->getOutput());

        $commit->setOutput("\033[1mfoo\033[0m");
        $this->assertEquals('<strong>foo</strong>', $commit->getDecoratedOutput());
    }

    public function testMessage()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $commit->setMessage('foo');
        $this->assertEquals('foo', $commit->getMessage());
    }

    public function testProject()
    {
        $commit = new Commit($project = new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $this->assertEquals($project, $commit->getProject());
    }

    public function testAuthor()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $commit->setAuthor('foo');
        $this->assertEquals('foo', $commit->getAuthor());
    }

    public function testDate()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $commit->setDate($date = new \DateTime());
        $this->assertEquals($date, $commit->getDate());
    }

    public function testBuildDate()
    {
        $commit = new Commit(new Project('Twig'), '7d78d5f7a8c039059046d6c5e1d7f66765bd91c7');
        $commit->setBuildDate($date = new \DateTime());
        $this->assertEquals($date, $commit->getBuildDate());
    }
}
