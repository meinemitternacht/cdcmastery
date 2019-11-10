<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/17/2017
 * Time: 9:09 PM
 */

namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Users\AfscUserCollection;
use CDCMastery\Models\Users\UserAfscAssociations;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;
use function count;

class CdcData extends Admin
{
    /**
     * @var AfscCollection
     */
    private $afscs;

    /**
     * @var QuestionHelpers
     */
    private $question_helpers;

    /**
     * @var UserAfscAssociations
     */
    private $user_afscs;

    /**
     * @var TestStats
     */
    private $test_stats;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        AfscCollection $afscs,
        QuestionHelpers $question_helpers,
        UserAfscAssociations $user_afscs,
        TestStats $test_stats
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->afscs = $afscs;
        $this->question_helpers = $question_helpers;
        $this->user_afscs = $user_afscs;
        $this->test_stats = $test_stats;
    }

    public function do_afsc_add(): Response
    {
        $params = [
            'name',
        ];

        $this->checkParameters($params);

        $name = $this->filter_string_default('name');
        $version = $this->filter_string_default('version');
        $description = $this->filter_string_default('description');
        $fouo = $this->filter_bool_default('fouo', false);
        $hidden = $this->filter_bool_default('hidden', false);

        $afsc = new Afsc();
        $afsc->setName($name);
        $afsc->setDescription($description);
        $afsc->setVersion($version);
        $afsc->setFouo($fouo);
        $afsc->setHidden($hidden);

        if ($this->afscs->exists($afsc)) {
            $this->flash()->add(MessageTypes::ERROR,
                                "The specified AFSC '{$afsc->getName()}' already exists in the database");
            goto out_return;
        }

        if (!$this->afscs->save($afsc)) {
            $this->flash()->add(MessageTypes::ERROR,
                                "The specified AFSC '{$afsc->getName()}' could not be added to the database");
            goto out_return;
        }

        $this->flash()->add(MessageTypes::SUCCESS,
                            "The specified AFSC '{$afsc->getName()}' was added to the database");

        out_return:
        return $this->redirect('/admin/cdc/afsc');
    }

    /**
     * @param string $uuid
     * @return Response
     */
    public function show_afsc_home(string $uuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if ($afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $afsc_users = $this->user_afscs->fetchAllByAfsc($afsc);
        $afsc_questions = $this->question_helpers->getNumQuestionsByAfsc([$afsc->getUuid()]);

        $n_afsc_questions = 0;
        if (is_array($afsc_questions) && count($afsc_questions) > 0) {
            $n_afsc_questions = (int)array_shift($afsc_questions);
        }

        $data = [
            'afsc' => $afsc,
            'afscUsers' => count($afsc_users->getUsers()),
            'afscQuestions' => $n_afsc_questions,
            'subTitle' => 'Tests By Month',
            'period' => 'month',
            'averages' => StatisticsHelpers::formatGraphDataTests(
                $this->test_stats->afscAverageByMonth($afsc)
            ),
            'counts' => StatisticsHelpers::formatGraphDataTests(
                $this->test_stats->afscCountByMonth($afsc)
            ),
        ];

        return $this->render(
            'admin/cdc/afsc/afsc.html.twig',
            $data
        );
    }

    private function do_afsc_disable_restore(string $uuid, bool $disable): Response
    {
        $disable_restore_str = $disable
            ? 'disable'
            : 'restore';

        $afsc = $this->afscs->fetch($uuid);

        if ($afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC does not exist');

            return $this->redirect('/admin/cdc/afsc');
        }

        if ($disable === $afsc->isHidden()) {
            $this->flash()->add(MessageTypes::WARNING,
                                "The specified AFSC has already been {$disable_restore_str}d");

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $afsc->setHidden($disable);

        if (!$this->afscs->save($afsc)) {
            $this->flash()->add(MessageTypes::WARNING,
                                "AFSC '{$afsc->getName()}' could not be {$disable_restore_str}d due to a database error");

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        return $this->redirect('/admin/cdc/afsc');
    }

    public function do_afsc_disable(string $uuid): Response
    {
        return $this->do_afsc_disable_restore($uuid, true);
    }

    public function do_afsc_restore(string $uuid): Response
    {
        return $this->do_afsc_disable_restore($uuid, false);
    }

    private function show_afsc_disable_restore(string $uuid, bool $disable): Response
    {
        $disable_restore_str = $disable
            ? 'disable'
            : 'restore';

        $afsc = $this->afscs->fetch($uuid);

        if ($afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC does not exist');

            return $this->redirect('/admin/cdc/afsc');
        }

        if ($disable === $afsc->isHidden()) {
            $this->flash()->add(MessageTypes::WARNING,
                                "The specified AFSC has already been {$disable_restore_str}d");

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $afsc_users = $this->user_afscs->fetchAllByAfsc($afsc);
        $afsc_questions = $this->question_helpers->getNumQuestionsByAfsc([$afsc->getUuid()]);

        $n_afsc_questions = 0;
        if (is_array($afsc_questions) && count($afsc_questions) > 0) {
            $n_afsc_questions = (int)array_shift($afsc_questions);
        }

        $data = [
            'afsc' => $afsc,
            'afscUsers' => count($afsc_users->getUsers()),
            'afscQuestions' => $n_afsc_questions,
        ];

        return $this->render(
            "admin/cdc/afsc/afsc-{$disable_restore_str}.html.twig",
            $data
        );
    }

    public function show_afsc_disable(string $uuid): Response
    {
        return $this->show_afsc_disable_restore($uuid, true);
    }

    public function show_afsc_restore(string $uuid): Response
    {
        return $this->show_afsc_disable_restore($uuid, false);
    }

    /**
     * @return Response
     */
    public function show_afsc_list(): Response
    {
        $flags = AfscCollection::SHOW_FOUO;

        if ($this->auth_helpers->assert_admin()) {
            $flags |= AfscCollection::SHOW_HIDDEN | AfscCollection::SHOW_OBSOLETE;
        }

        $afscList = $this->afscs->fetchAll($flags,
                                           [
                                               AfscCollection::COL_NAME => AfscCollection::ORDER_ASC,
                                               AfscCollection::COL_VERSION => AfscCollection::ORDER_ASC,
                                               AfscCollection::COL_DESCRIPTION => AfscCollection::ORDER_ASC,
                                           ]);

        if (count($afscList) === 0) {
            $this->flash()->add(MessageTypes::WARNING,
                                'There are no AFSCs in the database');
            return $this->redirect('/');
        }

        $afscUsers = $this->user_afscs->fetchAll(UserAfscAssociations::GROUP_BY_AFSC);
        $afscQuestions = $this->question_helpers->getNumQuestionsByAfsc(AfscHelpers::listUuid($afscList));

        $afscUserCounts = [];
        /** @var AfscUserCollection $afscUser */
        foreach ($afscUsers as $afscUser) {
            $afscUserCounts[$afscUser->getAfsc()] = count($afscUser->getUsers());
        }

        $data = [
            'afscs' => $afscList,
            'afscUsers' => $afscUserCounts,
            'afscQuestions' => $afscQuestions,
        ];

        return $this->render(
            'admin/cdc/afsc/list.html.twig',
            $data
        );
    }
}