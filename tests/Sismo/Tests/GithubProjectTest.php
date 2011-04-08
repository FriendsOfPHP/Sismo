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

use Sismo\GithubProject;

class GithubProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testSetRepository()
    {
        $project = new GithubProject('Twig', 'fabpot/Twig');
        $this->assertEquals('master', $project->getBranch());
        $this->assertEquals('https://github.com/fabpot/Twig.git', $project->getRepository());
        $this->assertEquals('https://github.com/fabpot/Twig/commit/%commit%', $project->getUrlPattern());

        $project = new GithubProject('Twig', 'fabpot/Twig@foo');
        $this->assertEquals('foo', $project->getBranch());
        $this->assertEquals('https://github.com/fabpot/Twig.git', $project->getRepository());
        $this->assertEquals('https://github.com/fabpot/Twig/commit/%commit%', $project->getUrlPattern());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetRepositoryThrowsAnExceptionIfRepositoryIsNotAGithubOne()
    {
        $project = new GithubProject('Twig', 'fabpot/Twig/foobar');
    }
}
