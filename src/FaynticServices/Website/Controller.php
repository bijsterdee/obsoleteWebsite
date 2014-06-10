<?php

namespace FaynticServices\Website;

use FaynticServices\Website\Model\Account;
use FaynticServices\Website\Model\AccountQuery;
use FaynticServices\Website\Model\Language;
use FaynticServices\Website\Model\LanguageQuery;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Controller extends ContainerAware implements ContainerAwareInterface
{
    const STATUS_SUCCESS = 'success';
    const STATUS_INFO = 'info';
    const STATUS_WARNING = 'warning';
    const STATUS_DANGER = 'danger';

    /** @var Account|null */
    protected $account;

    /** @var string */
    protected $locale;

    /** @var array */
    private $requestParameters = array();

    /** @var bool */
    private $maintenance = false;

    /** @var array */
    private $template = array(
        'data' => array(),
        'layout' => 'Default.twig',
        'template' => null
    );

    /**
     * @param string $method
     * @param array $parameters
     */
    public function __construct($method = 'Default', array $parameters = array())
    {
        if (!headers_sent() && 'xmlhttprequest' === strtolower(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH'))) {
            $requestMethod = 'Ajax';
        } else {
            $requestMethod = ucfirst(strtolower(filter_input(INPUT_SERVER, 'REQUEST_METHOD')));
        }

        $this->requestParameters['requestMethod'] = lcfirst($requestMethod);
        foreach (["handle{$requestMethod}{$method}", "handle{$requestMethod}Default", "handle{$method}", "handleDefault"] as $method) {
            $this->requestParameters['classMethod'] = $method;
            if (method_exists($this, $method)) {
                break;
            }
        }
        $this->requestParameters['classParameters'] = $parameters;
    }

    public function dispatch()
    {
        try {
            $this->dispatchStartup();
            $this->preDispatch();
            call_user_func_array(array($this, $this->requestParameters['classMethod']), $this->requestParameters['classParameters']);
            $this->postDispatch();
        } catch (\UnexpectedValueException $exception) {
            $this->setStatus($exception->getMessage(), self::STATUS_WARNING);
        } catch (\Exception $exception) {
            $trackingId = uniqid();
            syslog(LOG_SYSLOG, "[{$trackingId}] {$exception}");
            $this->setStatus("[{$trackingId}] Er is een onherstelbare fout opgetreden. Neem contact op met onze servicedesk", self::STATUS_DANGER);
        } finally {
            $this->dispatchShutdown();
        }
    }

    public function redirect($url, $statusCode = 302, $delay = null)
    {
        if (!$delay) {
            header("Location: {$url}", true, $statusCode);
            exit;
        }
        header("Refresh:{$delay};url={$url}", true, $statusCode);
    }

    public function setLayout($layout)
    {
        $this->template['layout'] = $layout;
    }

    /**
     * @return bool
     */
    public function getMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * @param bool $status
     */
    public function setMaintenance($status)
    {
        $this->maintenance = true === $status;
    }

    public function setStatus($message, $type = self::STATUS_INFO, $dismissable = true)
    {
        $object = new \stdClass();
        $object->message = $message;
        $object->type = $type;
        $object->dismissable = $dismissable;

        $_SESSION['status'] = $object;
    }

    public function getStatus($clear = true)
    {
        if (isset($_SESSION['status'])) {
            $status = $_SESSION['status'];

            if ($clear) {
                unset($_SESSION['status']);
            }
            return $status;
        }
        return null;
    }

    public function setTemplate($template, array $data = array(), $layout = null)
    {
        $this->template['template'] = $template;
        $this->template['data'] = array_merge($this->template['data'], $data);
        if ($layout) {
            $this->template['layout'] = $layout;
        }
    }

    public function addTemplateData(array $data)
    {
        $this->template['data'] = array_merge($this->template['data'], $data);
    }

    protected function dispatchStartup()
    {
        if ($this->maintenance) {
            $this->redirect('/maintenance/');
        }

        $configuration = $this->container->get('configuration');
        if ('development' === $configuration->application->environment) {
            error_reporting(-1);
            ini_set('display_errors', true);
        }

        if (isset($_SESSION['account'])) {
            if ($this->account = AccountQuery::create()->findOneById($_SESSION['account'])) {
                $this->locale = $this->account->getLanguage()->getIdentifier();
            } else {
                unset($_SESSION['account']);
            }
        }
        if (!isset($this->locale)) {
            $acceptLanguage = locale_accept_from_http(filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
            /** @var Language[] $languages */
            $languages = LanguageQuery::create()->find();
            foreach ($languages as $language) {
                if (false !== strpos($language->getIdentifier(), $acceptLanguage)) {
                    $this->locale = $language->getIdentifier();
                    break;
                } elseif (!isset($this->locale) && $language->getIsDefault()) {
                    $this->locale = $language->getIdentifier();
                }
            }
        }
    }

    protected function preDispatch()
    {
    }

    protected function handleDefault()
    {
    }

    protected function postDispatch()
    {
    }

    protected function dispatchShutdown()
    {
        if ('ajax' === $this->requestParameters['requestMethod']) {
            if ($status = $this->getStatus()) {
                echo $status->message;
            }
            return;
        }

        $configuration = $this->container->get('configuration');

        $loader = new \Twig_Loader_Filesystem();
        $loader->addPath(__DIR__ . DIRECTORY_SEPARATOR . 'Template');

        $twig = new \Twig_Environment();
        $twig->setLoader($loader);
        if ('development' !== $configuration->application->environment) {
            $twig->setCache("{$configuration->directories->cache}/twig");
        }

        $controllerPrefix = 'Controller';
        if (isset($this->template['template'])) {
            $this->template['data'] = array_merge(
                array('template' => $twig->loadTemplate("{$controllerPrefix}/{$this->template['template']}")),
                $this->template['data']
            );
        }

        $this->template['data'] = array_merge(
            array(
                'account' => $this->account,
                'configuration' => $this->container->get('configuration')->toArray(),
                'request' => array_change_key_case(array_merge($_GET, $_POST), CASE_LOWER),
                'server' => array_change_key_case($_SERVER, CASE_LOWER),
                'locale' => $this->locale,
                'status' => $this->getStatus()
            ),
            $this->template['data']
        );

        echo $twig->render("Layout/{$this->template['layout']}", $this->template['data']);
    }

    protected function getTwigTemplate()
    {
        $configuration = $this->container->get('configuration');

        $twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__ . DIRECTORY_SEPARATOR . 'Template'));
        if ('development' !== $configuration->application->environment) {
            $twig->setCache("{$configuration->directories->cache}/twig");
        }
        return $twig;
    }
}
