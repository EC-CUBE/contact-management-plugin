<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Form\Extension\Front;

use Eccube\Form\Type\Front\ContactType;
use Plugin\ContactManagement\Form\Type\AllCharactersKanaType;
use Plugin\ContactManagement\Form\Type\Master\ContactPurposeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\ContactManagement\Entity\Master\ContactPurpose;
use Plugin\ContactManagement\Repository\Master\ContactPurposeRepository;
use Plugin\ContactManagement\Form\Type\ContactCommentType;
use Plugin\ContactManagement\Entity\Contact;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Eccube\Entity\Customer;

class ContactTypeExtension extends AbstractTypeExtension
{
    /**
     * @var ContactPurposeRepository
     */
    protected $contactPurposeRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;


    public function __construct(
        ContactPurposeRepository $contactPurposeRepository,
        EccubeConfig $eccubeConfig,
        TokenStorageInterface $tokenStorage
    ) {
        $this->contactPurposeRepository = $contactPurposeRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ContactPurpose', ContactPurposeType::class, [
                'data_class' => null,
                'label' => 'front.contact.purpose',
                'expanded' => true,
                'required' => true,
                'data' => $this->contactPurposeRepository->find(ContactPurpose::DEFAULT),
            ])
            ->remove('contents')
            ->add('contents', TextAreaType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltextarea_len']]),
                ],
            ])
            ->add('ContactComment', ContactCommentType::class, [
                'mapped' => false,
            ]);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setCustomerData']);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ContactType::class;
    }

    /**
     * @param FormEvent $event
     */
    public function setCustomerData(FormEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        $Customer = $token ? $token->getUser() : null;

        if ($Customer instanceof Customer && $Customer->getId()) {
            $form = $event->getForm();

            $form['name']['name01']->setData($Customer->getName01());
            $form['name']['name02']->setData($Customer->getName02());
            $form['kana']['kana01']->setData($Customer->getKana01());
            $form['kana']['kana02']->setData($Customer->getKana02());
            $form['postal_code']->setData($Customer->getPostalCode());
            $form['address']['pref']->setData($Customer->getPref());
            $form['address']['addr01']->setData($Customer->getAddr01());
            $form['address']['addr02']->setData($Customer->getAddr02());
            $form['phone_number']->setData($Customer->getPhoneNumber());
            $form['email']->setData($Customer->getEmail());
        }
    }
}
