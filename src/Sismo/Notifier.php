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
 * Base class for notifiers.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class Notifier
{
    abstract public function notify(Commit $commit);

    protected function format($format, Commit $commit)
    {
        return strtr($format, $this->getPlaceholders($commit));
    }

    protected function getPlaceholders(Commit $commit)
    {
        $project = $commit->getProject();

        return array(
            '%slug%'        => $project->getSlug(),
            '%name%'        => $project->getName(),
            '%status%'      => $commit->getStatus(),
            '%status_code%' => $commit->getStatusCode(),
            '%STATUS%'      => strtoupper($commit->getStatus()),
            '%sha%'         => $commit->getSha(),
            '%short_sha%'   => $commit->getShortSha(),
            '%author%'      => $commit->getAuthor(),
            '%message%'     => $commit->getMessage(),
        );
    }
}
