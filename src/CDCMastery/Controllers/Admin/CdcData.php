<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/17/2017
 * Time: 9:09 PM
 */

namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Exceptions\AccessDeniedException;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\AfscHelpers;
use CDCMastery\Models\CdcData\Answer;
use CDCMastery\Models\CdcData\AnswerCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\CdcData\QuestionHelpers;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Sorting\Users\UserSortOption;
use CDCMastery\Models\Statistics\StatisticsHelpers;
use CDCMastery\Models\Statistics\TestStats;
use CDCMastery\Models\Users\Associations\Afsc\AfscUserCollection;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use JsonException;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;
use function count;

class CdcData extends Admin
{
    private const NEW_QUESTION_TMP = 'new_question_tmp';
    private CdcDataCollection $cdc_datas;
    private AfscCollection $afscs;
    private QuestionCollection $questions;
    private AnswerCollection $answers;
    private QuestionHelpers $question_helpers;
    private UserAfscAssociations $user_afscs;
    private UserCollection $users;
    private BaseCollection $bases;
    private TestStats $test_stats;

    /**
     * CdcData constructor.
     * @param Logger $logger
     * @param Environment $twig
     * @param Session $session
     * @param AuthHelpers $auth_helpers
     * @param CacheHandler $cache
     * @param Config $config
     * @param CdcDataCollection $cdc_datas
     * @param AfscCollection $afscs
     * @param QuestionCollection $questions
     * @param AnswerCollection $answers
     * @param QuestionHelpers $question_helpers
     * @param \CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations $user_afscs
     * @param UserCollection $users
     * @param BaseCollection $bases
     * @param TestStats $test_stats
     * @throws AccessDeniedException
     */
    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CacheHandler $cache,
        Config $config,
        CdcDataCollection $cdc_datas,
        AfscCollection $afscs,
        QuestionCollection $questions,
        AnswerCollection $answers,
        QuestionHelpers $question_helpers,
        UserAfscAssociations $user_afscs,
        UserCollection $users,
        BaseCollection $bases,
        TestStats $test_stats
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers, $cache, $config);

        $this->cdc_datas = $cdc_datas;
        $this->afscs = $afscs;
        $this->questions = $questions;
        $this->answers = $answers;
        $this->question_helpers = $question_helpers;
        $this->user_afscs = $user_afscs;
        $this->users = $users;
        $this->bases = $bases;
        $this->test_stats = $test_stats;
    }

    private function validate_sort(string $column, string $direction): ?ISortOption
    {
        try {
            return new UserSortOption($column,
                                      strtolower($direction ?? 'asc') === 'asc'
                                          ? ISortOption::SORT_ASC
                                          : ISortOption::SORT_DESC);
        } catch (Throwable$e) {
            $this->log->debug($e);
            unset($e);
            return null;
        }
    }

    public function do_afsc_add(?Afsc $afsc = null): Response
    {
        $edit = $afsc !== null;

        if (!$edit) {
            $params = [
                'name',
            ];

            if (!$this->checkParameters($params)) {
                goto out_return;
            }
        }

        $name = $this->filter_string_default('name');
        $version = $this->filter_string_default('version');
        $edit_code = $this->filter_string_default('editcode');
        $description = $this->filter_string_default('description');
        $fouo = $this->filter_bool_default('fouo', false);
        $hidden = $this->filter_bool_default('hidden', false);
        $obsolete = $this->filter_bool_default('obsolete', false);

        if (!$edit) {
            $afsc = new Afsc();
        }

        $afsc->setName($name);
        $afsc->setDescription($description);
        $afsc->setVersion($version);
        $afsc->setEditCode($edit_code);
        $afsc->setFouo($fouo);
        $afsc->setHidden($hidden);
        $afsc->setObsolete($obsolete);

        foreach ($this->afscs->fetchAll(AfscCollection::SHOW_ALL) as $db_afsc) {
            if ($edit && $db_afsc->getUuid() === $afsc->getUuid()) {
                continue;
            }

            if ($db_afsc->getName() !== $name ||
                $db_afsc->getEditCode() !== $edit_code) {
                continue;
            }

            $this->flash()->add(MessageTypes::ERROR,
                                "The specified AFSC '{$db_afsc->getName()}' already exists in the database");
            goto out_return;
        }

        if (!$this->afscs->save($afsc)) {
            $this->trigger_request_debug(__METHOD__);
            $this->flash()->add(MessageTypes::ERROR,
                                "The specified AFSC '{$afsc->getName()}' could not be added to the database");
            goto out_return;
        }

        $edit
            ? $this->log->info("edit afsc :: {$afsc->getName()} [{$afsc->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}")
            : $this->log->info("add afsc :: {$afsc->getName()} [{$afsc->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            $edit
                                ? "The specified AFSC '{$afsc->getName()}' was modified successfully"
                                : "The specified AFSC '{$afsc->getName()}' was added to the database");

        out_return:
        return $this->redirect('/admin/cdc/afsc');
    }

    public function do_afsc_edit(string $uuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC does not exist');

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/cdc/afsc');
        }

        return $this->do_afsc_add($afsc);
    }

    private function do_afsc_disable_restore(string $uuid, bool $disable): Response
    {
        $disable_restore_str = $disable
            ? 'disable'
            : 'restore';

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
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

            $this->log->alert("{$disable_restore_str} afsc failed :: {$afsc->getName()} [{$afsc->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $this->log->notice("{$disable_restore_str} afsc :: {$afsc->getName()} [{$afsc->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");
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

    public function do_afsc_delete(string $uuid): Response
    {
        if (!CDC_DEBUG) {
            $this->trigger_request_debug(__METHOD__);
            throw new RuntimeException('This endpoint cannot be accessed when debug mode is disabled');
        }

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC does not exist');

            return $this->redirect('/admin/cdc/afsc');
        }

        $this->afscs->delete($afsc);

        $this->log->alert("delete afsc :: {$afsc->getName()} [{$afsc->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");
        $this->flash()->add(MessageTypes::SUCCESS,
                            'The specified AFSC was removed successfully');

        return $this->redirect('/admin/cdc/afsc');
    }

    private function do_afsc_question_add_common(
        Afsc $afsc,
        Question $question,
        array $answers,
        bool $legacy
    ): Response {
        $this->questions->save($afsc, $question);
        $this->answers->saveArray($afsc, $answers);

        $this->log->alert("add question :: {$afsc->getName()} [{$afsc->getUuid()}] :: {$question->getUuid()} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The question was added successfully'
        );

        $this->session->remove(self::NEW_QUESTION_TMP);
        return $this->redirect($legacy
                                   ? "/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy"
                                   : "/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
    }

    private function do_afsc_question_add_legacy(Afsc $afsc): Response
    {
        $params = [
            'questionText',
            'answerCorrect',
            'answers',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy");
        }

        $qtext = $this->get('questionText');
        $acorrect = $this->filter_int_default('answerCorrect');
        $answers = $this->get('answers');

        if (!is_array($answers)) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided answer data was incorrectly formatted'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy");
        }

        $new_question_tmp = [
            'mode' => 'legacy',
            'qtext' => $qtext,
            'answers' => $answers,
            'acorrect' => $acorrect,
        ];

        $this->session->set(self::NEW_QUESTION_TMP, $new_question_tmp);

        if (count($answers) !== 4) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The request must include exactly four answers'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy");
        }

        if (trim($qtext) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided question cannot be blank'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy");
        }

        foreach ($answers as $answer) {
            if (trim($answer) === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'Each provided answer must have content'
                );

                return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy");
            }
        }

        if (!isset($answers[ $acorrect ])) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The answer marked as "correct" was not one of the provided answers'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add/legacy");
        }

        foreach ($this->questions->fetchAfsc($afsc) as $db_question) {
            if ($db_question->getText() === $qtext) {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The provided question already exists in the database -- edit or delete this question instead'
                );

                return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/{$db_question->getUuid()}");
            }
        }

        $quuid = UUID::generate();
        $question = new Question();
        $question->setAfscUuid($afsc->getUuid());
        $question->setUuid($quuid);
        $question->setText($qtext);

        $new_answers = [];
        foreach ($answers as $k => $answer) {
            $a_uuid = UUID::generate();
            $new_answer = new Answer();
            $new_answer->setUuid($a_uuid);
            $new_answer->setText($answer);
            $new_answer->setCorrect($acorrect === $k);
            $new_answer->setQuestionUuid($quuid);

            $new_answers[ $a_uuid ] = $new_answer;
        }

        return $this->do_afsc_question_add_common($afsc, $question, $new_answers, true);
    }

    private function do_afsc_question_add_combined(Afsc $afsc): Response
    {
        $params = [
            'questionData',
            'answerCorrect',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
        }

        $qdata = $this->get('questionData');
        $acorrect = $this->filter_int_default('answerCorrect');

        if (trim($qdata) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided question and answer data cannot be blank'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
        }

        $new_question_tmp = [
            'mode' => 'combined',
            'qdata' => $qdata,
        ];

        $qdata_split = preg_split("/\r\n(.)\. /", $qdata);

        if (count($qdata_split) === 1) {
            $qdata_tmp = $qdata;
            $qdata_tmp = preg_replace("/( [a]\. )/", "\r\na. ", $qdata_tmp);
            $qdata_tmp = preg_replace("/( [b]\. )/", "\r\nb. ", $qdata_tmp);
            $qdata_tmp = preg_replace("/( [c]\. )/", "\r\nc. ", $qdata_tmp);
            $qdata_tmp = preg_replace("/( [d]\. )/", "\r\nd. ", $qdata_tmp);
            $qdata_split = preg_split("/\r\n(.)\. /", $qdata_tmp);
            unset($qdata_tmp);
        }

        $qtext = array_shift($qdata_split);
        $qtext = preg_replace("/\r\n/", ' ', $qtext);
        $qtext = preg_replace("/^[\d]+\. \([\d]+\)/", null, $qtext);
        $answers = array_values($qdata_split);

        unset($qdata_split);

        $this->session->set(self::NEW_QUESTION_TMP, $new_question_tmp);

        if (count($answers) !== 4) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The request must include exactly four answers'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
        }

        if (trim($qtext) === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The provided question cannot be blank'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
        }

        foreach ($this->questions->fetchAfsc($afsc) as $db_question) {
            if ($db_question->getText() === $qtext) {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The provided question already exists in the database -- edit or delete this question instead'
                );

                return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/{$db_question->getUuid()}");
            }
        }

        foreach ($answers as $answer) {
            if (trim($answer) === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'You must provide four answers.  Make sure that each answer has a letter and a period ' .
                    'followed by a space at the beginning: e.g., A._ where the underscore is a space.'
                );

                return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
            }
        }

        if (!isset($answers[ $acorrect ])) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The answer marked as "correct" was not one of the provided answers'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/add");
        }

        array_walk(
            $answers,
            static function ($v) {
                return preg_replace("/\.$/", null, $v);
            }
        );

        $quuid = UUID::generate();
        $question = new Question();
        $question->setAfscUuid($afsc->getUuid());
        $question->setUuid($quuid);
        $question->setText($qtext);

        $new_answers = [];
        foreach ($answers as $k => $answer) {
            $a_uuid = UUID::generate();
            $new_answer = new Answer();
            $new_answer->setUuid($a_uuid);
            $new_answer->setText($answer);
            $new_answer->setCorrect($acorrect === $k);
            $new_answer->setQuestionUuid($quuid);

            $new_answers[ $a_uuid ] = $new_answer;
        }

        return $this->do_afsc_question_add_common($afsc, $question, $new_answers, false);
    }

    public function do_afsc_question_add(string $uuid): Response
    {
        $this->session->remove(self::NEW_QUESTION_TMP);

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        if ($this->has('questionText')) {
            return $this->do_afsc_question_add_legacy($afsc);
        }

        return $this->do_afsc_question_add_combined($afsc);
    }

    public function do_afsc_question_delete(string $uuid, string $quuid): Response
    {
        if (!CDC_DEBUG) {
            $this->trigger_request_debug(__METHOD__);
            throw new RuntimeException('This endpoint cannot be accessed when debug mode is disabled');
        }

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/cdc/afsc');
        }

        $this->questions->delete($quuid);

        $this->log->warning("delete question :: {$afsc->getName()} [{$afsc->getUuid()}] :: {$quuid} :: user {$this->auth_helpers->get_user_uuid()}");
        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The question was successfully deleted'
        );

        return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions");
    }

    private function do_afsc_question_disable_restore(string $uuid, string $quuid, bool $disable): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/cdc/afsc');
        }

        $question = $this->questions->fetch($afsc, $quuid);

        if (!$question) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question does not exist or does not belong to this AFSC'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        if ($disable === $question->isDisabled()) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question is already in the desired state'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions");
        }

        $question->setDisabled($disable);
        $this->questions->save($afsc, $question);

        $this->log->info(($disable
                             ? 'disable'
                             : 'restore') .
                         " question :: {$afsc->getName()} [{$afsc->getUuid()}] :: {$quuid} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The question was successfully ' . ($disable
                ? 'disabled'
                : 'restored')
        );

        return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions");
    }

    public function do_afsc_question_disable(string $uuid, string $quuid): Response
    {
        return $this->do_afsc_question_disable_restore($uuid, $quuid, true);
    }

    public function do_afsc_question_restore(string $uuid, string $quuid): Response
    {
        return $this->do_afsc_question_disable_restore($uuid, $quuid, false);
    }

    public function do_afsc_question_edit(string $uuid, string $quuid): Response
    {
        $params = [
            'questionText',
            'answerCorrect',
        ];

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect('/admin/cdc/afsc');
        }

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $question = $this->questions->fetch($afsc, $quuid);

        if (!$question) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question does not exist or does not belong to this AFSC'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $qtext = $this->get('questionText');
        $acorrect = $this->filter_string_default('answerCorrect');

        $all_params = $this->request->request->all();

        $answers = [];
        foreach ($all_params as $key => $val) {
            if (!str_starts_with($key, 'answer_')) {
                continue;
            }

            $nkey = str_replace('answer_', null, $key);

            $answers[ $nkey ] = $val;
        }

        if (count($answers) !== 4) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'There must be exactly four answers provided in the request'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/{$question->getUuid()}/edit");
        }

        if ($acorrect === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'None of the provided answers were marked as "correct"'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/{$question->getUuid()}/edit");
        }

        if (!isset($answers[ $acorrect ])) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The answer marked as "correct" was not one of the provided answers'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/{$question->getUuid()}/edit");
        }

        $db_answers = $this->answers->fetchByQuestion($afsc, $question);

        if (!is_array($db_answers) || count($db_answers) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'There are no answers in the database for the specified question; please create a new question'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $valid_answers = array_intersect_key($db_answers, $answers);

        if (count($valid_answers) !== 4) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'Some of the provided answers are not associated with the question; please contact the site administrator'
            );

            $this->trigger_request_debug(__METHOD__);
            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions/{$question->getUuid()}/edit");
        }

        $changed = false;

        if ($question->getText() !== $qtext) {
            $changed = true;
            $question->setText($qtext);
        }

        foreach ($db_answers as $db_answer) {
            $a_uuid = $db_answer->getUuid();

            if (!isset($answers[ $a_uuid ])) {
                throw new RuntimeException("request answer uuid not present: {$a_uuid}");
            }

            if ($db_answer->getText() === $answers[ $a_uuid ] &&
                $db_answer->isCorrect() === ($acorrect === $a_uuid)) {
                /* no change */
                continue;
            }

            $changed = true;
            $db_answer->setText($answers[ $a_uuid ]);
            $db_answer->setCorrect($acorrect === $a_uuid);
        }

        if ($changed) {
            $this->answers->saveArray($afsc, $db_answers);
            $this->questions->save($afsc, $question);
            $this->log->info("edit question :: {$afsc->getName()} [{$afsc->getUuid()}] :: {$quuid} :: user {$this->auth_helpers->get_user_uuid()}");
        }

        $this->flash()->add(
            $changed
                ? MessageTypes::SUCCESS
                : MessageTypes::INFO,
            $changed
                ? 'The question data has been modified successfully'
                : 'No modifications were made to the question or answer data'
        );

        return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions");
    }

    /**
     * @param string $uuid
     * @return Response
     * @throws JsonException
     */
    public function show_afsc_home(string $uuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $n_users = $this->user_afscs->countAllByAfsc($afsc);
        $afsc_questions = $this->question_helpers->getNumQuestionsByAfsc([$afsc->getUuid()]);

        $n_afsc_questions = 0;
        if (is_array($afsc_questions) && $afsc_questions) {
            $n_afsc_questions = (int)array_shift($afsc_questions);
        }

        $data = [
            'afsc' => $afsc,
            'numUsers' => $n_users,
            'numQuestions' => $n_afsc_questions,
            'numTests' => $this->test_stats->afscCountOverall($afsc),
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
     * @param string $uuid
     * @return Response
     */
    public function show_afsc_users(string $uuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $sort = $sortCol
            ? [$this->validate_sort($sortCol, $sortDir)]
            : [
                new UserSortOption(UserSortOption::COL_NAME_LAST),
                new UserSortOption(UserSortOption::COL_NAME_FIRST),
                new UserSortOption(UserSortOption::COL_RANK),
                new UserSortOption(UserSortOption::COL_BASE),
            ];

        $sort[] = new UserSortOption(UserSortOption::COL_UUID);

        $n_users = $this->user_afscs->countAllByAfsc($afsc);
        $afsc_users = $this->user_afscs->fetchAllByAfsc($afsc, $curPage * $numRecords, $numRecords, $sort);

        $users = $this->users->fetchArray($afsc_users->getUsers(), $sort);

        $base_uuids = [];
        foreach ($users as $user) {
            $base_uuids[ $user->getBase() ] = true;
        }

        $bases = $this->bases->fetchArray(array_keys($base_uuids));

        $pagination = ArrayPaginator::buildLinks(
            "/admin/cdc/afsc/{$uuid}/users",
            $curPage,
            ArrayPaginator::calcNumPagesNoData(
                $n_users,
                $numRecords
            ),
            $numRecords,
            $n_users,
            $sortCol,
            $sortDir
        );

        $data = [
            'afsc' => $afsc,
            'users' => $users,
            'bases' => $bases,
            'pagination' => $pagination,
            'sort' => [
                'col' => $sortCol,
                'dir' => $sortDir,
            ],
        ];

        return $this->render(
            'admin/cdc/afsc/afsc-users.html.twig',
            $data
        );
    }

    private function show_afsc_disable_restore(string $uuid, bool $disable): Response
    {
        $disable_restore_str = $disable
            ? 'disable'
            : 'restore';

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
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
        if (is_array($afsc_questions) && $afsc_questions) {
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

    public function show_afsc_delete(string $uuid): Response
    {
        if (!CDC_DEBUG) {
            throw new RuntimeException('This endpoint cannot be accessed when debug mode is disabled');
        }

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC does not exist');

            return $this->redirect('/admin/cdc/afsc');
        }

        $data = [
            'afsc' => $afsc,
        ];

        return $this->render(
            "admin/cdc/afsc/afsc-delete.html.twig",
            $data
        );
    }

    public function show_afsc_edit(string $uuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified AFSC does not exist');

            return $this->redirect('/admin/cdc/afsc');
        }

        $data = [
            'afsc' => $afsc,
        ];

        return $this->render(
            "admin/cdc/afsc/afsc-edit.html.twig",
            $data
        );
    }

    public function show_afsc_restore(string $uuid): Response
    {
        return $this->show_afsc_disable_restore($uuid, false);
    }

    public function show_afsc_list(): Response
    {
        $flags = AfscCollection::SHOW_ALL;

        $afscList = $this->afscs->fetchAll($flags,
                                           [
                                               AfscCollection::COL_IS_OBSOLETE => AfscCollection::ORDER_ASC,
                                               AfscCollection::COL_IS_HIDDEN => AfscCollection::ORDER_ASC,
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
            $afscUserCounts[ $afscUser->getAfsc() ] = count($afscUser->getUsers());
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

    public function show_afsc_question(string $uuid, string $quuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $question = $this->questions->fetch($afsc, $quuid);

        if (!$question) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question does not exist or does not belong to this AFSC'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $answers = $this->answers->fetchByQuestion($afsc, $question);

        if (!is_array($answers) || count($answers) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'There are no answers in the database for the specified question'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $data = [
            'afsc' => $afsc,
            'question' => $question,
            'answers' => $answers,
        ];

        return $this->render(
            'admin/cdc/afsc/afsc-question.html.twig',
            $data
        );
    }

    public function show_afsc_question_delete(string $uuid, string $quuid): Response
    {
        if (!CDC_DEBUG) {
            throw new RuntimeException('This endpoint cannot be accessed when debug mode is disabled');
        }

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $question = $this->questions->fetch($afsc, $quuid);

        if (!$question) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question does not exist or does not belong to this AFSC'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $answers = $this->answers->fetchByQuestion($afsc, $question);

        if (!is_array($answers) || count($answers) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'There are no answers in the database for the specified question'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $data = [
            'afsc' => $afsc,
            'question' => $question,
            'answers' => $answers,
        ];

        return $this->render(
            'admin/cdc/afsc/afsc-question-delete.html.twig',
            $data
        );
    }

    private function show_afsc_question_disable_restore(string $uuid, string $quuid, bool $disable): Response
    {
        $disable_restore_str = $disable
            ? 'disable'
            : 'restore';

        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $question = $this->questions->fetch($afsc, $quuid);

        if (!$question) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question does not exist or does not belong to this AFSC'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        if ($disable === $question->isDisabled()) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question is already in the desired state'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}/questions");
        }

        $answers = $this->answers->fetchByQuestion($afsc, $question);

        if (!is_array($answers) || count($answers) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'There are no answers in the database for the specified question'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $data = [
            'afsc' => $afsc,
            'question' => $question,
            'answers' => $answers,
        ];

        return $this->render(
            "admin/cdc/afsc/afsc-question-{$disable_restore_str}.html.twig",
            $data
        );
    }

    public function show_afsc_question_disable(string $uuid, string $quuid): Response
    {
        return $this->show_afsc_question_disable_restore($uuid, $quuid, true);
    }

    public function show_afsc_question_restore(string $uuid, string $quuid): Response
    {
        return $this->show_afsc_question_disable_restore($uuid, $quuid, false);
    }

    public function show_afsc_question_add(string $uuid, bool $legacy = false): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $data = [
            'afsc' => $afsc,
            'new_question_tmp' => $this->session->get(self::NEW_QUESTION_TMP),
        ];

        return $this->render(
            $legacy
                ? 'admin/cdc/afsc/afsc-question-add-legacy.html.twig'
                : 'admin/cdc/afsc/afsc-question-add-combined.html.twig',
            $data
        );
    }

    public function show_afsc_question_add_legacy(string $uuid): Response
    {
        return $this->show_afsc_question_add($uuid, true);
    }

    public function show_afsc_question_edit(string $uuid, string $quuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $question = $this->questions->fetch($afsc, $quuid);

        if (!$question) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified question does not exist or does not belong to this AFSC'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $answers = $this->answers->fetchByQuestion($afsc, $question);

        if (!is_array($answers) || count($answers) === 0) {
            $this->flash()->add(
                MessageTypes::WARNING,
                'There are no answers in the database for the specified question'
            );

            return $this->redirect("/admin/cdc/afsc/{$afsc->getUuid()}");
        }

        $data = [
            'afsc' => $afsc,
            'question' => $question,
            'answers' => $answers,
        ];

        return $this->render(
            'admin/cdc/afsc/afsc-question-edit.html.twig',
            $data
        );
    }

    public function show_afsc_questions(string $uuid): Response
    {
        $afsc = $this->afscs->fetch($uuid);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::WARNING,
                'The specified AFSC does not exist'
            );

            return $this->redirect('/admin/cdc/afsc');
        }

        $cdc_data = $this->cdc_datas->fetch($afsc);

        $data = [
            'afsc' => $afsc,
            'question_data' => $cdc_data->getQuestionAnswerData(),
            /*'missed_data' => $this->test_stats->afscMissedByQuestion($afsc),*/ // this takes way too long
        ];

        return $this->render(
            'admin/cdc/afsc/afsc-questions.html.twig',
            $data
        );
    }
}
