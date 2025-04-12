<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;

return static function (ECSConfig $ECSConfig): void {

    $services = $ECSConfig->services();

    $services
        ->set(HeaderCommentFixer::class)
        ->call('configure', [[
            'header' => "This file is part of Zip Bundle.\n\n(c) Marko Cupic <m.cupic@gmx.ch>\n@license MIT\nFor the full copyright and license information,\nplease view the LICENSE file that was distributed with this source code.\n@link https://github.com/markocupic/zip-bundle",
        ]])
    ;
};