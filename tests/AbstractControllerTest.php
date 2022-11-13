<?php


namespace App\Tests;

use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\SodiumPasswordEncoder;

abstract class AbstractControllerTest extends WebTestCase
{
    private EntityManagerInterface $manager;

    private ORMExecutor $executor;

    protected AbstractBrowser $client;

    public function setUp() : void
    {
        parent::setUp();
        self::bootKernel();

        $kernel = self::$kernel;

        $this->manager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->executor = new ORMExecutor($this->manager, new ORMPurger());

        // Run the schema update tool using our entity metadata
        $schemaTool = new SchemaTool($this->manager);
        $schemaTool->updateSchema($this->manager->getMetadataFactory()->getAllMetadata());
        $this->loadFixture(new AppFixtures());

        self::ensureKernelShutdown();
        $this->client = static::createClient([], [
            'HTTP_X-API-KEY' => '1234',
            'HTTP_Accept' => 'application/json',
        ]);
    }

    protected function loadFixture($fixture)
    {
        $loader = new Loader();
        $fixtures = is_array($fixture) ? $fixture : [$fixture];
        foreach ($fixtures as $item) {
            $loader->addFixture($item);
        }
        $this->executor->execute($loader->getFixtures());
    }

    public function tearDown() : void
    {
        //(new SchemaTool($this->manager))->dropDatabase();
        parent::tearDown();
    }
}