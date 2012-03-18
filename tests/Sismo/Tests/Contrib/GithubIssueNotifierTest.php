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

use Sismo\Contrib\GithubIssueNotifier;
use Sismo\Commit;
use Sismo\GithubProject;

class GithubIssueNotifierTest extends \PHPUnit_Framework_TestCase
{
    private $github_username = "your_username";
    private $github_password = "your_password";
     
    public function testCreateIssue()
    {
       $notifier = new GithubIssueNotifier($this->github_username, $this->github_password);
       $project = new GithubProject('cv-master', 'loalf/cv');
       $commit = new Commit($project, '676789');
       $commit->setOutput('dummy_output');
       $commit->setAuthor('dummy_author');

       $bool = $notifier->createIssue(GithubIssueNotifier::getJsonTemplate(), $commit);
       $this->assertTrue($bool);
    }
    
    public function testGuessGithubRepository()
    {
      $notifier = new GithubIssueNotifier($this->github_username, $this->github_password);
     
      $project  = new GithubProject('cv-master', 'loalf/cv');
      $this->assertEquals('loalf/cv', $notifier->guessGithubRepository($project));  

      $project  = new GithubProject('symfony-master', 'symfony/symfony');
      $this->assertEquals('symfony/symfony', $notifier->guessGithubRepository($project));
    }


    
}
