<?php
/**
 * Created by IntelliJ IDEA.
 * User: christian
 * Date: 25.08.19
 * Time: 21:36
 */

# path to file-based cache
$directories = array(
    "communityRegistry" => "temp/communityRegistry/communityRegistry.json",
    "communityDefinitionsDir" => "temp/communityDefinitions/",
    "nodeListDir" => "temp/nodeLists/",
    "communication.log" => "temp/communication.log",
    "communicationDetails" => "temp/communication.details",
    "jsonSchemaDir" => "jsonSchema/",
);

# URL to community registry (aka community directory)
$communityRegistryUrl = "https://raw.githubusercontent.com/freifunk/directory.api.freifunk.net/master/directory.json";
