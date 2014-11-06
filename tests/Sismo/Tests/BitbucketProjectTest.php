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

use Sismo\BitbucketProject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class BitbucketProjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getRepositoryProvider
     */
    public function testThatItSetRepository($repositoryPath, $branch, $repository, $urlPattern)
    {
        $project = new BitbucketProject('Twig', $repositoryPath);
        $this->assertEquals($branch, $project->getBranch());
        $this->assertEquals($repository, $project->getRepository());
        $this->assertEquals($urlPattern, $project->getUrlPattern());
    }

    public function getRepositoryProvider()
    {
        return array(
            array('acme/Demo', 'master', 'git@bitbucket.org:/acme/Demo.git', 'https://bitbucket.org/acme/Demo/commits/%commit%'),
            array('acme/Demo@develop', 'develop', 'git@bitbucket.org:/acme/Demo.git', 'https://bitbucket.org/acme/Demo/commits/%commit%'),
            array('acme/no.no1', 'master', 'git@bitbucket.org:/acme/no.no1.git', 'https://bitbucket.org/acme/no.no1/commits/%commit%'),
            array('no.no/acme', 'master', 'git@bitbucket.org:/no.no/acme.git', 'https://bitbucket.org/no.no/acme/commits/%commit%'),
            array('acme/no_no1', 'master', 'git@bitbucket.org:/acme/no_no1.git', 'https://bitbucket.org/acme/no_no1/commits/%commit%'),
            array('acme/no-no1', 'master', 'git@bitbucket.org:/acme/no-no1.git', 'https://bitbucket.org/acme/no-no1/commits/%commit%'),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetRepositoryThrowsAnExceptionIfRepositoryIsNotAGithubOne()
    {
        $project = new BitbucketProject('Twig', 'twigphp/Twig/foobar');
    }

    public function localRepositoryProvider()
    {
        return array(
            array('https://bitbucket.org/atlassian/stash-example-plugin.git'),
            array('git@bitbucket.org:atlassian/stash-example-plugin.git'),
        );
    }

    /**
     * @dataProvider localRepositoryProvider
     */
    public function testSetRepositoryLocal($url)
    {
        $fs = new Filesystem();
        $repository = sys_get_temp_dir().'/sismo/atlassian/stash-example-plugin';
        $fs->remove($repository);
        $fs->mkdir($repository);

        $process = new Process('git init && git remote add origin '.$url, $repository);
        $process->run();

        $project = new BitbucketProject('Stash', $repository);
        $this->assertEquals('master', $project->getBranch());
        $this->assertEquals($repository, $project->getRepository());
        $this->assertEquals('https://bitbucket.org/atlassian/stash-example-plugin/commits/%commit%', $project->getUrlPattern());

        $fs->remove($repository);
    }
}
