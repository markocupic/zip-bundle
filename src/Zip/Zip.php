<?php

declare(strict_types=1);

/*
 * This file is part of Zip Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/zip-bundle
 */

namespace Markocupic\ZipBundle\Zip;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class Zip
{
    private ?\ZipArchive $zip;
    private array $arrStorage = [];
    private ?string $strStripSourcePath;
    private bool $ignoreDotFiles = true;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (!\extension_loaded('zip')) {
            throw new \Exception('PHP Extension "ext-zip" not loaded.');
        }

        return $this;
    }

    /**
     * Strip the source path in the zip archive.
     *
     * @return $this
     */
    public function stripSourcePath(string $path): self
    {
        $this->strStripSourcePath = $path;

        return $this;
    }

    /**
     * Ignore dot files/folders like .ecs, .gitattribute, etc.
     */
    public function ignoreDotFiles(bool $blnIgnore): self
    {
        $this->ignoreDotFiles = $blnIgnore;

        return $this;
    }

    /**
     * Zip directory recursively and store it to a predefined destination.
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function addFile(string $source): self
    {
        if (!is_file($source)) {
            throw new \Exception(sprintf('File "%s" not found.', $source));
        }

        $this->addToStorage($source);

        return $this;
    }

    /**
     * Add files from the directory.
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function addDir(string $source): self
    {
        if (!is_dir($source)) {
            throw new \Exception(sprintf('Source directory "%s" not found.', $source));
        }

        $this->addToStorage($source, 0, true);

        return $this;
    }

    /**
     * @throws \Exception
     *
     * @return $this
     */
    public function addDirRecursive(string $source, int $intDepth = -1, bool $blnFilesOnly = false): self
    {
        if (!is_dir($source)) {
            throw new \Exception(sprintf('Source directory "%s" not found.', $source));
        }

        $this->addToStorage($source, $intDepth, $blnFilesOnly);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function run(string $destinationPath): bool
    {
        if ($this->zip($destinationPath)) {
            $this->reset();

            return true;
        }

        return false;
    }

    public function downloadArchive(string $filename): void
    {
        if (!is_file($filename)) {
            throw new FileNotFoundException(sprintf('File "%s" not found.', $filename));
        }
        $response = new Response(file_get_contents($filename));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.basename($filename).'"');
        $response->headers->set('Content-length', filesize($filename));

        $response->send();
    }

    public function getStorage(): array
    {
        return $this->arrStorage;
    }

    /**
     * @return $this
     */
    public function purgeStorage(): self
    {
        $this->arrStorage = [];

        return $this;
    }

    /**
     * Add files/directories (recursive or not) to the storage.
     *
     * @return $this
     */
    private function addToStorage(string $source, int $intDepth = -1, bool $blnFilesOnly = false): self
    {
        if (!file_exists($source)) {
            throw new FileNotFoundException(sprintf('File or folder "%s" not found', $source));
        }

        if (is_dir($source)) {
            $finder = new Finder();

            $finder->ignoreDotFiles($this->ignoreDotFiles);

            if ($intDepth > -1) {
                if ($blnFilesOnly) {
                    $finder->files();
                }
                $finder->depth('== '.$intDepth);
            } else {
                if ($blnFilesOnly) {
                    $finder->files();
                }
            }

            foreach ($finder->in($source) as $file) {
                $this->arrStorage[] = $file->getRealPath();
            }
        } else {
            $this->arrStorage[] = $source;
        }

        $this->arrStorage = array_unique($this->arrStorage);

        return $this;
    }

    /**
     * @throws \Exception
     */
    private function zip(string $destination): bool
    {
        if (!preg_match('/\.zip$/', $destination)) {
            throw new \Exception(sprintf('Illegal destination path defined "%s". Destination must be a valid path (f.ex. "file/path/to/archive.zip".', $destination));
        }

        if (!is_dir(\dirname($destination))) {
            throw new \Exception(sprintf('Destination directory "%s" not found.', $destination));
        }

        $this->zip = new \ZipArchive();
        $this->zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Check if $this->strStripSourcePath stands at the beginning of each file path
        $blnStripSourcePath = false;

        if (\strlen((string) $this->strStripSourcePath)) {
            $blnStripSourcePath = true;

            foreach ($this->arrStorage as $res) {
                if (0 !== strpos($this->strStripSourcePath, $res)) {
                    $blnStripSourcePath = false;
                    break;
                }
            }
        }

        foreach ($this->arrStorage as $res) {
            if (is_dir($res)) {
                // Add empty dir (and remove the source path)
                if (true === $blnStripSourcePath) {
                    $this->zip->addEmptyDir(str_replace($this->strStripSourcePath.\DIRECTORY_SEPARATOR, '', $res));
                } else {
                    $this->zip->addEmptyDir(ltrim($res, \DIRECTORY_SEPARATOR));
                }
            } else {
                if (is_file($res)) {
                    // Add file (and remove the source path)
                    if (true === $blnStripSourcePath) {
                        $this->zip->addFromString(str_replace($this->strStripSourcePath.\DIRECTORY_SEPARATOR, '', $res), file_get_contents($res));
                    } else {
                        $this->zip->addFromString(ltrim($res, \DIRECTORY_SEPARATOR), file_get_contents($res));
                    }
                }
            }
        }
        $this->zip->close();

        return true;
    }

    /**
     * Reset to defaults.
     *
     * @return $this
     */
    private function reset(): self
    {
        $this->zip = null;
        $this->purgeStorage();
        $this->strStripSourcePath = null;

        return $this;
    }
}
