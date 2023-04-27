<?php
namespace axenox\PackageManager\Common\Updater;

class ReleaseLogEntry
{    
    private $logsPath = null;
    
    private $releasePath = null;
    
    private $logFileName = null;
    
    private $logArray = null;
    
    private $updaterOutput = "";
    
    /**
     * 
     * @param ReleaseLog $log
     */
    public function __construct(ReleaseLog $log)
    {
        $this->logsPath = $log->getBasePath();
        $this->releasePath = $log->getReleasePath();
    }

    /**
     * 
     * @param string $output
     */
    public function addUpdaterOutput(string $output)
    {
        $log = $this->updaterOutput;
        $log .= $output;
        $this->updaterOutput = $log;
    }
    
    /**
     * 
     * @return string
     */
    public function getUpdaterOutput() : string
    {
        return $this->updaterOutput;
    }

    /**
     * 
     * @return \Generator
     */
    public function getCurrentLogText() : \Generator
    {
        foreach($this->logArray as $logArrayKey => $logElement) {
            if(! is_numeric($logArrayKey)) {
                yield "{$logArrayKey}: {$logElement}" . PHP_EOL;
            } else {
                yield PHP_EOL . "File number: {$logArrayKey}" . PHP_EOL;
                foreach($logElement as $logElementKey => $fileValue) {
                    yield "{$logElementKey}: {$fileValue}" . PHP_EOL;
                }
            }
        }
    }

    /**
     * 
     * @param UpdateDownloader $updateDownloader
     * @param number $fileNumber
     */
    public function addDownload(UpdateDownloader $updateDownloader, $fileNumber = 1)
    {
        $logArray = [];
        $logArray[$fileNumber]['Filename'] = $updateDownloader->getFileName();
        $logArray[$fileNumber]['Filesize'] = $updateDownloader->getFileSize();
        $logArray[$fileNumber]['Download status'] = $updateDownloader->getFormatedStatusMessage();
        $this->logArray = $logArray;
    }
    
    /**
     * 
     * @param UploadedRelease $uploadedRelease
     */
    public function addUpload(UploadedRelease $uploadedRelease, $fileNumber = 1)
    {
        $logArray = [];
        foreach ($uploadedRelease->getUploadedFiles() as $file) {
            $logArray[$fileNumber]['Filename'] = $file->getClientFilename();
            $logArray[$fileNumber]['Filesize'] = $file->getSize();
            $logArray[$fileNumber]['Upload status'] = $uploadedRelease->getFormatedStatusMessage($file->UploadSuccess);
            $fileNumber++;
        }
        $this->logArray = $logArray;
    }

    /**
     * 
     * @param SelfUpdateInstaller $selfUpdateInstaller
     */
    public function addInstallation(SelfUpdateInstaller $selfUpdateInstaller)
    {
        $installArray = [];
        $timeStamp = $this->formatTimeStamp($selfUpdateInstaller->getTimestamp());
        $this->logFileName = $installArray['Logfile name'] = $timeStamp . "_" . $this->getRequestType() . "_" . $selfUpdateInstaller->getFormatedStatusMessage() . ".txt";
        $installArray['Logfile route'] = "log" . DIRECTORY_SEPARATOR . $installArray['Logfile name'];
        $installArray['Installation status'] = $selfUpdateInstaller->getFormatedStatusMessage();
        $installArray['Timestamp'] = $timeStamp;
        $this->logArray = $installArray;
    }
    
    /**
     * 
     * @return string
     */
    protected function getRequestType()
    {
        return array_key_exists('Download status', $this->logArray[1]) === true ? "download" : "upload";
    }
    
    /**
     * 
     * @param string $timeStamp
     * @return string
     */
    protected function formatTimeStamp(string $timeStamp) : string
    {
        return date('Y-m-d_His', $timeStamp);
    }

    /**
     * 
     * @return string
     */
    public function getLogFileName() : string
    {
        return $this->logFileName;
    }
    
    /**
     *
     * @return array
     */
    public function getEntryArray() : array
    {
        return $this->logArray;
    }
    
    /**
     * 
     * @param string $timeStamp
     * @param string $fileName
     */
    public function addDeploymentSuccess(string $timeStamp, string $fileName)
    {
        $date = date('YmdHis', $timeStamp);
        $fileName = trim($fileName, ".phx");
        $newDeployment = PHP_EOL . "{$date},{$fileName}";
        file_put_contents($this->releasePath, $newDeployment, FILE_APPEND);
    }
}