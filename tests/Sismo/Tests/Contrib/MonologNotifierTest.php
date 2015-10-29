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

use Sismo\Contrib\MonologNotifier;
use Sismo\Commit;
use Sismo\Project;

class MonologNotifierTest extends \PHPUnit_Framework_TestCase
{
    public function testLoggerNotify()
    {
        $project = new Project('Twig');
        $successCommit = new Commit($project, '123455');
        $successCommit->setAuthor('Fabien');
        $successCommit->setMessage('Bar');
        $successCommit->setStatusCode('success');

        $failedCommit = new Commit($project, '123456');
        $failedCommit->setAuthor('Fabien');
        $failedCommit->setMessage('Foo');
        $failedCommit->setStatusCode('failed');

        $project->setCommits(array(
            $failedCommit,
            $successCommit,
        ));

        $logger = $this->getMock('Psr\Log\NullLogger');
        $logger->expects($this->once())->method('info');
        $logger->expects($this->once())->method('critical');

        $notifier = new MonologNotifier($logger);

        //notify success commit
        $notifier->notify($successCommit);
        //notify failed commit
        $notifier->notify($failedCommit);
    }

    public function testMonologNotify()
    {
        if (!class_exists('Monolog\Logger')) {
            return;
        }
        $project = new Project('Twig');
        $successCommit = new Commit($project, '123455');
        $successCommit->setAuthor('Fabien');
        $successCommit->setMessage('Bar');
        $successCommit->setStatusCode('success');

        $failedCommit = new Commit($project, '123456');
        $failedCommit->setAuthor('Fabien');
        $failedCommit->setMessage('Foo');
        $failedCommit->setStatusCode('failed');

        $project->setCommits(array(
            $failedCommit,
            $successCommit,
        ));

        $handler = $this->getMock('Monolog\Handler\TestHandler');
        $handler->expects($this->once())
                ->method('setLevel');

        $logger = $this->getMock('Monolog\Logger', array(), array($handler));
        $logger->expects($this->once())
               ->method('info');
        $logger->expects($this->once())
               ->method('critical');
        $logger->expects($this->once())
               ->method('getHandlers')
               ->will($this->returnValue(array($handler)));

        $notifier = new MonologNotifier($logger);

        //notify success commit
        $notifier->notify($successCommit);
        //notify failed commit
        $notifier->notify($failedCommit);
    }
}
