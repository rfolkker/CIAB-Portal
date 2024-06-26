<?php

namespace App\Tests\Base;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Environment;
use App\Tests\Base\BlankMiddleWare;
use Chadicus\Slim\OAuth2\Middleware;
use Atlas\Query\Insert;
use Atlas\Query\Delete;
use Osteel\OpenApi\Testing\ValidatorBuilder;

if (is_file(__DIR__.'/../../../../.env')) {
    $dotenv = \Dotenv\Dotenv::create(__DIR__.'/../../../..');
    $dotenv->load();
}

require __DIR__.'/../../App/Routes.php';
require __DIR__.'/../../App/Dependencies.php';
require __DIR__.'/../../App/OAuth2.php';
require __DIR__.'/../../App/Vendors.php';
require __DIR__.'/../../App/Install.php';

require __DIR__.'/../../../../functions/functions.inc';
require_once __DIR__.'/../../../../backends/oauth2.inc';

require_once __DIR__.'/../../../../modules/concom/functions/RBAC.inc';

abstract class CiabTestCase extends TestCase
{

    /**
     * @var string
     */
    protected static $login = 'allfather@oneeye.com';

    /**
     * @var array[string]
     */
    protected static $unpriv_logins = array(
        'loki@oneeye.com',
        'frigga@oneeye.com'
    );

    /**
     * @var string
     */
    protected static $password = 'Sleipnir';

    /**
     * @var string
     */
    protected static $client = 'ciab';

    /**
     * @var object
     */
    protected static $validator;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var App
     */
    protected $app;

    /**
     * @var object
     */
    protected $middleware;

    /**
     * @var bool
     */
    protected $setupToken = true;

    /**
     * @var bool
     */
    protected $useOAuth2 = true;

    /**
     * @var bool
     */
    protected $setupUnpriv = true;

    /**
     * @var object
     */
    protected $token;

    /**
     * @var array[]
     */
    protected $unpriv_tokens;

    /**
     * @var array[string]
     */
    protected $testing_accounts = [];

    const YAMLFILE = __DIR__.'/../../../../ciab.openapi.yaml';


    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $already_loaded = array_key_exists('init', $GLOBALS);
        $GLOBALS['init'] = true;

        if (!$already_loaded) {
            $modules = scandir(__DIR__.'/../../Modules');
            foreach ($modules as $key => $value) {
                if (!in_array($value, array(',', '..'))) {
                    if (is_dir(__DIR__.'/../../Modules/'.$value)) {
                        if (is_file(__DIR__.'/../../Modules/'.$value.'/Settings.php')) {
                            if (is_file(__DIR__.'/../../Modules/'.$value.'/App/Dependencies.php')) {
                                include(__DIR__.'/../../Modules/'.$value.'/App/Dependencies.php');
                            }
                            if (is_file(__DIR__.'/../../Modules/'.$value.'/App/Routes.php')) {
                                include(__DIR__.'/../../Modules/'.$value.'/App/Routes.php');
                            }
                        }
                    }
                }
            }
        }

        CiabTestCase::$validator = ValidatorBuilder::fromYamlFile(CiabTestCase::YAMLFILE)->getValidator();

    }


    protected function setUp(): void
    {
        parent::setUp();

        _config_from_Database();

        $settings = require __DIR__.'/../../App/Settings.php';
        $this->app = new \Slim\App($settings);
        setupAPIDependencies($this->app, $settings);
        setupAPIVendors($settings);

        $container = $this->app->getContainer();
        if ($container === null) {
            throw new UnexpectedValueException('Container must be initialized');
        }

        $this->container = $container;

        if ($this->useOAuth2) {
            $data = setupOAUTH2();
            setupAPIOAuth2($this->app, $data[0]);
            $this->middleware = new Middleware\Authorization($data[0], $this->container);
        } else {
            $this->middleware = new BlankMiddleWare();
        }
        setupAPIRoutes($this->app, $this->middleware);

        $modules = scandir(__DIR__.'/../../Modules');
        foreach ($modules as $key => $value) {
            if (!in_array($value, array(',', '..'))) {
                if (is_dir(__DIR__.'/../../Modules/'.$value)) {
                    if (is_file(__DIR__.'/../../Modules/'.$value.'/Settings.php')) {
                        $module = include(__DIR__.'/../../Modules/'.$value.'/Settings.php');
                        if (array_key_exists('setupRoutes', $module)) {
                            call_user_func($module['setupRoutes'], $this->app, $this->middleware);
                        }
                        if (array_key_exists('setupDependencies', $module)) {
                            call_user_func($module['setupDependencies'], $this->app, $module);
                        }

                        $settings = $container->get('settings');
                        $modules = $settings['modules'];
                        $modules[] = $module['module'];
                        $settings->replace([
                            'modules' => $modules
                        ]);
                    }
                }
            }
        }

        if ($this->setupToken && $this->useOAuth2) {
            $this->token = $this->createToken(self::$login);

            if ($this->setupUnpriv) {
                foreach (self::$unpriv_logins as $login) {
                    $this->createTestingAccount($login);
                    $this->unpriv_tokens[] = $this->createToken($login);
                }
            }
        }
        setupInstall($this->container);

    }


    protected function tearDown(): void
    {
        foreach ($this->testing_accounts as $account) {
            Delete::new($this->container->db)
                ->from('Authentication')
                ->whereEquals(['AccountID' => $account])
                ->perform();

            Delete::new($this->container->db)
                ->from('Members')
                ->whereEquals(['AccountID' => $account])
                ->perform();
        }

        $this->middleware = null;
        $this->container = null;
        $this->app = null;

        parent::tearDown();

    }


    protected function createTestingAccount($email): string
    {
        $insert = Insert::new($this->container->db)
            ->into('Members')
            ->columns([
            'FirstName' => 'PHPTester',
            'LastName' => 'TestingMcTesterTest',
            'Email' => $email,
            'Gender' => 'Amoeba'
            ]);
        $insert->perform();
        $id = $insert->getLastInsertId();

        $auth = \password_hash(static::$password, PASSWORD_DEFAULT);

        Insert::new($this->container->db)
            ->into('Authentication')
            ->columns([
            'AccountID' => $id,
            'Authentication' => $auth,
            'LastLogin' => null,
            'Expires' => date('Y-m-d', strtotime('+1 year')),
            'FailedAttempts' => 0,
            'OneTime' => null,
            'OneTimeExpires' => null
            ])
            ->perform();

        array_push($this->testing_accounts, $id);

        return $id;

    }


    protected function createToken($email)
    {
        $response = $this->runRequest('POST', '/token', null, ['grant_type' => 'password', 'username' => $email, 'password' => self::$password, 'client_id' => self::$client]);
        $data = json_decode((string)$response->getBody());
        $this->assertNotEmpty($data);
        return $data;

    }


    protected function createRequest(
        string $method,
        $uri,
        string $serverParams = null,
        object $token = null
    ) {
        $env = Environment::mock([
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $uri,
            'QUERY_STRING'   => $serverParams
            ]);
        $request = Request::createFromEnvironment($env);
        $request = $request->withHeader('Content-Type', 'multipart/form-data');
        if ($token) {
            $request = $request->withHeader('Authorization', 'Bearer '.$token->access_token);
        } elseif ($this->token) {
            $request = $request->withHeader('Authorization', 'Bearer '.$this->token->access_token);
        }
        return $request;

    }


    public function runRequest(
        string $method,
        string $uri,
        array $serverParams = null,
        array $body = null,
        int $code = null,
        object $token = null,
        string $yamlUri = null
    ) {
        if (!empty($serverParams)) {
            $params = [];
            foreach ($serverParams as $key => $value) {
                $params[] = "$key=$value";
            }
            $serverParams = implode('&', $params);
        }
        $request = $this->createRequest($method, $uri, $serverParams, $token);
        if ($yamlUri) {
            self::$validator->validate($request, $yamlUri, $method);
        }
        if (!empty($body)) {
            $request = $request->withParsedBody($body);
        }
        $this->container['request'] = $request;
        $response = $this->app->run(true);
        if ($code !== null) {
            try {
                $this->assertSame($response->getStatusCode(), $code);
            } catch (\Exception $e) {
                error_log((string)$response->getBody());
                throw($e);
            }
        }

        if ($yamlUri) {
            try {
                self::$validator->validate($response, $yamlUri, $method);
            } catch (\Exception $e) {
                error_log((string)$response->getBody());
                throw($e);
            }
        }

        return $response;

    }


    public function NPRunRequest(
        string $method,
        string $uri,
        array $serverParams = null,
        array $body = null,
        int $code = null,
        int $loginIndex = 0,
        string $yamlUri = null
    ) {
        return $this->runRequest($method, $uri, $serverParams, $body, $code, $this->unpriv_tokens[$loginIndex], $yamlUri);

    }


    protected function assertIncludes($data, $id)
    {
        $tid = $data->{$id};
        $this->assertNotEmpty($tid);
        $this->assertIsObject($tid);
        $this->assertObjectHasAttribute('id', $tid);

    }


    /* End */
}
