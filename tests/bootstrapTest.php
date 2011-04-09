<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\HttpKernel\Util\Filesystem;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $this->app = require __DIR__.'/../src/bootstrap.php';

        $this->baseDir = sys_get_temp_dir().'/sismo';
        $fs = new Filesystem();
        $fs->mkdir($this->baseDir);
        $fs->mkdir($this->baseDir.'/config');
        $app['data.path'] = $this->baseDir.'/db';
        $app['config.file'] = $this->baseDir.'/config.php';

        @unlink($this->app['db.path']);
        file_put_contents($app['config.file'], '<?php return array();');
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->app['storage']->close();

        $fs = new Filesystem();
        $fs->remove($this->baseDir);
    }

    public function testServices()
    {
        $this->assertInstanceOf('SQLite3', $this->app['db']);
        $this->assertInstanceOf('Sismo\Storage', $this->app['storage']);
        $this->assertInstanceOf('Sismo\Builder', $this->app['builder']);
        $this->assertInstanceOf('Sismo\Sismo', $this->app['sismo']);
    }
}
