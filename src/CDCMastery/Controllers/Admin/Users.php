<?php

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbol;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Users\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class Users extends Admin
{
    private UserCollection $users;
    private BaseCollection $bases;
    private RoleCollection $roles;
    private OfficeSymbolCollection $symbols;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        BaseCollection $bases,
        RoleCollection $roles,
        OfficeSymbolCollection $symbols
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->users = $users;
        $this->bases = $bases;
        $this->roles = $roles;
        $this->symbols = $symbols;
    }

    private function validate_sort(string $column, string $direction): ?UserSortOption
    {
        try {
            return new UserSortOption($column,
                                      strtolower($direction ?? 'asc') === 'asc'
                                          ? UserSortOption::SORT_ASC
                                          : UserSortOption::SORT_DESC);
        } catch (Throwable $e) {
            unset($e);
            return null;
        }
    }

    public function show_home(): Response
    {
        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $sort = $sortCol
            ? [self::validate_sort($sortCol, $sortDir)]
            : [
                new UserSortOption(UserSortOption::COL_NAME_LAST),
                new UserSortOption(UserSortOption::COL_NAME_FIRST),
                new UserSortOption(UserSortOption::COL_RANK),
                new UserSortOption(UserSortOption::COL_BASE),
            ];

        $sort[] = new UserSortOption(UserSortOption::COL_UUID);

        $users = $this->users->fetchAll($sort);
        $bases = $this->bases->fetchArray(array_map(static function (User $v): string {
            return $v->getBase();
        }, $users));
        $roles = $this->roles->fetchArray(array_map(static function (User $v): string {
            return $v->getRole();
        }, $users));
        $symbols = $this->symbols->fetchArray(array_map(static function (User $v): string {
            return $v->getOfficeSymbol();
        }, $users));

        $filteredList = ArrayPaginator::paginate(
            $users,
            $curPage,
            $numRecords
        );

        $pagination = ArrayPaginator::buildLinks(
            '/admin/users',
            $curPage,
            ArrayPaginator::calcNumPagesData(
                $users,
                $numRecords
            ),
            $numRecords,
            $sortCol,
            $sortDir
        );

        $data = [
            'users' => $filteredList,
            'bases' => $bases,
            'roles' => $roles,
            'symbols' => $symbols,
            'pagination' => $pagination,
            'sort' => [
                'col' => $sortCol,
                'dir' => $sortDir,
            ],
        ];

        return $this->render(
            "admin/users/list.html.twig",
            $data
        );
    }
}