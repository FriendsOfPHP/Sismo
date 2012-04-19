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
 * Describes a project accessible via SSH.
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class SSHProject extends Project
{
    /**
     * Sets the project repository URL.
     *
     * @param string $url The project repository URL
     */
    public function setRepository($url)
    {
        $this->repository = $url;
    }
}
