<?php

/**
 * This file is part of a markocupic Contao Bundle
 *
 * @copyright  Marko Cupic 2020 <m.cupic@gmx.ch>
 * @author     Marko Cupic
 * @package    zip bundle
 * @license    MIT
 * @see        https://github.com/markocupic/zip-bundle
 *
 */

declare(strict_types=1);

namespace Markocupic\ZipBundle\Tests\Zip;

use Contao\TestCase\ContaoTestCase;
use Markocupic\ZipBundle\Zip\Zip;

/**
 * Class ZipTest
 *
 * @package Markocupic\ZipBundle\Tests\Zip
 */
class ZipTest extends ContaoTestCase
{
    private $zip;

    private $arrRes;

    private $zipDestPath;

    public function setUp(): void
    {
        parent::setUp();
        $this->zip = new Zip();
        $this->arrRes = [
            ['folder' => 'dir1'],
            ['folder' => 'dir1/subdir1_1'],
            ['folder' => 'dir1/subdir1_2'],
            ['file' => 'dir1/subdir1_1/file_1_1_1.txt', 'content' => 'FooBar'],
            ['file' => 'dir1/subdir1_2/file_1_1_2.txt', 'content' => 'FooBar'],
        ];

        $this->zipDestPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'myzip.zip';

        // Delete files
        $this->delTempFiles(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->arrRes[0]['folder']);

        foreach ($this->arrRes as $res)
        {
            if (isset($res['folder']))
            {
                mkdir(sys_get_temp_dir() . '/' . $res['folder'], 0777, true);
            }
            else
            {
                $fh = fopen(sys_get_temp_dir() . '/' . $res['file'], 'w');
                fwrite($fh, $res['content']);
                fclose($fh);
            }
        }
    }

    public function tearDown(): void
    {
        // Delete files
        $this->delTempFiles(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->arrRes[0]['folder']);
        $this->delTempFiles($this->zipDestPath);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf(Zip::class, new Zip());
    }

    public function testAddDirRecursive(): void
    {
        $this->zip->addDirRecursive(sys_get_temp_dir() . '/' . $this->arrRes[0]['folder']);
        $this->assertTrue(count($this->arrRes) - 1 === count($this->zip->getStorage()));
    }

    public function testRun()
    {
        // Delete old files
        $this->delTempFiles($this->zipDestPath);

        // Make zip archive
        $source = sys_get_temp_dir() . '/' . $this->arrRes[0]['folder'];

        $this->zip
            ->addDirRecursive($source)
            ->stripSourcePath($source)
            ->run($this->zipDestPath);
        $this->assertTrue(true === is_file($this->zipDestPath));
        $this->assertTrue(true === filesize($this->zipDestPath) > 0);
    }

    private function delTempFiles($res)
    {
        if (!file_exists($res))
        {
            return;
        }
        // File
        if (is_file($res))
        {
            return unlink($res);
        }

        // Folder
        $files = array_diff(scandir($res), ['.', '..']);
        foreach ($files as $file)
        {
            (is_dir($res . DIRECTORY_SEPARATOR . $file)) ? $this->delTempFiles($res . DIRECTORY_SEPARATOR . $file) : unlink($res . DIRECTORY_SEPARATOR . $file);
        }
        return rmdir($res);
    }

}
