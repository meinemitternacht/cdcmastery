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
use CDCMastery\Models\Users\AfscUserCollection;
use CDCMastery\Models\Users\UserAfscAssociations;

class CdcData extends Admin
{
    public function renderAfscHome(string $afscUuid): string
    {
        $afscCollection = $this->container->get(AfscCollection::class);
        $questionHelpers = $this->container->get(QuestionHelpers::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $afsc = $afscCollection->fetch($afscUuid);

        if (empty($afsc->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The specified AFSC does not exist'
            );

            AppHelpers::redirect('/admin/cdc/afsc');
        }

        $afscUsers = $userAfscAssociations->fetchAllByAfsc($afsc);
        $afscQuestionsArr = $questionHelpers->getNumQuestionsByAfsc([$afsc->getUuid()]);

        if (empty($afscQuestionsArr) || !is_array($afscQuestionsArr)) {
            $afscQuestions = 0;
            goto out_return;
        }

        $afscQuestions = count($afscQuestionsArr[0] ?? []);

        out_return:
        $data = [
            'afsc' => $afsc,
            'afscUsers' => count($afscUsers->getUsers()),
            'afscQuestions' => $afscQuestions
        ];

        return $this->render(
            'admin/cdc/afsc/afsc.html.twig',
            $data
        );
    }

    /**
     * @return string
     */
    public function renderAfscList(): string
    {
        $afscCollection = $this->container->get(AfscCollection::class);
        $questionHelpers = $this->container->get(QuestionHelpers::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $flags = AuthHelpers::isAdmin()
            ? (AfscCollection::SHOW_HIDDEN | AfscCollection::SHOW_FOUO | AfscCollection::SHOW_OBSOLETE)
            : AfscCollection::SHOW_FOUO;

        $afscList = $afscCollection->fetchAll(
            [
                AfscCollection::COL_NAME => AfscCollection::ORDER_ASC,
                AfscCollection::COL_VERSION => AfscCollection::ORDER_ASC,
                AfscCollection::COL_DESCRIPTION => AfscCollection::ORDER_ASC
            ],
            $flags
        );

        if (empty($afscList)) {
            Messages::add(
                Messages::WARNING,
                'There are no AFSCs in the database'
            );

            AppHelpers::redirect('/');
        }

        $afscUsers = $userAfscAssociations->fetchAll(UserAfscAssociations::GROUP_BY_AFSC);
        $afscQuestions = $questionHelpers->getNumQuestionsByAfsc(
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