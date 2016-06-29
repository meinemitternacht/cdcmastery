<?php
/**
 * Created by PhpStorm.
 * User: claude
 * Date: 6/27/16
 * Time: 3:32 AM
 */

$matchPassArray = [];
$matchFailArray = [];
$matchIgnoreArray = [];

$matchPassCount = 0;
$matchFailCount = 0;
$matchIgnoreCount = 0;

$stmt = $db->prepare("SELECT uuid, userEmail FROM userData ORDER BY userEmail ASC");
$stmt->execute();
$stmt->bind_result($uuid,$userEmailRaw);

while($stmt->fetch()){
    $userEmail = strtolower($userEmailRaw);
    if(preg_match("/\.mil/",$userEmail)) {
        if (preg_match("/(([A-Za-z_-])+\.([A-Za-z_-])+(\.[0-9A-Za-z]+)?\@(([A-Za-z]+\.afcent\.af)|([A-Za-z]+)\.af|us\.af|mail)(\.mil))|(([A-Za-z_-])+\.([A-Za-z_-])+\.([A-Za-z0-9_-])+(\.mil)\@(mail)(\.mil))/", $userEmail)) {
            $matchPassArray[] = $userEmail;
        }
        else {
            $matchFailArray[] = $userEmail;
            $matchFailUsers[] = $uuid;
        }
    }
    else{
        $matchIgnoreArray[] = $userEmail;
    }
}

$stmt->close();

if(isset($matchFailArray) && is_array($matchFailArray)){
    $matchFailCount = count($matchFailArray);
    $sysMsg->addMessage("Failed Matches: ".$matchFailCount,"danger");
}

if(isset($matchPassArray) && is_array($matchPassArray)){
    $matchPassCount = count($matchPassArray);
    $sysMsg->addMessage("Passed Matches: ".$matchPassCount,"success");
}

if(isset($matchIgnoreArray) && is_array($matchIgnoreArray)){
    $matchIgnoreCount = count($matchIgnoreArray);
    $sysMsg->addMessage("Ignored Matches: ".$matchIgnoreCount,"warning");
}

$totalMatches = $matchFailCount + $matchPassCount + $matchIgnoreCount;
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h1>Test E-mail Address Validity</h1>
                </header>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="4u">
            <strong>Passes</strong>
            <ul>
            <?php foreach($matchPassArray as $matchPass): ?>
                <li><?php echo $matchPass; ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
        <div class="4u">
            <strong>Failures</strong>
            <ul>
                <?php foreach($matchFailArray as $matchFail): ?>
                    <li><?php echo $matchFail; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="4u">
            <strong>Ignored</strong>
            <ul>
                <?php foreach($matchIgnoreArray as $matchIgnore): ?>
                    <li><?php echo $matchIgnore; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
