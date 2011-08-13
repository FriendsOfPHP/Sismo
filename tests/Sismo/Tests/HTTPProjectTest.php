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

use Sismo\HTTPProject;

class HTTPProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testSetRepository()
    {
        $project = new HTTPProject('Twig Local');
        $project->setRepository('https://github.com/fabpot/Twig.git@feat');
        $this->assertEquals('https://github.com/fabpot/Twig.git', $project->getRepository());
        $this->assertEquals('feat', $project->getBranch());
    }
}
