<?php
/**
 * Created by IntelliJ IDEA.
 * User: christian
 * Date: 01.09.19
 * Time: 12:29
 */

namespace Freifunk;
use Opis\JsonSchema\{
    Validator, ValidationResult, ValidationError, Schema
};

use Rs\Json\Pointer\ {
    InvalidJsonException, NonexistentValueReferencedException
};

/**
 * Class RegistryAnalyser
 * @package Freifunk
 */
class RegistryAnalyser
{
    /** @var  \Freifunk\CommunityCrawler */
    protected $_scraper;

    protected $_redundantEndpoints;
    protected $_insecureEndpoints;
    protected $_unexpectedProtocol;
    protected $_showHints = false;
    protected $_apiVersions = array();
    protected $_notJson = array();
    protected $_invalidJson = array();
    protected $_hashedReponses = array();
    protected $_reportData;
    /**
     * @var TemplateHelper
     */
    protected $_templateHelper;
    protected $_errors;

    /**
     * RegistryAnalyser constructor.
     *
     * @param CommunityCrawler $scraper
     */
    public function __construct(\Freifunk\CommunityCrawler $scraper)
    {
        $this->_scraper = $scraper;
        $this->_reportData = new \Freifunk\ReportDataDto();
        $this->_templateHelper = new \Freifunk\TemplateHelper();
        $this->getReport()->registryDownloadable = $this->_scraper->_registryDownloadable;
        $this->getReport()->registryInvalid = $this->_scraper->_registryInvalidJson;
        $this->_errors = array();
    }

    /**
     * Returns the report (data transfer object) for e.g. templating
     *
     * @return ReportDataDto
     */
    public function getReport() {
        return $this->_reportData;
    }

    /**
     * By default hints (messages) are disabled. To get more help on how to fix the reported issues just enable the hints.
     */
    public function enableHints() {
        $this->_showHints = true;
    }

    public function run() {
        $this->findDuplicatedEndpoints();
        $this->findInsecureEndpoints();
        $this->generateMetrics();
    }

    /**
     * Finds endpoints that are used multiple times (on multiple cities)
     */
    protected function findDuplicatedEndpoints(){
        $this->_redundantEndpoints = array();
        $countedEndpoints = array_count_values($this->_scraper->getRegistryArray());

        foreach ($countedEndpoints as $endpoint => $nbr) {
            if ($nbr > 1) {
                $this->recordCitiesForThisEndpoint($endpoint);
            }
        }
    }

    /**
     * Records all cities that are using a specific endpoint
     *
     * @param $endpoint
     */
    protected function recordCitiesForThisEndpoint($endpoint) {
        foreach ($this->_scraper->getRegistryArray() as $city => $originalEndpoint) {
            if ($originalEndpoint == $endpoint) {
                $this->_redundantEndpoints[$endpoint][] = $city;
            }
        }
    }

    /**
     * Prints a detailed error report to CLI console.
     */
    public function printReport() {
        echo PHP_EOL;
        echo "REPORT:".PHP_EOL;
        $this->generateReportOnConnections();
        $this->printDetailedReport();
    }

    //TODO: split analysis from report rendering
    public function generateReportOnConnections() {

        foreach ($this->_redundantEndpoints as $redundantEndpoint => $cities) {
            echo "ERROR: Multiple cities (".implode(', ', $cities).") share the same endpoint / community definition: ".$redundantEndpoint.PHP_EOL;

            if ($this->_showHints) {
                echo "HINT: Each city should have a dedicated local user group (community). It should not share the community definition with another city. Use the meta community field to link multiple multiple local user groups / cities to a meta community.".PHP_EOL;
            }

        }

        foreach ($this->_unexpectedProtocol as $city => $endpoint) {
            echo "ERROR: City (".$city.") is using a not supported protocol.".PHP_EOL;
            if ($this->_showHints) {
                echo "HINT: Please use HTTPS or at least HTTP.".PHP_EOL;
            }
        }

        foreach ($this->_insecureEndpoints as $city => $endpoint) {
            echo "WARNING: City (".$city.") is using insecure protocol (HTTP).".PHP_EOL;
            if ($this->_showHints) {
                echo "HINT: Please change endpoint to HTTPS. Host your files at github for free if you have no HTTPS endpoints.".PHP_EOL;
            }
        }

        $failures = $this->_scraper->getEndpointFailures();
        $connectionIssue = $failures["connectionIssue"];
        unset($failures);
        $operationFailed = $connectionIssue["operation failed"];
        unset($connectionIssue["operation failed"]);
        $connectionTimedOut = $connectionIssue["Connection timed out"];
        unset($connectionIssue["Connection timed out"]);
        $phpNetworkGetAddresses = $connectionIssue["php_network_getaddresses"];
        unset($connectionIssue["php_network_getaddresses"]);
        $connectionRefused = $connectionIssue["Connection refused"];
        unset($connectionIssue["Connection refused"]);

        #TODO: print now whats left in $connectionIssue as ERROR


        # operation failed
        foreach ($operationFailed as $errorsForThisEndpoint) {
            $cleanMessages = array();
            foreach ($errorsForThisEndpoint as $oneError) {
                $oneCleanMessage = str_replace("file_get_contents(", "", $oneError["message"]);
                $oneCleanMessage = str_replace("):", "", $oneCleanMessage);
                $oneCleanMessage = str_replace("failed to open stream:", "", $oneCleanMessage);
                $cleanMessages[] = $oneCleanMessage;
            }
            echo "ERROR: Operation failed. Most often a SSL/TLS issue. Error text is: ".implode('; ', $cleanMessages).PHP_EOL;
        }

        # Connection times out
        foreach ($connectionTimedOut as $errorsForThisEndpoint) {
            $cleanMessages = array();
            foreach ($errorsForThisEndpoint as $oneError) {
                $oneCleanMessage = str_replace("file_get_contents(", "", $oneError["message"]);
                $oneCleanMessage = str_replace("): ", "", $oneCleanMessage);
                $oneCleanMessage = str_replace(" failed to open stream:", "", $oneCleanMessage);
                $cleanMessages[] = $oneCleanMessage;
            }
            echo "ERROR: Got no response within 5 seconds. Remote system is taking to long to send something. Error text is: ".implode('; ', $cleanMessages).PHP_EOL;
        }

        # php Network Get Addresses
        foreach ($phpNetworkGetAddresses as $errorsForThisEndpoint) {
            $cleanMessages = array();
            foreach ($errorsForThisEndpoint as $oneError) {
                $oneCleanMessage = str_replace("file_get_contents(", "", $oneError["message"]);
                $oneCleanMessage = str_replace("): ", "", $oneCleanMessage);
                $oneCleanMessage = str_replace(" failed to open stream:", "", $oneCleanMessage);
                $cleanMessages[] = $oneCleanMessage;
            }
            echo "ERROR: Could not resolve IP address for this endpoint. Error text is: ".implode('; ', $cleanMessages).PHP_EOL;
        }

        # connection refused
        foreach ($connectionRefused as $errorsForThisEndpoint) {
            $cleanMessages = array();
            foreach ($errorsForThisEndpoint as $oneError) {
                $oneCleanMessage = str_replace("file_get_contents(", "", $oneError["message"]);
                $oneCleanMessage = str_replace("):", "", $oneCleanMessage);
                $oneCleanMessage = str_replace(" failed to open stream:", "", $oneCleanMessage);
                $cleanMessages[] = $oneCleanMessage;
            }
            echo "ERROR: A firewall is blocking or no process at remote system is listening at this port. Error text is: ".implode('; ', $cleanMessages).PHP_EOL;
        }


        if (!empty($connectionIssue)) {
            echo "IMPORTANT: There are more error types then listed above. Right now not supported error types: ".implode(', ', array_keys($connectionIssue)).PHP_EOL;
        }

    }

    //TODO: use a template file to render these results (CLI + HTML)
    protected function printDetailedReport() {
        foreach ($this->_errors as $errorMessage) {
            echo $errorMessage;
        }
    }

    //TODO: refactor / improve this method
    protected function generateResponseErrors() {
        $directory = "temp/communityDefinitions/"; //TODO: inject config
        $directoryEntries = array_diff(scandir($directory), array('.', '..','.gitkeep'));
        if (empty($directoryEntries)) {
            $this->_errors[] = "No files found in: ".$directory.PHP_EOL;
        }
        foreach ($directoryEntries as $directoryEntry) {

            $city = substr($directoryEntry, 0, strlen($directoryEntry) - 5);
            $content = file_get_contents($directory . $directoryEntry);
            if ($content != null) {
                $json = json_decode($content, true);
                $jsonObject = json_decode($content);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->_errors[] = "ERROR: Community definition is not a JSON file. City: " . $city . PHP_EOL;
                    $this->_notJson[] = $city;

                    continue;
                }
            } else {
                $this->_errors[] = "File is empty: " . $directory . $directoryEntry . PHP_EOL;
                continue;
            }

            // hash content
            $this->_hashedReponses[sha1($content)][] = $city;

            //list / count versions
            $this->_apiVersions[$json["api"]][] = $directoryEntry;

            //check if a schema file for this api version exists locally
            //TODO: move path to config and inject
            $schemaFile = "jsonSchema/" . $json["api"] . ".json";
            if (!file_exists($schemaFile)) {
                $this->_errors[] = "ERROR: Referenced API version is unknown, or we have no schema for it. Version: " . $json["api"] . "; City: " . $city . PHP_EOL;
                $this->_errors[] = "HINT: You may want to checkout https://github.com/freifunk/api.freifunk.net/tree/master/specs and ask the admin to mirror that json schema for the report generator." . PHP_EOL;
                $this->_invalidJson["schemaMissing"][] = $city;
                continue;
            }
            //TODO: download from github if not, fail if not existing on github, save locally if download was successful

            #$schema = Schema::fromJsonString(file_get_contents("jsonSchema/".$json["api"].".json"));
            $myFile = file_get_contents("jsonSchema/" . $json["api"] . ".json");
            $myJson = json_decode($myFile, true);

            $fixedSchema = json_encode($myJson["schema"]);
            file_put_contents("jsonSchemaFixed/" . $json["api"] . ".json", $fixedSchema);
//TODO: convert all currently not activly used schemas to ...Fixed

            // Validate
            $validator = new \JsonSchema\Validator;
            $validator->validate($jsonObject, (object)['$ref' => 'file://'.realpath("jsonSchemaFixed/" . $json["api"] . ".json")]);

            if ($validator->isValid()) {
                $this->_errors[] = "Community definition (JSON) for '".$city."' is valid (valid against schema).".PHP_EOL;
            } else {

                $this->_errors[] = "ERROR: Community definition (JSON) of '".$city."' is invalid. Violations:".PHP_EOL;
//[0-9]+\.[0-9]+\.[0-9]+.json
                $this->_invalidJson["nbrInvalidFiles"] = $this->_invalidJson["nbrInvalidFiles"]+1;

                /*
                 * get file list:
                 * curl -L https://api.github.com/repos/freifunk/api.freifunk.net/contents/specs > list.json
                 *
                 * get only files that have a version number followed by ".json"
                 * cat list.json | jq -r '.[] | select(.name|test("^([0-9]*.){0,1}[0-9]+.[0-9]+.json$")) | .download_url'
                 */
                $jsonPointer = new \Rs\Json\Pointer($content);
                foreach ($validator->getErrors() as $error) {
                    $jsonValue = null;
                    try {
                        $jsonValue = $jsonPointer->get($error["pointer"]);
                    } catch (NonexistentValueReferencedException $e) {
                        //silencing the exception
                    }
                    if (!is_null($jsonValue)) {
                        $this->_invalidJson["invalidData"][$city][] =  "ERROR: Schema violation. Value: ".$jsonValue."; Message: ".$error["message"]."; Pointer: ".$error["pointer"].PHP_EOL;
                    } else {
                        $this->_invalidJson["invalidData"][$city][] = "ERROR: Schema violation. " . $error["message"] . "; Pointer: " . $error["pointer"] . PHP_EOL;
                    }
                }
            }

        }

    }

    /**
     * Checks for insecure (HTTP) and unknown protocols.
     */
    protected function findInsecureEndpoints() {
        $this->_insecureEndpoints = array();
        $this->_unexpectedProtocol = array();

        foreach ($this->_scraper->getRegistryArray() as $city => $endpoint) {
            $prefix = mb_substr($endpoint, 0, 5);
            if (mb_strtolower($prefix) == "http:") {
                $this->_insecureEndpoints[$city] = $endpoint;
                continue;
            }

            if (mb_strtolower($prefix) != "https") {
                $this->_unexpectedProtocol[$city] = $endpoint;
            }
        }
    }

    /**
     * Returns the template helper object.
     *
     * @return TemplateHelper
     */
    public function getTemplateHelper() {
        return $this->_templateHelper;
    }

    /**
     * Converts hard to understand error messages into user-friendly user names.
     *
     * @param $errorName
     * @return string
     */
    protected function generateUserFriendlyErrorNames($errorName) {

        switch ($errorName) {
            case "php_network_getaddresses":
                return "DNS Resolution Issue";
                break;
            case "php_network_getaddresses":
                return "DNS Resolution Issue";
                break;
            case "HTTP request failed! HTTP/1.1 400 Bad Request":
                return "Bad Request";
                break;
            case "operation failed":
                return "Operation failed";
                break;
        }

        return $errorName;
    }

    /**
     * Calculates all metrics
     */
    protected function generateMetrics() {
        $this->getReport()->nbrCities =    count(array_keys($this->_scraper->getRegistryArray()));
        $this->getReport()->nbrEndpoints = count(array_keys(array_count_values($this->_scraper->getRegistryArray())));
        $this->getReport()->endpointsInsecure = count(array_keys($this->_insecureEndpoints));
        $this->getReport()->endpointsSecure = $this->getReport()->nbrEndpoints - $this->getReport()->endpointsInsecure;

        $this->getReport()->endpointsShared = count(array_keys($this->_redundantEndpoints));
        $this->getReport()->endpointsNotShared = $this->getReport()->nbrEndpoints - $this->getReport()->endpointsShared;

        $failures = $this->_scraper->getEndpointFailures();
        $this->getReport()->redirects301 = count($failures[301]);
        $this->getReport()->redirects302 = count($failures[302]);
        $this->getReport()->responseOk = count($failures[200]);

        $this->getReport()->responsesEmpty = count($this->getEmptyResponses());
        $this->getReport()->responsesEmptyList = $this->getEmptyResponses();

        foreach ($this->getUserfiendlyConnectionIssues() as $shortName => $affectedUrls) {
            $this->getReport()->connectionIssues[$shortName] = count($affectedUrls);
            $this->getReport()->endpointConnectionFailed += count($affectedUrls);
        }

        $this->getReport()->responseTotalFinals = 0;
        foreach ($this->getAllOtherFailures() as $httpStatusCode => $nbrAffectedUrls) {
            $this->getReport()->responseTotalFinals += count($nbrAffectedUrls);
            if ($httpStatusCode != 200) {
                $this->getReport()->responseNotOk += count($nbrAffectedUrls);
                $this->getReport()->responseBadFinals[$httpStatusCode] = count($nbrAffectedUrls);
            }
        }
        $this->getReport()->responseBadFinals['other issues'] = $this->getReport()->responseNotOk;
        $this->getReport()->responsesNotEmpty = $this->getReport()->nbrEndpoints - $this->getReport()->responsesEmpty;
        $this->getReport()->responseInvalidFormat = count($this->_invalidJson["invalidData"]);
        $this->getReport()->responseValidFormat = (count(array_keys($this->_scraper->getRegistryArray())) - $this->getNbrOfFailedSchemaValidations());
        $this->generateResponseErrors();
        foreach ($this->_apiVersions as $version => $entries) {
            $this->getReport()->apiVersions[$version] = count($entries);
        }

        $this->getReport()->responseInvalidFormat = count($this->_notJson);

        foreach ($this->_hashedReponses as $cities) {
            $nbrCities = count($cities);
            $this->getReport()->endpointsConnectionNotFailed += $nbrCities;
            if ($nbrCities > 1) {
                $this->getReport()->responseSame += $nbrCities;
                $this->getReport()->responseSameCities[] = $cities;
            }
        }
        $this->getReport()->responseUnique = ($this->getReport()->endpointsConnectionNotFailed - $this->getReport()->responseSame);
    }

    /**
     * Returns an array of endpoints where the response was empty.
     *
     * @return array
     */
    protected function getEmptyResponses() {
        $failures = $this->_scraper->getEndpointFailures();
        return $failures["emptyResponses"];
    }

    /**
     * Returns an array of response headers of endpoints where the response was empty.
     *
     * @return array
     */
    protected function getEmptyResponseHeaders() {
        $failures = $this->_scraper->getEndpointFailures();
        return $failures["emptyResponsesHeaders"];
    }

    /**
     * Returns an array of endpoints where a communication error occurred.
     *
     * @return array
     */
    protected function getConnectionIssues() {
        $failures = $this->_scraper->getEndpointFailures();
        return $failures["connectionIssue"];
    }

    /**
     * Returns an array of endpoints where no communication error occured and where response was not empty.
     *
     * @return array
     */
    protected function getAllOtherFailures() {
        $failures = $this->_scraper->getEndpointFailures();
        unset($failures["connectionIssue"]);
        unset($failures["emptyResponses"]);
        unset($failures["emptyResponsesHeaders"]);
        unset($failures[301]);
        unset($failures[302]);
        return $failures;
    }

    /**
     * Returns an array of endpoints with connection problems.
     * Problem messages are converted to user-friendly messages.
     *
     * @return array
     */
    protected function getUserfiendlyConnectionIssues() {
        $temp = array();
        foreach ($this->getConnectionIssues() as $shortName => $affectedUrls) {
            $temp[$this->generateUserFriendlyErrorNames($shortName)] = $affectedUrls;
        }
        return $temp;
    }

    /**
     * Returns an array of endpoints where the response was not valid against Freifunk JSON schema.
     *
     * @return int
     */
    protected function getNbrOfFailedSchemaValidations() {
        return count($this->_invalidJson["invalidData"], COUNT_RECURSIVE) - count($this->_invalidJson["invalidData"]);
    }

    /**
     * Outputs statistics to CLI console.
     */
    public function printStatistics() {
        include "template/cli.php";
    }

    /**
     * Renders the HTML templates and stores HTML page on disk.
     */
    //TODO: Detailed error report (messages) is not jet part of the HTML output. Add it!
    public function generateHTML() {
        ob_start();
        include "template/index.php";
        $fileContent = ob_get_clean();
        file_put_contents("htmlReport/index.html", $fileContent);
    }

}