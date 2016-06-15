<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 10/5/2015
 * Time: 3:08 PM
 */

$flashCardManager = new flashCardManager($db,$log);
$categoryUUID = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;
$sessionAction = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : false;

if($sessionAction){
    switch($sessionAction){
        case "new":
            $flashCardManager->clearSession();
            $flashCardManager->loadCardCategory($categoryUUID);
            if($flashCardManager->getCategoryType() == "afsc"){
                $flashCardManager->listFlashCards(false,true);
            }
            else{
                $flashCardManager->listFlashCards();
            }
            $flashCardManager->cardCurrentState = "front";
            $flashCardManager->saveSession();

            $log->setAction("NEW_FLASH_CARD_SESSION");
            $log->setDetail("Category UUID",$categoryUUID);
            $log->saveEntry();

            $cdcMastery->redirect("/cards/study/".$categoryUUID);
            break;
        case "reset":
            $flashCardManager->clearSession();
            $flashCardManager->loadCardCategory($categoryUUID);
            if($flashCardManager->getCategoryType() == "afsc"){
                $flashCardManager->listFlashCards(false,true);
            }
            else{
                $flashCardManager->listFlashCards();
            }
            $flashCardManager->saveSession();

            $log->setAction("NEW_FLASH_CARD_SESSION");
            $log->setDetail("Category UUID",$categoryUUID);
            $log->saveEntry();

            $cdcMastery->redirect("/cards/study/".$categoryUUID);
            break;
        default:
            $cdcMastery->redirect("/cards/study/".$categoryUUID);
            break;
    }
}

if(!$categoryUUID){
    $sysMsg->addMessage("Category must be specified.","warning");
    $cdcMastery->redirect("/cards");
}
elseif(!$flashCardManager->loadCardCategory($categoryUUID)){
    $sysMsg->addMessage("That card category does not exist!","warning");
    $cdcMastery->redirect("/cards");
}
?>
<script type="text/javascript">

    $(document).ready(function () {
        function loading_show() {
            $('#cardLoading').html("<img src='/images/loader.gif'>").fadeIn('fast');
        }

        function navigateFlashCards(destination) {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: destination
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
                getProgress();
            });

            return false;
        }

        function flipCard(){
            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'flipCard'
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            return false;
        }

        function getProgress(){
            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'getProgress'
            }, function (response) {
                setTimeout("finishAjax('progressBarContainer', '" + escape(response) + "')", 500);
            });

            return false;
        }

        $(window).keydown(function(e) {
            switch (e.keyCode) {
                case 32:
                    e.preventDefault();
                    loading_show();

                    $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                        action: 'flipCard'
                    }, function (response) {
                        setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
                    });

                    getProgress();
                    return;
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
            action: 'loadCard',
            actionData: '1'
        }, function (response) {
            setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
        });

        getProgress();

        $('#flashCardArea').swipe( {
            swipeLeft:function() {
                navigateFlashCards("nextCard");
            },
            threshold:0
        });

        $('#flashCardArea').swipe( {
            swipeRight:function() {
                navigateFlashCards("previousCard");
            },
            threshold:0
        });

        $('#flashCardArea').click(function () {
            loading_show();
            flipCard();
        });

        $('#flip').click(function () {
            loading_show();
            flipCard();
        });

        $('#goFirst').click(function () {
            navigateFlashCards("firstCard");
        });

        $('#goPrevious').click(function () {
            loading_show();
            navigateFlashCards("previousCard");
        });

        $('#goNext').click(function () {
            loading_show();
            navigateFlashCards("nextCard");
        });

        $('#goLast').click(function () {
            loading_show();
            navigateFlashCards("lastCard");
        });

        $('#shuffle').click(function () {
            loading_show();

            $.post("/ajax/flashCardPlatform/<?php echo $categoryUUID; ?>", {
                action: 'shuffleCards'
            }, function (response) {
                setTimeout("finishAjax('flashCardArea', '" + escape(response) + "')", 500);
            });

            getProgress();

            return false;

        });
    });

    function finishAjax(id, response) {
        $('#cardLoading').fadeOut('fast');
        $('#' + id).html(unescape(response));
    }

</script>
<div id="cardLoading"><img src="/images/loader.gif"/></div>
<div class="container">
    <div class="row">
        <div class="8u -2u">
            <section>
                <header>
                    <h2 style="text-align:center;"><?php echo $flashCardManager->getCategoryName(); ?></h2>
                </header>
                <div id="flashCardArea" onclick="flipCard()"></div>
                <div class="clearfix">&nbsp;</div>
                <div class="4u -4u">
                    <div class="flip-button">
                        <a href="#" id="flip">Flip Card</a>
                    </div>
                </div>
                <div id="progressBarContainer" class="8u -2u"></div>
                <div class="test-nav">
                    <button class="test-nav-button" id="goFirst" title="First Card">&lt;&lt;</button>
                    <button class="test-nav-button" id="goPrevious" title="Previous Card">&lt;</button>
                    <button class="test-nav-button" id="shuffle" title="Shuffle Cards" style="padding-right:1em;">Shuffle</button>
                    <button class="test-nav-button" id="goNext" title="Next Card">&gt;</button>
                    <button class="test-nav-button" id="goLast" title="Last Card">&gt;&gt;</button>
                </div>
                <div class="clearfix">&nbsp;</div>
                <div class="text-center">
                    <strong>Tip:</strong> You can use the <strong>Left</strong> and <strong>Right</strong> arrow keys to navigate through the flash cards.<br>
                    <strong>Home</strong> and <strong>End</strong> will take you to the first and last cards.<br>
                </div>
            </section>
        </div>
    </div>
</div>
