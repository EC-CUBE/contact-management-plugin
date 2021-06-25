<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Plugin\ContactManagement\Entity\Contact;
use Plugin\ContactManagement\Repository\ContactCommentRepository;
use Doctrine\ORM\EntityManagerInterface;

class MailService
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var ContactCommentRepository
     */
    protected $contactCommentRepository;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * MailService constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param ContactCommentRepository $contactCommentRepository
     * @param \Twig_Environment $twig
     * @param EntityManagerInterface $entityManager
     * @param BaseInfoRepository $baseInfoRepository
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        \Swift_Mailer $mailer,
        ContactCommentRepository $contactCommentRepository,
        \Twig_Environment $twig,
        EntityManagerInterface $entityManager,
        BaseInfoRepository $baseInfoRepository,
        EccubeConfig $eccubeConfig
    ) {
        $this->mailer = $mailer;
        $this->contactCommentRepository = $contactCommentRepository;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * Send contact Mail from store
     *
     * @param Contact $Contact
     * @return \Swift_Message
     */
    public function sendContactMailFromStore(Contact $Contact)
    {
        log_info('問い合わせメール 送信開始');

        $LatestShopComment = $this->contactCommentRepository->getLatestCommentsFromStore($Contact);

        $Comment = $LatestShopComment->getComment();

        $message = (new \Swift_Message())
            ->setSubject($LatestShopComment->getSubject().'[お問い合わせID:'.$Contact->getId().']')
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Contact->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($Comment);

        $ContactCommentImages = $LatestShopComment->getContactCommentImages();
        if ($ContactCommentImages) {
            foreach ($ContactCommentImages as $ContactCommentImage) {
                $message->attach(\Swift_Attachment::fromPath($this->eccubeConfig['eccube_image_contact_comment_dir'].'/'.$ContactCommentImage->getFileName()));
            }
        }

        $count = $this->mailer->send($message);

        log_info('問い合わせメール 送信完了', ['count' => $count]);

        return $message;
    }
}
