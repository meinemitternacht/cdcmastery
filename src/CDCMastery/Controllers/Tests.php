<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/8/2017
 * Time: 2:59 PM
 */

namespace CDCMastery\Controllers;

use CDCMastery\Exceptions\Parameters\MissingParameterException;
use CDCMastery\Helpers\AppHelpers;
use CDCMastery\Helpers\ParameterHelpers;
use CDCMastery\Helpers\SessionHelpers;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\Messages\Messages;
use CDCMastery\Models\Tests\TestHandler;
use CDCMastery\Models\Tests\TestOptions;
use CDCMastery\Models\Users\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;

class Tests extends RootController
{
    /**
     * @return string
     */
    public function renderTestsHome(): string
    {

    }

    public function processNewTest(): string
    {
        try {
            ParameterHelpers::checkRequiredParameters(
                $this->getRequest(), [
                    'afscs',
                    'numQuestions'
                ]
            );
        } catch (MissingParameterException $e) {
            Messages::add(
                Messages::WARNING,
                'Please ensure all applicable options are selected before beginning a test'
            );

            AppHelpers::redirect('/tests/new');
        }

        $afscs = $this->getRequest()->request->get('afscs');
        $numQuestions = $this->getRequest()->request->filter('numQuestions', FILTER_VALIDATE_INT);

        if (!is_array($afscs) || empty($afscs)) {
            Messages::add(
                Messages::WARNING,
                'You must select at least one AFSC'
            );

            AppHelpers::redirect('/tests/new');
        }

        if ($numQuestions === false) {
            Messages::add(
                Messages::WARNING,
                'The provided amount of questions for the test was invalid'
            );

            AppHelpers::redirect('/tests/new');
        }

        $userCollection = $this->container->get(UserCollection::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $userAfscCollection = $userAfscAssociations->fetchAllByUser(
            $userCollection->fetch(
                SessionHelpers::getUserUuid()
            )
        );

        $validAfscs = array_intersect(
            $userAfscCollection->getAssociations(),
            $afscs
        );

        if (empty($validAfscs)) {
            $this->log->addWarning(
                'create test failed :: afsc not associated :: user ' .
                $userAfscCollection->getUser()->getUuid() .
                ' [' .
                $userAfscCollection->getUser()->getName() .
                '] :: AFSC list ' .
                implode(',', $afscs)
            );

            Messages::add(
                Messages::WARNING,
                'None of the provided AFSCs are associated with your account'
            );

            AppHelpers::redirect('/tests/new');
        }

        $afscCollection = $this->container->get(AfscCollection::class);
        $validAfscs = $afscCollection->fetchArray($validAfscs);

        if (empty($validAfscs)) {
            Messages::add(
                Messages::WARNING,
                'None of the provided AFSCs are valid'
            );

            AppHelpers::redirect('/tests/new');
        }

        foreach ($validAfscs as $validAfsc) {
            $isAuthorized = $userAfscAssociations->assertAuthorized(
                $userAfscCollection->getUser(),
                $validAfsc
            );

            if (!$isAuthorized) {
                $this->log->addWarning(
                    'create test failed :: afsc not authorized :: user ' .
                    $userAfscCollection->getUser()->getUuid() .
                    ' [' .
                    $userAfscCollection->getUser()->getName() .
                    '] :: AFSC ' .
                    $validAfsc->getUuid() .
                    ' [' .
                    $validAfsc->getName() .
                    ']'
                );

                Messages::add(
                    Messages::WARNING,
                    'You are not authorized to take tests for ' .
                    $validAfsc->getName()
                );

                AppHelpers::redirect('/tests/new');
            }
        }

        $testOptions = new TestOptions();
        $testOptions->setNumQuestions($numQuestions);
        $testOptions->setUser($userAfscCollection->getUser());
        $testOptions->setAfscs($validAfscs);

        $newTest = TestHandler::factory(
            $this->container->get(\mysqli::class),
            $this->container->get(Logger::class),
            $testOptions
        );

        if (empty($newTest->getTest()->getUuid())) {
            $this->log->addWarning(
                'create test failed :: user ' .
                $userAfscCollection->getUser()->getUuid() .
                ' [' .
                $userAfscCollection->getUser()->getName() .
                '] :: options -- AFSC List ' .
                implode(',', AfscHelpers::listUuid($testOptions->getAfscs())) .
                ' :: numQuestions ' .
                $testOptions->getNumQuestions()
            );

            Messages::add(
                Messages::WARNING,
                'We could not generate a test using those options'
            );

            AppHelpers::redirect('/tests/new');
        }

        return AppHelpers::redirect('/tests/' . $newTest->getTest()->getUuid());
    }

    public function renderNewTest(): string
    {
        $userCollection = $this->container->get(UserCollection::class);
        $userAfscAssociations = $this->container->get(UserAfscAssociations::class);

        $userAfscCollection = $userAfscAssociations->fetchAllByUser(
            $userCollection->fetch(
                SessionHelpers::getUserUuid()
            )
        );

        if (empty($userAfscCollection->getUser()->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'An error has occurred while fetching your user data'
            );

            AppHelpers::redirect('/auth/logout');
        }

        if (empty($userAfscCollection->getAssociations())) {
            Messages::add(
                Messages::WARNING,
                'You are not associated with any AFSCs'
            );

            AppHelpers::redirect('/');
        }

        $afscCollection = $this->container->get(AfscCollection::class);
        $userAfscs = $afscCollection->fetchArray($userAfscCollection->getAssociations());

        $userAfscList = [];
        foreach ($userAfscs as $afsc) {
            $userAfscList[] = [
                'uuid' => $afsc->getUuid(),
                'name' => $afsc->getName(),
                'description' => $afsc->getDescription(),
                'version' => $afsc->getVersion()
            ];
        }

        $data = [
            'afscList' => $userAfscList
        ];

        return $this->render(
            'tests/new.html.twig',
            $data
        );
    }

    public function renderTest(string $testUuid): string
    {

    }

    private function renderTestComplete(string $testUuid): string
    {

    }

    private function renderTestIncomplete(string $testUuid): string
    {

    }
}