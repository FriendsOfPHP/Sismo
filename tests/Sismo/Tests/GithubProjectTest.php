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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class GithubProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testSetRepository()
    {
        $project = new GithubProject('Twig', 'twigphp/Twig');
        $this->assertEquals('master', $project->getBranch());
        $this->assertEquals('https://github.com/twigphp/Twig.git', $project->getRepository());
        $this->assertEquals('https://github.com/twigphp/Twig/commit/%commit%', $project->getUrlPattern());

        $project = new GithubProject('Twig', 'twigphp/Twig@foo');
        $this->assertEquals('foo', $project->getBranch());
        $this->assertEquals('https://github.com/twigphp/Twig.git', $project->getRepository());
        $this->assertEquals('https://github.com/twigphp/Twig/commit/%commit%', $project->getUrlPattern());
    }

    public function localRepositoryProvider()
    {
        return array(
            array('https://github.com/twigphp/Twig.git'),
            array('git@github.com:twigphp/Twig.git'),
        );
    }

    /**
     * @dataProvider localRepositoryProvider
     */
    public function testSetRepositoryLocal($url)
    {
        $fs = new Filesystem();
        $repository = sys_get_temp_dir().'/sismo/twigphp/Twig';
        $fs->remove($repository);
        $fs->mkdir($repository);

        $process = new Process('git init && git remote add origin '.$url, $repository);
        $process->run();

        $project = new GithubProject('Twig', $repository);
        $this->assertEquals('master', $project->getBranch());
        $this->assertEquals($repository, $project->getRepository());
        $this->assertEquals('https://github.com/twigphp/Twig/commit/%commit%', $project->getUrlPattern());

        $fs->remove($repository);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetRepositoryThrowsAnExceptionIfRepositoryIsNotAGithubOne()
    {
        $project = new GithubProject('Twig', 'twigphp/Twig/foobar');
    }
}
