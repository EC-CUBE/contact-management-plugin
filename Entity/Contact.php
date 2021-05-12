<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contact
 *
 * @ORM\Table(name="plg_contact")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement\Repository\ConfigRepository")
 */
class Contact extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="Contacts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @var \Eccube\Entity\Master\Pref
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Pref")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pref_id", referencedColumnName="id")
     * })
     */
    private $Pref;

    /**
     * @var string
     *
     * @ORM\Column(name="name01", type="string", length=255)
     */
    private $name01;

    /**
     * @var string
     *
     * @ORM\Column(name="name02", type="string", length=255)
     */
    private $name02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kana01", type="string", length=255, nullable=true)
     */
    private $kana01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kana02", type="string", length=255, nullable=true)
     */
    private $kana02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postal_code", type="string", length=8, nullable=true)
     */
    private $postal_code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr01", type="string", length=255, nullable=true)
     */
    private $addr01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr02", type="string", length=255, nullable=true)
     */
    private $addr02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone_number", type="string", length=14, nullable=true)
     */
    private $phone_number;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var \Plugin\ContactManagement\Entity\Master\ContactPurpose
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement\Entity\Master\ContactPurpose")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_purpose_id", referencedColumnName="id")
     * })
     */
    private $ContactPurpose;

    /**
     * @var \Plugin\ContactManagement\Entity\Master\ContactStatusColor
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement\Entity\Master\ContactStatusColor")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_status_id", referencedColumnName="id")
     * })
     */
    private $ContactStatusColor;

    /**
     * @var \Plugin\ContactManagement\Entity\Master\ContactStatus
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement\Entity\Master\ContactStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_status_id", referencedColumnName="id")
     * })
     */
    private $ContactStatus;

    /**
     * @var boolean
     *
     * @ORM\Column(name="replied", type="boolean", nullable=true ,options={"default":0})
     */
    private $replied;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member", inversedBy="Contacts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="charge_member_id", referencedColumnName="id")
     * })
     */
    private $ChargeMember;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member", inversedBy="Contacts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="update_member_id", referencedColumnName="id")
     * })
     */
    private $UpdateMember;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note_title", type="string", length=255, nullable=true)
     */
    private $note_title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="string", length=4000, nullable=true)
     */
    private $note;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member", inversedBy="Contacts")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="browse_member_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $BrowseMember;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="browse_date", type="datetimetz", nullable=true)
     */
    private $browse_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @var \Doctrine\Common\Collections\Collection|ContactComment[]
     *
     * @ORM\OneToMany(targetEntity="Plugin\ContactManagement\Entity\ContactComment", mappedBy="Contact", cascade={"persist","remove"})
     * @ORM\OrderBy({
     * "update_date"="DESC"
     * })
     */
    private $ContactComments;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set customer.
     *
     * @param \Eccube\Entity\Customer|null $customer
     *
     * @return Contact
     */
    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return \Eccube\Entity\Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set pref.
     *
     * @param \Eccube\Entity\Master\Pref|null $pref
     *
     * @return Contact
     */
    public function setPref(\Eccube\Entity\Master\Pref $pref = null)
    {
        $this->Pref = $pref;

        return $this;
    }

    /**
     * Get pref.
     *
     * @return \Eccube\Entity\Master\Pref|null
     */
    public function getPref()
    {
        return $this->Pref;
    }

    /**
     * @param string $name01
     *
     * @return Contact
     */
    public function setName01($name01)
    {
        $this->name01 = $name01;

        return $this;
    }

    /**
     * @return string
     */
    public function getName01()
    {
        return $this->name01;
    }

    /**
     * @param string $name02
     *
     * @return Contact
     */
    public function setName02($name02)
    {
        $this->name02 = $name02;

        return $this;
    }

    /**
     * @return string
     */
    public function getName02()
    {
        return $this->name02;
    }

    /**
     * @param string|null $kana01
     *
     * @return Contact
     */
    public function setKana01($kana01 = null)
    {
        $this->kana01 = $kana01;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKana01()
    {
        return $this->kana01;
    }

    /**
     * @param string|null $kana02
     *
     * @return Contact
     */
    public function setKana02($kana02 = null)
    {
        $this->kana02 = $kana02;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKana02()
    {
        return $this->kana02;
    }

    /**
     * Set postal_code.
     *
     * @param string|null $postal_code
     *
     * @return Contact
     */
    public function setPostalCode($postal_code = null)
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    /**
     * Get postal_code.
     *
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * Set addr01.
     *
     * @param string|null $addr01
     *
     * @return Contact
     */
    public function setAddr01($addr01 = null)
    {
        $this->addr01 = $addr01;

        return $this;
    }

    /**
     * Get addr01.
     *
     * @return string|null
     */
    public function getAddr01()
    {
        return $this->addr01;
    }

    /**
     * Set addr02.
     *
     * @param string|null $addr02
     *
     * @return Contact
     */
    public function setAddr02($addr02 = null)
    {
        $this->addr02 = $addr02;

        return $this;
    }

    /**
     * Get addr02.
     *
     * @return string|null
     */
    public function getAddr02()
    {
        return $this->addr02;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $phone_number
     *
     * @return Contact
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * Set url.
     *
     * @param string|null $url
     *
     * @return Contact
     */
    public function setUrl($url = null)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set contactPurpose.
     *
     * @param \Plugin\ContactManagement\Entity\Master\ContactPurpose|null $contactPurpose
     *
     * @return Contact
     */
    public function setContactPurpose(\Plugin\ContactManagement\Entity\Master\ContactPurpose $contactPurpose = null)
    {
        $this->ContactPurpose = $contactPurpose;

        return $this;
    }

    /**
     * Get contactPurpose.
     *
     * @return \Plugin\ContactManagement\Entity\Master\ContactPurpose|null
     */
    public function getContactPurpose()
    {
        return $this->ContactPurpose;
    }

    /**
     * Set contactStatusColor.
     *
     * @param \Plugin\ContactManagement\Entity\Master\ContactStatusColor|null $contactStatusColor
     *
     * @return Contact
     */
    public function setContactStatusColor(\Plugin\ContactManagement\Entity\Master\ContactStatusColor $contactStatusColor = null)
    {
        $this->ContactStatusColor = $contactStatusColor;

        return $this;
    }

    /**
     * Get contactStatusColor.
     *
     * @return \Plugin\ContactManagement\Entity\Master\ContactStatusColor|null
     */
    public function getContactStatusColor()
    {
        return $this->ContactStatusColor;
    }

    /**
     * Set contactStatus.
     *
     * @param \Plugin\ContactManagement\Entity\Master\ContactStatus|null $contactStatus
     *
     * @return Contact
     */
    public function setContactStatus(\Plugin\ContactManagement\Entity\Master\ContactStatus $contactStatus = null)
    {
        $this->ContactStatus = $contactStatus;

        return $this;
    }

    /**
     * Get contactStatus.
     *
     * @return \Plugin\ContactManagement\Entity\Master\ContactStatus|null
     */
    public function getContactStatus()
    {
        return $this->ContactStatus;
    }

    /**
     * Set replied
     *
     * @param boolean $replied
     *
     * @return Contact
     */
    public function setReplied($replied)
    {
        $this->replied = $replied;

        return $this;
    }

    /**
     * Have you replied?
     *
     * @return boolean
     */
    public function isReplied()
    {
        return $this->replied;
    }

    /**
     * Set chargeMember.
     *
     * @param \Eccube\Entity\Member|null $chargeMember
     *
     * @return Contact
     */
    public function setChargeMember(\Eccube\Entity\Member $chargeMember = null)
    {
        $this->ChargeMember = $chargeMember;

        return $this;
    }

    /**
     * Get chargeMember.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getChargeMember()
    {
        return $this->ChargeMember;
    }

    /**
     * Set setUpdateMember.
     *
     * @param \Eccube\Entity\Member|null $updateMember
     *
     * @return Contact
     */
    public function setUpdateMember(\Eccube\Entity\Member $updateMember = null)
    {
        $this->UpdateMember = $updateMember;

        return $this;
    }

    /**
     * Get updateMember.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getUpdateMember()
    {
        return $this->UpdateMember;
    }

    /**
     * Set note.
     *
     * @param string|null $note
     *
     * @return Contact
     */
    public function setNote($note = null)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note.
     *
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set noteTitle.
     *
     * @param string|null $note_title
     *
     * @return Contact
     */
    public function setNoteTitle($note_title = null)
    {
        $this->note_title = $note_title;

        return $this;
    }

    /**
     * Get noteTitle.
     *
     * @return string|null
     */
    public function getNoteTitle()
    {
        return $this->note_title;
    }

    /**
     * Set browseMember.
     *
     * @param \Eccube\Entity\Member|null $browseMember
     *
     * @return Contact
     */
    public function setBrowseMember(\Eccube\Entity\Member $browseMember = null)
    {
        $this->BrowseMember = $browseMember;

        return $this;
    }

    /**
     * Get browseMember.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getBrowseMember()
    {
        return $this->BrowseMember;
    }

    /**
     * Set browseDate.
     *
     * @param \DateTime|null $browseDate
     *
     * @return Customer
     */
    public function setBrowseDate($browseDate = null)
    {
        $this->browse_date = $browseDate;

        return $this;
    }

    /**
     * Get browseDate.
     *
     * @return \DateTime|null
     */
    public function getBrowseDate()
    {
        return $this->browse_date;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return Contact
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return Contact
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * Get contactComments.
     *
     * @return \Doctrine\Common\Collections\Collection|ContactComment[]
     */
    public function getContactComments()
    {
        return $this->ContactComments;
    }

    /**
     * Add productClass.
     *
     * @param \Plugin\ContactManagement\Entity\ContactComment $contactComment
     *
     * @return Contact
     */
    public function addContactComment(\Plugin\ContactManagement\Entity\ContactComment $contactComment)
    {
        $this->ContactComments[] = $contactComment;

        return $this;
    }

    /**
     * Get className.
     *
     * @return \String
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Get replyUrl.
     *
     * @param $baseUrl
     * @return \String
     */
    public function getReplyUrl($baseUrl)
    {
        return $baseUrl.'contact/'.$this->getUrl().'/reply';
    }

//    既存のお問い合わせメールテンプレートによるエラー回避のため作成
    public function getContents()
    {
        return '';
    }
}
