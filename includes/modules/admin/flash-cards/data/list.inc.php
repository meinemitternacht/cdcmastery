<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 2:06 PM
 */

if($flashCardManager->getCategoryType() == "afsc"){
    if($flashCardManager->listFlashCards(false,true)){
        $flashCardList = $flashCardManager->flashCardArray;
    }
}
else{
    if($flashCardManager->listFlashCards()){
        $flashCardList = $flashCardManager->flashCardArray;
    }
}
?>
<section>
    <header>
        <h2>Flash Card Data for <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <p>
        Note: This data is truncated to fit in the table.  Click on the "Edit" link to the right of the card data to view the full text.  Note:  If the flash card category is AFSC-based, you will
        have to edit the questions and answers in the CDC Data section of the Admin Panel.
    </p>
    <?php if(is_array($flashCardList) && !empty($flashCardList)): ?>
        <table>
            <thead>
                <tr>
                    <th>Card Front</th>
                    <th>Card Back</th>
                    <?php if($flashCardManager->getCategoryType() != "afsc"): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($flashCardList as $flashCardRow): ?>
                <?php if($flashCardManager->getCategoryType() == "afsc"): ?>
                    <?php if($flashCardManager->loadAFSCFlashCardData($flashCardRow)): ?>
                        <tr>
                            <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getFrontText(),100); ?></td>
                            <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getBackText(),100); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Data could not be retrieved.</td>
                        </tr>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if($flashCardManager->loadFlashCardData($flashCardRow['uuid'])): ?>
                        <tr>
                            <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getFrontText(),100); ?></td>
                            <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getBackText(),100); ?></td>
                            <td><a href="/admin/card-data/<?php echo $workingChild; ?>/edit/<?php echo $flashCardRow['uuid']; ?>">[edit]</a></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">Data could not be retrieved.</td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="clearfix">&nbsp;</div>
        There are no flash cards for this category.
    <?php endif; ?>
</section>
