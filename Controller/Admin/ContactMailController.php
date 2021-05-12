<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\ContactManagement\Entity\ContactTemplate;
use Plugin\ContactManagement\Form\Type\Admin\ContactMailType;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * Class ContactMailController
 */
class ContactMailController extends AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/contact/mail", name="admin_contact_mail")
     * @Route("/%eccube_admin_route%/contact/mail/{id}", requirements={"id" = "\d+"}, name="admin_contact_mail_edit")
     * @Template("@ContactManagement/admin/Contact/mail.twig")
     */
    public function index(Request $request, ContactTemplate $Mail = null, Environment $twig)
    {
        $builder = $this->formFactory
            ->createBuilder(ContactMailType::class, $Mail);

        $form = $builder->getForm();
        $form['template']->setData($Mail);

        // 更新時
        if (!is_null($Mail)) {
            // テンプレートファイルの取得
            $source = $twig->getLoader()
                ->getSourceContext($Mail->getFileName())
                ->getCode();

            $form->get('tpl_data')->setData($source);
        }

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            // 新規登録は現時点では未実装とする.
            if (is_null($Mail)) {
                $this->addError('admin.common.save_error', 'admin');

                return $this->redirectToRoute('admin_contact_mail_edit');
            }

            if ($form->isValid()) {
                $this->entityManager->flush();

                // ファイル生成・更新
                $templatePath = $this->getParameter('eccube_theme_front_dir');
                $filePath = $templatePath.'/'.$Mail->getFileName();

                $fs = new Filesystem();
                $mailData = $form->get('tpl_data')->getData();
                $mailData = StringUtil::convertLineFeed($mailData);
                $fs->dumpFile($filePath, $mailData);

                $this->addSuccess('admin.common.save_complete', 'admin');

                return $this->redirectToRoute('admin_contact_mail_edit', ['id' => $Mail->getId()]);
            }
        }

        return [
            'form' => $form->createView(),
            'id' => is_null($Mail) ? null : $Mail->getId(),
        ];
    }
}
