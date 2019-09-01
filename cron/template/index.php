<?php
    /**
     * To enable code completion:
     * @var $this \Freifunk\RegistryAnalyser
     */

    $goodColors = array(
        '#DFF2BF',
        '#caf2a2',
        '#bdf282',
        '#adf239',
        '#c8f216',
        '#86f200',
        '#d6f200',
        '#e1f21a',
        '#e5f23d',
        '#e6f259',
        '#e8f26f',
        '#f0f289',
        '#f2f1b2',
    );
    $badColors = array(
        '#FFD2D2',
        '#FFBABE',
        '#FF9BA0',
        '#FF7A85',
        '#ff6870',
        '#ff495a',
        '#ff2843',
        '#ff0196',
        '#ff00f6',
        '#ff47ba',
        '#ff61b9',
        '#ff91c2',
        '#ffb8e5',
    );

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Freifunk Communiy Registry File - Quality Assurance Report</title>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/d821eed1eb.js"></script>
<style>
    body {
        font-family: "Open Sans";
        margin: 20px;
    }

    .banner {
        background-color: #777777;
        color: whitesmoke;
        width: 100%;
        text-align: center;
        margin-top: 0em;
        line-height: 1.5em;
    }

    .reportbox {
        width: 500px;
        border: solid;
        border-width: 2px;
        border-color: #777777;
        margin-top: 10px;
        margin-bottom: 25px;
        margin-right:10px;
        float: left;
    }

    .report {
        float: left;
        width: 100%;
    }

    .checkmark {
        background-color: #DFF2BF;
        margin: 10px;
        color: #4F8A10;
    }

    .error {
        background-color: #FFD2D2;
        margin: 10px;
        color: #D8000C;
    }

    .hint {
        background-color: #fffae5;
        margin: 10px;
            color: #fc9900;
    }

    .fa-check-circle,
    .fa-times-circle,
    .fa-exclamation-circle {
        margin-right:10px;
        margin-left:10px;

    }

</style>
</head>
<body>
<h1>Freifunk Quality Assurance Report</h1>

<div>Quality Assurance Report for Freifunk Community Registry File. Community Registry is also know as Community Directory.</div>
<div>This report is two-fold. First part is about global community metrics. Second part (at the end of this page) is about each local user group (a city/town). </div>
<div>In this report we use the term 'community' only for worldwide scope (all users) and the term 'local user group' for all users in one city/town.</div>
<div>
    <h2>Usage Tips</h2>
    <div>Hover over a pie diagram piece or tab on it to see its value.</div>
    <div>Click or tab on an entry in the diagram legend to hide or unhide this piece in diagram.</div>
    <div>If entry in diagram legend is strikethrough then it means that this entry has currently a value of zero (0).</div>
    <div>Note: Very low values (compared to the rest) may not be visible at a first glace, as the pie piece is to tiny. Hide big pieces to see the tiny once.</div>
</div>
<div>
    <h2>Alternative</h2>
    <div>As an alternative to this Quality Assurance Report you can use the <a href="https://api-viewer.freifunk.net/index.html">API Viewer</a>.
    It shows the current status of all endpoints, but be warned, currently down endpoints are sometimes not listed as down.
    Instead cached data is listed without special notice.<div>
</div>
<div class="report">
    <div class="reportbox">
        <h2 class="banner">Availability Status</h2>
        <div>
            <?php if ($this->getReport()->registryDownloadable) { ?>
                <div class="checkmark"> <i class="far fa-check-circle checkmark fa-2x"></i> File is available for download.</div>
            <?php  } else { ?>
            <div class="error"> <i class="far fa-times-circle error fa-2x"></i> File is not available for download.</div>
            <?php
            }

            if (!$this->getReport()->registryInvalid) {
            ?>
            <div class="checkmark"> <i class="far fa-check-circle checkmark fa-2x"></i> File is a valid json file. (syntactically correct) </div>
            <?php } else { ?>
            <div class="error"> <i class="far fa-times-circle error fa-2x"></i> File is not a valid json file. </div>
            <?php } ?>
            <div class="hint"> <i class="fas fa-exclamation-circle hint fa-2x"></i> This report was generated at:
                <?php
                $date = new DateTime();
                echo $date->format('Y-m-d H:i:s');
                ?>
                <br>
                <i class="fas fa-exclamation-circle hint fa-2x"></i> Report will be updated every hour. </div>
        </div>

    </div>

    <div class="reportbox">
        <h2 class="banner">Insecure Endpoints:</h2>
        <canvas id="InsecureEndpoints" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>

    </div>

    <div class="reportbox">
        <h2 class="banner">Cities With Shared Endpoint</h2>
        <canvas id="SharedEndpoints" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">Connection Issues</h2>
        <canvas id="ConnectionIssues" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">HTTP Redirects</h2>
        <canvas id="Redirects" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">HTTP Status Codes</h2>
        <canvas id="StatusCodes" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">Endpoints With Empty Response</h2>
        <canvas id="EmptyResponses" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">Endpoints With Same Response</h2>
        <canvas id="SameResponse" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">Invalid Data Format</h2>
        <canvas id="InvalidDataFormat" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>

    <div class="reportbox">
        <h2 class="banner">API Versions</h2>
        <canvas id="ApiVersions" class="chartjs" width="770" height="385" style="display: block; width: 770px; height: 385px;"></canvas>
    </div>
</div>

<div class="report">
    <h1>Detailed Report For Each Community</h1>
</div>

<div class="report">
    <div class="reportbox">
        <h2 class="banner">Community Reports</h2>
        <ul>
            <li><a href="#">Freifunk Hamm</a></li>
            <li><a href="#">Freifunk Dortmund</a></li>
            <li><a href="#">Freifunk Werne</a></li>
            <li><a href="#">Freifunk Münster</a></li>
            <li><a href="#">Freifunk ...</a></li>
        </ul>
    </div>
</div>

<div>Proudly presented by <a href="http://freifunk-hamm.de/">Freifunk Hamm</a></div>
<div>We love open source. Source code of this monitoring and reporting tool is available at <a href="https://github.com/christian-weiss/freifunk-community-registry-report">github.com/christian-weiss/freifunk-community-registry-report</a>. Feel free to contribute and to file issues. </div>
<div>This report was generated with support of:
    <a href="https://www.php.net/">PHP</a>,
    <a href="https://fontawesome.com/">Font Awesome</a> (Free pure CSS icons),
    <a href="https://www.chartjs.org/">Chart.js</a> (Symbols),
    <a href="https://github.com/justinrainbow/json-schema">JSON Schema for PHP</a>, (JSON Schema)
    <a href="https://github.com/raphaelstolt/php-jsonpointer">JSON Pointer for PHP</a> (JSON Pointer),
    <a href="https://www.cloudflare.com">Cloudflare</a> (CDN),
    <a href="https://www.webgo.de/">Webgo</a> (Hosting) and
    <a href="https://www.xing.com/profile/Christian_Weiss63">Christian Weiss (DevOps Engineer)</a>
</div>

<script>
// Insecure Endpoints
    var ctx = document.getElementById('InsecureEndpoints');
    var insecureEndpints = {
        datasets: [{
            data: [<?php echo $this->getReport()->endpointsSecure; ?>, <?php echo $this->getReport()->endpointsInsecure; ?>],
            "backgroundColor": ["<?php echo $goodColors[0]; ?>", "<?php echo $badColors[0]; ?>"],
            weight: 100
        }],

        labels: [
            'Secure (HTTPS)',
            'Insecure (HTTP)',
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: insecureEndpints
    });

// Shared Endpoints
    var ctx = document.getElementById('SharedEndpoints');
    var sharedEndpointsData = {
        datasets: [{
            data: [<?php echo $this->getReport()->endpointsNotShared; ?>, <?php echo $this->getReport()->endpointsShared; ?>],
            "backgroundColor": ["<?php echo $goodColors[0]; ?>", "<?php echo $badColors[0]; ?>"],
            weight: 100
        }],

        labels: [
            'Not Shared Endpoints',
            'Shared Endpoints',
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: sharedEndpointsData
    });

// Connection Issues
    var ctx = document.getElementById('ConnectionIssues');
    var connectionIssuesData = {
        datasets: [{
            data: [<?php echo $this->getTemplateHelper()->wrapAndGlueValues($this->getReport()->connectionIssues, "'", ",")?>],
            "backgroundColor": [<?php echo $this->getTemplateHelper()->wrapAndGlueValues($badColors, "'", ', '); ?>],
            weight: 100
        }],

        labels: [<?php echo $this->getTemplateHelper()->wrapAndGlueKeys($this->getReport()->connectionIssues, "'", ",")?>]
    }

    new Chart(ctx, {
        type: 'pie',
        data: connectionIssuesData
    });

// Redirects
    var ctx = document.getElementById('Redirects');
    var redirectsData = {
        datasets: [{
            data: [<?php echo $this->getReport()->redirects301; ?>, <?php echo $this->getReport()->redirects302; ?>],
            "backgroundColor": ["<?php echo $badColors[0]; ?>", "<?php echo $badColors[1]; ?>"],
            weight: 100
        }],

        labels: [
            '301',
            '302',
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: redirectsData
    });

// Status Codes
    var ctx = document.getElementById('StatusCodes');
    var statusCodeData = {
        datasets: [{
            data: [<?php echo $this->getReport()->responseOk, ", ", implode(', ', $this->getReport()->responseBadFinals); ?>],
            "backgroundColor": [<?php echo $this->getTemplateHelper()->wrapAndGlueValues(array_merge(array($goodColors[0]), $badColors), "'", ', '); ?>],
            weight: 100
        }],

        labels: [
            '200',<?php echo $this->getTemplateHelper()->wrapAndGlueKeys($this->getReport()->responseBadFinals, "'", ", "); ?>
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: statusCodeData
    });

// Empty Responses
    var ctx = document.getElementById('EmptyResponses');
    var emptyResponsesData = {
        datasets: [{
            data: [<?php echo $this->getReport()->responsesNotEmpty; ?>, <?php echo $this->getReport()->responsesEmpty; ?>],
            "backgroundColor": ["<?php  echo $goodColors[0]; ?>", "<?php echo $badColors[0]; ?>"],
            weight: 100
        }],

        labels: [
            'non-empty',
            'empty',
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: emptyResponsesData
    });

// Same Response
    var ctx = document.getElementById('SameResponse');
    var sameResponseData = {
        datasets: [{
            data: [<?php echo $this->getReport()->responseUnique; ?>, <?php echo $this->getReport()->responseSame; ?>],
            "backgroundColor": ["<?php  echo $goodColors[0]; ?>", "<?php echo $badColors[0]; ?>"],
            weight: 100
        }],

        labels: [
            'unique response',
            'same response',
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: sameResponseData
    });

// Invalid Data Format
    var ctx = document.getElementById('InvalidDataFormat');
    var invalidFormatData = {
        datasets: [{
            data: [<?php echo $this->getReport()->responseValidFormat; ?>, <?php echo $this->getReport()->responseInvalidFormat; ?>],
            "backgroundColor": ["<?php  echo $goodColors[0]; ?>", "<?php echo $badColors[0]; ?>"],
            weight: 100
        }],

        labels: [
            'valid JSON',
            'invalid JSON',
        ]
    }

    new Chart(ctx, {
        type: 'pie',
        data: invalidFormatData
    });

// Api Versions
<?php $nbrRecentVersion = 3; ?>
    var ctx = document.getElementById('ApiVersions');
    var sapiVersionsData = {
        datasets: [{
            data: [<?php echo $this->getTemplateHelper()->wrapAndGlueValues(array_merge($this->getTemplateHelper()->getGoodVersions($this->getReport()->apiVersions,$nbrRecentVersion ), $this->getTemplateHelper()->getBadVersions($this->getReport()->apiVersions,$nbrRecentVersion )), "'", ', '); ?>],
            "backgroundColor": [<?php echo $this->getTemplateHelper()->wrapAndGlueValues(array_merge(array_slice($goodColors,0,$nbrRecentVersion), $badColors), "'", ", "); ?>],
            weight: 100
        }],

        labels: [<?php echo $this->getTemplateHelper()->wrapAndGlueKeys(array_merge($this->getTemplateHelper()->getGoodVersions($this->getReport()->apiVersions,$nbrRecentVersion ), $this->getTemplateHelper()->getBadVersions($this->getReport()->apiVersions,$nbrRecentVersion )), "'", ', '); ?>]
    }

    new Chart(ctx, {
        type: 'pie',
        data: sapiVersionsData
    });

</script>


</body>
</html>