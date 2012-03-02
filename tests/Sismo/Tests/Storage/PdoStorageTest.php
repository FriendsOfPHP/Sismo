<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Tests\Storage;

use Sismo\Storage\PdoStorage;

require_once __DIR__.'/StorageTest.php';

class PdoStorageTest extends StorageTest
{
    private $db;
    private $run = false;

    public function setUp()
    {
        // Skip checks once everything is fine.
        if (!$this->run) {
            if (!class_exists('\PDO')) {
                $this->markTestSkipped('PDO is not available.');
            }

            if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
                $this->markTestSkipped('SQLite PDO driver is not available.');
            }

            $this->run = true;
        }

        $app = require __DIR__.'/../../../../src/app.php';

        // sqlite with file is tested by StorageTest, so we use memory here
        $this->db = new \PDO('sqlite::memory:');
        $this->db->exec($app['db.schema']);
    }

    public function testStaticCreate()
    {
        $this->assertInstanceOf('\Sismo\Storage\PdoStorage', PdoStorage::create('sqlite::memory:'));
    }

    public function testInitExistingCommit()
    {
        $project = $this->getProject();

        $storage = $this->getStorage();
        $commit = $storage->initCommit($project, '7d78d5', 'fabien', new \DateTime(), 'foo');
        $this->assertInstanceOf('\Sismo\Commit', $commit);

        $commit2 = $storage->initCommit($project, '7d78d5', 'fabien', new \DateTime(), 'foo-amended');
        $this->assertInstanceOf('\Sismo\Commit', $commit2);

        $this->assertEquals('foo-amended', $storage->getCommit($project, '7d78d5')->getMessage());
    }

    public function tearDown()
    {
        unset($this->db);
    }

    protected function getStorage()
    {
        return new PdoStorage($this->db);
    }
}
