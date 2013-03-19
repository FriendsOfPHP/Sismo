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

/**
 * Represents a project commit.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Commit
{
    private $project;
    private $sha;
    private $message;
    private $author;
    private $date;
    private $build;
    private $output;
    private $buildDate;
    private $status = 'building';
    private $statuses = array('building' => 'building', 'success' => 'succeeded', 'failed' => 'failed');

    /**
     * Constructor.
     *
     * @param Project $project A Project instance
     * @param string  $sha     The sha of the commit
     */
    public function __construct(Project $project, $sha)
    {
        $this->project = $project;
        $this->sha = $sha;
    }

    /**
     * Returns a string representation of the Commit.
     *
     * @return string The string representation of the Commit
     */
    public function __toString()
    {
        return sprintf('%s@%s', $this->project, $this->getShortSha());
    }

    /**
     * Returns true if the commit is being built.
     *
     * @return Boolean true of the commit is being built, false otherwise
     */
    public function isBuilding()
    {
        return 'building' === $this->status;
    }

    /**
     * Returns true if the commit has already been built.
     *
     * @return Boolean true of the commit has already been built, false otherwise
     */
    public function isBuilt()
    {
        return in_array($this->status, array('success', 'failed'));
    }

    /**
     * Returns true if the commit was built successfully.
     *
     * @return Boolean true of the commit was built successfully, false otherwise
     */
    public function isSuccessful()
    {
        return 'success' === $this->status;
    }

    /**
     * Sets the build status code of the commit.
     *
     * Can be one of "building", "success", or "failed".
     *
     * @param string $status The commit build status code
     */
    public function setStatusCode($status)
    {
        if (!in_array($status, array('building', 'success', 'failed'))) {
            throw new \InvalidArgumentException(sprintf('Invalid status code "%s".', $status));
        }

        $this->status = $status;
    }

    /**
     * Gets the build status code of the commit.
     *
     * Can be one of "building", "success", or "failed".
     *
     * @return string The commit build status code
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * Gets the build status of the commit.
     *
     * Can be one of "building", "succeeded", or "failed".
     *
     * @return string The commit build status
     */
    public function getStatus()
    {
        return $this->statuses[$this->status];
    }

    /**
     * Sets the build output.
     *
     * @param string $output The build output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Gets the raw build output.
     *
     * The output can contain ANSI code characters.
     *
     * @return string The raw build output
     *
     * @see getDecoratedOutput()
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Gets the commit message.
     *
     * @return string The commit message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the commit message.
     *
     * @param string $message The commit message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Gets the commit SHA1.
     *
     * @return string The commit SHA1
     */
    public function getSha()
    {
        return $this->sha;
    }

    /**
     * Gets the short commit SHA1 (6 first characters).
     *
     * @return string The short commit SHA1
     */
    public function getShortSha()
    {
        return substr($this->sha, 0, 6);
    }

    /**
     * Gets the Project associated with this Commit.
     *
     * @return Project A Project instance
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Gets the author associated with this commit.
     *
     * @return string The commit author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the author associated with this commit.
     *
     * @return string The commit author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Gets the creation date of this commit.
     *
     * @return \DateTime A \DateTime instance
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets the creation date of this commit.
     *
     * @param \DateTime $date A \DateTime instance
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Gets the build date of this commit.
     *
     * @return \DateTime A \DateTime instance
     */
    public function getBuildDate()
    {
        return $this->buildDate;
    }

    /**
     * Sets the build date of this commit.
     *
     * @param \DateTime $date A \DateTime instance
     */
    public function setBuildDate(\DateTime $date)
    {
        $this->buildDate = $date;
    }
}
