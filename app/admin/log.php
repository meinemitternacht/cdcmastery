<?php
$logFilter = new logFilter($db, $user);

$pageNumber = isset($_SESSION['vars'][0]) ? $_SESSION['vars'][0] : 0;
$pageRows = isset($_SESSION['vars'][1]) ? $_SESSION['vars'][1] : 32;
$sortBy = isset($_SESSION['vars'][2]) ? $_SESSION['vars'][2] : "microtime";
$sortDirection = isset($_SESSION['vars'][3]) ? $_SESSION['vars'][3] : "DESC";
$filterBy = isset($_SESSION['vars'][4]) ? $_SESSION['vars'][4] : false;
$filterValue = isset($_SESSION['vars'][5]) ? $_SESSION['vars'][5] : false;

/*
 * Set filterBy and filterValue from form submission
 */

if(isset($_POST['filterBy']) && !empty($_POST['filterBy']))
    $filterBy = $_POST['filterBy'];

if(isset($_POST['filterValue']) && !empty($_POST['filterValue']))
    $filterValue = $_POST['filterValue'];

/*
 * Starting Row
 */
if($pageNumber <= 0){
	$rowOffset = 0;
}
else{
	$rowOffset = $pageNumber * $pageRows;
}

if($filterBy && $filterValue){
    switch($filterBy){
        case "action":
            $logFilter->setFilterAction($filterValue);
            break;
        case "user":
            $logFilter->setFilterUserUUID($filterValue);
            break;
        case "ip":
            $logFilter->setFilterIP(base64_decode($filterValue));
            break;
    }

    $logFiltered = true;
}
else{
    $logFiltered = false;
}

/*
 * Get some stats
 */
$totalLogEntries = $logFilter->countEntries();

$logFilter->setRowOffset(intval($rowOffset));
$logFilter->setPageRows(intval($pageRows));
$logFilter->setSortBy($sortBy);
$logFilter->setSortDirection($sortDirection);

$logEntries = $logFilter->listEntries();

/*
 * Total Pages
 */
$totalPages = ceil($totalLogEntries / $pageRows) - 1;

if($logEntries): ?>
    <?php if(!isset($_GET['norefresh'])): ?>
    <script>
        $(document).ready(function () {
            setTimeout(function () { location.reload(); }, 30000);
        });
    </script>
    <?php endif; ?>
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
        });
    </script>
    <!--[if !IE]><!-->
    <style type="text/css">
    @media
    only screen and (max-width: 760px),
    (min-device-width: 768px) and (max-device-width: 1024px)  {
        table, thead, tbody, th, td, tr {
            display: block;
        }

        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        tr { border: 1px solid #ccc; }

        td {
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 30%;
        }

        td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 25%;
            padding-right: 10px;
            white-space: nowrap;
        }

        table#log-table-1 td:nth-of-type(1):before { content: "Timestamp"; }
        table#log-table-1 td:nth-of-type(2):before { content: "Action"; }
        table#log-table-1 td:nth-of-type(3):before { content: "User"; }
        table#log-table-1 td:nth-of-type(4):before { content: "Details"; }
    }

    @media only screen
    and (min-device-width : 320px)
    and (max-device-width : 480px) {
        body {
            padding: 0;
            margin: 0;
            width: 320px; }
        }

    @media only screen and (min-device-width: 768px) and (max-device-width: 1024px) {
        body {
            width: 495px;
        }
    }

    </style>
    <!--<![endif]-->
    <div class="container">
        <div class="row">
            <div class="3u">
                <section>
                    <header>
                        <h2>Navigation</h2>
                    </header>
                    <div class="sub-menu">
                        <ul>
                            <?php if($pageNumber > 0): ?>
                            <li><a href="/admin/log/0/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>" title="First"><i class="icon-inline icon-20 ic-arrow-left"></i>First</a></li>
                            <?php endif; ?>
                            <?php if($pageNumber > 0): ?>
                            <li><a href="/admin/log/<?php echo ($pageNumber - 1); ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>" title="Previous"><i class="icon-inline icon-20 ic-arrow-left-silver"></i>Previous</a></li>
                            <?php endif; ?>
                            <?php if($pageNumber < $totalPages): ?>
                            <li><a href="/admin/log/<?php echo ($pageNumber + 1); ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>" title="Next"><i class="icon-inline icon-20 ic-arrow-right-silver"></i>Next</a></li>
                            <li><a href="/admin/log/<?php echo $totalPages; ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>" title="Last"><i class="icon-inline icon-20 ic-arrow-right"></i>Last</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="clearfix">&nbsp;</div>
                    <strong>The current system time is <br><?php echo date("F j, Y h:i A",time()); ?></strong><br>
                    <br>
                    <a href="/admin/log">Reset log view</a><br>
                    <strong>Log Entries:</strong> <?php echo number_format($totalLogEntries); ?>
                    <br>
                    <br>
                    <?php if(!isset($_GET['norefresh'])): ?>
                        <em>Note: This page will automatically refresh every 30 seconds. <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; ?>?norefresh">Disable Refresh</a></em>
                    <?php else: ?>
                        <em><a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/<?php echo $sortBy; ?>/<?php echo $sortDirection; if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; ?>">Enable Automatic Refresh</a></em>
                    <?php endif; ?>
                    <br>
                </section>
                <section>
                    <header>
                        <h2>Sort By</h2>
                    </header>
                    <ul>
                        <li>
                            <span class="tableLinksDashed">
                                <a <?php if($sortBy == "timestamp") echo "class=\"active\""?> href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/timestamp/desc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>">Timestamp</a>:
                            </span>
                            <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/timestamp/asc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>"><i class="icon-inline icon-20 ic-arrow-up"></i></a>
                            <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/timestamp/desc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>"><i class="icon-inline icon-20 ic-arrow-down"></i></a>
                        </li>
                        <li>
                            <span class="tableLinksDashed">
                                <a <?php if($sortBy == "action") echo "class=\"active\""?> href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/action/asc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>">Action</a>:
                            </span>
                            <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/action/asc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>"><i class="icon-inline icon-20 ic-arrow-up"></i></a>
                            <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/action/desc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>"><i class="icon-inline icon-20 ic-arrow-down"></i></a>
                        </li>
                        <li>
                            <span class="tableLinksDashed">
                                <a <?php if($sortBy == "useruuid") echo "class=\"active\""?> href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/useruuid/asc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>">User</a>:
                            </span>
                            <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/useruuid/asc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>"><i class="icon-inline icon-20 ic-arrow-up"></i></a>
                            <a href="/admin/log/<?php echo $pageNumber; ?>/<?php echo $pageRows; ?>/useruuid/desc<?php if($logFiltered): ?>/<?php echo $filterBy; ?>/<?php echo $filterValue; endif; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>"><i class="icon-inline icon-20 ic-arrow-down"></i></a>
                        </li>
                    </ul>
                </section>
                <div class="clearfix">&nbsp;</div>
                <section>
                    <header>
                        <h2>Filter By</h2>
                    </header>
                    <ul>
                        <li><strong>Action</strong></li>
                        <li>
                            <form action="/admin/log" method="POST" id="filterAction">
                                <input type="hidden" name="filterBy" value="action">
                                <select name="filterValue" class="input_full" size="1" onChange="javascript:document.forms['filterAction'].submit()">
                                    <option value="">Choose Action...</option>
                                    <?php
                                    $logActionList = $log->listLogActions();

                                    foreach($logActionList as $logAction): ?>
                                        <?php if($filterValue == $logAction): ?>
                                            <option value="<?php echo $logAction; ?>" SELECTED><?php echo $logAction; ?></option>
                                        <?php else: ?>
                                            <option value="<?php echo $logAction; ?>"><?php echo $logAction; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </li>
                    </ul>
                    <ul>
                        <li><strong>User</strong></li>
                        <li>
                            <em>Choose a user by typing the first few letters of their name.</em>
                            <form action="/admin/log" method="POST" id="filterUser">
                                <input type="hidden" name="filterBy" value="user">
                                <input type="text" id="userUUID" name="filterValue" class="input_full">
                                <br>
                                <br>
                                <input type="submit" value="Go">
                            </form>
                        </li>
                    </ul>
                </section>
            </div>
            <div class="9u">
                <section>
                    <header>
                        <h2>Log Data</h2>
                    </header>
                    <div class="tableSmallText">
                        <table id="log-table-1">
                            <tr>
                                <th>Timestamp</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Details</th>
                            </tr>
                            <?php foreach($logEntries as $logUUID => $logData): ?>
                            <tr>
                                <td><?php echo $cdcMastery->outputDateTime($logData['timestamp'], $_SESSION['timeZone']); ?></td>
                                <td><span class="<?php echo $log->getRowStyle($logData['action']); ?>"><a href="/admin/log/0/<?php echo $pageRows; ?>/timestamp/DESC/action/<?php echo $logData['action']; if(isset($_GET['norefresh'])) echo "?norefresh"; ?>" title="Filter by <?php echo $cdcMastery->formatOutputString($logData['action'],25); ?>"><?php echo $logData['action']; ?></a></span></td>
                                <?php if(!in_array($logData['userUUID'],$cdcMastery->getStaticUserArray())): ?>
                                    <td><a href="/admin/users/<?php echo $logData['userUUID']; ?>" title="Manage User"><?php echo $user->getUserNameByUUID($logData['userUUID']); ?></a></td>
                                <?php else: ?>
                                    <td><?php echo $user->getUserNameByUUID($logData['userUUID']); ?></td>
                                <?php endif; ?>
                                <td><a href="/admin/log-detail/<?php echo $logUUID; ?>/log"><i class="icon-inline icon-10 ic-log"></i>details</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
<?php else: ?>
	<?php
	if($logFiltered) {
		$sysMsg->addMessage("There were no results using that log filter.");
		$cdcMastery->redirect("/admin/log");
	} else { ?>
		There are no log entries in the database.  That's unusual...
	<?php
	} ?>
<?php endif; ?>