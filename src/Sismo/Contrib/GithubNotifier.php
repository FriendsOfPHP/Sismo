<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Sergey Kolodyazhnyy <sergey.kolodyazhnyy@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Contrib;

use Sismo\Commit;
use Sismo\Notifier\Notifier;

// @codeCoverageIgnoreStart
/**
 * Class GithubNotifier
 * @package Sismo\Contrib
 */
class GithubNotifier extends Notifier
{
    /** @var string */
    protected $apikey;

    /** @var string */
    protected $host = 'https://api.github.com';

    /** @var string */
    protected $repo;

    /** @var string */
    protected $context = 'fabpot/sismo';

    /** @var string */
    protected $description = "Sismo";

    /** @var string */
    protected $targetUrlPattern = null;

    /**
     * @param string $apikey           personal API key
     * @param string $repo             repository name, e.g. fabpot/Sismo
     * @param string $targetUrlPattern status target URL pattern, e.g. http://sismo/%slug%/%sha%
     */
    public function __construct($apikey, $repo, $targetUrlPattern = null)
    {
        $this->apikey  = $apikey;
        $this->repo    = trim($repo, '/');

        $this->setTargetUrlPattern($targetUrlPattern);
    }

    /**
     * @param  string $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param  string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param  string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = rtrim($host, '/');

        return $this;
    }

    /**
     * @param  string $targetUrlPattern
     * @return $this
     */
    public function setTargetUrlPattern($targetUrlPattern)
    {
        $this->targetUrlPattern = $targetUrlPattern;

        return $this;
    }

    /**
     * Notifies a commit.
     *
     * @param Commit $commit A Commit instance
     */
    public function notify(Commit $commit)
    {
        $this->request(
            $this->getStatusEndpointUrl($commit),
            array(
                'state'       => $this->getGitHubState($commit),
                'target_url'  => $this->format($this->targetUrlPattern, $commit),
                'description' => $this->format($this->description, $commit),
                'context'     => $this->context,
            ),
            $this->getGitHubHeaders()
        );
    }

    /**
     * @param  Commit $commit
     * @return string
     */
    protected function getStatusEndpointUrl(Commit $commit)
    {
        return $this->host."/repos/".$this->repo."/statuses/".$commit->getSha();
    }

    /**
     * @param  Commit $commit
     * @return string
     */
    protected function getGitHubState(Commit $commit)
    {
        switch ($commit->getStatusCode()) {
            case 'building':
                return 'pending';
            case 'success':
                return 'success';
            case 'failed':
                return 'failure';
            default:
                return 'error';
        }
    }

    /**
     * @return array
     */
    protected function getGitHubHeaders()
    {
        return array(
            "User-Agent: Sismo GitHub notifier",
            "Authorization: Basic ".base64_encode($this->apikey.":x-oauth-basic"),
        );
    }

    /**
     * @param $url
     * @param $data
     * @param  array $headers
     * @return bool
     */
    protected function request($url, $data, array $headers = array())
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return floor($statusCode / 100) == 2;
    }
}
// @codeCoverageIgnoreEnd
