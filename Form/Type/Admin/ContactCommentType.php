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

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Plugin\ContactManagement\Entity\ContactComment;
use Eccube\Form\Validator\TwigLint;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;


class ContactCommentType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ContactCommentType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltextarea_len'],
                    ]),
                ],
            ])
            ->add('memo_comment', TextAreaType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_ltextarea_len']]),
                    new TwigLint(),
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('comment_type', ChoiceType::class, [
                'label' => trans('admin.contact.comment_type'),
                'choices' => [
                    trans('admin.contact.comment_send_mail_type') => $this->eccubeConfig['eccube_contact_comment_send_mail_type'],
                    trans('admin.contact.comment_memo_registration_type') => $this->eccubeConfig['eccube_contact_comment_memo_registration_type'],
                ],
                'data' => $this->eccubeConfig['eccube_contact_comment_send_mail_type'],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'mapped' => false,
            ])
            ->add('image_file_upload_1', FileType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('image_file_upload_2', FileType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('image_name_1', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('image_name_2', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('delete_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ]);

        if ($options['commentRequired']) {
            $builder
                ->add('comment', TextAreaType::class, [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['max' => $this->eccubeConfig['eccube_ltextarea_len']]),
                        new TwigLint(),
                    ],
                ]);
        } else {
            $builder
                ->add('comment', TextAreaType::class, [
                    'required' => false,
                    'constraints' => [
                        new Assert\Length(['max' => $this->eccubeConfig['eccube_ltextarea_len']]),
                        new TwigLint(),
                    ],
                ]);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var FormInterface $form */
            $form = $event->getForm();
            $this->validateFilePath($form->get('image_name_1'), $this->eccubeConfig['eccube_temp_image_contact_comment_dir']);
            $this->validateFilePath($form->get('image_name_2'), $this->eccubeConfig['eccube_temp_image_contact_comment_dir']);
        });
    }

    /**
     * 指定したディレクトリ以下のパスかどうかを確認。
     *
     * @param $form FormInterface
     * @param $dir string
     */
    private function validateFilePath($form, $dir)
    {
        $fileName = $form->getData();
        if ($fileName) {
            $topDirPath = realpath($dir);
            $filePath = realpath($dir.'/'.$fileName);
            if (strpos($filePath, $topDirPath) !== 0 || $filePath === $topDirPath) {
                if ($form->getName() == 'image_name_1') {
                    $form->getRoot()['ContactComment']['image_file_upload_1']->addError(new FormError('画像のパスが不正です。'));
                } else {
                    $form->getRoot()['ContactComment']['image_file_upload_2']->addError(new FormError('画像のパスが不正です。'));
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactComment::class,
            'commentRequired' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_comment';
    }
}
