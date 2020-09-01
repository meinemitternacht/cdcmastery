<?php


namespace CDCMastery\Models\Statistics\Bases;


interface IBaseStats
{
    public const DEFAULT_CUTOFF = '-1 year';

    public const STAT_BASES_AVG_BETWEEN = 'bases_avg_between';
    public const STAT_BASES_AVG_BY_MONTH = 'bases_avg_by_month';
    public const STAT_BASES_AVG_BY_WEEK = 'bases_avg_by_week';
    public const STAT_BASES_AVG_BY_YEAR = 'bases_avg_by_year';
    public const STAT_BASES_AVG_LAST_SEVEN = 'bases_avg_last_seven';
    public const STAT_BASES_AVG_OVERALL = 'bases_avg_overall';
    public const STAT_BASES_COUNT_BETWEEN = 'bases_count_between';
    public const STAT_BASES_COUNT_BY_MONTH = 'bases_count_by_month';
    public const STAT_BASES_COUNT_BY_WEEK = 'bases_count_by_week';
    public const STAT_BASES_COUNT_BY_YEAR = 'bases_count_by_year';
    public const STAT_BASES_COUNT_LAST_SEVEN = 'bases_count_last_seven';
    public const STAT_BASES_COUNT_OVERALL = 'bases_count_overall';

    public const STAT_BASE_AVG_BETWEEN = 'base_avg_between';
    public const STAT_BASE_AVG_BY_MONTH = 'base_avg_by_month';
    public const STAT_BASE_AVG_BY_WEEK = 'base_avg_by_week';
    public const STAT_BASE_AVG_BY_YEAR = 'base_avg_by_year';
    public const STAT_BASE_AVG_LAST_SEVEN = 'base_avg_last_seven';
    public const STAT_BASE_AVG_OVERALL = 'base_avg_overall';
    public const STAT_BASE_AVG_COUNT_OVERALL_BY_USER = 'base_avg_count_overall_by_user';
    public const STAT_BASE_COUNT_BETWEEN = 'base_count_between';
    public const STAT_BASE_COUNT_BY_MONTH = 'base_count_by_month';
    public const STAT_BASE_COUNT_BY_WEEK = 'base_count_by_week';
    public const STAT_BASE_COUNT_BY_YEAR = 'base_count_by_year';
    public const STAT_BASE_COUNT_LAST_SEVEN = 'base_count_last_seven';
    public const STAT_BASE_COUNT_OVERALL = 'base_count_overall';
}