<?php
namespace axenox\PackageManager\Common\Updater;

use GuzzleHttp\Psr7\ServerRequest;

/**
 * Contains the result of an upload request to the UpdaterFacade
 * 
 * @author thomas.ressel
 *
 */
class UploadedRelease
{
    private $request = null;
    
    private $timeStamp = null;
    
    private $uploadedFiles = null;
    
    private $uploadPath = null;
    
    /**
     * 
     * @param ServerRequest $request
     */
    public function __construct(ServerRequest $request, string $uploadPath)
    {
        $this->request = $request;
        $this->timeStamp = time();
        $this->uploadPath = $uploadPath;
    }
    
    /**
     * Moves uploaded files to folder and sets upload-Status
     */
    public function moveUploadedFiles()
    {
        $this->uploadedFiles = $this->request->getUploadedFiles();
        foreach($this->request->getUploadedFiles() as $uploadedFile) {
            /* @var $uploadedFile \GuzzleHttp\Psr7\UploadedFile */
            $fileName = $uploadedFile->getClientFilename();
            $uploadedFile->moveTo($this->uploadPath . $fileName);
            $uploadedFile->UploadSuccess = $uploadedFile->isMoved();
        }
    }
    
    /**
     *
     * @return string
     */
    public function getPathAbsolute() : string
    {
        return $this->uploadPath . $this->getInstallationFileName();
    }
    
    /**
     * Placeholder
     * @return string
     */
    public function getInstallationFileName() : string
    {
        return $this->getUploadedFiles()['file1']->getClientFilename();
    }

    /**
     *
     * @return string
     */
    public function getFormatedStatusMessage(bool $success) : string
    {
        return $success === true  ? "Success" : "Failure";
    }
    
    /**
     * 
     * @return array
     */
    public function getUploadedFiles() : array
    {
        return $this->uploadedFiles;
    }
}