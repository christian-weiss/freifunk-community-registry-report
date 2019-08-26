# Freifunk Community Registry Quality Assurance Report
Monitoring and reporting tool for Freifunk Community Registry (aka Community Directory) and referenced Community Definitions (aka Community API files). 
You can register to get alerted on changes &amp; problems related to your community definition.

## Live Demo
You can visit <a href="http://community-registry.ff-hamm.de/">community-registry.ff-hamm.de/</a> to see a productive instance of this reporting tool.

## Project Status
Source code is currently under heavy development. Initial release will be available in approx. 14 days. Stay tuned.

## Installation
1. load most up-to-date Freifunk JSON Schema files: `cd cron/jsonSchema/` and `./downloadSpecs.sh`
2. upload `<gitRoot>/cron/` to your server as `<path>/cron/`
3. make `cron/htmlReport/`the document root of your webserver