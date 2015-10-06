<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 2:06 PM
 */

if($flashCardManager->listFlashCards()){
    $flashCardList = $flashCardManager->flashCardArray;
}
?>
<section>
    <header>
        <h2>Flash Card Data for <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <p>
        Note: This data is truncated to fit within the table.  Click on the "Edit" link to the right of the card data to view the full text.
    </p>
    <?php if(isset($flashCardList) && is_array($flashCardList) && !empty($flashCardList)): ?>
        <table>
            <thead>
                <tr>
                    <th>Card Front</th>
                    <th>Card Back</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($flashCardList as $flashCardRow): ?>
                <?php if($flashCardManager->loadFlashCardData($flashCardRow['uuid'])): ?>
                    <tr>
                        <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getFrontText(),100); ?></td>
                        <td><?php echo $cdcMastery->formatOutputString($flashCardManager->getBackText(),100); ?></td>
                        <td>
                            <a href="/cards/data/<?php echo $workingChild; ?>/delete/<?php echo $flashCardRow['uuid']; ?>"><i class="icon-inline icon-20 ic-delete"></i></a>
                            <a href="/cards/data/<?php echo $workingChild; ?>/edit/<?php echo $flashCardRow['uuid']; ?>"><i class="icon-inline icon-20 ic-pencil"></i></a>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Data could not be retrieved.</td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="clearfix">&nbsp;</div>
        There are no flash cards for this category.  You should add some using the menu to the left.
    <?php endif; ?>
</section>
