<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/11/2015
 * Time: 4:29 AM
 */

$testUUID = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;
$testManager = new CDCMastery\TestManager($db, $systemLog, $afscManager);

if($testManager->loadIncompleteTest($testUUID)) {
    $questionList = $testManager->getIncompleteQuestionList();
    if($questionList) {
        if ($testManager->loadTestData($testUUID)) {
            $testData = $testManager->getTestData();

            $answerManager = new CDCMastery\AnswerManager($db, $systemLog);
            $questionManager = new CDCMastery\QuestionManager($db, $systemLog, $afscManager, $answerManager);
            ?>
            Incomplete test started by
            <em><?php echo $userManager->getUserNameByUUID($testManager->getIncompleteUserUUID()); ?></em> on
            <strong><?php echo $cdcMastery->outputDateTime($testManager->getIncompleteTimeStarted(), $_SESSION['timeZone']); ?></strong>
            <table>
                <tr>
                    <th>Percent Complete</th>
                    <th>Total Questions</th>
                    <th>Questions Answered</th>
                    <th>Current Question</th>
                    <th>AFSC</th>
                    <th>Combined Test</th>
                </tr>
                <tr>
                    <td><?php echo $testManager->getIncompletePercentComplete(); ?></td>
                    <td><?php echo $testManager->getIncompleteTotalQuestions(); ?></td>
                    <td><?php echo $testManager->getIncompleteQuestionsAnswered(); ?></td>
                    <td><?php echo $testManager->getIncompleteCurrentQuestion(); ?></td>
                    <td>
                        <?php
                        $afscList = $testManager->getIncompleteAFSCList();
                        if (is_array($afscList)) {
                            array_walk($afscList, array($afscManager, "getAFSCNameCallback"));
                            echo implode(",", $afscList);
                        } else {
                            echo $afscManager->getAFSCName($afscUUID);
                        }
                        ?>
                    </td>
                    <td><?php echo ($testManager->getIncompleteCombinedTest()) ? "Yes" : "No"; ?></td>
                </tr>
            </table>
            <div class="clearfix">&nbsp;</div>
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>AFSC</th>
                    <th>Question</th>
                    <th>Answer</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i=1;
                foreach ($questionList as $questionUUID):
                    $questionManager->loadQuestion($questionUUID);
                    if (isset($testData[$questionUUID])):
                        if ($questionManager->queryQuestionFOUO($questionUUID)) {
                            $answerManager->setFOUO(true);
                        } else {
                            $answerManager->setFOUO(false);
                        }
                        $answerManager->loadAnswer($testData[$questionUUID]); ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $afscManager->getAFSCName($questionManager->getAFSCUUID()); ?></td>
                            <td><?php echo $questionManager->getQuestionText(); ?></td>
                            <td><span class="<?php if ($answerManager->getAnswerCorrect()) {
                                    echo "text-success";
                                } else {
                                    echo "text-warning";
                                } ?>"><?php echo $answerManager->getAnswerText(); ?></span></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $afscManager->getAFSCName($questionManager->getAFSCUUID()); ?></td>
                            <td><?php echo $questionManager->getQuestionText(); ?></td>
                            <td>Not answered.</td>
                        </tr>
                    <?php endif; ?>
                    <?php $i++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
    }
    else {
        ?>
        This incomplete test no longer exists.  It has either been completed or deleted by the user.
        <?php
    }
}
else {
    ?>
    This incomplete test no longer exists.  It has either been completed or deleted by the user.
    <?php
}
?>
