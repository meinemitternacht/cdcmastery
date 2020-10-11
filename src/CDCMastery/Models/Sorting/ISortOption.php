<?php
declare(strict_types=1);


namespace CDCMastery\Models\Sorting;


interface ISortOption
{
    public const SORT_ASC = 0;
    public const SORT_DESC = 1;

    public function getColumn(): string;

    public function getDirection(): string;

    public function getJoinClause(): ?string;

    public function getJoinTgtSortColumn(): ?string;
}
