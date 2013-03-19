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

use Sismo\Storage\StorageInterface;

/**
 * Main entry point for Sismo.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Sismo
{
    const VERSION = '1.0.0';

    const FORCE_BUILD  = 1;
    const LOCAL_BUILD  = 2;
    const SILENT_BUILD = 4;

    private $storage;
    private $builder;
    private $projects = array();

    /**
     * Constructor.
     *
     * @param StorageInterface $storage A StorageInterface instance
     * @param Builder          $builder A Builder instance
     */
    public function __construct(StorageInterface $storage, Builder $builder)
    {
        $this->storage = $storage;
        $this->builder = $builder;
    }

    /**
     * Builds a project.
     *
     * @param Project $project  A Project instance
     * @param string  $revision The revision to build (or null for the latest revision)
     * @param integer $flags    Flags (a combinaison of FORCE_BUILD, LOCAL_BUILD, and SILENT_BUILD)
     * @param mixed   $callback A PHP callback
     */
    public function build(Project $project, $revision = null, $flags = 0, $callback = null)
    {
        // project already has a running build
        if ($project->isBuilding() && Sismo::FORCE_BUILD !== ($flags & Sismo::FORCE_BUILD)) {
            return;
        }

        $this->builder->init($project, $callback);

        list($sha, $author, $date, $message) = $this->builder->prepare($revision, Sismo::LOCAL_BUILD !== ($flags & Sismo::LOCAL_BUILD));

        $commit = $this->storage->getCommit($project, $sha);

        // commit has already been built
        if ($commit && $commit->isBuilt() && Sismo::FORCE_BUILD !== ($flags & Sismo::FORCE_BUILD)) {
            return;
        }

        $commit = $this->storage->initCommit($project, $sha, $author, \DateTime::createFromFormat('Y-m-d H:i:s O', $date), $message);

        $process = $this->builder->build();

        if (!$process->isSuccessful()) {
            $commit->setStatusCode('failed');
            $commit->setOutput(sprintf("\033[31mBuild failed\033[0m\n\n\033[33mOutput\033[0m\n%s\n\n\033[33m Error\033[0m%s", $process->getOutput(), $process->getErrorOutput()));
        } else {
            $commit->setStatusCode('success');
            $commit->setOutput($process->getOutput());
        }

        $this->storage->updateCommit($commit);

        if (Sismo::SILENT_BUILD !== ($flags & Sismo::SILENT_BUILD)) {
            foreach ($project->getNotifiers() as $notifier) {
                $notifier->notify($commit);
            }
        }
    }

    /**
     * Checks if Sismo knows about a given project.
     *
     * @param string $slug A project slug
     */
    public function hasProject($slug)
    {
        return isset($this->projects[$slug]);
    }

    /**
     * Gets a project.
     *
     * @param string $slug A project slug
     */
    public function getProject($slug)
    {
        if (!isset($this->projects[$slug])) {
            throw new \InvalidArgumentException(sprintf('Project "%s" does not exist.', $slug));
        }

        return $this->projects[$slug];
    }

    /**
     * Adds a project.
     *
     * @param Project $project A Project instance
     */
    public function addProject(Project $project)
    {
        $this->storage->updateProject($project);

        $this->projects[$project->getSlug()] = $project;
    }

    /**
     * Gets all projects.
     *
     * @return array An array of Project instances
     */
    public function getProjects()
    {
        return $this->projects;
    }
}
