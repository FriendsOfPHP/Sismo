<?php

namespace Sismo\Contrib;

use Sismo\Notifier\Notifier;
use Sismo\Commit;
use Sismo\Project;
use Sismo\BuildException;

/**
 * A notifier that opens a issue in Github notifying wether the build failed or success.
 *
 * @author Javier Lopez <alcaraz1983@gmail.com>
 */
class GithubIssueNotifier extends Notifier
{
  const API_URL = 'https://api.github.com';
  
  /**
   * Constructor
   *
   * Check tests/Sismo/Tests/Contrib/GithubIssueNotifierTest.php for some examples
   * on how to use it notifier.
   * 
   * @param string $username Github username that will open the issue
   * @param string $password Github password
   */
  public function __construct($username, $password)
  {
    $this->username = $username;
    $this->password = $password;
  }

  public function notify(Commit $commit)
  {
    $this->createIssue(self::getJsonTemplate(), $commit);
  }

  public function createIssue($format, Commit $commit)
  {
     $repository = $this->guessGithubRepository($commit->getProject());
     $url = sprintf(self::API_URL."/repos/%s/issues", $repository);
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, $url);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
     curl_setopt($ch, CURLOPT_USERPWD, sprintf("%s:%s", $this->username, $this->password));
     curl_setopt($ch, CURLOPT_POST, TRUE);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $this->format($format, $commit));
     $response = curl_exec($ch);
     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
     curl_close($ch);
     if($httpCode === 201){
       return true;
     }

     $data = json_decode($response, true);
     throw new BuildException(sprintf("Unable to open ticket at %s with message '%s'", $url, $data['message']));
  }

  /*
   * Guess the name of the Github repository where the notifier will create the issue.
   *
   * In other words, if the name of the project is 'fabpot/Sismo.git', this function will
   * return 'fabpot/Sismo'.
   * 
   * @param Sismo\Project $project The Sismo project
   *
   * @return string The name of the Github project
   */
  public function guessGithubRepository(Project $project)
  {
    $repository = $project->getRepository();
    preg_match('/(\w+\/\w+)\.git$/', $repository, $matches);
    if(isset($matches[1])){
      return $matches[1];
    }

    throw new BuildException(sprintf('Unable to guess Github repository: %s', $repository ));
  }

  static public function getJsonTemplate()
  {
    return <<<EOF
{
  "title": "%name% built %status%",
  "body" : "Started by: %author%"
}
EOF;
  }

}
