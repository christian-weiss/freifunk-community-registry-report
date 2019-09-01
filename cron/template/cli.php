
STATISTICS:<?php echo PHP_EOL ?>
Cities              : <?php echo $this->getReport()->nbrCities ?><?php echo PHP_EOL ?>
Endpoints           : <?php echo $this->getReport()->nbrEndpoints ?><?php echo PHP_EOL ?>
Endpoints (insecure): <?php echo $this->getReport()->endpointsInsecure ?><?php echo PHP_EOL ?>
Endpoints (secure)  : <?php echo $this->getReport()->endpointsSecure ?><?php echo PHP_EOL ?>

SHARED Endpoints  : <?php echo PHP_EOL ?>
Endpoints (shared): <?php echo $this->getReport()->endpointsShared ?><?php echo PHP_EOL ?>
Endpoints (unique): <?php echo $this->getReport()->endpointsNotShared ?><?php echo PHP_EOL ?>

CONNECTION ISSUES: <?php echo PHP_EOL ?>
<?php foreach ($this->getReport()->connectionIssues as $shortName => $nbrAffectedUrls) { ?>
- <?php echo $shortName ?> :  <?php echo $nbrAffectedUrls ?><?php echo PHP_EOL ?>
<?php } ?>
Total: <?php echo $this->getReport()->endpointConnectionFailed ?><?php echo PHP_EOL ?>

HTTP STATUS WARNING:<?php echo PHP_EOL ?>
WARNING: HTTP Status Code 301: <?php echo $this->getReport()->redirects301 ?> <?php echo PHP_EOL ?>
WARNING: HTTP Status Code 302: <?php echo $this->getReport()->redirects302 ?><?php echo PHP_EOL ?>

FINAL HTTP STATUS CODES: <?php echo PHP_EOL ?>
<?php foreach ($this->getAllOtherFailures() as $httpStatusCode => $nbrAffectedUrls) { ?>
- Code <?php echo $httpStatusCode ?> :  <?php echo count($nbrAffectedUrls) ?><?php echo PHP_EOL ?>
<?php } ?>
Status Problems: <?php echo $this->getReport()->responseNotOk ?><?php echo PHP_EOL ?>
Total: <?php echo count($this->getAllOtherFailures(), COUNT_RECURSIVE) - count($this->getAllOtherFailures()) ?> <?php echo PHP_EOL ?>

EMPTY RESPONSES:<?php echo PHP_EOL ?>
<?php foreach ($this->getReport()->responsesEmptyList as $city => $endpoint) { ?>
City '<?php echo $city ?>': <?php echo $endpoint ?><?php echo PHP_EOL ?>
<?php } ?>
Empty:     <?php echo $this->getReport()->responsesEmpty ?><?php echo PHP_EOL ?>
Not empty: <?php echo ($this->getReport()->nbrEndpoints - $this->getReport()->responsesEmpty) ?><?php echo PHP_EOL ?>
Total:     <?php echo $this->getReport()->nbrEndpoints ?><?php echo PHP_EOL ?>

INVALID JSON: <?php echo PHP_EOL ?>
<?php foreach ($this->_invalidJson["invalidData"] as $city => $nbrError) { ?>
City '<?php echo $city ?>': <?php echo count($nbrError) ?><?php echo PHP_EOL ?>
<?php } ?>
Total: <?php echo $this->getNbrOfInvalidCities() ?><?php echo PHP_EOL ?>

API VERIONS: <?php echo PHP_EOL ?>
<?php foreach ($this->getReport()->apiVersions as $version => $nbrCommunities) { ?>
Version <?php echo $version ?>: <?php echo $nbrCommunities ?><?php echo PHP_EOL ?>
<?php } ?>
Total: <?php echo $this->getReport()->endpointsConnectionNotFailed ?><?php echo PHP_EOL ?>
Not JSON: <?php echo $this->getReport()->responseInvalidFormat ?><?php echo PHP_EOL ?>
Status Problems: <?php echo $this->getReport()->responseNotOk ?><?php echo PHP_EOL ?>
Connection Issue:<?php echo $this->getReport()->endpointConnectionFailed ?><?php echo PHP_EOL ?>

SAME RESPONSES:<?php echo PHP_EOL ?>
<?php foreach ($this->getReport()->responseSameCities as $cities) { ?>
Same respone in following cities: <?php echo implode(", ", $cities) ?><?php echo PHP_EOL ?>
<?php } ?>
Same Responses:   <?php echo $this->getReport()->responseSame ?><?php echo PHP_EOL ?>
Unique Responses: <?php echo $this->getReport()->responseUnique ?><?php echo PHP_EOL ?>
Total: <?php echo $this->getReport()->endpointsConnectionNotFailed ?><?php echo PHP_EOL ?>