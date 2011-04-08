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

use Sismo\Notifier;
use Sismo\Commit;
use Sismo\Project;

class NotifierTest extends \PHPUnit_Framework_TestCase
{
    public function testFormat()
    {
        $notifier = $this->getMock('Sismo\Notifier');
        $r = new \ReflectionObject($notifier);
        $m = $r->getMethod('format');
        $m->setAccessible(true);

        $project = new Project('Twig');
        $commit = new Commit($project, '123456');
        $commit->setAuthor('Fabien');
        $commit->setMessage('Foo');

        $this->assertEquals('twig', $m->invoke($notifier, '%slug%', $commit));
        $this->assertEquals('Twig', $m->invoke($notifier, '%name%', $commit));
        $this->assertEquals('building', $m->invoke($notifier, '%status%', $commit));
        $this->assertEquals('building', $m->invoke($notifier, '%status_code%', $commit));
        $this->assertEquals('123456', $m->invoke($notifier, '%sha%', $commit));
        $this->assertEquals('Fabien', $m->invoke($notifier, '%author%', $commit));
        $this->assertEquals('Foo', $m->invoke($notifier, '%message%', $commit));
    }
}
