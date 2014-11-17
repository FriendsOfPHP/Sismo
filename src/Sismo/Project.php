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

use Sismo\Notifier\Notifier;

/**
 * Represents a project.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Project
{
    protected static $defaultCommand = 'phpunit';

    protected $name;
    protected $slug;
    protected $repository;
    protected $branch = 'master';
    protected $command;
    protected $urlPattern;
    protected $commits = array();
    protected $building = false;
    protected $notifiers = array();

    /**
     * Constructor.
     *
     * @param string $name       The project name
     * @param string $repository The repository URL
     * @param array  $notifiers  An array of Notifier instances
     * @param string $slug       The project slug
     */
    public function __construct($name, $repository = null, $notifiers = array(), $slug = null)
    {
        $this->name = $name;
        $this->slug = $slug ?: $this->slugify($name);
        $this->command = static::$defaultCommand;

        if (null !== $repository) {
            $this->setRepository($repository);
        }

        if (!is_array($notifiers)) {
            $notifiers = array($notifiers);
        }

        foreach ($notifiers as $notifier) {
            $this->addNotifier($notifier);
        }
    }

    /**
     * Returns a string representation of the Project.
     *
     * @return string The string representation of the Project
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Toggles the building status flag.
     *
     * @param bool $bool The build status flag
     */
    public function setBuilding($bool)
    {
        $this->building = (bool) $bool;
    }

    /**
     * Returns true if the project is currently being built.
     *
     * @return bool true if the project is currently being built, false otherwise
     */
    public function isBuilding()
    {
        return $this->building;
    }

    /**
     * Adds a notifier.
     *
     * @param Notifier $notifier A Notifier instance
     */
    public function addNotifier(Notifier $notifier)
    {
        $this->notifiers[] = $notifier;

        return $this;
    }

    /**
     * Gets the notifiers associated with this project.
     *
     * @return array An array of Notifier instances
     */
    public function getNotifiers()
    {
        return $this->notifiers;
    }

    /**
     * Sets the branch of the project we are interested in.
     *
     * @param string $branch The branch name
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * Gets the project branch name.
     *
     * @return string The branch name
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * Sets the commits associated with the project.
     *
     * @param array $commits An array of Commit instances
     */
    public function setCommits(array $commits = array())
    {
        $this->commits = $commits;
    }

    /**
     * Gets the commits associated with the project.
     *
     * @return array An array of Commit instances
     */
    public function getCommits()
    {
        return $this->commits;
    }

    /**
     * Gets the latest commit of the project.
     *
     * @return Commit A Commit instance
     */
    public function getLatestCommit()
    {
        return $this->commits ? $this->commits[0] : null;
    }

    /**
     * Gets the build status code of the latest commit.
     *
     * If the commit has been built, it returns the Commit::getStatusCode()
     * value; if not, it returns "no_build".
     *
     * @return string The build status code for the latest commit
     *
     * @see Commit::getStatusCode()
     */
    public function getStatusCode()
    {
        return !$this->commits ? 'no_build' : $this->commits[0]->getStatusCode();
    }

    /**
     * Gets the build status of the latest commit.
     *
     * If the commit has been built, it returns the Commit::getStatus()
     * value; if not, it returns "not built yet".
     *
     * @return string The build status for the latest commit
     *
     * @see Commit::getStatus()
     */
    public function getStatus()
    {
        return !$this->commits ? 'not built yet' : $this->commits[0]->getStatus();
    }

    /**
     * Gets the build status of the latest commit as a Cruise Control string.
     *
     * The value is one of "Unknown", "Success", or "Failure".
     *
     * @return string The build status for the latest commit
     */
    public function getCCStatus()
    {
        if (!$this->commits || !$this->commits[0]->isBuilt()) {
            return 'Unknown';
        }

        return $this->commits[0]->isSuccessful() ? 'Success' : 'Failure';
    }

    /**
     * Gets the build status activity of the latest commit as a Cruise Control string.
     *
     * The value is one of "Building" or "Sleeping".
     *
     * @return string The build status activity for the latest commit
     */
    public function getCCActivity()
    {
        return $this->commits && $this->commits[0]->isBuilding() ? 'Building' : 'Sleeping';
    }

    /**
     * Gets the project name.
     *
     * @return string The project name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the project short name.
     *
     * @return string The project short name
     */
    public function getShortName()
    {
        list($name) = explode('(', $this->name);

        return trim($name);
    }

    /**
     * Gets the project sub name.
     *
     * @return string The project sub name
     */
    public function getSubName()
    {
        if (false !== $pos = strpos($this->name, '(')) {
            return trim(substr($this->name, $pos + 1, -1));
        }

        return '';
    }

    /**
     * Gets the project slug.
     *
     * @return string The project slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Sets the project slug.
     *
     * @param string $slug The project slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Gets the project repository URL.
     *
     * @return string The project repository URL
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Sets the project repository URL.
     *
     * @param string $url The project repository URL
     */
    public function setRepository($url)
    {
        if (false !== strpos($url, '@')) {
            list($url, $branch) = explode('@', $url);
            $this->branch = $branch;
        }

        $this->repository = $url;

        return $this;
    }

    /**
     * Gets the command to use to build the project.
     *
     * @return string The build command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Sets the command to use to build the project.
     *
     * @param string $command The build command
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    public static function setDefaultCommand($command)
    {
        self::$defaultCommand = $command;
    }

    /**
     * Gets the URL pattern to use to link to commits.
     *
     * @return string The URL pattern
     */
    public function getUrlPattern()
    {
        return $this->urlPattern;
    }

    /**
     * Sets the URL pattern to use to link to commits.
     *
     * In a pattern, you can use the "%commit%" placeholder to reference
     * the commit SHA1.
     *
     * @return string The URL pattern
     */
    public function setUrlPattern($pattern)
    {
        $this->urlPattern = $pattern;

        return $this;
    }

    // code derived from http://php.vrana.cz/vytvoreni-pratelskeho-url.php
    private function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('#[^\\pL\d]+#u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('#[^-\w]+#', '', $text);

        if (empty($text)) {
            throw new \RuntimeException(sprintf('Unable to compute a slug for "%s". Define it explicitly.', $text));
        }

        return $text;
    }
}
