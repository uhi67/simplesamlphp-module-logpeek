<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\logpeek\Controller;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\logpeek\Controller;
use SimpleSAML\Session;
use SimpleSAML\Utils\Auth;
use Symfony\Component\HttpFoundation\Request;

use function dirname;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

/**
 * Set of tests for the controllers in the "logpeek" module.
 */
#[CoversClass(Controller\Logpeek::class)]
class LogpeekTest extends TestCase
{
    /** @var Configuration */
    protected Configuration $config;

    /** @var Session */
    protected Session $session;

    /** @var Auth */
    protected Auth $authUtils;

    /** @var string */
    protected string $tmpfile;


    /**
     * Set up for each test.
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = Session::getSessionFromRequest();

        $this->tmpfile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simplesamlphp.log';

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['logpeek' => true],
                'loggingdir' => dirname($this->tmpfile),
                'logging.handler' => 'file',
            ],
            '[ARRAY]',
            'simplesaml',
        );

        $tag = $this->session->getTrackID();
        file_put_contents(
            $this->tmpfile,
            [
                sprintf("Aug 17 19:21:51 SimpleSAMLphp WARNING [%s] some" . PHP_EOL, $tag),
                sprintf("Aug 17 19:21:52 SimpleSAMLphp WARNING [%s] test" . PHP_EOL, $tag),
                sprintf("Aug 17 19:21:53 SimpleSAMLphp WARNING [%s] data" . PHP_EOL, $tag),
            ],
        );

        $this->authUtils = new class () extends Auth {
            public function requireAdmin(): void
            {
                // stub
            }
        };

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'logfile' => $this->tmpfile,
                    'lines'   => 1500,

                    // Read block size. 8192 is max, limited by fread.
                    'blocksz' => 8192,
                    'requireAdmin' => false, // default is true.
                ],
                '[ARRAY]',
                'simplesaml',
            ),
            'module_logpeek.php',
        );
    }


    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unlink($this->tmpfile);
    }


    /**
     * @throws Exception
     */
    public function testMain(): void
    {
        $request = Request::create(
            '/',
        );

        $c = new Controller\Logpeek();
        $c->setAuthUtils($this->authUtils);
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     * @throws Exception
     */
    public function testMainWithTag(): void
    {
        $request = Request::create(
            '/',
            'GET',
            ['tag' => $this->session->getTrackID()],
        );

        $c = new Controller\Logpeek();
        $c->setAuthUtils($this->authUtils);
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     */
    public function testMainWithInvalidTag(): void
    {
        $request = Request::create(
            '/',
            'GET',
            ['tag' => 'WRONG'],
        );

        $c = new Controller\Logpeek();
        $c->setAuthUtils($this->authUtils);

        $this->expectException(Exception::class);
        $c->main($request);
    }
}
