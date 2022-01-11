<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CreateJsonFileService
{
    const JSON_PATH_FILES = "public/jsonFiles";

    /**
     * Create a JSON file logic
     *
     * @param string $filename
     * @param string $content
     * @return string|void
     */
    public function saveResultAsJsonFile(string $filename, string $content)
    {
        $path_file = self::JSON_PATH_FILES . '/' . $filename;

        $dirIsCreated = $this->createDir(self::JSON_PATH_FILES);

        if($dirIsCreated) {
            $this->saveFile($path_file, $content);
            return $path_file;
        }
    }

    /**
     * Create dir if not exists
     *
     * @param string $dirName
     * @return bool
     */
    private function createDir(string $dirName)
    {
        if(!is_dir($dirName))
        {
            mkdir($dirName, 0775);
        }

        return true;
    }

    /**
     * Save content in targeted file
     *
     * @param string $filename
     * @param string $content
     * @return bool
     */
    private function saveFile(string $filename, string $content)
    {
        try {
            file_put_contents($filename, $content);
            return true;
        } catch (FileException $exception) {
            throw new FileException("Error creating file at " . $filename);
        }
    }
}