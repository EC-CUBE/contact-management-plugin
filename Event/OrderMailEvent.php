<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Event;

use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\ContactManagement\Entity\Contact;
use Plugin\ContactManagement\Entity\ContactComment;
use Plugin\ContactManagement\Repository\Master\ContactStatusRepository;
use Plugin\ContactManagement\Repository\ContactRepository;
use Plugin\ContactManagement\Entity\Master\ContactStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\ContactManagement\Service\ContactService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Twig_Environment;
use Eccube\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderMailEvent implements EventSubscriberInterface
{
    use ControllerTrait;
    /**
     * @var ContactService
     */
    protected $contactService;

    /**
     * @var ContactStatusRepository
     */
    protected $contactStatusRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * ContactEvent constructor.
     *
     * @param ContactService $contactService
     * @param ContactStatusRepository $contactStatusRepository
     * @param ContactRepository $contactRepository
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $entityManager
     * @param Twig_Environment $twig
     * @param ContainerInterface $container
     */
    public function __construct(
        ContactService $contactService,
        ContactStatusRepository $contactStatusRepository,
        ContactRepository $contactRepository,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        Twig_Environment $twig,
        ContainerInterface $container
    ) {
        $this->contactService = $contactService;
        $this->contactStatusRepository = $contactStatusRepository;
        $this->contactRepository = $contactRepository;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'mail.admin.order' => 'onMailAdminOrder',
            '@admin/Order/mail.twig' => 'onAdminOrderMailTwig',
        ];
    }

    /**
     * 管理画面 -> 受注管理　-> メール通知（確認）
     */
    public function onAdminOrderMailTwig(TemplateEvent $event)
    {
        $event->addSnippet('@ContactManagement/admin/Order/mail.twig');
    }

    /**
     * 管理画面 -> 受注管理　-> メール送信
     */
    public function onMailAdminOrder(EventArgs $event)
    {
        $message = $event->getArgument('message');
        $Order = $event->getArgument('Order');
        $formData = $event->getArgument('formData');

//        受注情報を元にContactオブジェクトを作成
        $Contact = new Contact();
        $ContactStatus = $this->contactStatusRepository->find(ContactStatus::DURING_CORRESPONDENCE);
        if ($Order->getCustomer()) {
            $Contact->setCustomer($Order->getCustomer());
        }
        $LoginedMember = $this->tokenStorage->getToken()->getUser();

        $Contact->setName01($Order->getName01());
        $Contact->setName02($Order->getName02());
        $Contact->setKana01($Order->getKana01());
        $Contact->setKana02($Order->getKana02());
        $Contact->setKana02($Order->getPostalCode());
        $Contact->setPref($Order->getPref());
        $Contact->setAddr01($Order->getAddr01());
        $Contact->setAddr02($Order->getAddr02());
        $Contact->setPhoneNumber($Order->getPhoneNumber());
        $Contact->setEmail($Order->getEmail());
        $Contact->setUrl($this->contactRepository->getUniqueUrl());
        $Contact->setReplied(true);
        $Contact->setContactStatus($ContactStatus);
        $Contact->setChargeMember($LoginedMember);
        $Contact->setUpdateMember($LoginedMember);

        $baseUrl = $this->generateUrl('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $replyUrl = $Contact->getReplyUrl($baseUrl);
        $twig = new \Twig_Environment(new \Twig_Loader_Array([]));
        $template = $twig->createTemplate($formData['tpl_data']);
        $body = $template->render(['replyUrl' => $replyUrl]);

        $ContactComment = new ContactComment();
        $ContactComment->setComment($body);
        $ContactComment->setContact($Contact);
        $ContactComment->setMember($LoginedMember);
        $ContactComment->setSend(true);
        $ContactComment->setMemo(false);

        $Contact->addContactComment($ContactComment);

        $this->entityManager->persist($Contact);
        $this->entityManager->flush();

        $message->setSubject($message->getSubject().'[お問い合わせID:'.$Contact->getId().']');
        $message->setBody($body);
    }
}
