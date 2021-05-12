<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Entity\Master;

use Doctrine\ORM\Mapping as ORM;

/**
 * MasterContactStatus
 *
 * @ORM\Table(name="mtb_contact_status")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement\Repository\Master\ContactStatusRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ContactStatus extends \Eccube\Entity\Master\AbstractMasterEntity
{
    /**
     * 新規受付
     *
     * @var integer
     */
    const NEW_RECEPTION = 1;

    /**
     * 返信受付
     *
     * @var integer
     */
    const REPLY_RECEPTION = 2;

    /**
     * 未解決
     *
     * @var integer
     */
    const UNSOLVED = 3;

    /**
     * 対応中
     *
     * @var integer
     */
    const DURING_CORRESPONDENCE = 4;

    /**
     * 保留
     *
     * @var integer
     */
    const ON_HOLD = 5;

    /**
     * 解決済み
     *
     * @var integer
     */
    const RESOLVED = 1001;

    /**
     * 対応しない
     *
     * @var integer
     */
    const DO_NOT_CORRESPOND = 1002;
}