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
 * Describes a project.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Project
{
    private $name;
    private $slug;
    private $repository;
    private $branch = 'master';
    private $command = 'phpunit';
    private $urlPattern;
    private $commits = array();
    private $building = false;
    private $notifiers = array();

    public function __construct($name, $repository = null, $notifiers = array(), $slug = null)
    {
        $this->name = $name;
        $this->slug = $slug ?: $this->slugify($name);

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

    public function __toString()
    {
        return $this->name;
    }

    public function setBuilding($bool)
    {
        $this->building = (Boolean) $bool;
    }

    public function isBuilding()
    {
        return $this->building;
    }

    public function addNotifier(Notifier $notifier)
    {
        $this->notifiers[] = $notifier;
    }

    public function getNotifiers()
    {
        return $this->notifiers;
    }

    public function setBranch($branch)
    {
        $this->branch = $branch;
    }

    public function getBranch()
    {
        return $this->branch;
    }

    public function setCommits(array $commits = array())
    {
        $this->commits = $commits;
    }

    public function getCommits()
    {
        return $this->commits;
    }

    public function getLatestCommit()
    {
        return $this->commits ? $this->commits[0] : null;
    }

    public function getStatusCode()
    {
        return !$this->commits ? 'no_build' : $this->commits[0]->getStatusCode();
    }

    public function getStatus()
    {
        return !$this->commits ? 'not built yet' : $this->commits[0]->getStatus();
    }

    public function getCCStatus()
    {
        if (!$this->commits || !$this->commits[0]->isBuilt()) {
            return 'Unknown';
        }

        return $this->commits[0]->isSuccessful() ? 'Success' : 'Failure';
    }

    public function getCCActivity()
    {
        return $this->commits && $this->commits[0]->isBuilding() ? 'Building' : 'Sleeping';
    }

    public function getName()
    {
        return $this->name;
    }

    public function getShortName()
    {
        list($name, ) = explode('(', $this->name);

        return trim($name);
    }

    public function getSubName()
    {
        if (false !== $pos = strpos($this->name, '(')) {
            return trim(substr($this->name, $pos + 1, -1));
        }

        return '';
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($url)
    {
        if (false !== strpos($url, '@')) {
            list($url, $branch) = explode('@', $url);
            $this->branch = $branch;
        }

        $this->repository = $url;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function setCommand($command)
    {
        $this->command = $command;
    }

    public function getUrlPattern()
    {
        return $this->urlPattern;
    }

    public function setUrlPattern($pattern)
    {
        $this->urlPattern = $pattern;
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
