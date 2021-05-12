<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Controller;

use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormInterface;
use Eccube\Controller\AbstractController;
use Plugin\ContactManagement\Repository\Master\ContactStatusRepository;
use Plugin\ContactManagement\Repository\ContactRepository;
use Plugin\ContactManagement\Entity\Master\ContactStatus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Plugin\ContactManagement\Form\Type\ContactCommentType;
use Plugin\ContactManagement\Entity\ContactCommentImage;
use Plugin\ContactManagement\Entity\ContactComment;
use Plugin\ContactManagement\Service\ContactService;

class ContactReplyController extends AbstractController
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var ContactStatusRepository
     */
    protected $contactStatusRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var ContactService
     */
    protected $contactService;

    /**
     * ContactReplyController constructor.
     *
     * @param MailService $mailService
     * @param ContactStatusRepository $contactStatusRepository
     * @param ContactRepository $contactRepository
     * @param ContactService $contactService
     */
    public function __construct(
        MailService $mailService,
        ContactStatusRepository $contactStatusRepository,
        ContactRepository $contactRepository,
        ContactService $contactService
    ) {
        $this->mailService = $mailService;
        $this->contactStatusRepository = $contactStatusRepository;
        $this->contactRepository = $contactRepository;
        $this->contactService = $contactService;
    }

    /**
     * お問い合わせ画面.
     *
     * @Route("/contact/{hash_url}/reply", name="contact_reply")
     * @Template("@ContactManagement/default/Contact/reply.twig")
     */
    public function reply(Request $request, $hash_url = null)
    {
        $ReplyContact = $this->contactRepository->findOneBy(['url' => $hash_url]);

        if (is_null($ReplyContact)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createBuilder(ContactCommentType::class);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    $form = $builder->getForm();
                    $form->handleRequest($request);

                    $this->contactService->deleteTempImage($form);

                    return $this->render('@ContactManagement/default/Contact/reply_confirm.twig', [
                        'form' => $form->createView(),
                        'Contact' => $ReplyContact,
                        'ContactComments' => $ReplyContact->getContactComments(),
                    ]);

                case 'complete':

                    $ContactComment = $form->getData();

                    $ContactStatus = $this->contactStatusRepository->find(ContactStatus::REPLY_RECEPTION);

                    $ContactComment->setContact($ReplyContact);
                    $ContactComment->setSend(true);
                    $ContactComment->setMemo(false);

                    $ReplyContact->setReplied(false);
                    $ReplyContact->setContactStatus($ContactStatus);

                    $ReplyContact->addContactComment($ContactComment);

                    $this->registerContactCommentImage($form, $ContactComment);

                    $this->entityManager->persist($ReplyContact);
                    $this->entityManager->flush();

                    // メール送信
                    $this->mailService->sendContactMail($ReplyContact);

                    return $this->redirect($this->generateUrl('contact_complete'));
            }
        }

        return [
            'form' => $form->createView(),
            'Contact' => $ReplyContact,
        ];
    }

    /**
     * @Route("/contact/image/add", name="front_contact_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        if ($request->files->get('contact')['ContactComment']) {
            $image = current($request->files->get('contact')['ContactComment']);
        } elseif ($request->files->get('contact_comment')) {
            $image = current($request->files->get('contact_comment'));
        } else {
            $image = null;
        }

        $allowExtensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];
        $file = null;
        if ($image) {
            //ファイルフォーマット検証
            $mimeType = $image->getMimeType();
            if (0 !== strpos($mimeType, 'image')) {
                throw new UnsupportedMediaTypeHttpException();
            }

            // 拡張子
            $extension = $image->getClientOriginalExtension();
            if (!in_array($extension, $allowExtensions)) {
                throw new UnsupportedMediaTypeHttpException();
            }

            $filename = date('mdHis').uniqid('_').'.'.$extension;

            $image->move($this->eccubeConfig['eccube_temp_image_contact_comment_dir'], $filename);
            $file = $filename;
        }

        return $this->json(['file' => $file], 200);
    }

    /**
     * Register ContactCommentImage.
     *
     * @param $form FormInterface
     * @param $ContactComment ContactComment
     */
    public function registerContactCommentImage($form, $ContactComment)
    {
        $ContactCommentImageNames = [];
        if (!empty($form->get('image_name_1')->getData())) {
            $ContactCommentImageNames[] = $form->get('image_name_1')->getData();
        }
        if (!empty($form->get('image_name_2')->getData())) {
            $ContactCommentImageNames[] = $form->get('image_name_2')->getData();
        }

        if (!empty($ContactCommentImageNames)) {
            foreach ($ContactCommentImageNames as $key => $ContactCommentImageName) {
                $ContactCommentImage = $this->eccubeConfig['eccube_temp_image_contact_comment_dir'].'/'.$ContactCommentImageName;
                if (file_exists($ContactCommentImage)) {
                    $file = new File($ContactCommentImage);
                    $file->move($this->eccubeConfig['eccube_image_contact_comment_dir']);
                }
                $ContactCommentImage = new ContactCommentImage();
                $ContactCommentImage
                    ->setContactComment($ContactComment)
                    ->setFileName($ContactCommentImageName)
                    ->setSortNo($key + 1);
                $ContactComment->addContactCommentImage($ContactCommentImage);
                $this->entityManager->persist($ContactCommentImage);
            }
        }
    }
}
