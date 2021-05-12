<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\EventListener;

use Eccube\Request\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig_Environment;

class OrderMailConfirmListener implements EventSubscriberInterface
{
    /**
     * @var Context
     */
    private $requestContext;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    public function __construct(Context $requestContext,
                                Twig_Environment $twig,
                                FormFactoryInterface $formFactory)
    {
        $this->requestContext = $requestContext;
        $this->twig = $twig;
        $this->formFactory = $formFactory;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        // Ignore symfony profiler
        if ($request->attributes->get('_route') == '_wdt') {
            return;
        }
        // Ignore ajax request
        if ($request->isXmlHttpRequest()) {
            return;
        }
        // Ignore front request
        if (!$this->requestContext->isAdmin()) {
            return;
        }

        $route = $request->attributes->get('_route');
        if ($request->get('mode') == 'confirm' && $route == 'admin_order_mail') {
            $response = $event->getResponse();
            $content = $response->getContent();
            $parts = $this->twig->render('@ContactManagement/admin/Order/mail_confirm.twig');
            $search = '</body>';
            $replace = $parts.$search;
            $content = str_replace($search, $replace, $content);
            $response->setContent($content);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse'],
        ];
    }
}
