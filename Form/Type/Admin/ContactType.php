<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Eccube\Common\EccubeConfig;
use Eccube\Form\DataTransformer;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Validator\Email;
use Eccube\Form\Type\KanaType;
use Eccube\Repository\CustomerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\ContactManagement\Entity\Contact;
use Plugin\ContactManagement\Repository\Master\ContactStatusRepository;
use Plugin\ContactManagement\Repository\ContactCommentRepository;
use Plugin\ContactManagement\Repository\ContactRepository;

class ContactType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ContactStatusRepository
     */
    protected $contactStatusRepository;

    /**
     * @var ContactCommentRepository
     */
    protected $contactCommentRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * ContactType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param EccubeConfig $eccubeConfig
     * @param ContactStatusRepository $contactStatusRepository
     * @param ContactCommentRepository $contactCommentRepository
     * @param CustomerRepository $customerRepository
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        ContactStatusRepository $contactStatusRepository,
        ContactCommentRepository $contactCommentRepository,
        CustomerRepository $customerRepository,
        ContactRepository $contactRepository
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->contactStatusRepository = $contactStatusRepository;
        $this->contactCommentRepository = $contactCommentRepository;
        $this->customerRepository = $customerRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $commentRequired = true;
        if ($options['saveDraft'] && $options['data']->getId()) {
            $commentRequired = false;
        }
        $builder
            ->add('name', NameType::class, [
            ])
            ->add('kana', KanaType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
                'attr' => [
                    'placeholder' => 'common.mail_address_sample',
                ],
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'required' => false,
            ])
            ->add('contact_purpose', EntityType::class, [
                'label' => 'admin.contact.contact_purpose',
                'class' => 'Plugin\ContactManagement\Entity\Master\ContactPurpose',
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.sort_no', 'ASC');
                },
            ])
            ->add('contact_template', EntityType::class, [
                'mapped' => false,
                'label' => 'admin.contact.contact_template',
                'required' => false,
                'class' => 'Plugin\ContactManagement\Entity\ContactTemplate',
                'choice_label' => 'name',
                'placeholder' => 'admin.contact.contact_template_select',
            ])
            ->add('note_title', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])
            ->add('charge_member', EntityType::class, [
                'label' => 'admin.contact.charge_member',
                'required' => false,
                'class' => 'Eccube\Entity\Member',
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.sort_no', 'ASC');
                },
            ])
            ->add('ContactStatus', EntityType::class, [
                'label' => 'admin.contact.contact_status',
                'class' => 'Plugin\ContactManagement\Entity\Master\ContactStatus',
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('cs')
                        ->orderBy('cs.sort_no', 'ASC');
                },
            ])
            ->add('ContactComment', ContactCommentType::class, [
                'mapped' => false,
                'commentRequired' => $commentRequired,
            ])
            ->add('return_link', HiddenType::class, [
                'mapped' => false,
            ]);
        $builder
            ->add($builder->create('Customer', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->entityManager,
                    '\Eccube\Entity\Customer'
                )));
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'defaultTitle']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'existingSubject']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'showDraft']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'showCustomerNote']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'checkCommentType']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'updateRelatedCustomerNotes']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'saveDraft' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact';
    }

    /**
     * @param FormEvent $event
     *
     * @throws NonUniqueResultException
     */
    public function defaultTitle(FormEvent $event)
    {
        $Contact = $event->getData();
        if ($Contact->getId()) {
            $isFirstReply = $this->contactCommentRepository->isFirstReply($Contact);
            if ($isFirstReply) {
                $form = $event->getForm();
                $form['ContactComment']['subject']->setData(trans('admin.contact.mail_default_subject'));
            }
        }
    }

    /**
     * @param FormEvent $event
     *
     * @throws NonUniqueResultException
     */
    public function existingSubject(FormEvent $event)
    {
        $Contact = $event->getData();
        if ($Contact->getId()) {
            $haveExistingSubject = $this->contactCommentRepository->haveExistingSubject($Contact);
            if ($haveExistingSubject) {
                $isSecondReply = $this->contactCommentRepository->isSecondReply($Contact);
                $LatestShopComment = $this->contactCommentRepository->getLatestCommentsFromStore($Contact);
                $form = $event->getForm();
                if ($isSecondReply) {
                    $form['ContactComment']['subject']->setData('Re: '.$LatestShopComment->getSubject());
                } else {
                    $form['ContactComment']['subject']->setData($LatestShopComment->getSubject());
                }
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function showDraft(FormEvent $event)
    {
        $Contact = $event->getData();
        if ($Contact->getId()) {
            $LatestShopComment = $this->contactCommentRepository->getLatestCommentsFromStore($Contact);

            if (!is_null($LatestShopComment) && !$LatestShopComment->isSend()) {
                $form = $event->getForm();
                $form['ContactComment']->setData($LatestShopComment);
                if ($LatestShopComment->getContactCommentImages()) {
                    $ContactCommentImages = $LatestShopComment->getContactCommentImages();
                    foreach ($ContactCommentImages as $key => $ContactCommentImage) {
                        $key++;
                        $form['ContactComment']['image_name_'.$key]->setData($ContactCommentImage->getFileName());
                    }
                }
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function showCustomerNote(FormEvent $event)
    {
        $Contact = $event->getData();
        if ($Contact->getCustomer()) {
            $Customer = $this->customerRepository->find($Contact->getCustomer()->getId());
            if (!is_null($Customer->getNote())) {
                $form = $event->getForm();
                $form['note']->setData($Customer->getNote());
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function checkCommentType(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data['ContactComment']['comment_type'] == 1) {
//            memo_commentのバリデーションを無効にする
            $form['ContactComment']->remove('memo_comment');
            $form['ContactComment']->add('memo_comment', TextAreaType::class, [
                'mapped' => false,
                'required' => false,
            ]);
        } else {
//            subject、commentのバリデーションを無効にする
            $form['ContactComment']->remove('subject');
            $form['ContactComment']->remove('comment');
            $form['ContactComment']->add('subject', TextType::class);
            $form['ContactComment']->add('comment', TextAreaType::class);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function updateRelatedCustomerNotes(FormEvent $event)
    {
        $form = $event->getForm();
        $Contact = $event->getData();
        if ($Contact->getCustomer()) {
            $Customer = $Contact->getCustomer();
            $Customer->setNote($form['note']->getData());

            $Contacts = $this->contactRepository->findBy(['Customer' => $Customer]);
            foreach ($Contacts as $Contact) {
                $Contact->setNote($form['note']->getData());
            }
            $this->entityManager->flush();
        }
    }
}
