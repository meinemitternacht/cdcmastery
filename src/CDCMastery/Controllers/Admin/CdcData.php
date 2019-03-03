<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/17/2017
 * Time: 9:09 PM
 */

namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\Tests;
use CDCMastery\Models\Users\AfscUserCollection;
use CDCMastery\Models\Users\UserAfscAssociations;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class CdcData extends Admin
{
    /**
     * @var AfscCollection
     */
    private $afscCollection;

    /**
     * @var QuestionHelpers
     */
    private $questionHelpers;

    /**
     * @var UserAfscAssociations
     */
    private $userAfscAssociations;

    /**
     * @var Tests
     */
    private $tests;

    public function __construct(
        Logger $logger,
        \Twig_Environment $twig,
        AfscCollection $afscCollection,
        QuestionHelpers $questionHelpers,
        UserAfscAssociations $userAfscAssociations,
        Tests $tests
    ) {
        parent::__construct($logger, $twig);

        $this->afscCollection = $afscCollection;
        $this->questionHelpers = $questionHelpers;
        $this->userAfscAssociations = $userAfscAssociations;
        $this->tests = $tests;
    }

    public function processAddAfsc(): Response
    {

    }

    /**
     * @param string $afscUuid
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscHome(string $afscUuid): Response
    {
        $afsc = $this->afscCollection->fetch($afscUuid);

        if ($afsc->getUuid() === '') {
            Messages::add(
                Messages::WARNING,
                'The specified AFSC does not exist'
            );

            return AppHelpers::redirect('/admin/cdc/afsc');
        }

        $afscUsers = $this->userAfscAssociations->fetchAllByAfsc($afsc);
        $afscQuestionsArr = $this->questionHelpers->getNumQuestionsByAfsc([$afsc->getUuid()]);

        if (!is_array($afscQuestionsArr) || \count($afscQuestionsArr) === 0) {
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
                $this->tests->afscAverageByMonth($afsc)
            ),
            'counts' => StatisticsHelpers::formatGraphDataTests(
                $this->tests->afscCountByMonth($afsc)
            )
        ];

        return $this->render(
            'admin/cdc/afsc/afsc.html.twig',
            $data
        );
    }

    /**
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderAfscList(): Response
    {
        $flags = AfscCollection::SHOW_FOUO;

        if (AuthHelpers::isAdmin()) {
            $flags |= AfscCollection::SHOW_HIDDEN | AfscCollection::SHOW_OBSOLETE;
        }

        $afscList = $this->afscCollection->fetchAll(
            [
                AfscCollection::COL_NAME => AfscCollection::ORDER_ASC,
                AfscCollection::COL_VERSION => AfscCollection::ORDER_ASC,
                AfscCollection::COL_DESCRIPTION => AfscCollection::ORDER_ASC
            ],
            $flags
        );

        if (\count($afscList) === 0) {
            Messages::add(
                Messages::WARNING,
                'There are no AFSCs in the database'
            );

            return AppHelpers::redirect('/');
        }

        $afscUsers = $this->userAfscAssociations->fetchAll(UserAfscAssociations::GROUP_BY_AFSC);
        $afscQuestions = $this->questionHelpers->getNumQuestionsByAfsc(
            AfscHelpers::listUuid($afscList)
        );

        $afscUserCounts = [];
        /** @var AfscUserCollection $afscUser */
        foreach ($afscUsers as $afscUser) {
            $afscUserCounts[$afscUser->getAfsc()] = count($afscUser->getUsers());
        }

        $data = [
            'afscs' => $afscList,
            'afscUsers' => $afscUserCounts,
            'afscQuestions' => $afscQuestions
        ];

        return $this->render(
            'admin/cdc/afsc/list.html.twig',
            $data
        );
    }
}