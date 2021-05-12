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
* MasterContactPurpose
*
* @ORM\Table(name="mtb_contact_purpose")
* @ORM\InheritanceType("SINGLE_TABLE")
* @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
* @ORM\HasLifecycleCallbacks()
* @ORM\Entity(repositoryClass="Plugin\ContactManagement\Repository\Master\ContactPurposeRepository")
* @ORM\Cache(usage="NONSTRICT_READ_WRITE")
*/
class ContactPurpose extends \Eccube\Entity\Master\AbstractMasterEntity
{
/**
*
* @var integer
*/
const DEFAULT = 1;
}