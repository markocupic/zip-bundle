<?php

declare(strict_types=1);

/*
 * This file is part of Zip Bundle.
 *
 * (c) Marko Cupic 2025 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/zip-bundle
 */

namespace Markocupic\ZipBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicZipBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
