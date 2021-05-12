<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement;

use Eccube\Entity\Layout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\ContactManagement\Entity\ContactTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        chmod($container->getParameter('eccube_contact_comment_image_dir'), 0777);
        chmod($container->getParameter('eccube_temp_image_contact_comment_dir'), 0777);
        chmod($container->getParameter('eccube_image_contact_comment_dir'), 0777);

        $em = $container->get('doctrine.orm.entity_manager');
        $MailTemplate = new MailTemplate();

        $MailTemplate->setName('問い合わせ受付メール（問い合わせ管理プラグイン用）');
        $MailTemplate->setFileName('ContactManagement/Resource/template/default/Contact/contact_mail.twig');
        $MailTemplate->setMailSubject('お問い合わせを受け付けました。');
        $em->persist($MailTemplate);

        $Page = new Page();
        $Page->setName('ご返信（入力ページ）');
        $Page->setUrl('contact_reply');
        $Page->setFileName('@ContactManagement/default/Contact/reply');
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setMetaRobots('noindex');

        $em->persist($Page);
        $em->flush();

        $layout_id = Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE;
        $Layout = $em->find(Layout::class, $layout_id);
        $MaxSortNoPageLayout = $em->getRepository(PageLayout::class)
            ->findOneBy(['layout_id' => $layout_id], ['sort_no' => 'desc']);

        $PageLayout = new PageLayout();
        $PageLayout->setPageId($Page->getId());
        $PageLayout->setPage($Page);
        $PageLayout->setLayoutId($layout_id);
        $PageLayout->setLayout($Layout);
        $PageLayout->setSortNo($MaxSortNoPageLayout->getSortNo() + 1);

        $em->persist($PageLayout);

        foreach ($this->getContactTemplates() as $contactTemplate) {
            $this->upsert($contactTemplate, $container);
        }

        $masterTables = $this->getMasterTables();

        foreach ($masterTables as $entityName => $records) {
            foreach ($records as $record) {
                $this->upsertMasterData($entityName, $record, $container);
            }
        }
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(MailTemplate::class);
        $ContactMail = $repository->findOneBy(['name' => '問い合わせ受付メール（問い合わせ管理プラグイン用）']);
        $em->remove($ContactMail);

        $repository = $em->getRepository(Page::class);
        $ReplyPage = $repository->findOneBy(['name' => 'ご返信（入力ページ）']);

        $em->remove($ReplyPage);
        $em->flush();
    }

    /**
     * [upsert]
     *
     * @param  [type] $contactTemplate
     *
     * @return [type]
     */
    private function upsert($contactTemplate, $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $ContactTemplate = $this->getBaseQueryBuilder($contactTemplate, $entityManager)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$ContactTemplate) {
            $ContactTemplate = new ContactTemplate();
            $ContactTemplate->setName($contactTemplate['name']);
            $ContactTemplate->setFileName($contactTemplate['file_name']);
            $ContactTemplate->setMailSubject($contactTemplate['mail_subject']);

            $entityManager->persist($ContactTemplate);
        } else {
            $ContactTemplate->setName($contactTemplate['name']);
            $ContactTemplate->setFileName($contactTemplate['file_name']);
            $ContactTemplate->setMailSubject($contactTemplate['mail_subject']);
            $entityManager->persist($ContactTemplate);
        }

        $entityManager->flush();
    }

    /**
     * [getContactTemplates description]
     *
     * @return [type] [description]
     */
    private function getContactTemplates()
    {
        return [
            0 => [
                'name' => '問い合わせテンプレート1',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template1.twig',
                'mail_subject' => '問い合わせテンプレート1',
            ],
            1 => [
                'name' => '問い合わせテンプレート2',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template2.twig',
                'mail_subject' => '問い合わせテンプレート2',
            ],
            2 => [
                'name' => '問い合わせテンプレート3',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template3.twig',
                'mail_subject' => '問い合わせテンプレート3',
            ],
            3 => [
                'name' => '問い合わせテンプレート4',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template4.twig',
                'mail_subject' => '問い合わせテンプレート4',
            ],
            4 => [
                'name' => '問い合わせテンプレート5',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template5.twig',
                'mail_subject' => '問い合わせテンプレート5',
            ],
            5 => [
                'name' => '問い合わせテンプレート6',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template6.twig',
                'mail_subject' => '問い合わせテンプレート6',
            ],
            6 => [
                'name' => '問い合わせテンプレート7',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template7.twig',
                'mail_subject' => '問い合わせテンプレート7',
            ],
            7 => [
                'name' => '問い合わせテンプレート8',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template8.twig',
                'mail_subject' => '問い合わせテンプレート8',
            ],
            8 => [
                'name' => '問い合わせテンプレート9',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template9.twig',
                'mail_subject' => '問い合わせテンプレート9',
            ],
            9 => [
                'name' => '問い合わせテンプレート10',
                'file_name' => 'ContactManagement/Resource/template/admin/Contact/contact_template10.twig',
                'mail_subject' => '問い合わせテンプレート10',
            ],
        ];
    }

    private function getBaseQueryBuilder($contactTemplate, $entityManager)
    {
        return $entityManager->createQueryBuilder()
            ->from(ContactTemplate::class, 'ct')
            ->select('ct')
            ->where('ct.file_name = :file_name')
            ->setParameter('file_name', $contactTemplate['file_name']);
    }

    /**
     * [upsertMasterData]
     *
     * @param  [type] $entityName [Plugin\{PluginName}\Entity\Master\{Entity} or Eccube\Entity\Master\{Entity}]
     * @param  [type] $id
     * @param  [type] $name
     * @param  [type] $sortNo
     *
     * @return [type]
     */
    private function upsertMasterData($entityName, $record, $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $id = $record['id'];
        $name = $record['name'];
        $sortNo = $record['sortNo'];

        $Entity = $entityManager->createQueryBuilder()
            ->from($entityName, 'e')
            ->select('e')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$Entity) {
            $Entity = new $entityName();

            if (method_exists($Entity, 'setId')) {
                $Entity->setId($id);
            }
        }

        $Entity->setName($name);
        $Entity->setSortNo($sortNo);

        $entityManager->persist($Entity);
        $entityManager->flush();
    }

    /**
     * [getMasterTables description]
     *
     * @return [type] [description]
     */
    private function getMasterTables()
    {
        return [
            'Plugin\ContactManagement\Entity\Master\ContactPurpose' => [
                0 => [
                    'id' => 1,
                    'name' => '注文に関して',
                    'sortNo' => 0,
                ],
                1 => [
                    'id' => 2,
                    'name' => '商品に関して',
                    'sortNo' => 1,
                ],
                2 => [
                    'id' => 1001,
                    'name' => 'その他の内容',
                    'sortNo' => 1000,
                ],
            ],
            'Plugin\ContactManagement\Entity\Master\ContactStatus' => [
                0 => [
                    'id' => 1,
                    'name' => '新規受付',
                    'sortNo' => 0,
                ],
                1 => [
                    'id' => 2,
                    'name' => '返信受付',
                    'sortNo' => 1,
                ],
                2 => [
                    'id' => 3,
                    'name' => '未解決',
                    'sortNo' => 2,
                ],
                3 => [
                    'id' => 4,
                    'name' => '対応中',
                    'sortNo' => 3,
                ],
                4 => [
                    'id' => 5,
                    'name' => '保留',
                    'sortNo' => 4,
                ],
                5 => [
                    'id' => 1001,
                    'name' => '解決済',
                    'sortNo' => 1000,
                ],
                6 => [
                    'id' => 1002,
                    'name' => '対応しない',
                    'sortNo' => 1001,
                ],
            ],
            'Plugin\ContactManagement\Entity\Master\ContactStatusColor' => [
                0 => [
                    'id' => 1,
                    'name' => '#FF0000',
                    'sortNo' => 0,
                ],
                1 => [
                    'id' => 2,
                    'name' => '#FF0000',
                    'sortNo' => 1,
                ],
                2 => [
                    'id' => 3,
                    'name' => '#437ec4',
                    'sortNo' => 2,
                ],
                3 => [
                    'id' => 4,
                    'name' => '#437ec4',
                    'sortNo' => 3,
                ],
                4 => [
                    'id' => 5,
                    'name' => '#437ec4',
                    'sortNo' => 4,
                ],
                5 => [
                    'id' => 1001,
                    'name' => '#437ec4',
                    'sortNo' => 5,
                ],
                6 => [
                    'id' => 1002,
                    'name' => '#437ec4',
                    'sortNo' => 6,
                ],
            ],
        ];
    }
}
