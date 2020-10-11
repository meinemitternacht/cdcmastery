<?php
declare(strict_types=1);


namespace CDCMastery\Models\Sorting\Cards;


use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Sorting\TSortOption;
use RuntimeException;

class CardCategorySortOption implements ISortOption
{
    use TSortOption;

    public const COL_UUID = 'uuid';
    public const COL_NAME = 'categoryName';
    public const COL_ENCRYPTED = 'categoryEncrypted';
    public const COL_TYPE = 'categoryType';
    public const COL_BINDING = 'categoryBinding';
    public const COL_PRIVATE = 'categoryPrivate';
    public const COL_CREATED_BY = 'categoryCreatedBy';
    public const COL_COMMENTS = 'categoryComments';

    public const JOIN_TABLE = [
        self::COL_CREATED_BY => 'userData',
    ];

    public const JOIN_COLUMNS = [
        self::COL_CREATED_BY => 'flashCardCategories.categoryCreatedBy = userData.uuid',
    ];

    public const JOIN_TGT_SORT_COLUMN = [
        self::COL_CREATED_BY => '`userData`.`userLastName`',
    ];

    public function __construct(string $column, int $direction = ISortOption::SORT_ASC)
    {
        switch ($column) {
            case self::COL_UUID:
            case self::COL_NAME:
            case self::COL_ENCRYPTED:
            case self::COL_TYPE:
            case self::COL_BINDING:
            case self::COL_PRIVATE:
            case self::COL_CREATED_BY:
            case self::COL_COMMENTS:
                break;
            default:
                throw new RuntimeException("invalid sort column {$column}");
        }

        $this->column = $column;
        $this->direction = $direction;
    }
}
