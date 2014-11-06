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

use Sismo\SSHProject;

class SSHProjectTest extends \PHPUnit_Framework_TestCase
{
    public function sshRepositoryProvider()
    {
        return array(
            array('git@github.com:twigphp/Twig.git'),
            array('git@git.assembla.com:Twig.git'),
            array('ssh://git@git.example.com:Twig.git'),
        );
    }

    /**
     * @dataProvider sshRepositoryProvider
     */
    public function testSetRepository($repository)
    {
        $project = new SSHProject('Project');
        $project->setRepository($repository);

        $this->assertEquals($repository, $project->getRepository());
        $this->assertEquals('master', $project->getBranch());
    }
}
