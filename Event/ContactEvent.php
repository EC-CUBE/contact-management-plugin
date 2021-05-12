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
use Plugin\ContactManagement\Repository\Master\ContactStatusRepository;
use Plugin\ContactManagement\Entity\Master\ContactStatus;
use Plugin\ContactManagement\Controller\ContactReplyController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\ContactManagement\Service\ContactService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Common\EccubeConfig;
use Plugin\ContactManagement\Repository\ContactCommentRepository;
use Twig_Environment;
use Eccube\Service\MailService;
use Plugin\ContactManagement\Repository\ContactRepository;
use Eccube\Entity\Customer;

class ContactEvent implements EventSubscriberInterface
{
    /**
     * @var ContactService
     */
    protected $contactService;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ContactReplyController
     */
    protected $contactReplyController;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ContactCommentRepository
     */
    protected $contactCommentRepository;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * ContactEvent constructor.
     *
     * @param ContactService $contactService
     * @param ContactStatusRepository $contactStatusRepository
     * @param TokenStorageInterface $tokenStorage
     * @param ContactReplyController $contactReplyController
     * @param EntityManagerInterface $entityManager
     * @param MailTemplateRepository $mailTemplateRepository
     * @param EccubeConfig $eccubeConfig
     * @param ContactCommentRepository $contactCommentRepository
     * @param Twig_Environment $twig
     * @param MailService $mailService
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        ContactService $contactService,
        ContactStatusRepository $contactStatusRepository,
        TokenStorageInterface $tokenStorage,
        ContactReplyController $contactReplyController,
        EntityManagerInterface $entityManager,
        MailTemplateRepository $mailTemplateRepository,
        EccubeConfig $eccubeConfig,
        ContactCommentRepository $contactCommentRepository,
        Twig_Environment $twig,
        MailService $mailService,
        ContactRepository $contactRepository
    ) {
        $this->contactService = $contactService;
        $this->contactStatusRepository = $contactStatusRepository;
        $this->tokenStorage = $tokenStorage;
        $this->contactReplyController = $contactReplyController;
        $this->entityManager = $entityManager;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->contactCommentRepository = $contactCommentRepository;
        $this->twig = $twig;
        $this->mailService = $mailService;
        $this->contactRepository = $contactRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'front.contact.index.initialize' => 'onFrontContactIndexInitialize',
            'Contact/index.twig' => 'onDefaultContactIndexTwig',
            'front.contact.index.complete' => 'onFrontContactIndexComplete',
            'mail.contact' => 'onMailContact',
        ];
    }

    /**
     * フロント画面 -> 問い合わせ（入力）
     */
    public function onFrontContactIndexInitialize(EventArgs $event)
    {
        $builder = $event->getArgument('builder');
        $builder->setData(null);
        $builder->get('ContactComment')->remove('comment');
    }

    /**
     * フロント画面 -> 問い合わせ（入力）
     */
    public function onDefaultContactIndexTwig(TemplateEvent $event)
    {
        $event->addSnippet('@ContactManagement/default/Contact/index.twig');
    }

    /**
     * フロント画面 -> 問い合わせ（確認）
     */
    public function onFrontContactIndexComplete(EventArgs $event)
    {
        $form = $event->getArgument('form');
        $Contact = $event->getArgument('data');
        $ContactStatus = $this->contactStatusRepository->find(ContactStatus::NEW_RECEPTION);
        $token = $this->tokenStorage->getToken();
        $Customer = $token ? $token->getUser() : null;
        if ($Customer instanceof Customer && $Customer->getId()) {
            $Contact->setCustomer($Customer);
        }
        $Contact->setUrl($this->contactRepository->getUniqueUrl());
        $Contact->setReplied(false);
        $Contact->setContactStatus($ContactStatus);

        $ContactComment = $form['ContactComment']->getData();
        $ContactComment->setContact($Contact);
        $ContactComment->setComment($form->get('contents')->getData());
        $ContactComment->setSend(true);
        $ContactComment->setMemo(false);

        $Contact->addContactComment($ContactComment);

        $this->contactReplyController->registerContactCommentImage($form['ContactComment'], $ContactComment);

        $this->entityManager->persist($Contact);
        $this->entityManager->flush();
    }

    /**
     * フロント画面 -> 問い合わせ（確認）
     */
    public function onMailContact(EventArgs $event)
    {
        $message = $event->getArgument('message');
        $Contact = $event->getArgument('formData');
        $BaseInfo = $event->getArgument('BaseInfo');

//        問い合わせ管理プラグイン用テンプレートへ差し替え
        $MailTemplate = $this->mailTemplateRepository->findOneBy(['name' => $this->eccubeConfig['eccube_contact_management_mail_template_name']]);
        $ContactComment = $this->contactCommentRepository->findOneBy(['Contact' => $Contact], ['create_date' => 'DESC']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Contact' => $Contact,
            'BaseInfo' => $BaseInfo,
            'ContactComment' => $ContactComment->getComment(),
        ]);

        $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());

        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Contact' => $Contact,
                'BaseInfo' => $BaseInfo,
                'ContactComment' => $ContactComment->getComment(),
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $message->setSubject($message->getSubject().'[お問い合わせID:'.$Contact->getId().']');

        $ContactCommentImages = $ContactComment->getContactCommentImages();
        if ($ContactCommentImages) {
            foreach ($ContactCommentImages as $ContactCommentImage) {
                $message->attach(\Swift_Attachment::fromPath($this->eccubeConfig['eccube_image_contact_comment_dir'].'/'.$ContactCommentImage->getFileName()));
            }
        }
    }
}
