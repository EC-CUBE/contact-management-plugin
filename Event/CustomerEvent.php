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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\ContactManagement\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomerEvent implements EventSubscriberInterface
{
    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * CustomerEvent constructor.
     *
     * @param ContactRepository $contactRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ContactRepository $contactRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->contactRepository = $contactRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'admin.customer.edit.index.complete' => 'onAdminCustomerEditIndexComplete',
        ];
    }

    /**
     * 管理画面 -> 会員登録
     */
    public function onAdminCustomerEditIndexComplete(EventArgs $event)
    {
        $Customer = $event->getArgument('Customer');
        if ($Customer->getNote()) {
            $Contacts = $this->contactRepository->findBy(['Customer' => $Customer]);
            if ($Contacts) {
                foreach ($Contacts as $Contact) {
                    $Contact->setNote($Customer->getNote());
                }
            }
        }
        $this->entityManager->flush();
    }
}
