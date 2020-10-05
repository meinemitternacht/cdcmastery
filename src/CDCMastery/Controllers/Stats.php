<?php
declare(strict_types=1);

namespace CDCMastery\Controllers;

use Symfony\Component\HttpFoundation\Response;

class Stats extends RootController
{
    public const TYPE_LAST_SEVEN = 0;
    public const TYPE_MONTH = 1;
    public const TYPE_WEEK = 2;
    public const TYPE_YEAR = 3;

    public function show_stats_home(): Response
    {
        return $this->redirect('/stats/tests/month');
    }
}