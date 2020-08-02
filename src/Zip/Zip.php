<?php

/**
 * This file is part of a markocupic Contao Bundle
 *
 * @copyright  Marko Cupic 2020 <m.cupic@gmx.ch>
 * @author     Marko Cupic
 * @package    zip-bundle
 * @license    MIT
 * @see        https://github.com/markocupic/zip-bundle
 *
 */

declare(strict_types=1);

namespace Markocupic\ZipBundle\Zip;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Class Zip
 *
 * @package Markocupic\ZipBundle\Zip
 */
class Zip
{
    /** @var bool */
    private static $STRIP_SOURCE = false;

    /** @var array */
    private $arrStorage = [];

    /** @var zip archive */
    private $zip;

    /**
     * Zip constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (!extension_loaded('zip'))
        {
            throw new \Exception('PHP Extension "zip" not loaded.');
        }

        return $this;
    }

    /**
     * Strip the source path in the zip archive
     *
     * @param bool $bln
     * @return $this
     */
    public function stripSourcePath(bool $bln): self
    {
        static::$STRIP_SOURCE = $bln;
        return $this;
    }

    /**
     * Zip folder recursively and store it to a predefined destination
     *
     * @param string $source
     * @param string $destination
     * @return $this
     */
    public function zipRecursive(string $source, string $destination): self
    {
        $this->addToStorage($source);
        if (!count($this->arrStorage) > 0)
        {
            return false;
        }

        $this->zip()->close();

        return true;
    }

    /**
     * @param $source
     * @return $this
     */
    private function addToStorage($source): self
    {
        if (!file_exists($source))
        {
            throw new FileNotFoundException(sprintf('File or folder "%s" not found', $source));
        }

        $source = realpath($source);
        if (is_dir($source))
        {
            $iterator = new \RecursiveDirectoryIterator($source);

            // Skip dot files while iterating
            $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $objSplFileInfo)
            {
                $this->arrStorage[] = $objSplFileInfo->getRealPath();
            }
        }
        else
        {
            if (is_file($source))
            {
                $this->arrStorage[] = $source;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function close(): self
    {
        if ($this->zip instanceof \ZipArchive)
        {
            $this->zip->close();
            $this->zip = null;
        }
        return $this;
    }

    /**
     * @return $this
     */
    private function zip(): self
    {
        $this->zip = new \ZipArchive();
        $this->zip->open($destination, \ZipArchive::CREATE);

        foreach ($this->arrStorage as $res)
        {
            if (is_dir($res))
            {
                // Add empty dir (and remove the source path)
                if (static::$STRIP_SOURCE)
                {
                    $this->zip->addEmptyDir(str_replace($source . '/', '', $res . '/'));
                }
                else
                {
                    $this->zip->addEmptyDir($res . '/');
                }
            }
            else
            {
                if (is_file($res))
                {
                    // Add file (and remove the source path)
                    if (static::$STRIP_SOURCE)
                    {
                        $this->zip->addFromString(str_replace($source . '/', '', $res), file_get_contents($res));
                    }
                    else
                    {
                        $this->zip->addFromString(file_get_contents($res));
                    }
                }
            }
        }

        return $this;
    }

}
