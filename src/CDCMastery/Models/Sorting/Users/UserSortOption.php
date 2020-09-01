<?php


namespace CDCMastery\Models\Sorting\Users;


use RuntimeException;

class UserSortOption
{
    public const SORT_ASC = 0;
    public const SORT_DESC = 1;

    public const COL_UUID = 'uuid';
    public const COL_NAME_FIRST = 'userFirstName';
    public const COL_NAME_LAST = 'userLastName';
    public const COL_HANDLE = 'userHandle';
    public const COL_PASSWORD = 'userPassword';
    public const COL_PASSWORD_LEGACY = 'userLegacyPassword';
    public const COL_EMAIL = 'userEmail';
    public const COL_RANK = 'userRank';
    public const COL_DATE_REGISTERED = 'userDateRegistered';
    public const COL_DATE_LAST_LOGIN = 'userLastLogin';
    public const COL_DATE_LAST_ACTIVE = 'userLastActive';
    public const COL_TIME_ZONE = 'userTimeZone';
    public const COL_ROLE = 'userRole';
    public const COL_OFFICE_SYMBOL = 'userOfficeSymbol';
    public const COL_BASE = 'userBase';
    public const COL_DISABLED = 'userDisabled';
    public const COL_DATE_REMINDER_SENT = 'reminderSent';

    public const JOIN_TABLE = [
        self::COL_BASE => 'baseList',
        self::COL_OFFICE_SYMBOL => 'officeSymbolList',
        self::COL_ROLE => 'roleList',
    ];

    public const JOIN_COLUMNS = [
        self::COL_BASE => 'userData.userBase = baseList.uuid',
        self::COL_OFFICE_SYMBOL => 'userData.userOfficeSymbol = officeSymbolList.uuid',
        self::COL_ROLE => 'userData.userRole = roleList.roleName',
    ];

    public const JOIN_TGT_SORT_COLUMN = [
        self::COL_BASE => '`baseList`.`baseName`',
        self::COL_OFFICE_SYMBOL => '`officeSymbolList`.`officeSymbol`',
        self::COL_ROLE => '`roleList`.`roleName`',
    ];

    private string $column;
    private int $direction;

    public function __construct(string $column, int $direction = self::SORT_ASC)
    {
        switch ($column) {
            case self::COL_UUID:
            case self::COL_NAME_FIRST:
            case self::COL_NAME_LAST:
            case self::COL_HANDLE:
            case self::COL_PASSWORD:
            case self::COL_PASSWORD_LEGACY:
            case self::COL_EMAIL:
            case self::COL_RANK:
            case self::COL_DATE_REGISTERED:
            case self::COL_DATE_LAST_LOGIN:
            case self::COL_DATE_LAST_ACTIVE:
            case self::COL_TIME_ZONE:
            case self::COL_ROLE:
            case self::COL_OFFICE_SYMBOL:
            case self::COL_BASE:
            case self::COL_DISABLED:
            case self::COL_DATE_REMINDER_SENT:
                break;
            default:
                throw new RuntimeException("invalid sort column {$column}");
        }

        $this->column = $column;
        $this->direction = $direction;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDirection(): string
    {
        return $this->direction === self::SORT_ASC
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