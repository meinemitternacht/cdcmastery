<?php


namespace CDCMastery\Models\Sorting;


trait TSortOption
{
    private string $column;
    private int $direction;

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDirection(): string
    {
        return $this->direction === ISortOption::SORT_ASC
            ? 'ASC'
            : 'DESC';
    }

    public function getJoinClause(): ?string
    {
        if (!isset(self::JOIN_TABLE[ $this->column ], self::JOIN_COLUMNS[ $this->column ])) {
            return null;
        }

        $table = self::JOIN_TABLE[ $this->column ];
        $on = self::JOIN_COLUMNS[ $this->column ];
        return "LEFT JOIN {$table} ON {$on}";
    }

    public function getJoinTgtSortColumn(): ?string
    {
        return self::JOIN_TGT_SORT_COLUMN[ $this->column ] ?? null;
    }
}