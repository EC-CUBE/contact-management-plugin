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
use Eccube\Repository\OrderRepository;
use Plugin\ContactManagement\Repository\ContactRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Filesystem\Filesystem;

class ContactService
{
    const MAX_NUMBER_OF_HISTORY = 100;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * ContactService constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        ContactRepository $contactRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->orderRepository = $orderRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * 受注、買取、問い合わせ履歴を会員IDを元に取得
     * @param $Customer
     * @return array
     */
    public function searchTransactionHistoryByCustomerId($Customer)
    {
        $ContactHistories = $this->contactRepository->createQueryBuilder('c')
            ->andWhere('c.Customer = :customer_id')
            ->orWhere('c.email = :email')
            ->setParameter('customer_id', $Customer)
            ->setParameter('email', $Customer->getEmail())
            ->getQuery()->getResult();

        $OrderHistories = $this->orderRepository->createQueryBuilder('o')
            ->andWhere('o.Customer = :customer_id')
            ->orWhere('o.email = :email')
            ->setParameter('customer_id', $Customer)
            ->setParameter('email', $Customer->getEmail())
            ->getQuery()
            ->getResult();

        $tradingHistories = array_merge($ContactHistories, $OrderHistories);

        foreach ($tradingHistories as $tradingHistory) {
            $update_date[] = $tradingHistory['update_date'];
        }

        if ($tradingHistories) {
            array_multisort($update_date, SORT_DESC, $tradingHistories);
            array_splice($tradingHistories, self::MAX_NUMBER_OF_HISTORY);
        }

        return $tradingHistories;
    }

    /**
     * 受注、問い合わせ履歴をEmailを元に取得
     *
     * @param $email
     *
     * @return array
     */
    public function searchTransactionHistoryByEmail($email)
    {
        $ContactHistories = $this->contactRepository->createQueryBuilder('c')
            ->andWhere('c.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();

        $OrderHistories = $this->orderRepository->createQueryBuilder('o')
            ->andWhere('o.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getResult();

        $tradingHistories = array_merge($ContactHistories, $OrderHistories);

        foreach ($tradingHistories as $tradingHistory) {
            $update_date[] = $tradingHistory['update_date'];
        }

        if ($tradingHistories) {
            array_multisort($update_date, SORT_DESC, $tradingHistories);
            array_splice($tradingHistories, self::MAX_NUMBER_OF_HISTORY);
        }

        return $tradingHistories;
    }

    /**
     * Delete temp image.
     *
     * @param $form FormInterface
     */
    public function deleteTempImage(FormInterface $form)
    {
        $deleteImages = $form->get('delete_images')->getData();
        foreach ($deleteImages as $deleteImage) {
            $fs = new Filesystem();
            $fs->remove($this->eccubeConfig['eccube_temp_image_contact_comment_dir'].'/'.$deleteImage);
        }
    }
}
