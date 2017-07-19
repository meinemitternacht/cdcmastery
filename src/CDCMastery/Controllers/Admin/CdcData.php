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
use CDCMastery\Models\Users\AfscUserCollection;
use CDCMastery\Models\Users\UserAfscAssociations;

class CdcData extends Admin
{
    public function renderAfscList(): string
    {
        $afscCollection = $this->container->get(AfscCollection::class);
        $questionHelpers = $this->container->get(QuestionHelpers::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $flags = AuthHelpers::isAdmin()
            ? (AfscCollection::SHOW_HIDDEN | AfscCollection::SHOW_FOUO)
            : AfscCollection::SHOW_FOUO;

        $afscList = $afscCollection->fetchAll(
            [
                AfscCollection::COL_NAME => AfscCollection::ORDER_ASC,
                AfscCollection::COL_VERSION => AfscCollection::ORDER_ASC,
                AfscCollection::COL_DESCRIPTION => AfscCollection::ORDER_ASC
            ],
            $flags
        );

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