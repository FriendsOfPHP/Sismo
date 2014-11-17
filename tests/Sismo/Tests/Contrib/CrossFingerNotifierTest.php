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
        $notifier = new CrossFingerNotifier(new \stdClass());
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

        $commit3 = clone $commit2;

        //a failed commit should be notified
        $this->assertTrue($m->invoke($notifier, $commit));

        //a successful commit without predecessor should be notified
        $this->assertTrue($m->invoke($notifier, $commit2));

        $project->setCommits(array(
            $commit3,
        ));
        //a successful commit with a successful predecessor should NOT be notified
        $this->assertFalse($m->invoke($notifier, $commit2));

        $project->setCommits(array(
            $commit2,
            $commit3,
        ));
        //a failed commit with a successful predecessor should be notified
        $this->assertTrue($m->invoke($notifier, $commit));
    }

    public function testNotify()
    {
        $project = new Project('Twig');
        $failedCommit = new Commit($project, '123456');
        $failedCommit->setAuthor('Fabien');
        $failedCommit->setMessage('Foo');

        $successCommit = new Commit($project, '123455');
        $successCommit->setAuthor('Fabien');
        $successCommit->setMessage('Bar');
        $successCommit->setStatusCode('success');

        $baseNotifier  = $this->getMock('Sismo\Notifier\Notifier');
        $baseNotifier->expects($this->once())
            ->method('notify')
            ->will($this->returnValue('foo'));

        $notifier = new CrossFingerNotifier(array($baseNotifier));

        //a failed commit should call notify on real notifier
        $this->assertTrue($notifier->notify($failedCommit));

        $project->setCommits(array(
            $successCommit,
        ));
        //a success commit should not call notify on real notifier
        $this->assertFalse($notifier->notify($successCommit));
    }
}
