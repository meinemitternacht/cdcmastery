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

    public function processAddAfsc(): Response
    {

    }

    /**
     * @param string $afscUuid
     * @return Response
     */
    public function renderAfscHome(string $afscUuid): Response
    {
        $afsc = $this->afscs->fetch($afscUuid);

        if ($afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $afscUsers = $this->user_afscs->fetchAllByAfsc($afsc);
        $afscQuestionsArr = $this->question_helpers->getNumQuestionsByAfsc([$afsc->getUuid()]);

        if (!is_array($afscQuestionsArr) || count($afscQuestionsArr) === 0) {
            $afscQuestions = 0;
            goto out_return;
        }

        $afscQuestions = intval(array_shift($afscQuestionsArr));

        out_return:
        $data = [
            'afsc' => $afsc,
            'afscUsers' => count($afscUsers->getUsers()),
            'afscQuestions' => $afscQuestions,
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

    /**
     * @return Response
     */
    public function renderAfscList(): Response
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