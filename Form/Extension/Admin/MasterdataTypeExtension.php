<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Form\Extension\Admin;

use Doctrine\ORM\EntityManager;
use Eccube\Form\Type\Admin\MasterdataType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class MasterdataTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $masterdata = [];

        /** @var MappingDriverChain $driverChain */
        $driverChain = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
        /** @var MappingDriver[] $drivers */
        $drivers = $driverChain->getDrivers();

        foreach ($drivers as $namespace => $driver) {
            if ($namespace == 'Eccube\Entity' || $namespace == 'Plugin\ContactManagement\Entity') {
                $classNames = $driver->getAllClassNames();
                foreach ($classNames as $className) {
                    /** @var ClassMetadata $meta */
                    $meta = $this->entityManager->getMetadataFactory()->getMetadataFor($className);
                    if (strpos($meta->rootEntityName, 'Master') !== false
                        && $meta->hasField('id')
                        && $meta->hasField('name')
                        && $meta->hasField('sort_no')
                    ) {
                        $metadataName = str_replace('\\', '-', $meta->getName());
                        $masterdata[$metadataName] = $meta->getTableName();
                    }
                }
            }
        }

        $options = $builder->get('masterdata')->getOptions();
        $options['choices'] = array_flip($masterdata);

        $builder
            ->add('masterdata', ChoiceType::class, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return MasterdataType::class;
    }
}