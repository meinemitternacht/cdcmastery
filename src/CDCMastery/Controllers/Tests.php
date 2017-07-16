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
use CDCMastery\Models\Tests\QuestionResponse;
use CDCMastery\Models\Tests\TestCollection;
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

    /**
     * @return string
     */
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

    public function processTest(string $testUuid): string
    {
        $testCollection = $this->container->get(TestCollection::class);
        $test = $testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            /** @todo send message that test does not exist */
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            /** @todo send message that test does not belong to this user */
        }

        if ($test->isComplete()) {
            /** @todo send message that test is already complete */
        }

        $testHandler = TestHandler::resume(
            $this->container->get(\mysqli::class),
            $this->container->get(Logger::class),
            $test
        );

        $payload = json_decode($this->getRequest()->getContent() ?? null);

        if (is_null($payload) || !isset($payload->action)) {
            /** @todo send message that request was malformed */
        }

        switch ($payload->action) {
            case TestHandler::ACTION_NO_ACTION:
                break;
            case TestHandler::ACTION_SUBMIT_ANSWER:
                if (!isset($payload->question) || !isset($payload->answer)) {
                    break;
                }

                $questionResponse = new QuestionResponse();
                $questionResponse->setTestUuid($testUuid);
                $questionResponse->setQuestionUuid($payload->question);
                $questionResponse->setAnswerUuid($payload->answer);

                $testHandler->saveResponse($questionResponse);
                break;
            case TestHandler::ACTION_NAV_FIRST:
                $testHandler->first();
                break;
            case TestHandler::ACTION_NAV_PREV:
                $testHandler->previous();
                break;
            case TestHandler::ACTION_NAV_NEXT:
                $testHandler->next();
                break;
            case TestHandler::ACTION_NAV_LAST:
                $testHandler->last();
                break;
            case TestHandler::ACTION_NAV_NUM:
                if (!isset($payload->idx)) {
                    break;
                }

                $testHandler->navigate($payload->idx);
                break;
            case TestHandler::ACTION_SCORE_TEST:
                if ($testHandler->getNumAnswered() !== $testHandler->getTest()->getNumQuestions()) {
                    break;
                }

                $testHandler->score();
                return json_encode([
                    'redirect' => '/tests/' . $testUuid . '?score'
                ]);
                break;
            default:
                /** @todo handle bad action */
                break;
        }

        return json_encode($testHandler->getDisplayData());
    }

    /**
     * @return string
     */
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

    /**
     * @param string $testUuid
     * @return string
     */
    public function renderTest(string $testUuid): string
    {
        $testCollection = $this->container->get(TestCollection::class);
        $test = $testCollection->fetch($testUuid);

        if (empty($test->getUuid())) {
            Messages::add(
                Messages::WARNING,
                'The provided Test ID does not exist'
            );

            AppHelpers::redirect('/tests/new');
        }

        if ($test->getUserUuid() !== SessionHelpers::getUserUuid()) {
            Messages::add(
                Messages::WARNING,
                'The selected test does not belong to your user account'
            );

            AppHelpers::redirect('/');
        }

        if ($test->isComplete()) {
            return $this->renderTestComplete($testUuid);
        }

        return $this->renderTestIncomplete($testUuid);
    }

    private function renderTestComplete(string $testUuid): string
    {

    }

    private function renderTestIncomplete(string $testUuid): string
    {
        return $this->render(
            'tests/test.html.twig', [
                'testUuid' => $testUuid
            ]
        );
    }
}