<?php
/**
 * General functions.
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

if (!function_exists('isMobileThemeActive')) {
    function isMobileThemeActive() {
        $themeManager = Gdn::themeManager();

        return ($themeManager->currentTheme() === 'mobile'
            && $themeManager->getThemeInfo('mobile')['Author'] === "Mark O'Sullivan");
    }
}
