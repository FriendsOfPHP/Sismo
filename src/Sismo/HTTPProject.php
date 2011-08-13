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

use Symfony\Component\Process\Process;

/**
 * Describes a project which uses Git URLs in the form
 * of https://example.com/username/MyProject.git@mybranch
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Rich Sage <rich.sage@gmail.com>
 */
class HTTPProject extends Project
{
    /**
     * Sets the repository URL after splitting to
     * find the branch (where present)
     *
     * @param  $url
     * @return void
     */
    public function setRepository($url)
    {
      if (false !== strpos($url, '@')) {
          list($url, $branch) = explode('@', $url);
          $this->setBranch($branch);
      }

      parent::setRepository($url);
    }
}
