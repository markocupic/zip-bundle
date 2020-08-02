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
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Zip
 *
 * @package Markocupic\ZipBundle\Zip
 */
class Zip
{
    /** @var \ZipArchive */
    private $zip;

    /** @var array */
    private $arrStorage = [];

    /** @var string */
    private $strStripSourcePath;

    /**
     * Zip constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        if (!extension_loaded('zip'))
        {
            throw new \Exception('PHP Extension "ext-zip" not loaded.');
        }


        return $this;
    }

    /**
     * Strip the source path in the zip archive
     *
     * @param string $path
     * @return $this
     */
    public function stripSourcePath(string $path): self
    {
        $this->strStripSourcePath = $path;
        return $this;
    }

    /**
     * Zip directory recursively and store it to a predefined destination
     *
     * @param string $source
     * @return $this
     * @throws \Exception
     */
    public function addDirRecursive(string $source): self
    {
        if (!is_dir($source))
        {
            throw new \Exception(sprintf('Source directory "%s" not found.', $source));
        }

        $this->addToStorage($source, true);

        return $this;
    }

    /**
     * @param string $destinationPath
     * @return bool
     * @throws \Exception
     */
    public function run(string $destinationPath): bool
    {
        if ($this->zip($destinationPath))
        {
            $this->reset();

            return true;
        }

        return false;
    }

    /**
     * @param string $filename
     */
    public function downloadArchive(string $filename)
    {
        if (!is_file($filename))
        {
            throw new FileNotFoundException(sprintf('File "%s" not found.', $filename));
        }
        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . basename($filename) . '"');
        $response->headers->set('Content-length', filesize($filename));

        $response->send();
    }

    /**
     * @param $source
     * @param bool $blnRecursive
     * @return $this
     */
    private function addToStorage($source, $blnRecursive = false): self
    {
        if (!file_exists($source))
        {
            throw new FileNotFoundException(sprintf('File or folder "%s" not found', $source));
        }

        $source = realpath($source);

        if ($blnRecursive === true) // Pick files in folders and subfolders ($blnRecursive === true)
        {
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
        }
        else // Pick only files and no folders and subfolder ($blnRecursive === false)
        {
            if (is_file($source))
            {
                $this->arrStorage[] = $source;
            }
            else
            {
                foreach (scandir($source) as $key => $file)
                {
                    if (!in_array($file, [".", ".."]))
                    {
                        if (is_file($source . DIRECTORY_SEPARATOR . $file))
                        {
                            $this->arrStorage[] = $file;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getStorage(): array
    {
        return $this->arrStorage;
    }

    /**
     * @param string $destination
     * @return bool
     * @throws \Exception
     */
    private function zip(string $destination): bool
    {
        if (!preg_match('/\.zip$/', $destination))
        {
            throw new \Exception(
                sprintf(
                    'Illegal destination path defined "%s". Destination must be a valid path (f.ex. "file/path/to/archive.zip".',
                    $destination
                )
            );
        }

        if (!is_dir(dirname($destination)))
        {
            throw new \Exception(sprintf('Destination directory "%s" not found.', $destination));
        }

        $this->zip = new \ZipArchive();
        $this->zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Check if $this->strStripSourcePath stands at the beginning of each file path
        $blnStripSourcePath = false;
        if (strlen((string) $this->strStripSourcePath))
        {
            $blnStripSourcePath = true;
            foreach ($this->arrStorage as $res)
            {
                if (strpos($this->strStripSourcePath, $res) != 0)
                {
                    $blnStripSourcePath = false;
                    break;
                }
            }
        }

        foreach ($this->arrStorage as $res)
        {
            if (is_dir($res))
            {
                // Add empty dir (and remove the source path)
                if ($blnStripSourcePath === true)
                {
                    $this->zip->addEmptyDir(str_replace($this->strStripSourcePath . DIRECTORY_SEPARATOR, '', $res));
                }
                else
                {
                    $this->zip->addEmptyDir(ltrim($res, DIRECTORY_SEPARATOR));
                }
            }
            else
            {
                if (is_file($res))
                {
                    // Add file (and remove the source path)
                    if ($blnStripSourcePath === true)
                    {
                        $this->zip->addFromString(str_replace($this->strStripSourcePath . DIRECTORY_SEPARATOR, '', $res), file_get_contents($res));
                    }
                    else
                    {
                        $this->zip->addFromString(ltrim($res, DIRECTORY_SEPARATOR), file_get_contents($res));
                    }
                }
            }
        }
        $this->zip->close();

        return true;
    }

    /**
     * Reset to defaults
     *
     * @return $this
     */
    private function reset(): self
    {
        $this->zip = null;
        $this->arrStorage = [];
        $this->strStripSourcePath = null;
        return $this;
    }

}
