<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 1/13/2016
 * Time: 6:54 PM
 */

/*
 * Show some sample data for non-fouo AFSC's
 */

$afscUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if($afscUUID && $afscManager->loadAFSC($afscUUID)):
    if(!$afscManager->getAFSCFOUO()): ?>
    <div class="container">
        <div class="row">
            <div class="8u">
                <section>
                    <header>
                        <h2><?php echo $afscManager->getAFSCName(); ?> Practice Test</h2>
                    </header>
                    <p>
                        Listed below are sample questions for <?php echo $afscManager->getAFSCName(); ?> from our database.  <a href="/auth/register">Register now</a> to start taking tests for this AFSC!  We have <?php echo $afscManager->getTotalQuestions(); ?>
                        questions currently in the database for this category.  <?php if(!empty($afscManager->getAFSCVersion())): ?><br><br>The version of this CDC we have in the database is <strong><?php echo $afscManager->getAFSCVersion(); ?></strong><?php endif; ?>
                    </p>
                    <a href="/about">&laquo; Return to AFSC List</a>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="8u">
                <section>
                    <?php
                    $answerManager = new AnswerManager($db, $systemLog);
                    $questionManager = new QuestionManager($db, $systemLog, $afscManager, $answerManager);

                    $questionManager->setAFSCUUID($afscUUID);
                    $questionList = $questionManager->listQuestionsForAFSC(10,true);

                    if(!empty($questionList) && sizeof($questionList) > 1):
                        $i = 1;
                        $c = 0;
                        foreach($questionList as $questionUUID):
                            if($questionManager->loadQuestion($questionUUID)): ?>
                                <ul style="border-left: 0.5em solid #aaa;background-color:<?php $color = ($c == 0) ? "#DFF5FC" : "#AADFF0"; echo $color; ?>">
                                    <li style="padding:0.3em;font-size:1.1em;">
                                        <strong><?php echo $i; ?>
                                            . <?php echo $questionManager->getQuestionText(); ?></strong><br>
                                        <em>
                                            <?php $answerManager->loadAnswer($answerManager->getCorrectAnswer($questionUUID)); ?>
                                            <?php echo $answerManager->getAnswerText(); ?>
                                        </em>
                                    </li>
                                </ul>
                            <?php endif;
                            $c = ($c == 0) ? 1 : 0;
                            $i++;
                        endforeach;
                    endif; ?>
                </section>
            </div>
        </div>
    </div>
<?php
    else:
        $systemMessages->addMessage("That AFSC is marked For Official Use Only.  In order to view questions, you must register an account.", "info");
        $cdcMastery->redirect("/about");
    endif;
else:
    $systemMessages->addMessage("That AFSC does not exist.", "warning");
    $cdcMastery->redirect("/about");
endif;