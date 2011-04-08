<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sismo\Project;
use Symfony\Component\HttpKernel\Util\Filesystem;
use Symfony\Component\Console\Tester\ApplicationTester;

class ConsoleTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $console;
    protected $baseDir;

    public function setUp()
    {
        $this->app = require __DIR__.'/../src/bootstrap.php';

        $this->app['sismo'] = $this->getMockBuilder('Sismo\Sismo')->disableOriginalConstructor()->getMock();

        $this->console = require __DIR__.'/../src/console.php';
        $this->console->setAutoExit(false);
        $this->console->setCatchExceptions(false);
    }

    public function testBuildForNonExistentProject()
    {
        $tester = new ApplicationTester($this->console);

        $this->assertEquals(1, $tester->run(array('command' => 'build', 'slug' => 'Twig')));
        $this->assertEquals('Project "Twig" does not exist.', trim($tester->getDisplay()));
    }

    public function testBuildForProject()
    {
        $project = $this->getMockBuilder('Sismo\Project')->disableOriginalConstructor()->getMock();

        $this->app['sismo']->expects($this->once())->method('hasProject')->will($this->returnValue(true));
        $this->app['sismo']->expects($this->once())->method('getProject')->will($this->returnValue($project));
        $this->app['sismo']->expects($this->once())->method('build');

        $tester = new ApplicationTester($this->console);

        $this->assertEquals(0, $tester->run(array('command' => 'build', 'slug' => 'Twig')));
        $this->assertEquals('Building Project "" (into "d41d8c")', trim($tester->getDisplay()));
    }

    public function testBuildForProjects()
    {
        $project1 = $this->getMock('Sismo\Project', null, array('Twig'));
        $project2 = $this->getMock('Sismo\Project', null, array('Silex'));

        $this->app['sismo']->expects($this->once())->method('getProjects')->will($this->returnValue(array($project1, $project2)));
        $this->app['sismo']->expects($this->exactly(2))->method('build');

        $tester = new ApplicationTester($this->console);

        $this->assertEquals(0, $tester->run(array('command' => 'build')));
        $this->assertEquals("Building Project \"Twig\" (into \"d41d8c\")\n\nBuilding Project \"Silex\" (into \"d41d8c\")", trim($tester->getDisplay()));
    }

    public function testVerboseBuildForProject()
    {
        $project = $this->getMockBuilder('Sismo\Project')->disableOriginalConstructor()->getMock();

        $this->app['sismo']->expects($this->once())->method('hasProject')->will($this->returnValue(true));
        $this->app['sismo']->expects($this->once())->method('getProject')->will($this->returnValue($project));
        $this->app['sismo']->expects($this->once())->method('build');

        $tester = new ApplicationTester($this->console);

        $this->assertEquals(0, $tester->run(array('command' => 'build', 'slug' => 'Twig', '--verbose' => true)));
        $this->assertEquals('Building Project "" (into "d41d8c")', trim($tester->getDisplay()));
    }
}
