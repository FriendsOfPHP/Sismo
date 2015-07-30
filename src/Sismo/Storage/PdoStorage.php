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
class PdoStorage implements StorageInterface
{
    /**
     * An established database connection.
     *
     * @var \PDO
     */
    private $db;

    /**
     * Constructor.
     *
     * @param \PDO $con An established PDO connection.
     */
    public function __construct(\PDO $con)
    {
        $this->db = $con;
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Create a PdoStorage by establishing a PDO connection.
     *
     * @throws \PDOException If the attempt to connect to the requested database fails.
     *
     * @param string $dsn      The data source name.
     * @param string $username The username to login with.
     * @param string $passwd   The password of the given user.
     * @param array  $options  Additional options to pass to the PDO driver.
     *
     * @return PdoStorage The created storage on the defined connection.
     */
    public static function create($dsn, $username = null, $passwd = null, array $options = array())
    {
        return new self(new \PDO($dsn, $username, $passwd, $options));
    }

    /**
     * Retrieves a commit out of a project.
     *
     * @param Project $project The project this commit is part of.
     * @param string  $sha     The hash of the commit to retrieve.
     *
     * @return Commit
     */
    public function getCommit(Project $project, $sha)
    {
        $stmt = $this->db->prepare('SELECT slug, sha, author, date, build_date, message, status, output FROM `commit` WHERE slug = :slug AND sha = :sha');
        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);
        $stmt->bindValue(':sha', $sha, \PDO::PARAM_STR);

        if ($stmt->execute()) {
            if (false !== $result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return $this->createCommit($project, $result);
            }
        } else {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to retrieve commit "%s" from project "%s".', $sha, $project), 1);
            // @codeCoverageIgnoreEnd
        }

        return false;
    }

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
    public function initCommit(Project $project, $sha, $author, \DateTime $date, $message)
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM `commit` WHERE slug = :slug');
        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);

        if (false === $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to verify existence of commit "%s" from project "%s".', $sha, $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        if ($stmt->fetchColumn(0)) {
            $stmt = $this->db->prepare('UPDATE `commit` SET slug = :slug, sha = :sha, author = :author, date = :date, message = :message, status = :status, output = :output, build_date = :build_date WHERE slug = :slug');
        } else {
            $stmt = $this->db->prepare('INSERT INTO `commit` (slug, sha, author, date, message, status, output, build_date) VALUES (:slug, :sha, :author, :date, :message, :status, :output, :build_date)');
        }

        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);
        $stmt->bindValue(':sha', $sha, \PDO::PARAM_STR);
        $stmt->bindValue(':author', $author, \PDO::PARAM_STR);
        $stmt->bindValue(':date', $date->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, \PDO::PARAM_STR);
        $stmt->bindValue(':status', 'building', \PDO::PARAM_STR);
        $stmt->bindValue(':output', '', \PDO::PARAM_STR);
        $stmt->bindValue(':build_date', '', \PDO::PARAM_STR);

        if (false === $stmt->execute()) {
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

    /**
     * Create or update the information of a project.
     *
     * If the project is already available, the information of the existing project will be updated.
     *
     * @param Project $project The project to create or update.
     *
     * @return StorageInterface $this
     */
    public function updateProject(Project $project)
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM project WHERE slug = :slug');
        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);

        if (false === $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to verify existence of project "%s".', $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        if ($stmt->fetchColumn(0)) {
            $stmt = $this->db->prepare('UPDATE project SET slug = :slug, name = :name, repository = :repository, branch = :branch, command = :command, url_pattern = :url_pattern WHERE slug = :slug');
        } else {
            $stmt = $this->db->prepare('INSERT INTO project (slug, name, repository, branch, command, url_pattern) VALUES (:slug, :name, :repository, :branch, :command, :url_pattern)');
        }

        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);
        $stmt->bindValue(':name', $project->getName(), \PDO::PARAM_STR);
        $stmt->bindValue(':repository', $project->getRepository(), \PDO::PARAM_STR);
        $stmt->bindValue(':branch', $project->getBranch(), \PDO::PARAM_STR);
        $stmt->bindValue(':command', $project->getCommand(), \PDO::PARAM_STR);
        $stmt->bindValue(':url_pattern', $project->getUrlPattern(), \PDO::PARAM_STR);

        if (false === $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to save project "%s".', $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        // related commits
        $stmt = $this->db->prepare('SELECT sha, author, date, build_date, message, status, output FROM `commit` WHERE slug = :slug ORDER BY `status` = "building" DESC, build_date DESC LIMIT 100');
        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);

        if (false === $stmt->execute()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to get latest commit for project "%s".', $project->getName()));
            // @codeCoverageIgnoreEnd
        }

        $commits = array();
        while ($result = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $commits[] = $this->createCommit($project, $result);
        }

        $project->setCommits($commits);

        // project building?
        $stmt = $this->db->prepare('SELECT COUNT(*) AS count FROM `commit` WHERE slug = :slug AND status = "building"');
        $stmt->bindValue(':slug', $project->getSlug(), \PDO::PARAM_STR);

        $building = false;
        if ($stmt->execute() and intval($stmt->fetchColumn(0))) {
            $building = true;
        }

        $project->setBuilding($building);
    }

    /**
     * Update the commits information.
     *
     * The commit is identified by its sha hash.
     *
     * @param Commit $commit
     *
     * @return StorageInterface $this
     */
    public function updateCommit(Commit $commit)
    {
        $stmt = $this->db->prepare('UPDATE `commit` SET status = :status, output = :output, build_date = :current_date WHERE slug = :slug AND sha = :sha');
        $stmt->bindValue(':slug', $commit->getProject()->getSlug(), \PDO::PARAM_STR);
        $stmt->bindValue(':sha', $commit->getSha(), \PDO::PARAM_STR);
        $stmt->bindValue(':status', $commit->getStatusCode(), \PDO::PARAM_STR);
        $stmt->bindValue(':output', $commit->getOutput(), \PDO::PARAM_STR);
        $stmt->bindValue(':current_date', date('Y-m-d H:i:s'), \PDO::PARAM_STR);

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

    /**
     * Shutdown the storage and all of its external resources.
     *
     * @return StorageInterface $this
     */
    public function close()
    {
        unset($this->db);
    }

    public function __destruct()
    {
        $this->close();
    }
}
