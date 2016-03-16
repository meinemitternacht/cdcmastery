<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/22/2015
 * Time: 10:05 PM
 */

$subPage = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : false;

if($subPage) {
    switch ($subPage) {
        case "execute":
            include BASE_PATH . "/includes/modules/admin/search/execute.inc.php";
            break;
        case "results":
            include BASE_PATH . "/includes/modules/admin/search/results.inc.php";
            break;
        default:
            $sysMsg->addMessage("There was an error processing that page path.");
            $cdcMastery->redirect("/admin/search");
            break;
    }
}
else {
    ?>
    <div class="container">
        <style>

            .ui-autocomplete {
                max-height: 8em;
                overflow-y: auto;
                overflow-x: hidden;
            }

            * html .ui-autocomplete {
                height: 120px;
            }

        </style>


        <script>

            $(function () {
                $("#userFirstName").autocomplete({
                    source: "/ajax/autocomplete/userFirstName",
                    minLength: 3
                });
                $("#userLastName").autocomplete({
                    source: "/ajax/autocomplete/userLastName",
                    minLength: 3
                });
                $("#userHandle").autocomplete({
                    source: "/ajax/autocomplete/userHandle",
                    minLength: 3
                });
                $("#userEmail").autocomplete({
                    source: "/ajax/autocomplete/userEmail",
                    minLength: 3
                });
                $('#userUUID').autocomplete({
                    source: function (request, response) {
                        $.ajax({
                            url: '/ajax/autocomplete/userFullName',
                            type: 'GET',
                            dataType: 'json',
                            data: request,
                            success: function (data) {
                                response($.map(data, function (value, key) {
                                    return {
                                        label: value,
                                        value: key
                                    };
                                }));
                            }
                        });
                    },
                    minLength: 2
                });
                $('#affectedUser').autocomplete({
                    source: function (request, response) {
                        $.ajax({
                            url: '/ajax/autocomplete/userFullName',
                            type: 'GET',
                            dataType: 'json',
                            data: request,
                            success: function (data) {
                                response($.map(data, function (value, key) {
                                    return {
                                        label: value,
                                        value: key
                                    };
                                }));
                            }
                        });
                    },
                    minLength: 2
                });

                $( "#search-tabs" ).tabs();
            });

        </script>
        <div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>Search</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="12u">
                <section>
                    <div id="search-tabs">
                        <ul>
                            <li><a href="#user-search">Users</a></li>
                            <li><a href="#afsc-associations-search">AFSC Associations</a></li>
                            <li><a href="#completed-tests-search">Completed Tests</a></li>
                            <li><a href="#log-entries-search">Log Entries</a></li>
                        </ul>
                        <div id="user-search">
                            <form action="/admin/search/execute" method="POST" autocomplete="false" autocomplete="off">
                                <div class="container">
                                    <div class="row">
                                        <div class="3u">
                                            <section>
                                                <header>
                                                    <h3>Users</h3>
                                                </header>
                                                <input type="hidden" name="doSearch" value="1">
                                                <input type="hidden" name="searchType" value="user">
                                                <ul>
                                                    <li>
                                                        <input type="radio" name="searchParameterJoinMethod" value="AND" CHECKED> Match All
                                                        Criteria<br>
                                                        <input type="radio" name="searchParameterJoinMethod" value="OR"> Match Any Criteria
                                                    </li>
                                                    <li>
                                                        <label for="userFirstName">First Name</label>
                                                        <input type="text" name="userFirstName" id="userFirstName" maxlength="255"
                                                               class="input_full">
                                                    </li>
                                                    <li>
                                                        <label for="userLastName">Last Name</label>
                                                        <input type="text" name="userLastName" id="userLastName" maxlength="255"
                                                               class="input_full">
                                                    </li>
                                                    <li>
                                                        <label for="userHandle">Username</label>
                                                        <input type="text" name="userHandle" id="userHandle" maxlength="255" class="input_full">
                                                    </li>
                                                    <li>
                                                        <label for="userEmail">E-mail</label>
                                                        <input type="text" name="userEmail" id="userEmail" maxlength="255" class="input_full">
                                                    </li>
                                                </ul>
                                            </section>
                                        </div>
                                        <div class="4u">
                                            <section>
                                                <ul>
                                                    <li>
                                                        <label for="userRank">Rank</label>
                                                        <select id="userRank"
                                                                name="userRank[]"
                                                                class="input_full"
                                                                style="height:10em;"
                                                                MULTIPLE>
                                                            <?php
                                                            $rankList = $cdcMastery->listRanks();
                                                            foreach ($rankList as $rankGroupLabel => $rankGroup) {
                                                                echo '<optgroup label="' . $rankGroupLabel . '">';
                                                                foreach ($rankGroup as $rankOrder) {
                                                                    foreach ($rankOrder as $rankKey => $rankVal): ?>
                                                                        <option value="<?php echo $rankKey; ?>"><?php echo $rankVal; ?></option>
                                                                        <?php
                                                                    endforeach;
                                                                }
                                                                echo '</optgroup>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </li>
                                                    <li>
                                                        <label for="userRole">Permission Group</label>
                                                        <select id="userRole"
                                                                name="userRole[]"
                                                                class="input_full"
                                                                style="height:10em;"
                                                                MULTIPLE>
                                                            <?php
                                                            $roleList = $roles->listRoles();
                                                            foreach ($roleList as $roleUUID => $roleDetails): ?>
                                                                <option
                                                                    value="<?php echo $roleUUID; ?>"><?php echo $roleDetails['roleName']; ?></option>
                                                                <?php
                                                            endforeach;
                                                            ?>
                                                        </select>
                                                    </li>
                                                </ul>
                                            </section>
                                        </div>
                                        <div class="4u">
                                            <section>
                                                <ul>
                                                    <li>
                                                        <label for="userBase">Base</label>
                                                        <select id="userBase"
                                                                name="userBase[]"
                                                                class="input_full"
                                                                style="height:10em;"
                                                                MULTIPLE>
                                                            <?php
                                                            $baseList = $bases->listBases();
                                                            foreach ($baseList as $baseUUID => $baseName): ?>
                                                                <option value="<?php echo $baseUUID; ?>"><?php echo $baseName; ?></option>
                                                                <?php
                                                            endforeach;
                                                            ?>
                                                        </select>
                                                    </li>
                                                    <li>
                                                        <label for="userOfficeSymbol">Office Symbol</label>
                                                        <select id="userOfficeSymbol"
                                                                name="userOfficeSymbol[]"
                                                                class="input_full"
                                                                style="height:10em;"
                                                                MULTIPLE>
                                                            <?php
                                                            $officeSymbolList = $officeSymbol->listOfficeSymbols();
                                                            foreach($officeSymbolList as $officeSymbolUUID => $officeSymbolName): ?>
                                                                <option value="<?php echo $officeSymbolUUID; ?>"><?php echo $officeSymbolName; ?></option>
                                                                <?php
                                                            endforeach;
                                                            ?>
                                                        </select>
                                                    </li>
                                                </ul>
                                            </section>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="12u">
                                            <section>
                                                <ul class="text-center">
                                                    <li>
                                                        <input type="submit" value="Find Users">
                                                    </li>
                                                </ul>
                                            </section>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div id="afsc-associations-search">
                            <div class="container">
                                <div class="row">
                                    <div class="4u">
                                        <section>
                                            <header>
                                                <h3>AFSC Associations</h3>
                                            </header>
                                            <form action="/admin/search/execute" method="POST">
                                                <input type="hidden" name="doSearch" value="1">
                                                <input type="hidden" name="searchType" value="AFSCassociations">
                                                <input type="hidden" name="searchParameterJoinMethod" value="AND">
                                                <ul>
                                                    <li>
                                                        <label for="afscUUID">AFSC</label>
                                                        <select id="afscUUID"
                                                                name="afscUUID[]"
                                                                class="input_full"
                                                                style="height:10em;"
                                                                MULTIPLE>
                                                            <?php
                                                            $afscList = $afsc->listAFSC();
                                                            foreach ($afscList as $afscUUID => $afscDetails): ?>
                                                                <option
                                                                    value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                                                <?php
                                                            endforeach;
                                                            ?>
                                                        </select>
                                                    </li>
                                                    <li>
                                                        <br>
                                                        <input type="submit" value="Search Associations">
                                                    </li>
                                                </ul>
                                            </form>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="completed-tests-search">
                            <div class="container">
                                <div class="row">
                                    <div class="4u">
                                        <section>
                                            <header>
                                                <h3>Completed Tests</h3>
                                            </header>
                                            <form action="/admin/search/execute" method="POST">
                                                <input type="hidden" name="doSearch" value="1">
                                                <input type="hidden" name="searchType" value="testHistory">
                                                <ul>
                                                    <li>
                                                        <input type="radio" name="searchParameterJoinMethod" value="AND"> Match All
                                                        Criteria<br>
                                                        <input type="radio" name="searchParameterJoinMethod" value="OR" CHECKED> Match Any Criteria
                                                    </li>
                                                    <li>
                                                        <label for="afscList">AFSC</label>
                                                        <select id="afscList"
                                                                name="afscList[]"
                                                                class="input_full"
                                                                style="height:10em;"
                                                                MULTIPLE>
                                                            <?php
                                                            $afscList = $afsc->listAFSC();
                                                            foreach ($afscList as $afscUUID => $afscDetails): ?>
                                                                <option
                                                                    value="<?php echo $afscUUID; ?>"><?php echo $afscDetails['afscName']; ?></option>
                                                                <?php
                                                            endforeach;
                                                            ?>
                                                        </select>
                                                    </li>
                                                    <li>
                                                        <label for="userUUID">User</label>
                                                        <input type="text" name="userUUID" id="userUUID" maxlength="255" class="input_full">
                                                        <br>
                                                        <em>To get a list of users, type the first few letters of their name</em>
                                                    </li>
                                                    <li>
                                                        <br>
                                                        <input type="submit" value="Find Tests">
                                                    </li>
                                                </ul>
                                            </form>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="log-entries-search">
                            <div class="container">
                                <div class="row">
                                    <div class="5u">
                                        <section>
                                            <header>
                                                <h3>Log Entries</h3>
                                            </header>
                                            <form action="/admin/search/execute" method="POST">
                                                <input type="hidden" name="doSearch" value="1">
                                                <input type="hidden" name="searchType" value="log">
                                                <input type="hidden" name="searchParameterJoinMethod" value="OR">
                                                <ul>
                                                    <li>
                                                        <label for="affectedUser">Show entries affecting the following user:</label>
                                                        <input type="text" name="affectedUser" id="affectedUser" maxlength="255"
                                                               class="input_full">
                                                        <br>
                                                        <em>To get a list of users, type the first few letters of their name</em>
                                                    </li>
                                                    <li>
                                                        <br>
                                                        <input type="submit" value="Find Log Entries">
                                                    </li>
                                                </ul>
                                            </form>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php
}
?>