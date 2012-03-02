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

use Sismo\Sismo;
use Sismo\Project;

class SismoTest extends \PHPUnit_Framework_TestCase
{
    public function testProject()
    {
        $sismo = new Sismo($this->getStorage(), $this->getBuilder());
        $this->assertEquals(array(), $sismo->getProjects());
        $this->assertFalse($sismo->hasProject('twig'));

        $sismo->addProject($project = new Project('Twig'));
        $this->assertTrue($sismo->hasProject('twig'));
        $this->assertSame($project, $sismo->getProject('twig'));

        $sismo->addProject($project1 = new Project('Silex'));

        $this->assertSame(array('twig' => $project, 'silex' => $project1), $sismo->getProjects());
    }

    public function testAddProjectSaveIt()
    {
        $storage = $this->getStorage();
        $storage->expects($this->once())->method('updateProject');

        $sismo = new Sismo($storage, $this->getBuilder());
        $sismo->addProject($project = new Project('Twig'));
        $sismo->getProject('twig');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetProjectWhenProjectDoesNotExist()
    {
        $sismo = new Sismo($this->getStorage(), $this->getBuilder());
        $sismo->getProject('twig');
    }

    public function testBuildIsNotCalledWhenCommitIsAlreadyBuilt()
    {
        // an already built commit
        $commit = $this->getCommit();
        $commit->expects($this->once())->method('isBuilt')->will($this->returnValue(true));

        // build won't be triggered
        $builder = $this->getBuilder();
        $builder->expects($this->never())->method('build');

        $sismo = new Sismo($this->getStorage($commit), $builder);
        $sismo->build($this->getProject());
    }

    public function testBuildIsForcedWhenCommitIsAlreadyBuiltAndForceIsTrue()
    {
        // an already built commit
        $commit = $this->getCommit();
        $commit->expects($this->once())->method('isBuilt')->will($this->returnValue(true));

        // build is triggered because of FORCE_BUILD flags
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($this->getProcess()));

        $sismo = new Sismo($this->getStorage($commit), $builder);
        $sismo->build($this->getProject(), null, Sismo::FORCE_BUILD);
    }

    public function testBuildIsNotCalledWhenProjectIsBuilding()
    {
        // a project with a running build
        $project = $this->getProject();
        $project->expects($this->once())->method('isBuilding')->will($this->returnValue(true));

        // build won't be triggered
        $builder = $this->getBuilder();
        $builder->expects($this->never())->method('build');

        $sismo = new Sismo($this->getStorage(), $builder);
        $sismo->build($project);
    }

    public function testBuildIsForcedWhenProjectIsBuildingAndForceIsTrue()
    {
        // a project with a running build
        $project = $this->getProject();
        $project->expects($this->once())->method('isBuilding')->will($this->returnValue(true));

        // build is triggered because of FORCE_BUILD flags
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($this->getProcess()));

        $commit = $this->getCommit();
        $commit->expects($this->once())->method('isBuilt')->will($this->returnValue(false));

        $sismo = new Sismo($this->getStorage($commit), $builder);
        $sismo->build($project, null, Sismo::FORCE_BUILD);
    }

    public function testBuildIsForcedWhenCommitDoesNotExist()
    {
        // build is triggered as commit does not exist
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($this->getProcess()));

        $storage = $this->getStorage();
        $storage->expects($this->any())->method('initCommit')->will($this->returnValue($this->getCommit()));

        $sismo = new Sismo($storage, $builder);
        $sismo->build($this->getProject());
    }

    public function testBuildWithNotifiers()
    {
        // build is triggered as commit does not exist
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($this->getProcess()));

        $storage = $this->getStorage();
        $storage->expects($this->any())->method('initCommit')->will($this->returnValue($this->getCommit()));

        // notifier will be called
        $notifier = $this->getNotifier();
        $notifier->expects($this->once())->method('notify');

        $project = $this->getMockBuilder('Sismo\Project')->disableOriginalConstructor()->getMock();
        $project->expects($this->once())->method('getNotifiers')->will($this->returnValue(array($notifier)));

        $sismo = new Sismo($storage, $builder);
        $sismo->build($project);
    }

    public function testBuildWithNotifiersWhenSilentIsTrue()
    {
        // build is triggered as commit does not exist
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($this->getProcess()));

        $storage = $this->getStorage();
        $storage->expects($this->any())->method('initCommit')->will($this->returnValue($this->getCommit()));

        // notifier won't be called
        $notifier = $this->getNotifier();
        $notifier->expects($this->never())->method('notify');

        // notifiers won't be get from project
        $project = $this->getMockBuilder('Sismo\Project')->disableOriginalConstructor()->getMock();
        $project->expects($this->never())->method('getNotifiers')->will($this->returnValue(array($notifier)));

        $sismo = new Sismo($storage, $builder);
        $sismo->build($project, null, Sismo::SILENT_BUILD);
    }

    public function testCommitResultForSuccessBuild()
    {
        // build is a success
        $process = $this->getProcess();
        $process->expects($this->once())->method('isSuccessful')->will($this->returnValue(true));
        $process->expects($this->once())->method('getOutput')->will($this->returnValue('foo'));

        // build is triggered as commit does not exist
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($process));

        // check commit status
        $commit = $this->getCommit();
        $commit->expects($this->once())->method('setStatusCode')->with($this->equalTo('success'));
        $commit->expects($this->once())->method('setOutput')->with($this->equalTo('foo'));

        // check that storage is updated
        $storage = $this->getStorage();
        $storage->expects($this->any())->method('initCommit')->will($this->returnValue($commit));
        $storage->expects($this->once())->method('updateCommit')->with($this->equalTo($commit));

        $sismo = new Sismo($storage, $builder);
        $sismo->build($this->getProject());
    }

    public function testCommitResultForSuccessFail()
    {
        // build is a fail
        $process = $this->getProcess();
        $process->expects($this->once())->method('isSuccessful')->will($this->returnValue(false));
        $process->expects($this->once())->method('getOutput')->will($this->returnValue('foo'));
        $process->expects($this->once())->method('getErrorOutput')->will($this->returnValue('bar'));

        // build is triggered as commit does not exist
        $builder = $this->getBuilder();
        $builder->expects($this->once())->method('prepare')->will($this->returnValue(array('sha1', 'fabien', '2011-01-01 01:01:01 +0200', 'initial commit')));
        $builder->expects($this->once())->method('build')->will($this->returnValue($process));

        // check commit status
        $commit = $this->getCommit();
        $commit->expects($this->once())->method('setStatusCode')->with($this->equalTo('failed'));
        $commit->expects($this->once())->method('setOutput')->with($this->matchesRegularExpression('/foo.*bar/s'));

        // check that storage is updated
        $storage = $this->getStorage();
        $storage->expects($this->any())->method('initCommit')->will($this->returnValue($commit));
        $storage->expects($this->once())->method('updateCommit')->with($this->equalTo($commit));

        $sismo = new Sismo($storage, $builder);
        $sismo->build($this->getProject());
    }

    private function getBuilder()
    {
        return $this->getMockBuilder('Sismo\Builder')->disableOriginalConstructor()->getMock();
    }

    private function getNotifier()
    {
        return $this->getMockBuilder('Sismo\Notifier\Notifier')->disableOriginalConstructor()->getMock();
    }

    private function getProject()
    {
        $project = $this->getMockBuilder('Sismo\Project')->disableOriginalConstructor()->getMock();
        $project->expects($this->any())->method('getNotifiers')->will($this->returnValue(array()));

        return $project;
    }

    private function getCommit()
    {
        return $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor()->getMock();
    }

    private function getProcess()
    {
        return $this->getMockBuilder('Symfony\Component\Process\Process')->disableOriginalConstructor()->getMock();
    }

    private function getStorage($commit = null)
    {
        $storage = $this->getMockBuilder('Sismo\Storage\Storage')->disableOriginalConstructor()->getMock();
        if (null !== $commit) {
            $storage->expects($this->once())->method('getCommit')->will($this->returnValue($commit));
            $storage->expects($this->any())->method('initCommit')->will($this->returnValue($commit));
        }

        return $storage;
    }
}
