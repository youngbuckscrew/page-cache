<?php

/**
 * This file is part of the PageCache package.
 *
 * @author Muhammed Mamedov <mm@turkmenweb.net>
 * @copyright 2016
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PageCache\Storage\FileSystem;

/**
 * Class HashDirectory.
 * HashDirectory creates subdirectories where cache files are stored, based on cache file name.
 *
 * @package PageCache
 */
class HashDirectory
{
    /**
     * Filename
     *
     * @var string|null
     */
    private $file = null;

    /**
     * Directory where filename will be stored.
     * Subdirectories are going to be created inside this directory, if necessary.
     *
     * @var string|null
     */
    private $dir = null;

    /**
     * HashDirectory constructor.
     *
     * @param string|null $file
     * @param string|null $dir
     * @throws \Exception
     */
    public function __construct($file = null, $dir = null)
    {
        $this->setDir($dir);
        $this->file = $file;
    }

    /**
     * Set directory
     *
     * @param string|null $dir
     * @throws \Exception
     */
    public function setDir($dir)
    {
        if (!empty($dir)) {
            if (!@is_dir($dir)) {
                throw new \Exception(__CLASS__ . ' dir in constructor is not a directory');
            }
            $this->dir = $dir;
        }
    }

    /**
     * Set file
     *
     * @param string|null $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * Based on incoming string (filename) return 2 directories to store cache file.
     * If directories(one or both) not present create whichever is not there yet.
     *
     * Returns null if $this->file or $this->dir is not set.
     *
     * @return string|null with two directory names like '10/55/', ready to be appended to cache_dir
     */
    public function getHash()
    {
        if (empty($this->file) || empty($this->dir)) {
            return null;
        }

        $directories = $this->getDirectoryPathByHash($this->file);
        $directories_array = explode('/', $directories);

        //create directories
        $this->createSubDirs($directories_array[0], $directories_array[1]);

        return $directories;
    }

    /**
     *  Inside $this->dir (Cache Directory), create 2 sub directories to store current cache file
     *
     * @param $dir1 string directory
     * @param $dir2 string directory
     *
     * @throws \Exception directories not created
     */
    private function createSubDirs($dir1, $dir2)
    {
        //dir1 not exists, create both
        if (!@is_dir($this->dir . $dir1)) {
            mkdir($this->dir . $dir1);
            mkdir($this->dir . $dir1 . '/' . $dir2);
        } else {
            //dir1 exists, create dir2
            if (!@is_dir($this->dir . $dir1 . '/' . $dir2)) {
                mkdir($this->dir . $dir1 . '/' . $dir2);
            }
        }
        //check if directories are there
        if (!@is_dir($this->dir . $dir1 . '/' . $dir2)) {
            throw new \Exception(__CLASS__.' ' . $dir1 . '/' . $dir2
                . ' cache directory could not be created');
        }
    }

    /**
     * Get subdirectories for location of where cache file would be placed.
     * Returns null when filename is empty, otherwise 2 subdirectories where filename would be located.
     *
     * @param string $filename Cache file name
     *
     * @return null|string null
     */
    public function getLocation($filename)
    {
        if (empty($filename)) {
            return null;
        }

        return $this->getDirectoryPathByHash($filename);
    }

    /**
     * Get a path with 2 directories, based on filename hash
     *
     * @param string $filename
     *
     * @return string directory path
     */
    private function getDirectoryPathByHash($filename)
    {
        //get 2 number
        $val1 = ord($filename[1]);
        $val2 = ord($filename[3]);

        //normalize to 99
        $val1 = $val1 % 99;
        $val2 = $val2 % 99;

        return $val1 . '/' . $val2 . '/';
    }

    /**
     * Removes all files and directories inside a directory.
     * Used for deleting all cache content.
     *
     * @param string $dir
     */
    public function clearDirectory($dir)
    {
        if (empty($dir)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($dir);
        $filter = new \RecursiveCallbackFilterIterator($iterator, function ($current) {
            /** @var \SplFileInfo $current */
            $filename = $current->getBasename();
            // Check for files and dirs starting with "dot" (.gitignore, etc)
            if (strlen($filename) && $filename[0] == '.') {
                return false;
            }
            return true;
        });

        /** @var \SplFileInfo[] $listing */
        $listing = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($listing as $item) {
            $path = $item->getPathname();
            $item->isDir() ? rmdir($path) : unlink($path);
        }
    }
}
