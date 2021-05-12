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

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'contact' => [
                'name' => 'admin.contact.contact_management',
                'icon' => 'fa-question-circle-o',
                'children' => [
                    'contact_list' => [
                        'name' => 'admin.contact.contact_list',
                        'url' => 'admin_contact',
                    ],
                    'contact_new' => [
                        'name' => 'admin.contact.contact_registration',
                        'url' => 'admin_contact_contact_new',
                    ],
                    'contact_mail' => [
                        'name' => 'admin.contact.contact_template',
                        'url' => 'admin_contact_mail',
                    ],
                ],
            ],
        ];
    }
}
