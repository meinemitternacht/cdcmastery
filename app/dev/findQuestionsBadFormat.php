<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 10/3/2015
 * Time: 2:13 AM
 */
$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);

$afscList = $afsc->listAFSCUUID(true);
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <table>
                <tr>
                    <td>AFSC</td>
                    <td>Question Text</td>
                </tr>
            <?php
            foreach($afscList as $afscUUID){
                $afsc->loadAFSC($afscUUID);
                $questionManager->setAFSCUUID($afscUUID);
                $questionList = $questionManager->listQuestionsForAFSC();

                if(is_array($questionList)) {
                    foreach ($questionList as $questionUUID) {
                        if($afsc->afscFOUO){
                            $questionManager->setFOUO(true);
                        }
                        else{
                            $questionManager->setFOUO(false);
                        }
                        $questionManager->loadQuestion($questionUUID);
                        $questionText = $questionManager->getQuestionText();
                        ?>
                        <tr>
                            <td><?php echo $afsc->getAFSCName(); ?></td>
                            <td><?php echo $questionText; ?></td>
                        </tr>
                        <?php
                        /*if(preg_match("/(([0-9]+\.) (\([0-9]+\))?)/",$questionText)):
                            $questionManager->setQuestionText(preg_replace("/^[0-9]+\. \([0-9]+\)/","",$questionText));
                            if($questionManager->saveQuestion()): ?>
                            <tr>
                                <td><?php echo $afsc->getAFSCName(); ?></td>
                                <td>Updated <?php echo $questionUUID; ?></td>
                            </tr>
                            <?php
                            else: ?>
                            <tr>
                                <td><?php echo $afsc->getAFSCName(); ?></td>
                                <td><strong>DID NOT UPDATE <?php echo $questionUUID; ?></strong></td>
                            </tr>
                            <?php endif;
                        endif;*/
                    }
                }
            }
            ?>
            </table>
        </div>
    </div>
</div>
