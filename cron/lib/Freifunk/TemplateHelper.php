<?php
/**
 * Created by IntelliJ IDEA.
 * User: christian
 * Date: 24.08.19
 * Time: 19:27
 *
 */

namespace Freifunk;

/**
 * Provides some helper functions for easyer rendering of HTML templates.
 *
 * Class TemplateHelper
 * @package Freifunk
 */
class TemplateHelper
{
    /**
     * Like implode() but wraps each element with $wrapChar before glue them together.
     * Array values are used as elements. Useful for injecting in JavaScript statements.
     *
     * @param $values
     * @param $wrapChar
     * @param $glueChar
     * @see wrapAndGlueKeys() for the counter-part of this method
     * @return string
     */
    public function wrapAndGlueValues($values, $wrapChar, $glueChar) {
        foreach ($values as $key => $value) {
            $values[$key] = $wrapChar.$value.$wrapChar;
        }
        return implode($glueChar, $values);
    }

    /**
     * Like implode() but wraps each element with $wrapChar before glue them together.
     * Array keys are used as elements. Useful for injecting in JavaScript statements.
     *
     * @param $values
     * @param $wrapChar
     * @param $glueChar
     * @see wrapAndGlueValues() for the counter-part of this method
     * @return string
     */
    public function wrapAndGlueKeys($values, $wrapChar, $glueChar) {
        foreach ($values as $key => $value) {
            $values[$key] = $wrapChar.$key.$wrapChar;
        }
        return implode($glueChar, $values);
    }

    /**
     * From an array of version number this method returns recent (good) versions only.
     * The most up-to-date versions are called 'good versions'. Counting from the latest release, you could define
     * in $nbrOfRecentVersions how many versions are treated as 'good versions'
     *
     * @param $apiVersions
     * @param $nbrOfRecentVersions
     * @see getBadVersions()  for the counter-part of this method
     * @return array Result will contain the maximum amount of elements as in the versions array.
     */
    public function getGoodVersions($apiVersions, $nbrOfRecentVersions) {
        $apiVersions = $this->sortByVersionNumber($apiVersions);

        if ($nbrOfRecentVersions > count(array_keys($apiVersions))) {
            $nbrOfRecentVersions = count(array_keys($apiVersions));
        }
        return array_slice($apiVersions, 0, $nbrOfRecentVersions);
    }

    /**
     * From an array of version number this method returns old (bad) versions only.
     * The most up-to-date versions are called 'good versions'. Counting from the latest release, you could define
     * in $nbrOfRecentVersions how many versions are treated as 'good versions'.
     *
     * @param $apiVersions
     * @param $nbrOfRecentVersions
     * @see getGoodVersions() for the counter-part of this method
     * @return array Result will be empty if $nbrOfRecentVersions is higher then number of version array elements.
     */
    public function getBadVersions($apiVersions, $nbrOfRecentVersions) {
        $apiVersions = $this->sortByVersionNumber($apiVersions);

        if ($nbrOfRecentVersions > count(array_keys($apiVersions))) {
            return array();
        } else {
            return array_slice($apiVersions, $nbrOfRecentVersions);
        }
    }

    /**
     * Sorts an array of key/value pairs by respecting keys as version numbers
     * Most recent version first, oldest version last.
     *
     * @param $apiVersions
     * @return array
     */
    protected function sortByVersionNumber($apiVersions) {
        $keys = array_keys($apiVersions);
        usort($keys, 'version_compare');

        $newArray = array();
        foreach ($keys as $oneVersionNumber) {
            $newArray[$oneVersionNumber] = $apiVersions[$oneVersionNumber];
        }

        return array_reverse($newArray);
    }
}