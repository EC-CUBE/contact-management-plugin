<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Service\Twig;

class ContactFunction extends \Twig_Extension
{
    public function getLastCommentDate($Contact)
    {
        $lastCommentDate = null;
        $ContactComments = $Contact->getContactComments();
        foreach ($ContactComments as $ContactComment) {
            if ($ContactComment->isSend()) {
                $lastCommentDate = $ContactComment->getUpdateDate();
                break;
            }
        }

        return $lastCommentDate;
    }

    /**
     * Twigでの呼び出し名の登録
     *
     * @return array|\Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getLastCommentDate', [$this, 'getLastCommentDate']),
        ];
    }

    /**
     * Twig拡張ファイルに必須
     *
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }
}
