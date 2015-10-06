<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 10/6/2015
 * Time: 1:02 AM
 */
$flashCardManager = new flashCardManager($db,$log);

$stmt = $db->prepare("SELECT systemLogData.dataType, systemLogData.data, action, timestamp, microtime FROM systemLogData LEFT JOIN systemLog ON systemLogData.logUUID=systemLog.uuid WHERE systemLog.action='FLASH_CARD_ADD' AND systemLogData.dataType='Card UUID' AND systemLog.microtime > '1444107370' ORDER BY systemLog.microtime DESC");

if($stmt->execute()){
    $stmt->bind_result($dataType,$data,$action,$timestamp,$microtime);
    ?>
    <table>
        <tr>
            <td>Data Type</td>
            <td>Data</td>
            <td>Action</td>
            <td>Timestamp</td>
            <td>Microtime</td>
        </tr>
    <?php
    $i=0;
    while($stmt->fetch()){ ?>
        <tr>
            <td><?php echo $dataType; ?></td>
            <td><?php echo $data; ?></td>
            <td><?php echo $action; ?></td>
            <td><?php echo $timestamp; ?></td>
            <td><?php echo $microtime; ?></td>
        </tr>
        <?php
        $fcArray[$i]['timestamp'] = $timestamp;
        $fcArray[$i]['microtime'] = $microtime;
        $fcArray[$i]['dataType'] = $dataType;
        $fcArray[$i]['data'] = $data;
        $i++;
    }
    ?>
    </table>
    <table>
    <?php

    $stmt->close();

    foreach($fcArray as $fcRow) {
        if ($flashCardManager->loadFlashCardData($fcRow['data'])) {
            ?>
            <tr>
                <td><?php echo $flashCardManager->getFrontText(); ?></td>
                <td><?php echo $flashCardManager->getBackText(); ?></td>
                <td><?php echo $fcRow['microtime']; ?></td>
                <td><?php echo $fcRow['timestamp']; ?></td>
            </tr>
            <?php
            $sqlArray[] = $fcRow['data'];
        }
    }
    ?>
    </table>
    <?php
    /*
    $query = "UPDATE flashCardData SET cardCategory='a056acb9-22ef-44fc-823a-36ad9ef90e75' WHERE uuid IN ('".implode("','",$sqlArray)."')";
    echo $query;
    if($db->query($query)){
        echo "Updated.";
    }*/
}