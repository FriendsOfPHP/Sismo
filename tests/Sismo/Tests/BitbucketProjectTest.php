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
            array('acme/Demo', 'master', 'git@bitbucket.org:/acme/Demo.git', 'https://bitbucket.org/acme/Demo/changeset/%commit%'),
            array('acme/Demo@develop', 'develop', 'git@bitbucket.org:/acme/Demo.git', 'https://bitbucket.org/acme/Demo/changeset/%commit%'),
            array('acme/no.no1', 'master', 'git@bitbucket.org:/acme/no.no1.git', 'https://bitbucket.org/acme/no.no1/changeset/%commit%'),
            array('no.no/acme', 'master', 'git@bitbucket.org:/no.no/acme.git', 'https://bitbucket.org/no.no/acme/changeset/%commit%'),
            array('acme/no_no1', 'master', 'git@bitbucket.org:/acme/no_no1.git', 'https://bitbucket.org/acme/no_no1/changeset/%commit%'),
            array('acme/no-no1', 'master', 'git@bitbucket.org:/acme/no-no1.git', 'https://bitbucket.org/acme/no-no1/changeset/%commit%'),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetRepositoryThrowsAnExceptionIfRepositoryIsNotAGithubOne()
    {
        $project = new BitbucketProject('Twig', 'fabpot/Twig/foobar');
    }
}
