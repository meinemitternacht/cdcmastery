<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 10/3/2015
 * Time: 2:13 AM
 */
$answerManager = new CDCMastery\AnswerManager($db, $systemLog);
$questionManager = new CDCMastery\QuestionManager($db, $systemLog, $afscManager, $answerManager);

$afscList = $afscManager->listAFSCUUID(true);
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <table>
                <tr>
                    <td>AFSC</td>
                    <td>Edit Link</td>
                    <td>Question Text</td>
                </tr>
            <?php
            foreach($afscList as $afscUUID){
                $afscManager->loadAFSC($afscUUID);
                $questionManager->setAFSCUUID($afscUUID);
                $questionList = $questionManager->listQuestionsForAFSC();

                if(is_array($questionList)) {
                    foreach ($questionList as $questionUUID) {
                        if($afscManager->afscFOUO){
                            $questionManager->setFOUO(true);
                        }
                        else{
                            $questionManager->setFOUO(false);
                        }
                        $questionManager->loadQuestion($questionUUID);
                        $questionText = $questionManager->getQuestionText();

                        if(preg_match('/^([0-9]+\. )?\([0-9]+\) ?/',$questionText)):
                            $questionText = preg_replace('/^([0-9]+\. )?\([0-9]+\) ?/','',$questionText);
                            //$questionManager->setQuestionText($questionText);
                            ?>
                            <tr>
                                <td><?php echo $afscManager->getAFSCName(); ?></td>
                                <td><a href="/admin/cdc-data/<?php echo $afscManager->getUUID(); ?>/question/<?php echo $questionUUID; ?>/edit">Edit</a></td>
                                <td><?php echo $questionText; ?></td>
                                <td><?php /*if($questionManager->saveQuestion()){ echo "Updated"; } else { echo "Not updated."; } */ ?></td>
                            </tr>
                            <?php
                        endif;
                        /*$encoding = mb_detect_encoding($questionText);

                        if(empty($encoding)):
                            $questionText = preg_replace('/A(.)10/','A-10',$questionText);
                            $questionText = preg_replace('/A(.)10(.)s/','A-10\'s',$questionText);
                            $questionText = preg_replace('/F(.)16/','F-16',$questionText);
                            $questionText = preg_replace('/F(.)16(.)s/','F-16\'s',$questionText);
                            $questionText = preg_replace('/F(.)15/','F-15',$questionText);
                            $questionText = preg_replace('/U(.)2/','U-2',$questionText);
                            $questionText = preg_replace('/U(.)2(.)s/','U-2\'s',$questionText);
                            $questionText = preg_replace('/RQ(.)1/','RQ-1',$questionText);
                            $questionText = preg_replace('/RQ(.)1(.)s/','RQ-1\'s',$questionText);
                            $questionText = preg_replace('/RQ(.) 1(.)s/','RQ-1\'s',$questionText);
                            $questionText = preg_replace('/F100(.)PW(.)220/','F100-PW-220',$questionText);
                            $questionText = preg_replace('/F110(.)GE(.)100/','F110-GE-100',$questionText);
                            $questionText = preg_replace('/TF34(.)GE(.)100A/','TF34-GE-100A',$questionText);
                            $questionText = preg_replace('/JP(.)8/','JP-8\'s',$questionText);
                            $questionText = preg_replace('/aircraft(.)s/','aircraft\'s',$questionText);
                            $questionText = preg_replace('/\((.)3\)/','(±3)',$questionText);
                            $questionText = preg_replace('/APU(.)driven/','APU-driven',$questionText);

                            $questionManager->setQuestionText($questionText);

                            if($questionManager->saveQuestion()){
                                $saveOutput = "Updated";
                            }
                            else{
                                $saveOutput = "Not Updated";
                            ?>
                            <tr>
                                <td><?php echo $afsc->getAFSCName(); ?></td>
                                <td><a href="/admin/cdc-data/<?php echo $afsc->getUUID(); ?>/question/<?php echo $questionUUID; ?>/edit">Edit</a></td>
                                <td><?php echo $encoding; ?></td>
                                <td><?php echo $questionText; ?></td>
                            </tr>
                            <?php
                        endif;*/
                    }
                }
            }
            ?>
            </table>
        </div>
    </div>
</div>
