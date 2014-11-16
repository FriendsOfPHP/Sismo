<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\Filesystem\Filesystem;

class appTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $this->app = require __DIR__.'/../src/app.php';

        $this->baseDir = sys_get_temp_dir().'/sismo';
        $fs = new Filesystem();
        $fs->mkdir($this->baseDir);
        $this->app['data.path'] = $this->baseDir.'/db';
        $this->app['config.file'] = $this->baseDir.'/config.php';

        // This file does not exist, so app will use default sqlite storage.
        $app['config.storage.file'] = $this->baseDir.'/storage.php';

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
        $this->assertInstanceOf('Sismo\Storage\StorageInterface', $this->app['storage']);
        $this->assertInstanceOf('Sismo\Builder', $this->app['builder']);
        $this->assertInstanceOf('Sismo\Sismo', $this->app['sismo']);
    }

    public function testMissingGit()
    {
        $this->app['git.path'] = 'gitinvalidcommand';

        $this->setExpectedException('\RuntimeException');
        $builder = $this->app['builder'];
    }

    public function testMissingConfigFile()
    {
        $this->app['config.file'] = $this->baseDir.'/missing-config.php';

        $this->setExpectedException('\RuntimeException');
        $sismo = $this->app['sismo'];
    }

    public function invalidConfigProvider()
    {
        return array(
            array('<?php return null;'),
            array('<?php return "invalid project";'),
            array('<?php return array("invalid project");'),
        );
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testInvalidConfig($config)
    {
        file_put_contents($this->app['config.file'], $config);

        $this->setExpectedException('\RuntimeException');
        $sismo = $this->app['sismo'];
    }
}
