<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;

use CDCMastery\Helpers\AppHelpers;

class Stats extends RootController
{
    public const TYPE_LAST_SEVEN = 0;
    public const TYPE_MONTH = 1;
    public const TYPE_WEEK = 2;
    public const TYPE_YEAR = 3;

    /**
     * @return string
     */
    public function renderStatsHome(): string
    {
        return AppHelpers::redirect('/stats/tests/month');
    }
}