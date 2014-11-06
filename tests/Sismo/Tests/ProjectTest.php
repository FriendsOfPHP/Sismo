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

class ProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('Twig Local', $project->getName());
        $this->assertEquals('twig-local', $project->getSlug());
        $this->assertEquals('master', $project->getBranch());
        $this->assertEquals(array(), $project->getNotifiers());

        $project = new Project('Twig Local', 'repo', array(), 'twig');
        $this->assertEquals('twig', $project->getSlug());
        $this->assertEquals(array(), $project->getNotifiers());

        $project = new Project('Twig Local', 'repo');
        $this->assertEquals('repo', $project->getRepository());
        $this->assertEquals('master', $project->getBranch());

        $project = new Project('Twig Local', 'repo@feat');
        $this->assertEquals('repo', $project->getRepository());
        $this->assertEquals('feat', $project->getBranch());

        $project = new Project('Twig Local', 'repo', array(
            $notifier1 = $this->getMock('Sismo\Notifier\Notifier'),
            $notifier2 = $this->getMock('Sismo\Notifier\Notifier'),
        ));
        $this->assertSame(array($notifier1, $notifier2), $project->getNotifiers());

        $project = new Project('Twig Local', 'repo', $notifier3 = $this->getMock('Sismo\Notifier\Notifier'));
        $this->assertSame(array($notifier3), $project->getNotifiers());
    }

    public function testSlug()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('twig-local', $project->getSlug());

        $project->setSlug('twig-local-my-slug');
        $this->assertEquals('twig-local-my-slug', $project->getSlug());
    }

    public function testToString()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('Twig Local', (string) $project);
    }

    public function testBuildingFlag()
    {
        $project = new Project('Twig Local');
        $this->assertFalse($project->isBuilding());

        $project->setBuilding(true);
        $this->assertTrue($project->isBuilding());

        $project->setBuilding('foo');
        $this->assertTrue($project->isBuilding());

        $project->setBuilding(false);
        $this->assertFalse($project->isBuilding());
    }

    public function testNotifiers()
    {
        $project = new Project('Twig Local');
        $this->assertEquals(array(), $project->getNotifiers());

        $project->addNotifier($notifier1 = $this->getMock('Sismo\Notifier\Notifier'));
        $this->assertSame(array($notifier1), $project->getNotifiers());

        $project->addNotifier($notifier2 = $this->getMock('Sismo\Notifier\Notifier'));
        $this->assertSame(array($notifier1, $notifier2), $project->getNotifiers());
    }

    public function testBranch()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('master', $project->getBranch());

        $project->setBranch('new-feature');
        $this->assertEquals('new-feature', $project->getBranch());
    }

    public function testCommits()
    {
        $project = new Project('Twig Local');
        $this->assertEquals(array(), $project->getCommits());

        $project->setCommits(array(
            $commit1 = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock(),
            $commit2 = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock(),
            $commit3 = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock(),
        ));
        $this->assertEquals(array($commit1, $commit2, $commit3), $project->getCommits());
        $this->assertEquals($commit1, $project->getLatestCommit());
    }

    public function testName()
    {
        $project = new Project('Twig (Local)');
        $this->assertEquals('Twig (Local)', $project->getName());
        $this->assertEquals('Twig', $project->getShortName());
        $this->assertEquals('Local', $project->getSubName());

        $project = new Project('Twig');
        $this->assertEquals('Twig', $project->getName());
        $this->assertEquals('Twig', $project->getShortName());
        $this->assertEquals('', $project->getSubName());
    }

    public function testStatus()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('no_build', $project->getStatusCode());
        $this->assertEquals('not built yet', $project->getStatus());

        $commit = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
        $commit->expects($this->once())->method('getStatusCode')->will($this->returnValue('success'));
        $project->setCommits(array($commit));
        $this->assertEquals('success', $project->getStatusCode());

        $commit = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
        $commit->expects($this->once())->method('getStatus')->will($this->returnValue('success'));
        $project->setCommits(array($commit));
        $this->assertEquals('success', $project->getStatus());
    }

    public function testCCStatus()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('Unknown', $project->getCCStatus());

        $commit = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
        $commit->expects($this->once())->method('isBuilt')->will($this->returnValue(true));
        $commit->expects($this->once())->method('isSuccessful')->will($this->returnValue(true));
        $project->setCommits(array($commit));
        $this->assertEquals('Success', $project->getCCStatus());

        $commit = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
        $commit->expects($this->once())->method('isBuilt')->will($this->returnValue(true));
        $commit->expects($this->once())->method('isSuccessful')->will($this->returnValue(false));
        $project->setCommits(array($commit));
        $this->assertEquals('Failure', $project->getCCStatus());
    }

    public function testCCActivity()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('Sleeping', $project->getCCActivity());

        $commit = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
        $commit->expects($this->once())->method('isBuilding')->will($this->returnValue(true));
        $project->setCommits(array($commit));
        $this->assertEquals('Building', $project->getCCActivity());

        $commit = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
        $commit->expects($this->once())->method('isBuilding')->will($this->returnValue(false));
        $project->setCommits(array($commit));
        $this->assertEquals('Sleeping', $project->getCCActivity());
    }

    public function testRepository()
    {
        $project = new Project('Twig Local');

        $project->setRepository('https://github.com/twigphp/Twig.git');
        $this->assertEquals('https://github.com/twigphp/Twig.git', $project->getRepository());

        $project->setRepository('https://github.com/twigphp/Twig.git@feat');
        $this->assertEquals('https://github.com/twigphp/Twig.git', $project->getRepository());
        $this->assertEquals('feat', $project->getBranch());
    }

    public function testCommand()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('phpunit', $project->getCommand());

        $project->setCommand('/path/to/phpunit');
        $this->assertEquals('/path/to/phpunit', $project->getCommand());
    }

    public function testDefaultCommand()
    {
        $project = new Project('Twig Local');
        $this->assertEquals('phpunit', $project->getCommand());

        Project::setDefaultCommand('phpunit --colors --strict');
        $project2 = new Project('Twig Local');
        $this->assertEquals('phpunit', $project->getCommand());
        $this->assertEquals('phpunit --colors --strict', $project2->getCommand());

        $project2->setCommand('phpunit');
        $this->assertEquals('phpunit', $project2->getCommand());
    }

    public function testUrlPattern()
    {
        $project = new Project('Twig Local');

        $project->setUrlPattern('https://github.com/twigphp/Twig/commit/%commit%');
        $this->assertEquals('https://github.com/twigphp/Twig/commit/%commit%', $project->getUrlPattern());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSlugify()
    {
        $project = new Project('');
    }
}
