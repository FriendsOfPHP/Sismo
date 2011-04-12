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
 * Stores projects and builds information.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Storage
{
    private $db;

    public function __construct(\SQLite3 $db)
    {
        $this->db = $db;
    }

    public function getCommit(Project $project, $sha)
    {
        $stmt = $this->db->prepare('SELECT slug, sha, author, date, build_date, message, status, output FROM `commit` WHERE slug = :slug AND sha = :sha');
        $stmt->bindValue(':slug', $project->getSlug(), SQLITE3_TEXT);
        $stmt->bindValue(':sha', $sha, SQLITE3_TEXT);

        if (false !== $result = $stmt->execute()) {
            if (false !== $result = $result->fetchArray(\SQLITE3_ASSOC)) {
                return $this->createCommit($project, $result);
            }
        }

        return false;
    }

    public function initCommit(Project $project, $sha, $author, \DateTime $date, $message)
    {
        $stmt = $this->db->prepare('INSERT OR REPLACE INTO `commit` (slug, sha, author, date, message, status, output, build_date) VALUES (:slug, :sha, :author, :date, :message, :status, :output, :build_date)');
        $stmt->bindValue(':slug', $project->getSlug(), SQLITE3_TEXT);
        $stmt->bindValue(':sha', $sha, SQLITE3_TEXT);
        $stmt->bindValue(':author', $author, SQLITE3_TEXT);
        $stmt->bindValue(':date', $date->format('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        $stmt->bindValue(':status', 'building', SQLITE3_TEXT);
        $stmt->bindValue(':output', '', SQLITE3_TEXT);
        $stmt->bindValue(':build_date', '', SQLITE3_TEXT);

        if (false === $result = $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to save commit "%s" from project "%s".', $sha, $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        $commit = new Commit($project, $sha);
        $commit->setAuthor($author);
        $commit->setMessage($message);
        $commit->setDate($date);

        return $commit;
    }

    public function updateProject(Project $project)
    {
        $stmt = $this->db->prepare('INSERT OR REPLACE INTO project (slug, name, repository, branch, command, url_pattern) VALUES (:slug, :name, :repository, :branch, :command, :url_pattern)');
        $stmt->bindValue(':slug', $project->getSlug(), SQLITE3_TEXT);
        $stmt->bindValue(':name', $project->getName(), SQLITE3_TEXT);
        $stmt->bindValue(':repository', $project->getRepository(), SQLITE3_TEXT);
        $stmt->bindValue(':branch', $project->getBranch(), SQLITE3_TEXT);
        $stmt->bindValue(':command', $project->getCommand(), SQLITE3_TEXT);
        $stmt->bindValue(':url_pattern', $project->getUrlPattern(), SQLITE3_TEXT);

        if (false === $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to save project "%s".', $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        // related commits
        $stmt = $this->db->prepare('SELECT sha, author, date, build_date, message, status, output FROM `commit` WHERE slug = :slug ORDER BY build_date DESC LIMIT 100');
        $stmt->bindValue(':slug', $project->getSlug(), SQLITE3_TEXT);

        if (false === $results = $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to get latest commit for project "%s".', $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        $commits = array();
        while ($result = $results->fetchArray(\SQLITE3_ASSOC)) {
            $commits[] = $this->createCommit($project, $result);
        }

        $project->setCommits($commits);

        // project building?
        $stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM `commit` WHERE slug = :slug AND status = "building"');
        $stmt->bindValue(':slug', $project->getSlug(), SQLITE3_TEXT);

        $building = false;
        if (false !== $result = $stmt->execute()) {
            if (false !== $result = $result->fetchArray(\SQLITE3_ASSOC)) {
                if ($result['count'] > 0) {
                    $building = true;
                }
            }
        }

        $project->setBuilding($building);
    }

    public function updateCommit(Commit $commit)
    {
        $stmt = $this->db->prepare('UPDATE `commit` SET status = :status, output = :output, build_date = CURRENT_TIMESTAMP WHERE slug = :slug AND sha = :sha');
        $stmt->bindValue(':slug', $commit->getProject()->getSlug(), SQLITE3_TEXT);
        $stmt->bindValue(':sha', $commit->getSha(), SQLITE3_TEXT);
        $stmt->bindValue(':status', $commit->getStatusCode(), SQLITE3_TEXT);
        $stmt->bindValue(':output', $commit->getOutput(), SQLITE3_TEXT);

        if (false === $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to save build "%s@%s".', $commit->getProject()->getName(), $commit->getSha()));
            // @codeCoverageIgnoreEnd
        }
    }

    private function createCommit($project, $result)
    {
        $commit = new Commit($project, $result['sha']);
        $commit->setAuthor($result['author']);
        $commit->setMessage($result['message']);
        $commit->setDate(\DateTime::createFromFormat('Y-m-d H:i:s', $result['date']));
        if ($result['build_date']) {
            $commit->setBuildDate(\DateTime::createFromFormat('Y-m-d H:i:s', $result['build_date']));
        }
        $commit->setStatusCode($result['status']);
        $commit->setOutput($result['output']);

        return $commit;
    }

    public function close()
    {
        $this->db->close();
    }

    public function __destruct()
    {
        $this->close();
    }
}
