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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\PhoneNumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\ContactManagement\Repository\Master\ContactStatusRepository;
use Plugin\ContactManagement\Entity\Master\ContactStatus;
use Plugin\ContactManagement\Form\Type\Master\ContactPurposeType;

class SearchContactType extends AbstractType
{
    const REPLIED = 1;
    const NOT_REPLYING = 0;

    const CUSTOMER = 1;
    const NOT_CUSTOMER = 0;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ContactStatusRepository
     */
    protected $contactStatusRepository;

    /**
     * SearchContactType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param ContactStatusRepository $contactStatusRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ContactStatusRepository $contactStatusRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->contactStatusRepository = $contactStatusRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $excludes = [ContactStatus::RESOLVED, ContactStatus::DO_NOT_CORRESPOND];
        $Criteria = new Criteria();
        $Criteria->where($Criteria::expr()->notIn('id', $excludes));
        $ContactStatuses = $this->contactStatusRepository->matching($Criteria);

        $builder
            ->add('multi_search', TextType::class, [
                'label' => 'admin.contact.contact_multi_search_label',
                'required' => false,
            ])->add($builder
                ->create('name', TextType::class, [
                    'label' => 'admin.contact.contact_name',
                    'required' => false,
                ]
            ))->add($builder
                ->create('kana', TextType::class, [
                    'label' => 'admin.contact.contact_kana',
                    'required' => false,
                    'constraints' => [
                        new Assert\Regex([
                            'pattern' => '/^[ァ-ヶｦ-ﾟー]+$/u',
                            'message' => 'form_error.kana_only',
                        ]),
                    ],
                ])
                ->addEventSubscriber(new \Eccube\Form\EventListener\ConvertKanaListener('CV')
            ))->add('status', ContactStatusType::class, [
                'label' => 'admin.contact.contact_status',
                'expanded' => true,
                'multiple' => true,
                'data' => $ContactStatuses,
            ])
            ->add('replied', ChoiceType::class, [
                'label' => 'admin.contact.replied',
                'choices' => [
                    'admin.contact.not_replying' => SearchContactType::NOT_REPLYING,
                    'admin.contact.replied' => SearchContactType::REPLIED,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('email', TextType::class, [
                'label' => 'admin.contact.contact_email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'common.mail_address_sample',
                ],
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'label' => 'admin.contact.contact_phone_number',
                'required' => false,
            ])
            ->add('contact_id', TextType::class, [
                'label' => 'admin.contact.contact_id',
                'required' => false,
            ])
            ->add('comment_id', TextType::class, [
                'label' => 'admin.contact.contact_comment_id',
                'required' => false,
            ])
            ->add('charge_member', EntityType::class, [
                'label' => 'admin.contact.charge_member',
                'required' => false,
                'class' => 'Eccube\Entity\Member',
                'choice_label' => 'name',
                'placeholder' => 'admin.contact.all_select',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.sort_no', 'ASC');
                },
            ])
            ->add('customer', ChoiceType::class, [
                'label' => 'admin.contact.customer',
                'choices' => [
                    'admin.contact.contact_customer' => SearchContactType::CUSTOMER,
                    'admin.contact.contact_not_customer' => SearchContactType::NOT_CUSTOMER,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('contact_purpose', ContactPurposeType::class, [
                'label' => 'admin.contact.contact_purpose',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('create_date_start', DateType::class, [
                'label' => 'admin.contact.contact_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_contact_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'admin.contact.contact_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_contact_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('comment_date_start', DateType::class, [
                'label' => 'admin.contact.comment_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_comment_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('comment_date_end', DateType::class, [
                'label' => 'admin.contact.comment_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_comment_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_search_contact';
    }
}
