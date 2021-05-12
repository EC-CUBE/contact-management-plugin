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
 * ContactCommentImageRepository
 *
 * @ORM\Table(name="plg_contact_comment_image")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement\Repository\ContactCommentImageRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ContactCommentImage extends \Eccube\Entity\AbstractEntity
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
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", length=255)
     */
    private $file_name;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_no", type="smallint", options={"unsigned":true})
     */
    private $sort_no;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \Plugin\ContactManagement\Entity\ContactComment
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement\Entity\ContactComment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_comment_id", referencedColumnName="id")
     * })
     */
    private $ContactComment;

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
     * Set fileName.
     *
     * @param string $fileName
     *
     * @return ContactCommentImage
     */
    public function setFileName($fileName)
    {
        $this->file_name = $fileName;

        return $this;
    }

    /**
     * Get fileName.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set sortNo.
     *
     * @param int $sortNo
     *
     * @return ContactCommentImage
     */
    public function setSortNo($sortNo)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    /**
     * Get sortNo.
     *
     * @return int
     */
    public function getSortNo()
    {
        return $this->sort_no;
    }

    /**
     * @param \DateTime $create_date
     *
     * @return ContactCommentImage
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * Set contactComment.
     *
     * @param \Plugin\ContactManagement\Entity\ContactComment|null $contactComment
     *
     * @return ContactCommentImage
     */
    public function setContactComment(\Plugin\ContactManagement\Entity\ContactComment $contactComment = null)
    {
        $this->ContactComment = $contactComment;

        return $this;
    }

    /**
     * Get contactComment.
     *
     * @return \Plugin\ContactManagement\Entity\ContactComment|null
     */
    public function getContactComment()
    {
        return $this->ContactComment;
    }
}
