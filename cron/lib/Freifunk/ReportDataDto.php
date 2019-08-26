<?php
/**
 * Created by IntelliJ IDEA.
 * User: christian
 * Date: 25.08.19
 * Time: 11:55
 */

namespace Freifunk;

/**
 * Class ReportDataDto
 *
 * This data transfer object is to store report results in memory and to transfer it to the template.
 *
 * @package Freifunk
 */
class ReportDataDto
{
    public $citiesTotal = 0;
    public $endpointsTotal = 0;
    public $endpointsSecure = 0;
    public $endpointsInsecure = 0;
    public $endpointsShared = 0;
    public $endpointsNotShared = 0;
    public $redirects301 = 0;
    public $redirects302 = 0;
    public $responseOk = 0;
    public $responseBadFinals = array();
    public $responsesEmpty = 0;
    public $responsesNotEmpty = 0;
    public $responseSame = 0;
    public $responseUnique = 0;
    public $responseInvalidFormat = 0;
    public $responseValidFormat = 0;
    public $apiVersions = array();
    public $connectionIssues = array();
}