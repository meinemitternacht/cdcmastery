<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 2:06 PM
 */

$flashCardList = $flashCardManager->listFlashCards();
?>
<section>
    <header>
        <h2>Flash Card Data for <?php echo $flashCardManager->getCategoryName(); ?></h2>
    </header>
    <p>
        Note: This data is truncated to fit in the table.  Click on the "Edit" link to the right of the card data to view the full text.
    </p>
    <?php if(is_array($flashCardList) && !empty($flashCardList)): ?>
        <table>
            <thead>
                <tr>
                    <th>Card Front</th>
                    <th>Card Back</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($flashCardList as $flashCardUUID): ?>
                <?php if($flashCardManager->loadFlashCardData($flashCardUUID)): ?>
                    <tr>
                        <td><?php echo $flashCardManager->getFrontText(); ?></td>
                        <td><?php echo $flashCardManager->getBackText(); ?></td>
                        <td><a href="/admin/card-data/<?php echo $workingChild; ?>/edit/<?php echo $flashCardUUID; ?>"</td>
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
        There are no flash cards for this category.
    <?php endif; ?>
</section>
