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
use Eccube\Form\Type\MasterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\ContactManagement\Entity\Master\ContactStatus;
use Plugin\ContactManagement\Repository\ContactRepository;

class ContactStatusType extends AbstractType
{
    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * ContactStatusType constructor.
     *
     * @param ContactRepository $contactRepository
     */
    public function __construct(
        ContactRepository $contactRepository
    ) {
        $this->contactRepository = $contactRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ContactStatus[] $ContactStatuses */
        $ContactStatuses = $options['choice_loader']->loadChoiceList()->getChoices();
        foreach ($ContactStatuses as $ContactStatus) {
            $id = $ContactStatus->getId();
            $count = $this->contactRepository->countByContactStatus($id);
            $view->vars['contact_count'][$id]['display'] = true;
            $view->vars['contact_count'][$id]['count'] = $count;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => ContactStatus::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('cs')
                    ->orderBy('cs.id', 'ASC');
            },
        ]);
    }

    public function getParent()
    {
        return MasterType::class;
    }

    public function getBlockPrefix()
    {
        return 'contact_status';
    }
}