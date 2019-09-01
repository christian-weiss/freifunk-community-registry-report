<?php
/**
 * Created by IntelliJ IDEA.
 * User: christian
 * Date: 01.09.19
 * Time: 12:32
 */

namespace Freifunk;

// stores all errors in global scope (used by custom error handler)
//TODO: refactor to a static class method / static class var (instead of global scope)
global $customError;
$customError = array();

/**
 * Custom error handler, to catch file_get_content() output before it is written to the buffer.
 * Enables us to render theses error messages to the output at a later point in time.
 *
 * @param $errorType
 * @param $errorMessage
 * @param $errorFile
 * @param $errorLine
 * @return bool
 */
function myErrorHandler($errorType, $errorMessage, $errorFile, $errorLine) {
    global $customError;

    #keep it in the same format as in the return value of error_get_last()
    $customError[] = array(
        'type' => $errorType,
        'message' => $errorMessage,
        'file' => $errorFile,
        'line' => $errorLine

    );

    @trigger_error($errorMessage);
    return true;
}
set_error_handler("\Freifunk\myErrorHandler");

/**
 * Downloads the community registry file (aka community directory) and crawls all referenced community definitions
 * (aka community API files). Allows a dry-run, where data is read from file-based cache.
 *
 * Class CommunityCrawler
 * @package Freifunk
 */
class CommunityCrawler
{
    protected $_directories;
    protected $_communityRegistryUrl;
    protected $_registryArray;
    protected $_endpointFailures;
    protected $_saveToFile;
    public $_registryDownloadable;
    public $_registryInvalidJson;

    /**
     * CommunityCrawler constructor.
     *
     * @param $dirs
     */
    public function __construct($dirs) {
        if (is_array($dirs)) {
            $this->_directories = $dirs;
            $this->_saveToFile = true;
        } else {
            $this->_directories = null;
            $this->_saveToFile = false;
        }
        $this->_registryDownloadable = true;
        $this->_registryInvalidJson = false;
    }

    /**
     * Set URL of community registry (community directory)
     *
     * @param $url
     */
    public function setCommunityRegistryUrl($url) {
        $this->_communityRegistryUrl = $url;
    }

    /**
     * Crawls all endpoints and stores them on disk.
     */
    public function run() {
        $this->cleanupFilesystem();
        $this->downloadCommunityRegistry();
        $this->downloadAllCommunityDefinitions();
        $this->saveCommunicationDetails();
    }

    /**
     * Dry-run do not crawl endpoints on the internet, it just loads, what it has downloaded on last real-run.
     */
    public function dryRun() {
        $this->loadCommunityRegistry();
        $this->loadCommunicationDetails();
    }

    /**
     * Loads community registry from disk.
     * Files on disk where created by a previous execution of run()
     */
    public function loadCommunityRegistry() {
        $content = file_get_contents($this->_directories["communityRegistry"]);
        $this->_registryArray = json_decode($content, true);
    }

    /**
     * Loads communication details from disk.
     * File on disk where created by a previous execution of run()
     */
    protected function loadCommunicationDetails() {
        $content = file_get_contents($this->_directories["communicationDetails"]);
        $this->_endpointFailures = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Unable to decode JSON from: ".$this->_directories["communicationDetails"]."; error: ".json_last_error_msg());
            exit(1);
        }
        $this->log("Communication details were loaded from ".$this->_directories["communicationDetails"].".");
    }

    /**
     * Caches the communication details on disk for later usage.
     * E.g. if you want to post-process it with dryRun() on development or with other tools.
     */
    protected function saveCommunicationDetails() {
        $communicationDetails = json_encode($this->_endpointFailures);
        file_put_contents($this->_directories["communicationDetails"], $communicationDetails);
        $this->log("Communication details were saved to ".$this->_directories["communicationDetails"].".");
    }

    /**
     * Removes all cached data from disk.
     */
    protected function cleanupFilesystem() {
        $this->deleteFile($this->_directories["communication.log"]);
        $this->deleteFile($this->_directories["communicationDetails"]);
        $this->deleteFile($this->_directories["communityRegistry"]);
        $this->deleteDirector($this->_directories["communityDefinitionsDir"]);
        $this->deleteDirector($this->_directories["nodeListDir"]);
    }

    /**
     * Returns an array that represents a community registry (community directory).
     *
     * @return array|null
     */
    public function getRegistryArray() {
        return $this->_registryArray;
    }

    /**
     * Returns an array of failures that happend when accessing the endpoints.
     *
     * @return array
     */
    public function getEndpointFailures() {
        return $this->_endpointFailures;
    }

    /**
     * Downloads the Freifunk Community Registry file (aka community directory) and parses the json data.
     */
    protected function downloadCommunityRegistry() {

        try {
            $fileContents = file_get_contents($this->_communityRegistryUrl);
            if ($fileContents === false) {
                $this->_registryDownloadable = false;
                $this->_registryArray = null;
                return;
            }

        } catch (\Exception $e) {
            $this->_registryDownloadable = false;
            $this->_registryArray = null;
            return;
        }



        $this->_registryArray = json_decode($fileContents,true);
        if ($this->_registryArray === null) {
            $this->_registryInvalidJson = true;
            return;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->_registryInvalidJson = true;
        }

        if ($this->_saveToFile) {
            file_put_contents($this->_directories["communityRegistry"], $fileContents);
            $this->log("Community registry file was saved to ".$this->_directories["communityRegistry"]);
        }
    }

    /**
     * Disables saving of files. No cache will be created.
     */
    public function doNotSaveFiles() {
        $this->_saveToFile = false;
    }

    /**
     * Crawls all community definitions (community API files) which are referenced in community registry (community directory)
     */
    protected function downloadAllCommunityDefinitions() {
        $httpStatusCodePosition = 1;
        $this->_endpointFailures = array();
        global $customError;

        $streamOptions = array(
            'http'=> array(
                'timeout' => 5,
                ),
            'https'=> array(
                'timeout' => 5,
                )
            );
        $doNotUseIncludePath = false;
        $streamContext = stream_context_create($streamOptions);

        foreach ($this->_registryArray as $city => $endpoint) {
            $this->log("Load community definition for '".$city."' from: ".$endpoint);

            # ensure all vars are clean
            $http_response_header = array();
            $error = null;
            $customError = array();

            # load content
            if (!$content = @file_get_contents($endpoint, $doNotUseIncludePath, $streamContext)) {
                $error = error_get_last();

                $messageElements = explode(":", $error["message"]);
                $cleanMessage = ltrim(str_replace(array("\r", "\n"), '', $messageElements[3]));

                switch ($cleanMessage) {
                    case 'HTTP request failed! HTTP/1.1 503 Service Temporarily Unavailable':
                    case 'HTTP request failed! HTTP/1.1 404 Not Found':
                    case 'HTTP request failed! HTTP/1.1 400 Bad Request':
                        # silencing this error, as it will be tracked by http status code
                        break;
                    default:
                        $this->_endpointFailures["connectionIssue"][$cleanMessage][] = $customError;
                }
            }

            if (empty($http_response_header) && is_null($error)) {
                $this->log("ERROR: No response header, but there was a successful connection. Strange!");
            }

            #search for HTTP lines and get the HTTP STATUS CODE
            $httpStatusCode = null;
            foreach ($http_response_header as $line) {
                $firstPartOfString = mb_substr($line, 0, 5);
                if (mb_strtoupper($firstPartOfString) == "HTTP/") {

                    $elements = explode(" ",$line);
                    $httpStatusCode = $elements[$httpStatusCodePosition];
                    $this->_endpointFailures[$httpStatusCode][] = $endpoint;
                }
            }

            /**
             * Some responses are empty by design, e.g. on a timeout or on a certificate issue (of endpoint or it's redirection target).
             * For that reason we track only empty message on HTTP status code 200.
             */
            if (empty($content) && $httpStatusCode == 200) {

                $this->_endpointFailures["emptyResponsesHeaders"][$city] = $http_response_header;
                $this->_endpointFailures["emptyResponses"][$city."_".$httpStatusCode] = $endpoint;
                $this->log("Response for ".$city." is empty");
            }

            if ($this->_saveToFile && !empty($content)) {
                file_put_contents($this->_directories["communityDefinitionsDir"].$city.".json", $content);
                $this->log("Response for '".$city."' was saved to ".$this->_directories["communityDefinitionsDir"].$city.".json.");
            }

        }
    }

    /**
     * Writes a line to a log file and to the console.
     *
     * @param $data
     */
    protected function log($data) {
        $time = date('Y-m-d h:i:s a', time());
        $line = $time." ".$data.PHP_EOL;
        echo $line;
        file_put_contents($this->_directories["communication.log"], $line,FILE_APPEND);
    }

    /**
     * Deletes a file from disk.
     *
     * @param $file
     */
    protected function deleteFile($file) {
        if (file_exists($file)) {
            unlink($file);
            $this->log("Deleted the file ".$file);
        } else {
            $this->log("Could not delete file ".$file.". File not found.");
        }
    }

    /**
     * Deletes all files within a directory from disk.
     *
     * @param $directory
     */
    protected function deleteDirector($directory) {
        $this->log("Starting to clean-up directory: ".$directory);
        $directoryEntries = array_diff(scandir($directory), array('.', '..', '.gitkeep'));
        if (empty($directoryEntries)) {
            $this->log("No files found in: ".$directory);
        }
        foreach ($directoryEntries as $directoryEntry) {
            $this->deleteFile($directory.$directoryEntry);
        }

    }

    /**
     * Deletes communication log file from disk.
     */
    protected function deleteCommunicationLog() {
        if (file_exists($this->_directories["communication.log"])) {
            unlink($this->_directories["communication.log"]);
            $this->log("Deleted the old log file.");
        }
    }

    /**
     * Parses HTTP headers and returns an array of key/value pairs.
     *
     * @param $headers
     * @return array
     */
    protected function parseHeaders( $headers )
    {
        $head = array();
        foreach ($headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1]))
                $head[trim($t[0])] = trim($t[1]);
            else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out))
                    $head['reponse_code'] = intval($out[1]);
            }
        }
        return $head;
    }
}