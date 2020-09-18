<?php


namespace CDCMastery\Controllers\Overviews;


use CDCMastery\Controllers\RootController;
use CDCMastery\Controllers\Tests;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Auth\PasswordReset\PasswordReset;
use CDCMastery\Models\Auth\PasswordReset\PasswordResetCollection;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Email\EmailCollection;
use CDCMastery\Models\Email\Templates\ResetPassword;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\Subordinates\SubordinateStats;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Tests\Offline\OfflineTestCollection;
use CDCMastery\Models\Tests\Offline\OfflineTestHandler;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use CDCMastery\Models\Tests\TestHelpers;
use CDCMastery\Models\Tests\TestOptions;
use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\Roles\RoleCollection;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use CDCMastery\Models\Users\UserSupervisorAssociations;
use CDCMastery\Models\Users\UserTrainingManagerAssociations;
use Monolog\Logger;
use mysqli;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class TrainingOverview extends RootController
{
    private const TYPE_COMPLETE = 0;
    private const TYPE_INCOMPLETE = 1;

    private AuthHelpers $auth_helpers;
    private UserCollection $users;
    private BaseCollection $bases;
    private RoleCollection $roles;
    private OfficeSymbolCollection $symbols;
    private TestStats $test_stats;
    private SubordinateStats $sub_stats;
    private TestCollection $tests;
    private TestDataHelpers $test_data_helpers;
    private AfscCollection $afscs;
    private UserAfscAssociations $afsc_assocs;
    private UserTrainingManagerAssociations $tm_assocs;
    private UserSupervisorAssociations $su_assocs;
    private PasswordResetCollection $pw_resets;
    private EmailCollection $emails;
    private OfflineTestCollection $offline_tests;
    private mysqli $db;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        UserCollection $users,
        BaseCollection $bases,
        RoleCollection $roles,
        OfficeSymbolCollection $symbols,
        TestStats $test_stats,
        SubordinateStats $sub_stats,
        TestCollection $tests,
        TestDataHelpers $test_data_helpers,
        AfscCollection $afscs,
        UserAfscAssociations $afsc_assocs,
        UserTrainingManagerAssociations $tm_assocs,
        UserSupervisorAssociations $su_assocs,
        PasswordResetCollection $pw_resets,
        EmailCollection $emails,
        OfflineTestCollection $offline_tests,
        mysqli $db
    ) {
        parent::__construct($logger, $twig, $session);

        $this->auth_helpers = $auth_helpers;
        $this->users = $users;
        $this->bases = $bases;
        $this->roles = $roles;
        $this->symbols = $symbols;
        $this->test_stats = $test_stats;
        $this->sub_stats = $sub_stats;
        $this->tests = $tests;
        $this->test_data_helpers = $test_data_helpers;
        $this->afscs = $afscs;
        $this->afsc_assocs = $afsc_assocs;
        $this->tm_assocs = $tm_assocs;
        $this->su_assocs = $su_assocs;
        $this->pw_resets = $pw_resets;
        $this->emails = $emails;
        $this->offline_tests = $offline_tests;
        $this->db = $db;
    }

    private function get_user(string $uuid): User
    {
        $user = $this->users->fetch($uuid);

        if ($user === null || $user->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                $uuid === $this->auth_helpers->get_user_uuid()
                                    ? 'You do not exist'
                                    : 'The specified user was not found');

            $this->redirect('/')->send();
            exit;
        }

        return $user;
    }

    private function check_subordinate(User $cur_user, ?Role $cur_role, User $subordinate): void
    {
        if (!$cur_role) {
            if ($this->tm_assocs->assertAssociated($subordinate, $cur_user) ||
                $this->su_assocs->assertAssociated($subordinate, $cur_user)) {
                return;
            }
        }

        switch ($cur_role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                if ($this->tm_assocs->assertAssociated($subordinate, $cur_user)) {
                    return;
                }
                break;
            case Role::TYPE_SUPERVISOR:
                if ($this->su_assocs->assertAssociated($subordinate, $cur_user)) {
                    return;
                }
                break;
        }

        $this->flash()->add(MessageTypes::ERROR,
                            'That user is not associated with your account');
        $this->redirect('/training')->send();
        exit;
    }

    public function do_afsc_association_add(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'new_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $new_afsc = $this->get('new_afsc');

        if (!is_array($new_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($new_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $this->afsc_assocs->batchAddAfscsForUser($user, $tgt_afscs, true);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully added'
        );

        return $this->redirect("/training/users/{$user->getUuid()}/afsc");
    }

    public function do_afsc_association_approve(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'approve_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $approve_afsc = $this->get('approve_afsc');

        if (!is_array($approve_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($approve_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        foreach ($tgt_afscs as $tgt_afsc) {
            $this->afsc_assocs->authorize($user, $tgt_afsc);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully approved'
        );

        return $this->redirect("/training/users/{$user->getUuid()}/afsc");
    }

    public function do_afsc_association_remove(string $uuid): Response
    {
        $user = $this->get_user($uuid);

        $params = [
            'del_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $del_afsc = $this->get('del_afsc');

        if (!is_array($del_afsc)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        $tgt_afscs = $this->afscs->fetchArray($del_afsc);

        if (!$tgt_afscs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected AFSCs were not valid'
            );

            return $this->redirect("/training/users/{$user->getUuid()}/afsc");
        }

        foreach ($tgt_afscs as $tgt_afsc) {
            $this->afsc_assocs->remove($user, $tgt_afsc);
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected AFSC associations were successfully removed'
        );

        return $this->redirect("/training/users/{$user->getUuid()}/afsc");
    }

    public function do_delete_incomplete_tests(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            static function ($v) {
                if (!$v instanceof Test) {
                    return false;
                }

                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        if (!is_array($tests) || count($tests) === 0) {
            $this->flash()->add(MessageTypes::INFO,
                                'There are no tests to delete for this user');

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        $this->tests->deleteArray(
            TestHelpers::listUuid($tests)
        );

        $this->flash()->add(MessageTypes::SUCCESS,
                            'All incomplete tests for this user have been removed from the database');

        return $this->redirect("/training/users/{$user->getUuid()}");
    }

    public function do_generate_offline_test(): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());

        $params = [
            'afsc',
            'questions',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/training/offline");
        }

        $afsc_uuid = $this->filter_string_default('afsc');
        $n_questions = $this->filter_int_default('questions');

        $afsc = $this->afscs->fetch($afsc_uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC was not found');

            return $this->redirect("/training/offline");
        }

        if (!$this->afsc_assocs->assertAuthorized($cur_user, $afsc)) {
            $this->flash()->add(MessageTypes::WARNING,
                                'Your account is not associated with that AFSC');

            return $this->redirect("/training/offline");
        }

        $opts = new TestOptions();
        $opts->addAfsc($afsc);
        $opts->setUser($cur_user);
        $opts->setNumQuestions($n_questions);

        try {
            $test = OfflineTestHandler::factory($this->db, $this->log, $opts)->getTest();
        } catch (RuntimeException $e) {
            $this->flash()->add(MessageTypes::ERROR,
                                "The offline test could not be generated: {$e->getMessage()}");

            return $this->redirect("/training/offline");
        }

        $this->offline_tests->save($test);
        $this->flash()->add(MessageTypes::SUCCESS,
                            'The offline test has been generated');

        return $this->redirect("/training/offline/{$test->getUuid()}");
    }

    public function do_password_reset(string $uuid): Response
    {
        $user = $this->get_user($uuid);
        $initiator = $this->get_user($this->auth_helpers->get_user_uuid());

        if ($this->pw_resets->fetchByUser($user) !== null) {
            $this->flash()->add(MessageTypes::ERROR,
                                'An active password reset request for this user already exists');

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        $pw_reset = PasswordReset::factory($user);
        $email = ResetPassword::email($initiator, $user, $pw_reset);

        $this->emails->queue($email);
        $this->pw_resets->save($pw_reset);

        $this->flash()->add(MessageTypes::SUCCESS,
                            'A password reset request for this user was successfully initiated');

        return $this->redirect("/training/users/{$user->getUuid()}");
    }

    public function do_subordinates_add(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $params = [
            'new_users',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/training/subordinates");
        }

        $new_users = $this->get('new_users');

        if (!is_array($new_users)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return $this->redirect("/training/subordinates");
        }

        $tgt_subs = $this->users->fetchArray($new_users);

        if (!$tgt_subs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/training/subordinates");
        }

        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                $this->tm_assocs->batchAddUsersForTrainingManager($tgt_subs, $user);
                break;
            case Role::TYPE_SUPERVISOR:
                $this->su_assocs->batchAddUsersForSupervisor($tgt_subs, $user);
                break;
            default:
                goto out_bad_role;
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected subordinates were successfully added'
        );

        return $this->redirect("/training/subordinates");

        out_bad_role:
        $this->flash()->add(MessageTypes::WARNING,
                            'We could not properly determine the state of your account. ' .
                            'Please contact the site administrator.');

        return $this->redirect('/');
    }

    public function do_subordinates_remove(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $params = [
            'del_users',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/training/subordinates");
        }

        $del_users = $this->get('del_users');

        if (!is_array($del_users)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The submitted data was malformed'
            );

            return $this->redirect("/training/subordinates");
        }

        $tgt_subs = $this->users->fetchArray($del_users);

        if (!$tgt_subs) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The selected users were not valid'
            );

            return $this->redirect("/training/subordinates");
        }

        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                foreach ($tgt_subs as $del_user) {
                    $this->tm_assocs->remove($del_user, $user);
                }
                break;
            case Role::TYPE_SUPERVISOR:
                foreach ($tgt_subs as $del_user) {
                    $this->su_assocs->remove($del_user, $user);
                }
                break;
            default:
                goto out_bad_role;
        }

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The selected subordinates were successfully removed'
        );

        return $this->redirect("/training/subordinates");

        out_bad_role:
        $this->flash()->add(MessageTypes::WARNING,
                            'We could not properly determine the state of your account. ' .
                            'Please contact the site administrator.');

        return $this->redirect('/');
    }

    public function show_afsc_associations(string $uuid): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());
        $user = $this->get_user($uuid);

        $this->check_subordinate($cur_user, $cur_role, $user);

        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);
        $afsc_assocs = $this->afsc_assocs->fetchAllByUser($user);

        $cmp = static function (Afsc $a, Afsc $b): int {
            return $a->getName() . $a->getEditCode() <=> $b->getName() . $b->getEditCode();
        };

        uasort($afscs, $cmp);

        $available = array_filter(
            $afscs,
            static function (Afsc $v): bool {
                return !$v->isHidden() && !$v->isObsolete();
            }
        );

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'user' => $user,
            'afscs' => [
                'authorized' => array_intersect_key($afscs, array_flip($afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($afscs, array_flip($afsc_assocs->getPending())),
                'available' => array_diff_key($available, array_flip($afsc_assocs->getAfscs())),
            ],
        ];

        return $this->render(
            'training/users/afsc/associations.html.twig',
            $data
        );
    }

    public function show_delete_incomplete_tests(string $uuid): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());
        $user = $this->get_user($uuid);

        $this->check_subordinate($cur_user, $cur_role, $user);

        $tests = $this->tests->fetchAllByUser($user);

        $tests = array_filter(
            $tests,
            static function (Test $v) {
                return $v->getScore() === 0.00 && $v->getTimeCompleted() === null;
            }
        );

        if (count($tests) === 0) {
            $this->flash()->add(
                MessageTypes::INFO,
                'There are no incomplete tests to delete for this user'
            );

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        uasort(
            $tests,
            static function (Test $a, Test $b) {
                return $b->getTimeStarted()->format('U') <=> $a->getTimeStarted()->format('U');
            }
        );

        $tests = TestHelpers::formatHtml($tests);

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'user' => $user,
            'tests' => $tests,
        ];

        return $this->render(
            'training/users/tests/delete-incomplete.html.twig',
            $data
        );
    }

    public function show_offline_tests(): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());

        $cur_afscs = $this->afsc_assocs->fetchAllByUser($cur_user)->getAuthorized();

        if ($cur_afscs) {
            $afscs = $this->afscs->fetchArray($cur_afscs);
        }

        $tests = $this->offline_tests->fetchAllByUser($cur_user);

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'afscs' => $afscs ?? null,
            'tests' => $tests,
        ];

        return $this->render(
            'training/offline/list.html.twig',
            $data
        );
    }

    public function show_offline_test(string $uuid, bool $print = false): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());

        $test = $this->offline_tests->fetch($uuid);

        if ($test === null || $test->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::INFO,
                'The specified offline test could not be found'
            );

            return $this->redirect('/training/offline');
        }

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'test' => $test,
        ];

        return $this->render(
            $print
                ? 'training/offline/print.html.twig'
                : 'training/offline/view.html.twig',
            $data
        );
    }

    public function show_offline_test_print(string $uuid): Response
    {
        return $this->show_offline_test($uuid, true);
    }

    public function show_test(string $uuid, string $test_uuid): Response
    {
        $user = $this->get_user($uuid);
        $test = $this->tests->fetch($test_uuid);

        if (!$test->getUuid()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified test could not be found'
            );

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        if (!$test->isComplete()) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Tests that are still in-progress cannot be viewed'
            );

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        return $this->show_test_complete($user, $test);
    }

    private function show_test_complete(User $user, Test $test): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());

        $this->check_subordinate($cur_user, $cur_role, $user);

        $testData = $this->test_data_helpers->list($test);

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'user' => $user,
            'timeStarted' => $test->getTimeStarted()->format(
                DateTimeHelpers::DT_FMT_LONG
            ),
            'timeCompleted' => $test->getTimeCompleted()->format(
                DateTimeHelpers::DT_FMT_LONG
            ),
            'afscList' => AfscHelpers::listNames($test->getAfscs()),
            'numQuestions' => $test->getNumQuestions(),
            'numMissed' => $test->getNumMissed(),
            'score' => $test->getScore(),
            'isArchived' => $test->isArchived(),
            'testData' => $testData,
        ];

        return $this->render(
            'training/users/tests/completed.html.twig',
            $data
        );
    }

    private function show_test_history(User $user, int $type): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());

        $this->check_subordinate($cur_user, $cur_role, $user);

        switch ($type) {
            case self::TYPE_COMPLETE:
                $path = "/training/users/{$user->getUuid()}/tests";
                $typeStr = 'complete';
                $template = 'training/users/tests/history-complete.html.twig';
                break;
            case self::TYPE_INCOMPLETE:
                $path = "/training/users/{$user->getUuid()}/tests/incomplete";
                $typeStr = 'incomplete';
                $template = 'training/users/tests/history-incomplete.html.twig';
                break;
            default:
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'We made a mistake when processing that request'
                );

                return $this->redirect("/training/users/{$user->getUuid()}");
        }

        $sortCol = $this->getRequest()->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->getRequest()->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->getRequest()->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->getRequest()->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        [$col, $dir] = Tests::validate_test_sort($sortCol, $sortDir);
        $userTests = $this->tests->fetchAllByUser($user,
                                                  [
                                                      $col => $dir,
                                                  ]);

        if (empty($userTests)) {
            $this->flash()->add(
                MessageTypes::INFO,
                'This user has not taken any tests'
            );

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        $userTests = array_filter(
            $userTests,
            function (Test $v) use ($type) {
                switch ($type) {
                    case self::TYPE_COMPLETE:
                        if ($v->getScore() > 0 && $v->getTimeCompleted() !== null) {
                            return true;
                        }
                        break;
                    case self::TYPE_INCOMPLETE:
                        if ($v->getScore() < 1 && $v->getTimeCompleted() === null) {
                            return true;
                        }
                        break;
                }

                return false;
            }
        );

        $userTests = TestHelpers::formatHtml($userTests);

        $filteredList = ArrayPaginator::paginate(
            $userTests,
            $curPage,
            $numRecords
        );

        if (count($filteredList) === 0) {
            $this->flash()->add(
                MessageTypes::INFO,
                $type === self::TYPE_INCOMPLETE
                    ? 'This account does not have ' . $typeStr . ' tests'
                    : 'This account has not taken any ' . $typeStr . ' tests'
            );

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        $pagination = ArrayPaginator::buildLinks(
            $path,
            $curPage,
            ArrayPaginator::calcNumPagesData(
                $userTests,
                $numRecords
            ),
            $numRecords,
            $col,
            $dir
        );

        return $this->render(
            $template,
            [
                'cur_user' => $cur_user,
                'cur_role' => $cur_role,
                'user' => $user,
                'tests' => $filteredList,
                'pagination' => $pagination,
                'sort' => [
                    'col' => $sortCol,
                    'dir' => $sortDir,
                ],
            ]
        );
    }

    public function show_test_history_complete(string $uuid): Response
    {
        return $this->show_test_history($this->get_user($uuid), self::TYPE_COMPLETE);
    }

    public function show_test_history_incomplete(string $uuid): Response
    {
        return $this->show_test_history($this->get_user($uuid), self::TYPE_INCOMPLETE);
    }

    public function show_password_reset(string $uuid): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());
        $user = $this->get_user($uuid);

        $this->check_subordinate($cur_user, $cur_role, $user);

        if ($this->pw_resets->fetchByUser($user) !== null) {
            $this->flash()->add(MessageTypes::ERROR,
                                'An active password reset request for this user already exists');

            return $this->redirect("/training/users/{$user->getUuid()}");
        }

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'user' => $user,
            'base' => $this->bases->fetch($user->getBase()),
            'role' => $this->roles->fetch($user->getRole()),
        ];

        return $this->render(
            'training/users/password-reset.html.twig',
            $data
        );
    }

    public function show_profile(string $uuid): Response
    {
        $cur_user = $this->get_user($this->auth_helpers->get_user_uuid());
        $cur_role = $this->roles->fetch($cur_user->getRole());
        $user = $this->get_user($uuid);

        $this->check_subordinate($cur_user, $cur_role, $user);

        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $u_symbol = $user->getOfficeSymbol();
        if ($u_symbol) {
            $symbol = $this->symbols->fetch($u_symbol);
        }

        $incomplete_tests = array_filter(
            $this->tests->fetchAllByUser($user),
            static function (Test $v) {
                return $v->getScore() < 1 && $v->getTimeCompleted() === null;
            }
        );

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        $afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);
        $afsc_assocs = $this->afsc_assocs->fetchAllByUser($user);
        $tm_assocs = $this->users->fetchArray($this->tm_assocs->fetchAllByUser($user), $user_sort);
        $su_assocs = $this->users->fetchArray($this->su_assocs->fetchAllByUser($user), $user_sort);

        $subs = null;
        switch ($role->getType()) {
            case Role::TYPE_SUPERVISOR:
                $subs = $this->users->fetchArray($this->su_assocs->fetchAllBySupervisor($user), $user_sort);
                break;
            case Role::TYPE_TRAINING_MANAGER:
                $subs = $this->users->fetchArray($this->tm_assocs->fetchAllByTrainingManager($user), $user_sort);
                break;
        }

        $data = [
            'cur_user' => $cur_user,
            'cur_role' => $cur_role,
            'user' => $user,
            'base' => $base,
            'symbol' => $symbol ?? null,
            'role' => $role,
            'afscs' => [
                'authorized' => array_intersect_key($afscs, array_flip($afsc_assocs->getAuthorized())),
                'pending' => array_intersect_key($afscs, array_flip($afsc_assocs->getPending())),
            ],
            'assocs' => [
                'tm' => $tm_assocs,
                'su' => $su_assocs,
                'subordinates' => $subs,
            ],
            'stats' => [
                'tests' => [
                    'complete' => [
                        'count' => $this->test_stats->userCountOverall($user),
                        'avg' => $this->test_stats->userAverageOverall($user),
                    ],
                    'incomplete' => [
                        'count' => count($incomplete_tests),
                    ],
                ],
            ],
        ];

        return $this->render(
            'training/users/profile.html.twig',
            $data
        );

        out_bad_role:
        $this->flash()->add(MessageTypes::WARNING,
                            'We could not properly determine the state of your account. ' .
                            'Please contact the site administrator.');

        return $this->redirect('/');
    }

    public function show_subordinates(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $base = $this->bases->fetch($user->getBase());
        $role = $this->roles->fetch($user->getRole());

        if ($role === null) {
            goto out_bad_role;
        }

        $user_sort = [
            new UserSortOption(UserSortOption::COL_NAME_LAST),
            new UserSortOption(UserSortOption::COL_NAME_FIRST),
            new UserSortOption(UserSortOption::COL_RANK),
            new UserSortOption(UserSortOption::COL_BASE),
        ];

        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                $cur = $this->users->fetchArray($this->tm_assocs->fetchAllByTrainingManager($user), $user_sort);
                $available = $this->users->fetchArray($this->tm_assocs->fetchUnassociatedByTrainingManager($user),
                                                      $user_sort);
                break;
            case Role::TYPE_SUPERVISOR:
                $cur = $this->users->fetchArray($this->su_assocs->fetchAllBySupervisor($user), $user_sort);
                $available = $this->users->fetchArray($this->su_assocs->fetchUnassociatedBySupervisor($user),
                                                      $user_sort);
                break;
            default:
                goto out_bad_role;
        }

        $data = [
            'cur_user' => $user,
            'cur_role' => $role,
            'base' => $base,
            'assocs' => [
                'cur' => $cur,
                'available' => $available,
            ],
        ];

        return $this->render(
            'training/subordinates.html.twig',
            $data
        );

        out_bad_role:
        $this->flash()->add(MessageTypes::WARNING,
                            'We could not properly determine the state of your account. ' .
                            'Please contact the site administrator.');

        return $this->redirect('/');
    }

    /**
     * @param array $stats
     * @param User[] $users
     * @return array|null
     */
    private function format_overview_graph_data(array &$stats, array $users): ?array
    {
        /*
         *  [
         *    user_uuid => [
         *      tAvg => test average (float),
         *      tCount => test count (int)
         *    ]
         *  ]
         */

        if (!$stats) {
            return null;
        }

        $average_data = [];
        $count_data = [];
        $i = 0;
        foreach ($stats as $user_uuid => $stat) {
            if (!isset($users[ $user_uuid ])) {
                continue;
            }

            $name = $users[ $user_uuid ]->getName();
            $ll = $users[ $user_uuid ]->getLastLogin();
            $stats[ $user_uuid ][ 'name' ] = $name;
            $stats[ $user_uuid ][ 'name_last' ] = $users[ $user_uuid ]->getLastName();
            $stats[ $user_uuid ][ 'name_first' ] = $users[ $user_uuid ]->getFirstName();
            $stats[ $user_uuid ][ 'last_login' ] = $ll
                ? $ll->format(DateTimeHelpers::DT_FMT_SHORT)
                : 'Never';

            $average_data[] = [
                'toolTipContent' => <<<LBL
{$name}<br>
Average: {$stat['tAvg']}<br>
Tests: {$stat['tCount']}
LBL,
                'x' => $i,
                'y' => $stat[ 'tAvg' ],
            ];

            $count_data[] = [
                'toolTipContent' => null,
                'x' => $i,
                'y' => $stat[ 'tCount' ],
            ];

            $i++;
        }

        if (!$average_data || !$count_data) {
            return null;
        }

        return ['avg' => json_encode($average_data), 'count' => json_encode($count_data)];
    }

    public function show_overview(): Response
    {
        $user = $this->get_user($this->auth_helpers->get_user_uuid());
        $role = $this->roles->fetch($user->getRole());

        if (!$role) {
            $this->flash()->add(MessageTypes::WARNING,
                                'We could not properly determine the state of your account. ' .
                                'Please contact the site administrator.');

            return $this->redirect('/');
        }

        switch ($role->getType()) {
            case Role::TYPE_TRAINING_MANAGER:
                $subs = $this->tm_assocs->fetchAllByTrainingManager($user);
                break;
            case Role::TYPE_SUPERVISOR:
                $subs = $this->su_assocs->fetchAllBySupervisor($user);
                break;
            default:
                $this->flash()->add(MessageTypes::WARNING,
                                    'We could not properly determine the state of your account. ' .
                                    'Please contact the site administrator.');

                return $this->redirect('/');
        }

        $users = $this->users->fetchArray($subs);

        $n_supervisors = 0;
        $graph_data = null;
        $sub_stats_count_avg_grouped = null;
        $sub_stats_latest = null;
        $sub_stats_count_overall = null;
        $sub_stats_avg_overall = null;

        if ($subs) {
            $sub_stats_count_avg_grouped = $this->sub_stats->subordinate_tests_count_avg($user);
            $sub_stats_latest = $this->sub_stats->subordinate_tests_latest_score($user);
            $sub_stats_count_overall = $this->sub_stats->subordinate_tests_count_overall($user);
            $sub_stats_avg_overall = $this->sub_stats->subordinate_tests_avg_overall($user);

            $graph_data = $this->format_overview_graph_data($sub_stats_count_avg_grouped, $users);

            if ($role->getType() === Role::TYPE_TRAINING_MANAGER) {
                $super_role = $this->roles->fetchType(Role::TYPE_SUPERVISOR);
                $n_supervisors = count(array_filter($users, static function (User $v) use ($super_role) {
                    return $v->getRole() === $super_role->getUuid();
                }));
            }

            uasort($sub_stats_count_avg_grouped, static function (array $a, array $b): int {
                if ($a[ 'name_last' ] === $b[ 'name_last' ]) {
                    return $a[ 'name_first' ] <=> $b[ 'name_first' ];
                }

                return $a[ 'name_last' ] <=> $b[ 'name_last' ];
            });
        }

        $data = [
            'cur_user' => $user,
            'cur_role' => $role,
            'role' => $role,
            'graph' => $graph_data,
            'stats' => [
                'count_avg' => $sub_stats_count_avg_grouped,
                'latest' => $sub_stats_latest,
                'tests' => $sub_stats_count_overall,
                'average' => $sub_stats_avg_overall,
                'n_users' => count($sub_stats_count_avg_grouped),
                'n_supervisors' => $n_supervisors,
            ],
        ];

        return $this->render(
            'training/overview.html.twig',
            $data
        );
    }
}