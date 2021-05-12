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

use Eccube\Form\Type\Front\ContactType;
use Eccube\Request\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Plugin\ContactManagement\Controller\ContactReplyController;
use Plugin\ContactManagement\Service\ContactService;
use Twig_Environment;

class ContactConfirmListener implements EventSubscriberInterface
{
    /**
     * @var Context
     */
    protected $requestContext;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var ContactService
     */
    protected $contactService;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * ContactConfirmListener constructor.
     *
     * @param Context $requestContext
     * @param FormFactoryInterface $formFactory
     * @param ContactService $contactService
     * @param Twig_Environment $twig
     */
    public function __construct(Context $requestContext,
                                FormFactoryInterface $formFactory,
                                ContactService $contactService,
                                Twig_Environment $twig)
    {
        $this->requestContext = $requestContext;
        $this->formFactory = $formFactory;
        $this->contactService = $contactService;
        $this->twig = $twig;
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
        // Ignore admin request
        if ($this->requestContext->isAdmin()) {
            return;
        }

        $route = $request->attributes->get('_route');
        if ($request->get('mode') == 'confirm' && $route == 'contact') {
            $response = $event->getResponse();
            $content = $response->getContent();

            $builder = $this->formFactory->createBuilder(ContactType::class);
            $form = $builder->getForm();
            $form->handleRequest($request);

            $this->contactService->deleteTempImage($form['ContactComment']);

            $parts = $this->twig->render('@ContactManagement/default/Contact/confirm.twig', [
                'form' => $form->createView(),
            ]);
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
