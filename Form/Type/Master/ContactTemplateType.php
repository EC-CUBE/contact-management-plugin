<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Form\Type\Master;

use Doctrine\ORM\EntityRepository;
use Eccube\Form\Type\MasterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MailTemplateType
 */
class ContactTemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'Plugin\ContactManagement\Entity\ContactTemplate',
            'placeholder' => 'common.select',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('ct')
                    ->orderBy('ct.id', 'ASC');
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return MasterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_template';
    }
}
