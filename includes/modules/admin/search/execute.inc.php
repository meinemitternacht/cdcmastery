<?php
/**
 * Created by PhpStorm.
 * User: Claude Bing
 * Date: 1/31/2016
 * Time: 5:54 PM
 */
$searchType = isset($_POST['searchType']) ? $_POST['searchType'] : false;
$searchParameterJoinMethod = isset($_POST['searchParameterJoinMethod']) ? $_POST['searchParameterJoinMethod'] : false;

if(isset($_POST['doSearch']) && $_POST['doSearch'] == true) {
    if(!$searchType){
        $sysMsg->addMessage("Search type not specified.","danger");
        $cdcMastery->redirect("/admin/search");
    }

    if(!$searchParameterJoinMethod){
        $sysMsg->addMessage("Search criteria join method not specified. Please select 'Match All' or 'Match Any' Criteria.","warning");
        $cdcMastery->redirect("/admin/search");
    }

    switch($searchType){
        case "AFSCassociations":
            $searchParameterList['afscUUID'] = isset($_POST['afscUUID']) ? $_POST['afscUUID'] : false;
            break;
        case "log":
            $searchParameterList['data'] = isset($_POST['affectedUser']) ? $_POST['affectedUser'] : false;
            $searchParameterList['userUUID'] = isset($_POST['affectedUser']) ? $_POST['affectedUser'] : false;
            break;
        case "testHistory":
            $searchParameterList['afscList'] = isset($_POST['afscList']) ? $_POST['afscList'] : false;
            $searchParameterList['userUUID'] = isset($_POST['userUUID']) ? $_POST['userUUID'] : false;
            break;
        case "user":
            $searchParameterList['userFirstName'] = isset($_POST['userFirstName']) ? $_POST['userFirstName'] : false;
            $searchParameterList['userLastName'] = isset($_POST['userLastName']) ? $_POST['userLastName'] : false;
            $searchParameterList['userHandle'] = isset($_POST['userHandle']) ? $_POST['userHandle'] : false;
            $searchParameterList['userEmail'] = isset($_POST['userEmail']) ? $_POST['userEmail'] : false;
            $searchParameterList['userRank'] = isset($_POST['userRank']) ? $_POST['userRank'] : false;
            $searchParameterList['userRole'] = isset($_POST['userRole']) ? $_POST['userRole'] : false;
            $searchParameterList['userBase'] = isset($_POST['userBase']) ? $_POST['userBase'] : false;
            $searchParameterList['userOfficeSymbol'] = isset($_POST['userOfficeSymbol']) ? $_POST['userOfficeSymbol'] : false;
            $searchParameterList['userDateRegistered'][0] = isset($_POST['register-start-date']) ? $_POST['register-start-date'] : false;
            $searchParameterList['userDateRegistered'][1] = isset($_POST['register-end-date']) ? $_POST['register-end-date'] : false;
            $searchParameterList['userLastLogin'][0] = isset($_POST['last-login-start-date']) ? $_POST['last-login-start-date'] : false;
            $searchParameterList['userLastLogin'][1] = isset($_POST['last-login-end-date']) ? $_POST['last-login-end-date'] : false;
            $searchParameterList['userLastActive'][0] = isset($_POST['last-active-start-date']) ? $_POST['last-active-start-date'] : false;
            $searchParameterList['userLastActive'][1] = isset($_POST['last-active-end-date']) ? $_POST['last-active-end-date'] : false;

            /**
             * @param $arrayKey
             * @param $searchParameterList
             * @return bool
             */
            function checkDateParameters($arrayKey, &$searchParameterList){
                $searchDateObj = new DateTime();

                if(!empty($searchParameterList[$arrayKey][0]) && !empty($searchParameterList[$arrayKey][1])){
                    $dateStart = strtotime($searchParameterList[$arrayKey][0]);
                    $dateEnd = strtotime($searchParameterList[$arrayKey][1]);

                    if($dateStart > $dateEnd){
                        return false;
                    }
                    else{
                        return true;
                    }
                }
                elseif(!empty($searchParameterList[$arrayKey][0]) && !$searchParameterList[$arrayKey][1]){
                    $searchParameterList[$arrayKey][1] = $searchDateObj->format("Y-m-d");
                    return true;
                }
                elseif(!$searchParameterList[$arrayKey][0] && !empty($searchParameterList[$arrayKey][1])){
                    $searchParameterList[$arrayKey][0] = "2012-01-01";
                    return true;
                }
                else{
                    unset($searchParameterList[$arrayKey]);
                    return true;
                }
            }

            if(!checkDateParameters("userDateRegistered",$searchParameterList)){
                $sysMsg->addMessage("The starting value for Date Registered cannot be greater than the end date.","warning");
                $cdcMastery->redirect("/admin/search");
            }
            elseif(!checkDateParameters("userLastLogin",$searchParameterList)){
                $sysMsg->addMessage("The starting value for Last Login Date cannot be greater than the end date.","warning");
                $cdcMastery->redirect("/admin/search");
            }
            elseif(!checkDateParameters("userLastActive",$searchParameterList)){
                $sysMsg->addMessage("The starting value for Last Active Date cannot be greater than the end date.","warning");
                $cdcMastery->redirect("/admin/search");
            }
            break;
    }

    if(!isset($searchParameterList)){
        $sysMsg->addMessage("Incorrect search parameters.","warning");
        $cdcMastery->redirect("/admin/search");
    }
    else {
        $searchObj = new search($db, $log);
        $searchObj->setSearchType($searchType);
        $searchObj->setSearchParameterJoinMethod($searchParameterJoinMethod);

        foreach ($searchParameterList as $searchParameterKey => $searchParameter) {
            if (!empty($searchParameter)) {
                if (is_array($searchParameter)) {
                    $searchObj->addSearchParameterMultipleValues($searchParameterKey, $searchParameter);
                } else {
                    $searchObj->addSearchParameterSingleValue(Array($searchParameterKey, $searchParameter));
                }
            }
        }

        $_SESSION['searchData']['searchResults'] = $searchObj->executeSearch();
        $_SESSION['searchData']['searchType'] = $searchObj->getSearchType();

        if($searchObj->getSearchType() == "AFSCassociations"){
            $_SESSION['searchData']['searchResults'] = $user->sortUserUUIDList($_SESSION['searchData']['searchResults'],"userLastName");
        }

        if (!$_SESSION['searchData']['searchResults']) {
            $sysMsg->addMessage("There were no results for that search query.","info");
            $sysMsg->addMessage($searchObj->error,"info");
            $sysMsg->addMessage($searchObj->searchQuery,"info");

            $cdcMastery->redirect("/admin/search");
        } else {
            $_SESSION['searchData']['searchResultCount'] = count($_SESSION['searchData']['searchResults']);
            $cdcMastery->redirect("/admin/search/results");
        }
    }
}