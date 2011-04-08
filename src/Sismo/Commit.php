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
 * Describes a project commit.
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

    public function __construct(Project $project, $sha)
    {
        $this->project = $project;
        $this->sha = $sha;
    }

    public function __toString()
    {
        return sprintf('%s@%s', $this->project, $this->getShortSha());
    }

    public function isBuilding()
    {
        return 'building' === $this->status;
    }

    public function isBuilt()
    {
        return in_array($this->status, array('success', 'failed'));
    }

    public function isSuccessful()
    {
        return 'success' === $this->status;
    }

    public function setStatusCode($status)
    {
        if (!in_array($status, array('building', 'success', 'failed'))) {
            throw new \InvalidArgumentException(sprintf('Invalid status code "%s".', $status));
        }

        $this->status = $status;
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function getStatus()
    {
        return $this->statuses[$this->status];
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function getDecoratedOutput()
    {
        return AnsiEscapeSequencesConverter::convertToHtml($this->output);
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getSha()
    {
        return $this->sha;
    }

    public function getShortSha()
    {
        return substr($this->sha, 0, 6);
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    public function getBuildDate()
    {
        return $this->buildDate;
    }

    public function setBuildDate(\DateTime $date)
    {
        $this->buildDate = $date;
    }
}
