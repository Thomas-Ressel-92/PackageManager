<?php
namespace axenox\PackageManager\Facades;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade;
use exface\Core\DataTypes\StringDataType;
use GuzzleHttp\Psr7\Response;
use exface\Core\Facades\AbstractHttpFacade\Middleware\AuthenticationMiddleware;
use axenox\PackageManager\Common\Updater\UploadedRelease;
use axenox\PackageManager\Common\Updater\ReleaseLog;
use axenox\PackageManager\Common\Updater\SelfUpdateInstaller;
use axenox\PackageManager\Common\Updater\ReleaseLogEntry;
use axenox\PackageManager\Common\Updater\ZipFile;

/**
 * HTTP facade to allow remote updates (deployment) on this server
 * 
 * @author Thomas Ressel
 *
 */
class UpdaterFacade extends AbstractHttpFacade
{
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::createResponse()
     */
    protected function createResponse(ServerRequestInterface $request) : ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $pathInFacade = mb_strtolower(StringDataType::substringAfter($path, $this->getUrlRouteDefault() . '/'));
        
        switch (true) {
            
            case $pathInFacade === 'upload-file':
                
                // Move uploaded files to uploadPath
                $uploadPath = __DIR__ . '/../../../../Upload/';
                $uploader = new UploadedRelease($request, $uploadPath);
                $uploader->moveUploadedFiles();
                // fill logFile with information about the upload
                $releaseLog = new ReleaseLog($this->getWorkbench());
                $releaseLogEntry = new ReleaseLogEntry($releaseLog);
                $releaseLogEntry->addUpload($uploader);
                
                foreach ($releaseLogEntry->getCurrentLogText() as $output) {
                    $releaseLogEntry->addUpdaterOutput($output);
                }
                
                // install
                $selfUpdateInstaller = new SelfUpdateInstaller($uploader->getPathAbsolute(), $this->getWorkbench()->filemanager()->getPathToCacheFolder());
                
                foreach ($selfUpdateInstaller->install() as $output) {
                    $releaseLogEntry->addUpdaterOutput($output);
                }
                
                // fills logFile with information about the installation
                $releaseLogEntry->addInstallation($selfUpdateInstaller);
                
                // save entry in file & in $releaseLog->CurrentEntry
                $releaseLog->saveEntry($releaseLogEntry);
                
                // update release file if installation was successful
                if($selfUpdateInstaller->getInstallationSuccess()) {
                    $releaseLogEntry->addDeploymentSuccess($selfUpdateInstaller->getTimestamp(), $uploader->getInstallationFileName());
                }

                $headers = ['Content-Type' => 'text/plain-stream'];
                return new Response(200, $headers, $releaseLog->getCurrentEntry());

            case $pathInFacade === 'status':
                $releaseLog = new ReleaseLog($this->getWorkbench());
                $output = "Last Deployment: " . $releaseLog->getLatestDeployment() . PHP_EOL. PHP_EOL;
                $output .= $releaseLog->getLatestLog();
                $headers = ['Content-Type' => 'text/plain-stream'];
                return new Response(200, $headers, $output);
                
            // Shows log-entries for all uploaded files
            case $pathInFacade === 'log':
                // Gets log-entries for all uploaded files as Json
                $releaseLog = new ReleaseLog($this->getWorkbench());
                $headers = ['Content-Type' => 'application/json'];
                return new Response(200, $headers, json_encode($releaseLog->getLogEntries(), JSON_PRETTY_PRINT));

            // Search for pathInFacade in log-directory
            default:
                $releaseLog = new ReleaseLog($this->getWorkbench());
                if($releaseLog->getLogContent($pathInFacade) !== null) {
                    $headers = ['Content-Type' => 'text/plain-stream'];
                    return new Response(200, $headers, $releaseLog->getLogContent($pathInFacade));
                }
        }
        return new Response(404);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::getMiddleware()
     */
    protected function getMiddleware() : array
    {
        return array_merge(parent::getMiddleware(), [
            new AuthenticationMiddleware($this, [
                [
                    AuthenticationMiddleware::class, 'extractBasicHttpAuthToken'
                ]
            ])
        ]);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Facades\AbstractHttpFacade\AbstractHttpFacade::getUrlRouteDefault()
     */
    public function getUrlRouteDefault(): string
    {
        return 'api/updater';
    }
    
    protected function printLineDelimiter() : string
    {
        return PHP_EOL . '--------------------------------' . PHP_EOL . PHP_EOL;
    }
}