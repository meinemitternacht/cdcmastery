<?php

namespace CDCMastery\Controllers;

class Stats extends RootController
{
    public const TYPE_LAST_SEVEN = 0;
    public const TYPE_MONTH = 1;
    public const TYPE_WEEK = 2;
    public const TYPE_YEAR = 3;

    /**
     * @return string
     */
    public function show_stats_home(): string
    {
        return $this->redirect('/stats/tests/month');
    }
}