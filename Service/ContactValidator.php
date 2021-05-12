<?php

/*
 * This file is part of the ContactManagement Plugin
 *
 * Copyright (C) 2020 Diezon.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement\Service;

class ContactValidator

{
    /**
     * 指定された複数ディレクトリのうち、いずれかのディレクトリ以下にファイルが存在するかを確認。
     *
     * @param $fileName string
     * @param $dirs array
     * @return boolean
     */
    public function fileExistsInDirs($fileName, $dirs)
    {
        $filteredDirs = array_filter($dirs, function ($dir) use ($fileName) {
            $filePath = realpath($dir.'/'.$fileName);
            $topDirPath = realpath($dir);
            return strpos($filePath, $topDirPath) === 0 && $filePath !== $topDirPath;
        });
        return !empty($filteredDirs);
    }
}
