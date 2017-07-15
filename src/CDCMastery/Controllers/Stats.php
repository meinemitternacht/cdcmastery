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
    /**
     * @return string
     */
    public function renderStatsHome(): string
    {
        return AppHelpers::redirect('/stats/tests/month');
    }
}