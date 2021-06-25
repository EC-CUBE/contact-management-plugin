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
 * ProductImage
 *
 * @ORM\Table(name="plg_contact_comment")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement\Repository\ContactCommentRepository")
 */
class ContactComment extends \Eccube\Entity\AbstractEntity
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ContactCommentImages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement\Entity\Contact", inversedBy="ContactComments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $Contact;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="comment", length=10000, type="string", nullable=true)
     */
    private $comment;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member", inversedBy="ContactComments")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="member_id", referencedColumnName="id")
     * })
     */
    private $Member;

    /**
     * @var boolean
     *
     * @ORM\Column(name="send", type="boolean", options={"default":0})
     */
    private $send;

    /**
     * @var boolean
     *
     * @ORM\Column(name="memo", type="boolean", options={"default":0})
     */
    private $memo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz", nullable=true)
     */
    private $update_date;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ContactManagement\Entity\ContactCommentImage", mappedBy="ContactComment", cascade={"remove"})
     * @ORM\OrderBy({
     *     "sort_no"="ASC"
     * })
     */
    private $ContactCommentImages;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Contact.
     *
     * @param Contact|null $contact
     *
     * @return ContactComment
     */
    public function setContact(Contact $contact = null)
    {
        $this->Contact = $contact;

        return $this;
    }

    /**
     * Get Contact.
     *
     * @return Contact|null
     */
    public function getContact()
    {
        return $this->Contact;
    }

    /**
     * Set subject.
     *
     * @param string|null $subject
     *
     * @return ContactComment
     */
    public function setSubject($subject = null)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set comment.
     *
     * @param string|null $comment
     *
     * @return ContactComment
     */
    public function setComment($comment = null)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set Member.
     *
     * @param \Eccube\Entity\Member|null $Member
     *
     * @return ContactComment
     */
    public function setMember(\Eccube\Entity\Member $member = null)
    {
        $this->Member = $member;

        return $this;
    }

    /**
     * Get Member.
     *
     * @return \Eccube\Entity\Member|null
     */
    public function getMember()
    {
        return $this->Member;
    }

    /**
     * Set send
     *
     * @param boolean $send
     *
     * @return ContactComment
     */
    public function setSend($send)
    {
        $this->send = $send;

        return $this;
    }

    /**
     * Did you send?
     *
     * @return boolean
     */
    public function isSend()
    {
        return $this->send;
    }

    /**
     * Set send
     *
     * @param boolean $memo
     *
     * @return ContactComment
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * Did you send?
     *
     * @return boolean
     */
    public function isMemo()
    {
        return $this->memo;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return ContactComment
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
     * @return ContactComment
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
     * Add contactComments.
     *
     * @param \Plugin\ContactManagement\Entity\ContactCommentImage $contactCommentImage
     *
     * @return Buy
     */
    public function addContactCommentImage(\Plugin\ContactManagement\Entity\ContactCommentImage $contactCommentImage)
    {
        $this->ContactCommentImages[] = $contactCommentImage;

        return $this;
    }

    /**
     * Get contactComments.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContactCommentImages()
    {
        return $this->ContactCommentImages;
    }

    /**
     * Remove contactComment.
     *
     * @param \Plugin\ContactManagement\Entity\ContactCommentImage $contactCommentImage
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeContactCommentImage(\Plugin\ContactManagement\Entity\ContactCommentImage $contactCommentImage)
    {
        return $this->ContactCommentImages->removeElement($contactCommentImage);
    }
}
