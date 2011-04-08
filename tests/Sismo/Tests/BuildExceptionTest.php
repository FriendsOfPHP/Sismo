<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Tests;

use Sismo\BuildException;

class BuildExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $this->assertInstanceOf('\Exception', new BuildException());
    }
}
