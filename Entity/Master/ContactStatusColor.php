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
 * MasterContactStatusColor
 *
 * @ORM\Table(name="mtb_contact_status_color")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement\Repository\Master\ContactStatusColorRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ContactStatusColor extends \Eccube\Entity\Master\AbstractMasterEntity
{
}