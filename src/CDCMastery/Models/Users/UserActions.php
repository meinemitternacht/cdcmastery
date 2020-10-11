<?php
declare(strict_types=1);


namespace CDCMastery\Models\Users;


use CDCMastery\Controllers\RootController;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\RequestHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Users\Roles\RoleCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class UserActions
{
    private LoggerInterface $log;
    private AuthHelpers $auth_helpers;
    private BaseCollection $bases;
    private OfficeSymbolCollection $symbols;
    private RoleCollection $roles;
    private UserCollection $users;
    private UserHelpers $user_helpers;

    /**
     * UserActions constructor.
     * @param LoggerInterface $log
     * @param AuthHelpers $auth_helpers
     * @param BaseCollection $bases
     * @param OfficeSymbolCollection $symbols
     * @param RoleCollection $roles
     * @param UserCollection $users
     * @param UserHelpers $user_helpers
     */
    public function __construct(
        LoggerInterface $log,
        AuthHelpers $auth_helpers,
        BaseCollection $bases,
        OfficeSymbolCollection $symbols,
        RoleCollection $roles,
        UserCollection $users,
        UserHelpers $user_helpers
    ) {
        $this->log = $log;
        $this->auth_helpers = $auth_helpers;
        $this->bases = $bases;
        $this->symbols = $symbols;
        $this->roles = $roles;
        $this->users = $users;
        $this->user_helpers = $user_helpers;
    }

    /**
     * @param FlashBagInterface $flash
     * @param Request $request
     * @param User $user
     * @param string $return_url_success
     * @param string $return_url_failure
     * @return Response
     * @throws AccessDeniedException
     */
    public function do_edit(
        FlashBagInterface $flash,
        Request $request,
        User $user,
        string $return_url_success,
        string $return_url_failure
    ): Response {
        $handle = RequestHelpers::filter_string_default($request, 'handle');
        $email = RequestHelpers::filter($request,
                                        'email',
                                        null,
                                        FILTER_VALIDATE_EMAIL,
                                        FILTER_NULL_ON_FAILURE);
        $rank = RequestHelpers::filter_string_default($request, 'rank');
        $first_name = RequestHelpers::get($request, 'first_name');
        $last_name = RequestHelpers::get($request, 'last_name');
        $base = RequestHelpers::get($request, 'base');
        $time_zone = RequestHelpers::get($request, 'time_zone');

        /* optional */
        $office_symbol = RequestHelpers::get($request, 'office_symbol');
        $role = RequestHelpers::get($request, 'role');
        $new_password = RequestHelpers::get($request, 'new_password');

        if ($office_symbol === '') {
            $office_symbol = null;
        }

        if ($role === '') {
            $role = null;
        }

        if ($new_password === '') {
            $new_password = null;
        }

        if (!$handle || trim($handle) === '') {
            $flash->add(
                MessageTypes::ERROR,
                'The Username field cannot be empty'
            );

            goto out_return;
        }

        if ($handle !== $user->getHandle() &&
            $this->user_helpers->findByUsername($handle)) {
            $flash->add(
                MessageTypes::ERROR,
                'The specified username is already in use by another user'
            );

            goto out_return;
        }

        if (!$email) {
            $flash->add(
                MessageTypes::ERROR,
                'The specified e-mail address is invalid'
            );

            goto out_return;
        }

        if ($email !== $user->getEmail() &&
            $this->user_helpers->findByEmail($email)) {
            $flash->add(
                MessageTypes::ERROR,
                'The specified e-mail address is already in use by another user'
            );

            goto out_return;
        }

        $valid_ranks = UserHelpers::listRanks(false);
        if (!$rank || !isset($valid_ranks[ $rank ]) || trim($rank) === '') {
            $flash->add(
                MessageTypes::ERROR,
                'The provided rank is invalid'
            );

            goto out_return;
        }

        if (!$first_name || trim($first_name) === '') {
            $flash->add(
                MessageTypes::ERROR,
                'The First Name field cannot be empty'
            );

            goto out_return;
        }

        if (!$last_name || trim($last_name) === '') {
            $flash->add(
                MessageTypes::ERROR,
                'The Last Name field cannot be empty'
            );

            goto out_return;
        }

        $tgt_base = $this->bases->fetch($base);
        if (!$tgt_base || $tgt_base->getUuid() === '') {
            $flash->add(
                MessageTypes::ERROR,
                'The chosen Base is invalid'
            );

            goto out_return;
        }

        $valid_time_zones = array_merge(...DateTimeHelpers::list_time_zones(false));
        if (!$time_zone || !in_array($time_zone, $valid_time_zones, true)) {
            $flash->add(
                MessageTypes::ERROR,
                'The chosen Time Zone is invalid'
            );

            goto out_return;
        }

        if ($role && !$this->auth_helpers->assert_admin() && $role !== $user->getRole()) {
            $msg = 'Your account type cannot change the role for this user';
            $flash->add(
                MessageTypes::ERROR,
                $msg
            );

            throw new AccessDeniedException($msg);
        }

        if ($office_symbol) {
            $new_office_symbol = $this->symbols->fetch($office_symbol);

            if (!$new_office_symbol || $new_office_symbol->getUuid() === '') {
                $flash->add(
                    MessageTypes::ERROR,
                    'The chosen Office Symbol is invalid'
                );

                goto out_return;
            }
        }

        if ($role) {
            $new_role = $this->roles->fetch($role);

            if (!$new_role || $new_role->getUuid() === '') {
                $flash->add(
                    MessageTypes::ERROR,
                    'The chosen Role is invalid'
                );

                goto out_return;
            }
        }

        if ($new_password !== null) {
            $complexity_check = AuthHelpers::check_complexity($new_password, $handle, $email);

            if ($complexity_check) {
                foreach ($complexity_check as $complexity_error) {
                    $flash->add(
                        MessageTypes::ERROR,
                        $complexity_error
                    );
                }

                goto out_return;
            }
        }

        $user->setHandle($handle);
        $user->setEmail($email);
        $user->setRank($rank);
        $user->setFirstName($first_name);
        $user->setLastName($last_name);
        $user->setBase($tgt_base->getUuid());
        $user->setTimeZone($time_zone);

        if ($office_symbol) {
            $user->setOfficeSymbol($new_office_symbol->getUuid());
        }

        if ($role) {
            $user->setRole($new_role->getUuid());
        }

        if ($new_password) {
            $user->setPassword(AuthHelpers::hash($new_password));
            $user->setLegacyPassword(null);
        }

        $this->users->save($user);

        $this->log->info("edit user :: {$user->getName()} [{$user->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        $flash->add(
            MessageTypes::SUCCESS,
            'The information for that user was successfully saved'
        );

        return RootController::static_redirect($return_url_success);

        out_return:
        return RootController::static_redirect($return_url_failure);
    }
}
