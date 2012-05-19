<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Storage;

use Sismo\Project;
use Sismo\Commit;

/**
 * Stores projects and builds information.
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
interface StorageInterface
{
    /**
     * Retrieves a commit out of a project.
     *
     * @throws \RuntimeException
     *
     * @param Project $project The project this commit is part of.
     * @param string  $sha     The hash of the commit to retrieve.
     *
     * @return Commit
     */
    public function getCommit(Project $project, $sha);

    /**
     * Initiate, create and save a new commit.
     *
     * @param Project   $project The project of the new commit.
     * @param string    $sha     The hash of the commit.
     * @param string    $author  The name of the author of the new commit.
     * @param \DateTime $date    The date the new commit was created originally (e.g. by external resources).
     * @param string    $message The commit message.
     *
     * @return Commit The newly created commit.
     */
    public function initCommit(Project $project, $sha, $author, \DateTime $date, $message);

    /**
     * Create or update the information of a project.
     *
     * If the project is already available, the information of the existing project will be updated.
     *
     * @param Project $project The project to create or update.
     */
    public function updateProject(Project $project);

    /**
     * Update the commits information.
     *
     * The commit is identified by its sha hash.
     *
     * @param Commit $commit
     */
    public function updateCommit(Commit $commit);

    /**
     * Shutdown the storage and all of its external resources.
     */
    public function close();
}
