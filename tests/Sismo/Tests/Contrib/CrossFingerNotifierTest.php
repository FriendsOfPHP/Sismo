<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Tests\Contrib;

use Sismo\Notifier\Notifier;
use Sismo\Contrib\CrossFingerNotifier;
use Sismo\Commit;
use Sismo\Project;

class CrossFingerNotifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConstruct()
    {
        $notifier = new CrossFingerNotifier(array(new \stdClass()));
    }

    public function testNotify()
    {
        $commitBuilder = $this->getMockBuilder('Sismo\Commit')->disableOriginalConstructor();
        $failedCommit  = $commitBuilder->getMock();
        $successCommit = $commitBuilder->getMock();
        $baseNotifier  = $this->getMock('Sismo\Notifier\Notifier');

        $failedCommit->expects($this->any())
            ->method('isSuccessful')
            ->will($this->returnValue(false));

        $successCommit->expects($this->any())
            ->method('isSuccessful')
            ->will($this->returnValue(true));

        $baseNotifier->expects($this->once())
            ->method('notify')
            ->will($this->returnValue('foo'));

        $notifier = new CrossFingerNotifier(array($baseNotifier));

        $this->assertTrue($notifier->notify($failedCommit));
        $this->assertFalse($notifier->notify($successCommit));
    }

    public function testCommitNeedNotification()
    {
        $notifier = $this->getMock('Sismo\Contrib\CrossFingerNotifier');
        $r = new \ReflectionObject($notifier);
        $m = $r->getMethod('commitNeedNotification');
        $m->setAccessible(true);

        $project = new Project('Twig');
        $commit = new Commit($project, '123456');
        $commit->setAuthor('Fabien');
        $commit->setMessage('Foo');


        $commit2 = new Commit($project, '123455');
        $commit2->setAuthor('Fabien');
        $commit2->setMessage('Bar');
        $commit2->setStatusCode('success');

        $this->assertTrue($m->invoke($notifier, $commit));
        $this->assertFalse($m->invoke($notifier, $commit2));

        $project->setCommits(array(
            $commit,
            $commit2
        ));
        $this->assertTrue($m->invoke($notifier, $commit));
    }
}
