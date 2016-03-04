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

use Sismo\Commit;
use Sismo\Contrib\LoggerNotifier;
use Sismo\Project;

class LoggerNotifierTest extends \PHPUnit_Framework_TestCase
{
    public function testNotify()
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

        $notifier = new LoggerNotifier($logger);

        //notify success commit
        $notifier->notify($successCommit);
        //notify failed commit
        $notifier->notify($failedCommit);
    }
}
