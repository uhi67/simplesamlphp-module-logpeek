<?php

declare(strict_types=1);

namespace SimpleSAML\Module\logpeek\Controller;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Module\logpeek\File\ReverseRead;
use SimpleSAML\Module\logpeek\Syslog;
use SimpleSAML\Session;
use SimpleSAML\Utils\Auth;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

use function array_reverse;
use function count;
use function date;
use function intval;
use function preg_match;

/**
 * Controller class for the logpeek module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp-module-logpeek
 */
class Logpeek
{
    /** @var Configuration */
    protected Configuration $config;

    /** @var Configuration */
    protected Configuration $moduleConfig;

    /** @var Session */
    protected Session $session;

    /** @var Auth */
    protected Auth $authUtils;
    protected bool $authorized;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->authUtils = new Auth();
        $this->config = Configuration::getConfig(); //$config;
        $this->moduleConfig = Configuration::getConfig('module_logpeek.php');
        $this->session = Session::getSession()??Session::getSessionFromRequest();;

        // Initialize authorization data
        $requireAdmin = $this->moduleConfig->getOptionalValue('requireAdmin', true);
        $requireAuth = $this->moduleConfig->getOptionalValue('requireAuth', false);

        if ($requireAdmin) {
            $this->authUtils->requireAdmin();
        }

        $this->authorized = true;
        if ($requireAuth) {
            $as = new Simple($requireAuth);
            if (!$as->isAuthenticated()) {
                $as->requireAuth();
            }
            $attributes = $as->getAttributes();
            $requiredAttrs = $this->moduleConfig->getOptionalArray('requiredAttrs', []);

            foreach ($requiredAttrs as $name => $value) {
                $attrValues = $attributes[$name] ?? [];
                if (!is_array($attrValues)) {
                    $attrValues = [$attrValues];
                }
                $hasValue = false;
                foreach ($attrValues as $av) {
                    if (preg_match('/^~[^~]*~$/', $value)) {
                        $hasValue = preg_match($value, $av);
                    } else {
                        $hasValue = $value == $av;
                    }
                    if ($hasValue) {
                        break;
                    }
                }
                if (!$hasValue) {
                    $this->authorized = false;
                    $this->authUtils->requireAdmin();
                }
            }
        }
    }


    /**
     * Inject the \SimpleSAML\Utils\Auth dependency.
     *
     * @param Auth $authUtils
     */
    public function setAuthUtils(Auth $authUtils): void
    {
        $this->authUtils = $authUtils;
    }


    /**
     * Main index controller.
     *
     * @param Request $request The current request.
     *
     * @return Template
     * @throws Exception
     */
    public function main(Request $request): Template
    {
        $this->authUtils->requireAdmin();

        $logfile = $this->moduleConfig->getOptionalValue('logfile', '/var/log/simplesamlphp.log');
        $blockSize = $this->moduleConfig->getOptionalValue('blocksz', 8192);

        $myLog = new ReverseRead($logfile, $blockSize);

        $results = [];
        $error = '';
        if (!$this->authorized) {
            $error = 'Not authorized.';
        }
        $tag = $this->session->getTrackID();
        if ($this->authorized && $request->query->has('tag') === true) {
            /** @psalm-var string $tag */
            $tag = $request->query->get('tag');
            Assert::notNull($tag);

            if (!preg_match('/^[a-f0-9]{10}$/D', $tag)) {
                $error = 'Invalid search tag! Search tag must be exactly 10 characters long hexadecimal number.';
                $results = [];
            } else {
                $results = $this->logFilter($myLog, $tag, $this->moduleConfig->getOptionalValue('lines', 500));
            }
        }

        $fileModYear = intval(date("Y", $myLog->getFileMtime()));
        $firstLine = $myLog->getFirstLine();
        $firstTimeEpoch = Syslog\ParseLine::getUnixTime($firstLine ?: '', $fileModYear);
        $lastLine = $myLog->getLastLine();
        $lastTimeEpoch = Syslog\ParseLine::getUnixTime($lastLine ?: '', $fileModYear);
        $fileSize = $myLog->getFileSize();

        $t = new Template($this->config, 'logpeek:logpeek.twig');
        $t->data['error'] = $error;
        $t->data['authorized'] = $this->authorized;
        $t->data['results'] = $results;
        $t->data['trackid'] = $tag;
        $t->data['timestart'] = date(DATE_RFC822, $firstTimeEpoch ?: time());
        $t->data['endtime'] = date(DATE_RFC822, $lastTimeEpoch ?: time());
        $t->data['filesize'] = $fileSize;
        $t->data['backUrl'] = $this->moduleConfig->getOptionalValue('backUrl', '');
        $t->data['logfile'] = $logfile;

        return $t;
    }


    /**
     * @param ReverseRead $objFile
     * @param string $tag
     * @param int $cut
     * @return array
     * @throws Exception
     */
    private function logFilter(ReverseRead $objFile, string $tag, int $cut): array
    {
        if (!preg_match('/^(TR[a-f0-9]{8}|CL[a-f0-9]{8}|[a-f0-9]{10})$/D', $tag)) {
            throw new Exception('Invalid search tag');
        }

        $i = 0;
        $results = [];
        $line = $objFile->getPreviousLine();
        while ($line !== false && ($i++ < $cut)) {
            if (str_contains($line, '[' . $tag . ']')) {
                $results[] = $line;
            }
            $line = $objFile->getPreviousLine();
        }

        $results[] = 'Searched ' . $i . ' lines backward. ' . count($results) . ' lines found.';
        return array_reverse($results);
    }
}
