<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/7/16
 * Time: 9:35 PM
 */

$answerManager = new answerManager($db,$log);
$questionManager = new questionManager($db,$log,$afsc,$answerManager);

$answerList = $answerManager->listAnswers();
?>
<div class="container">
    <div class="row">
        <div class="12u">
            <table>
                <tr>
                    <td>Question Text</td>
                    <td>Answer Text</td>
                </tr>
                <?php
                foreach($answerList as $answerUUID => $answerData){
                    $encoding = mb_detect_encoding($answerData['answerText']);

                    if(empty($encoding)):
                        $questionManager->loadQuestion($answerData['questionUUID']);
                        /*
                        if(preg_match('/^[a|b|c|d]\. ?/',$answerData['answerText'])):
                        $answerData['answerText'] = preg_replace('/^[a|b|c|d]\. ?/','',$answerData['answerText']);
                        $answerManager->setFOUO($answerData['fouo']);
                        if($answerManager->loadAnswer($answerUUID)){
                            $answerManager->setAnswerText($answerData['answerText']);
                            if($answerManager->saveAnswer()){
                                $updated = "Yes";
                            }
                            else{
                                $updated = "No";
                            }
                        }
                        else{
                            $updated = "Did not load";
                        }
                        */
                        $updated = "n/a";

                        $answerText = preg_replace('/([0-9]+)(.)(F|C)/','$1°$3',$answerData['answerText']);
                        $answerText = preg_replace('/\((.)([0-9]+)\)/','(±$2)',$answerText);
                        $answerText = preg_replace('/([0-9]+)(.) (up|down)/','$1° $3',$answerText);
                        $answerText = preg_replace('/M(.)24/','M-24',$answerText);
                        $answerText = preg_replace('/10(.)403/','10-403',$answerText);
                        $answerText = preg_replace('/23(.)110/','23-110',$answerText);
                        $answerText = preg_replace('/23(.)210/','23-210',$answerText);
                        $answerText = preg_replace('/23(.)2308/','23-2308',$answerText);
                        $answerText = preg_replace('/33(.)103/','33-103',$answerText);
                        $answerText = preg_replace('/36(.)123/','36-123',$answerText);
                        $answerText = preg_replace('/AFTTP 3(.)21.1/','AFTTP 3-21.1',$answerText);
                        $answerText = preg_replace('/1577(.)(1|2|3|4)/','1577-$2',$answerText);
                        $answerText = preg_replace('/code (.)(3|4).(.)$/','code $2',$answerText);
                        $answerText = preg_replace('/1348(.)1A/','1348-1A',$answerText);
                        $answerText = preg_replace('/\(FED-STD\)(.)313/','(FED-STD)-313',$answerText);
                        $answerText = preg_replace('/(JFBSL|JFBMM|JFBDC|JFBMR|JFBCW|JFBME) (.)/','$1 -',$answerText);
                        $answerText = preg_replace('/(.)(FWP|AWP|02P|03P)\.(.)/','"$2"',$answerText);
                        $answerText = preg_replace('/^(.)(E|B|X|D|AT|AZ|AR|AC)\.(.)$/','$2',$answerText);
                        $answerText = preg_replace('/ (.)(S|F)(.) /',' "$2" ',$answerText);
                    ?>
                    <tr>
                        <td><?php echo $questionManager->getQuestionText(); ?></td>
                        <td><?php echo $answerText; ?></td>
                    </tr>
                    <?php
                    endif;
                }
                ?>
            </table>
        </div>
    </div>
</div>
