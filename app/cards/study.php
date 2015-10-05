<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 3:08 PM
 */

$flashCardManager = new flashCardManager($db,$log);
$categoryUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if(!$categoryUUID){
    $sysMsg->addMessage("Category must be specified.");
    $cdcMastery->redirect("/cards");
}
elseif(!$flashCardManager->loadCardCategory($categoryUUID)){
    $sysMsg->addMessage("That card category does not exist!");
    $cdcMastery->redirect("/cards");
}
?>
<script type="text/javascript">

    $(document).ready(function () {
        function loading_show() {
            $('#loading').html("<img src='/images/loader.gif'>").fadeIn('fast');
        }

        function navigateFlashCards(destination) {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: destination
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            return false;

        }

        $(window).keydown(function(e) {
            switch (e.keyCode) {
                case 35:
                    e.preventDefault();
                    navigateFlashCards("lastCard");
                    return;
                case 36:
                    e.preventDefault();
                    navigateFlashCards("firstCard");
                    return;
                case 37:
                    navigateFlashCards("previousCard");
                    return;
                case 38:
                    e.preventDefault();
                    return;
                case 39:
                    navigateFlashCards("nextCard");
                    return;
                case 40:
                    e.preventDefault();
                    return;
            }
        });

        $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
            action: 'showCard',
            actionData: '1'
        }, function (response) {
            setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
        });

        $(document).on('click', '#flipCardToBack', function () {
            $('#cardFront').hide();
            $('#cardBack').show();
        });

        $(document).on('click', '#flipCardToFront', function () {
            $('#cardBack').hide();
            $('#cardFront').show();
        });

        $('#goFirst').click(function () {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'firstQuestion'
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            return false;

        });

        $('#goPrevious').click(function () {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'previousCard'
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            return false;

        });

        $('#goNext').click(function () {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'nextCard'
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            return false;

        });

        $('#goLast').click(function () {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'lastCard'
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            return false;

        });

    });

    function finishAjax(id, response) {
        $('#loading').fadeOut('fast');
        $('#' + id).html(unescape(response));
        $('.test-nav').fadeIn('fast');
    }

</script>
<div id="loading"><img src="/images/loader.gif"/></div>
<div class="container">
    <div class="row">
        <div class="12u">
            <section>
                <h2><?php echo $flashCardManager->getCategoryName(); ?></h2>
                <br>
                <div id="flashCardArea"></div>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <div class="test-nav" style="display: none;">
                    <button class="test-nav-button" id="goFirst" title="First Card">&lt;&lt;</button>
                    <button class="test-nav-button" id="goPrevious" title="Previous Card">&lt;</button>
                    <button class="test-nav-button" id="goNext" title="Next Card">&gt;</button>
                    <button class="test-nav-button" id="goLast" title="Last Card">&gt;&gt;</button>
                </div>
                <div class="clearfix">&nbsp;</div>
                <div class="text-center">
                    <strong>Tip:</strong> You can use the <strong>Left</strong> and <strong>Right</strong> arrow keys to navigate through the test.  <strong>Home</strong> and <strong>End</strong> will take you to the first and last questions.<br>
                    To answer a question, click on the answer or press the numbers <strong>1-4</strong> or the letters <strong>a</strong>, <strong>b</strong>, <strong>c</strong>, or <strong>d</strong>.
                </div>
            </section>
        </div>
    </div>
</div>
