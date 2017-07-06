<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 6/30/2017
 * Time: 8:13 PM
 */

namespace CDCMastery\Controllers;


use CDCMastery\Models\Messages\Messages;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class RootController
{
    /**
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * @var Logger $log
     */
    protected $log;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var Request
     */
    protected $request;

    /**
     * RootController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->log = $this->container->get(Logger::class);
        $this->twig = $this->container->get(\Twig_Environment::class);
        $this->request = Request::createFromGlobals();
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        return $this->twig->render(
            $template,
            array_merge(
                $data,
                [
                    'messages' => Messages::get()
                ]
            )
        );
    }
}