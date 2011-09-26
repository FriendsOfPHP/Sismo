<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo;

use Symfony\Component\Process\Process;

/**
 * Describes a project hosted on Github.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GithubProject extends Project
{
    public function setRepository($url)
    {
        parent::setRepository($url);

        if (file_exists($this->getRepository())) {
            $process = new Process('git remote -v', $this->getRepository());
            $process->run();
            foreach (explode("\n", $process->getOutput()) as $line) {
                $parts = explode("\t", $line);
                if ('origin' == $parts[0] && preg_match('#(?:\:|/|@)github.com(?:\:|/)(.*?)/(.*?)\.git#', $parts[1], $matches)) {
                    $this->setUrlPattern(sprintf('https://github.com/%s/%s/commit/%%commit%%', $matches[1], $matches[2]));

                    break;
                }
            }
        } elseif (preg_match('#^[a-z0-9_-]+/[a-z0-9_-]+$#i', $this->getRepository())) {
            $this->setUrlPattern(sprintf('https://github.com/%s/commit/%%commit%%', $this->getRepository()));
            parent::setRepository(sprintf('https://github.com/%s.git', $this->getRepository()));
        } else {
            throw new \InvalidArgumentException(sprintf('URL "%s" does not look like a Github repository.', $this->getRepository()));
        }
    }
}
