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

// @codeCoverageIgnoreStart
/**
 * Builds commits.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Builder
{
    private $project;
    private $baseBuildDir;
    private $buildDir;
    private $callback;
    private $gitPath;
    private $gitCmds;

    public function __construct($buildDir, $gitPath, array $gitCmds)
    {
        $this->baseBuildDir = $buildDir;
        $this->gitPath = $gitPath;
        $this->gitCmds = array_replace(array(
            'clone'    => 'clone --progress --recursive %repo% %dir% --branch %localbranch%',
            'fetch'    => 'fetch origin',
            'prepare'  => 'submodule update --init --recursive',
            'checkout' => 'checkout -q %branch%',
            'reset'    => 'reset --hard %revision%',
            'show'     => 'show -s --pretty=format:%format% %revision%',
        ), $gitCmds);
    }

    public function init(Project $project, $callback = null)
    {
        $this->project  = $project;
        $this->callback = $callback;
        $this->buildDir = $this->baseBuildDir.'/'.$this->getBuildDir($project);
    }

    public function build()
    {
        file_put_contents($this->buildDir.'/sismo-run-tests.sh', str_replace(array("\r\n", "\r"), "\n", $this->project->getCommand()));

        $process = new Process('sh sismo-run-tests.sh', $this->buildDir);
        $process->setTimeout(3600);
        $process->run($this->callback);

        return $process;
    }

    public function getBuildDir(Project $project)
    {
        return substr(md5($project->getRepository().$project->getBranch()), 0, 6);
    }

    public function prepare($revision, $sync)
    {
        if (!file_exists($this->buildDir)) {
            mkdir($this->buildDir, 0777, true);
        }

        if (!file_exists($this->buildDir.'/.git')) {
            $this->execute($this->getGitCommand('clone'), sprintf('Unable to clone repository for project "%s".', $this->project));
        }

        if ($sync) {
            $this->execute($this->gitPath.' '.$this->gitCmds['fetch'], sprintf('Unable to fetch repository for project "%s".', $this->project));
        }

        $this->execute($this->getGitCommand('checkout'), sprintf('Unable to checkout branch "%s" for project "%s".', $this->project->getBranch(), $this->project));

        if ($sync) {
            $this->execute($this->gitPath.' '.$this->gitCmds['prepare'], sprintf('Unable to update submodules for project "%s".', $this->project));
        }

        if (null === $revision || 'HEAD' === $revision) {
            $revision = null;
            if (file_exists($file = $this->buildDir.'/.git/HEAD')) {
                $revision = trim(file_get_contents($file));
                if (0 === strpos($revision, 'ref: ')) {
                    if (file_exists($file = $this->buildDir.'/.git/'.substr($revision, 5))) {
                        $revision = trim(file_get_contents($file));
                    } else {
                        $revision = null;
                    }
                }
            }

            if (null === $revision) {
                throw new BuildException(sprintf('Unable to get HEAD for branch "%s" for project "%s".', $this->project->getBranch(), $this->project));
            }
        }

        $this->execute($this->getGitCommand('reset', array('%revision%' => escapeshellarg($revision))), sprintf('Revision "%s" for project "%s" does not exist.', $revision, $this->project));

        $process = $this->execute($this->getGitCommand('show', array('%revision%' => escapeshellarg($revision))), sprintf('Unable to get logs for project "%s".', $this->project));

        return explode("\n", trim($process->getOutput()), 4);
    }

    protected function getGitCommand($command, array $replace = array())
    {
        $replace = array_merge(array(
            '%repo%'        => escapeshellarg($this->project->getRepository()),
            '%dir%'         => escapeshellarg($this->buildDir),
            '%branch%'      => escapeshellarg('origin/'.$this->project->getBranch()),
            '%localbranch%' => escapeshellarg($this->project->getBranch()),
            '%format%'      => escapeshellarg('%H%n%an%n%ci%n%s%n'),
        ), $replace);

        return strtr($this->gitPath.' '.$this->gitCmds[$command], $replace);
    }

    private function execute($command, $message)
    {
        if (null !== $this->callback) {
            call_user_func($this->callback, 'out', sprintf("Running \"%s\"\n", $command));
        }
        $process = new Process($command, $this->buildDir);
        $process->setTimeout(3600);
        $process->run($this->callback);
        if (!$process->isSuccessful()) {
            throw new BuildException($message);
        }

        return $process;
    }
}
// @codeCoverageIgnoreEnd
