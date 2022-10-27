<?php

use \PHPUnit\Framework\TestCase;

use extas\components\plugins\Plugin;
use extas\components\repositories\TSnuffRepository;
use extas\components\SystemContainer;

/**
 * Class DriverMongoTest
 *
 * @author jeyroik@gmail.com
 */
class DriverMongoTest extends TestCase
{
    use TSnuffRepository;

    protected function setUp(): void
    {
        putenv("EXTAS__CONTAINER_PATH_STORAGE_LOCK=vendor/jeyroik/extas-foundation/resources/container.dist.json");
        $this->extasDriver = '\\extas\\components\\repositories\\drivers\\DriverMongo';
        $this->extasDriverOptions = [
            'dsn' => 'mongodb://127.0.0.1:27017',
            'db' => 'tests'
        ];

        $this->buildRepo(__DIR__ . '/../../vendor/jeyroik/extas-foundation/resources', [
            'tests' => [
                "namespace" => "tests\\tmp",
                "item_class"=> "\\extas\\components\\items\\SnuffItem",
                "pk"=> "name"
            ]
        ]);
    }

    public function tearDown(): void
    {
       $this->unregisterSnuffRepos();
       $this->deleteRepo('tests');
    }

    public function testInsertAndFind()
    {
        $repo = SystemContainer::getItem('tests');

        $repo->create(new Plugin([
            Plugin::FIELD__CLASS => 'NotExisting',
            Plugin::FIELD__STAGE => ['not','existing']
        ]));

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = NotExisting');

        $plugin = $repo->one([Plugin::FIELD__CLASS => ['NotExisting']]);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class in [NotExisting]');

        $plugin = $repo->all([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin by all()');

        $plugin = $repo->all([Plugin::FIELD__CLASS => ['NotExisting']]);
        $this->assertNotEmpty($plugin, 'Can not find plugin by all() in [NotExisting]');

        $plugin = $repo->one([Plugin::FIELD__STAGE => 'not']);
        $this->assertNotEmpty($plugin, 'Can not find plugin by stage "not"');

        $plugin = $repo->one([Plugin::FIELD__STAGE => ['not']]);
        $this->assertNotEmpty($plugin, 'Can not find plugin by stage in [not]');
    }

    public function testUpdateOne()
    {
        $repo = SystemContainer::getItem('tests');

        $plugin = new Plugin([
            Plugin::FIELD__ID => '1',
            Plugin::FIELD__CLASS => 'NotExisting',
            Plugin::FIELD__STAGE => ['not','existing']
        ]);

        $repo->create($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = NotExisting');

        $plugin->setClass('Existing not today');

        $repo->update($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'Existing not today']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = Existing not today');
    }

    public function testUpdateMany()
    {
        $repo = SystemContainer::getItem('tests');

        $plugin = new Plugin([
            Plugin::FIELD__ID => '1',
            Plugin::FIELD__CLASS => 'NotExisting',
            Plugin::FIELD__STAGE => ['not','existing']
        ]);

        $repo->create($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = NotExisting');

        $plugin->setClass('Existing not today');

        $result = $repo->update($plugin, [Plugin::FIELD__CLASS => 'NotExisting']);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'Existing not today']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = Existing not today');

        $this->assertEquals($result, 1);
    }

    public function testDeleteOne()
    {
        $repo = SystemContainer::getItem('tests');

        $plugin = new Plugin([
            Plugin::FIELD__ID => '1',
            Plugin::FIELD__CLASS => 'NotExisting',
            Plugin::FIELD__STAGE => ['not','existing']
        ]);

        $repo->create($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = NotExisting');

        $repo->delete($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertEmpty($plugin, 'Found plugin with class = NotExisting');
    }

    public function testDeleteMany()
    {
        $repo = SystemContainer::getItem('tests');

        $plugin = new Plugin([
            Plugin::FIELD__ID => '1',
            Plugin::FIELD__CLASS => 'NotExisting',
            Plugin::FIELD__STAGE => ['not','existing']
        ]);

        $repo->create($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = NotExisting');

        $result = $repo->delete([Plugin::FIELD__CLASS => 'NotExisting']);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertEmpty($plugin, 'Found plugin with class = NotExisting');

        $this->assertEquals($result, 1);
    }

    public function testDrop()
    {
        $repo = SystemContainer::getItem('tests');

        $plugin = new Plugin([
            Plugin::FIELD__ID => '1',
            Plugin::FIELD__CLASS => 'NotExisting',
            Plugin::FIELD__STAGE => ['not','existing']
        ]);

        $repo->create($plugin);

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertNotEmpty($plugin, 'Can not find plugin with class = NotExisting');

        $repo->drop();

        $plugin = $repo->one([Plugin::FIELD__CLASS => 'NotExisting']);
        $this->assertEmpty($plugin, 'Found plugin with class = NotExisting');
    }
}
