<?php
/**
 * Created by IntelliJ IDEA.
 * User: christian
 * Date: 25.08.19
 * Time: 13:36
 */

/**
 * This cron is producing output, so you can set crontab to forward that output as an e-mail.
 */

require_once "config.php";
require_once "vendor/autoload.php";

echo date('Y-m-d h:i:s a', time()). " Cron started".PHP_EOL;

$scraper = new \Freifunk\CommunityCrawler($directories);
$scraper->setCommunityRegistryUrl($communityRegistryUrl);
$scraper->dryRun();

$registryAnalyser = new \Freifunk\RegistryAnalyser($scraper);
$registryAnalyser->run();
$registryAnalyser->printReport();
$registryAnalyser->printStatistics();
$registryAnalyser->generateHTML();

echo date('Y-m-d h:i:s a', time()). " Cron finished".PHP_EOL;