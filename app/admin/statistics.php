<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/23/2015
 * Time: 8:01 PM
 */
$statisticsObj = new statistics($db,$log,$emailQueue,$memcache);
$userObj = new user($db,$log,$emailQueue);
?>
<script>
    $(document).ready(function()
        {
            $("#logActionCountTable").tablesorter();
        }
    );
</script>
<div class="container">
    <div class="row">
        <div class="3u">
            <section>
                <header>
                    <h2>Statistics</h2>
                </header>
                <div class="sub-menu">
                    <ul>
                        <li><a href="/admin"><i class="icon-inline icon-20 ic-arrow-left"></i>Return to Admin Panel</a></li>
                    </ul>
                </div>
            </section>
        </div>
        <div class="6u">
            <section>
                <br>
                <p>
                    <em>Please note that statistics relating to time spans are not adjusted for time zones.</em>
                    <br>
                    <strong>The current system time is <?php echo date("F j, Y h:i A",time()); ?></strong>
                </p>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Testing Statistics</h2>
                </header>
                <table>
                    <tr>
                        <td>Completed Tests</td>
                        <td><?php echo number_format($statisticsObj->getTotalCompletedTests()); ?></td>
                    </tr>
                    <tr>
                        <td>Incomplete Tests</td>
                        <td><?php echo number_format($statisticsObj->getTotalIncompleteTests()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Tests</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalTests()); ?></td>
                    </tr>
                    <tr>
                        <td>Question Responses in Database</td>
                        <td><?php echo number_format($statisticsObj->getTotalDatabaseQuestionsAnswered()); ?></td>
                    </tr>
                    <tr>
                        <td>Archived Responses on Filesystem</td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsAnswered() - $statisticsObj->getTotalDatabaseQuestionsAnswered()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Question Responses</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsAnswered()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-success"><a href="/admin/log/0/25/timestamp/DESC/action/TEST_START">Tests Started</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("TEST_START")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-caution"><a href="/admin/log/0/25/timestamp/DESC/action/TEST_ARCHIVE">Tests Archived</a></span></td>
                        <td><?php echo number_format($statisticsObj->getTotalArchivedTests()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><span class="text-caution"><a href="/admin/log/0/25/timestamp/DESC/action/INCOMPLETE_TEST_DELETE">Tests Deleted</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("TEST_DELETE") + $statisticsObj->getLogCountByAction("INCOMPLETE_TEST_DELETE")); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Flash Card Sessions</strong></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("NEW_FLASH_CARD_SESSION")); ?></td>
                    </tr>
                    <tr>
                        <td><a href="/admin/flash-card-categories">AFSC Flash Card Categories</a></td>
                        <td><?php echo number_format($statisticsObj->getTotalAFSCFlashCardCategories()); ?></td>
                    </tr>
                    <tr>
                        <td><a href="/admin/flash-card-categories">Global Flash Card Categories</a></td>
                        <td><?php echo number_format($statisticsObj->getTotalGlobalFlashCardCategories()); ?></td>
                    </tr>
                    <tr>
                        <td><a href="/admin/flash-card-categories">Private Flash Card Categories</a></td>
                        <td><?php echo number_format($statisticsObj->getTotalPrivateFlashCardCategories()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Flash Card Categories</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalFlashCardCategories()); ?></td>
                    </tr>
                </table>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>User Statistics</h2>
                </header>
                <?php
                $totalAccounts = $statisticsObj->getTotalUsers();
                $inactiveAccounts = $statisticsObj->getInactiveUsers();
                $totalUsers = $statisticsObj->getTotalRoleUser();
                $totalSupervisors = $statisticsObj->getTotalRoleSupervisor();
                $totalTrainingManagers = $statisticsObj->getTotalRoleTrainingManager();
                $totalAdministrators = $statisticsObj->getTotalRoleAdministrator();
                $totalSuperAdministrators = $statisticsObj->getTotalRoleSuperAdministrator();
                $totalQuestionEditors = $statisticsObj->getTotalRoleEditor();

                $percentUserClass['inactive'] = round((($inactiveAccounts/$totalAccounts) * 100),2) . "%";
                $percentUserClass['user'] = round((($totalUsers/$totalAccounts) * 100),2) . "%";
                $percentUserClass['supervisors'] = round((($totalSupervisors/$totalAccounts) * 100),2) . "%";
                $percentUserClass['trainingManagers'] = round((($totalTrainingManagers/$totalAccounts) * 100),2) . "%";
                $percentUserClass['administrators'] = round((($totalAdministrators/$totalAccounts) * 100),2) . "%";
                $percentUserClass['superAdministrators'] = round((($totalSuperAdministrators/$totalAccounts) * 100),2) . "%";
                $percentUserClass['questionEditors'] = round((($totalQuestionEditors/$totalAccounts) * 100),2) . "%";
                ?>
                <table>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Users</strong></td>
                        <td colspan="2"><?php echo number_format($totalAccounts); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Inactive Users</td>
                        <td><?php echo number_format($inactiveAccounts); ?></td>
                        <td><?php echo $percentUserClass['inactive']; ?></td>
                    </tr>
                    <tr>
                        <td>Normal Users</td>
                        <td><?php echo number_format($totalUsers); ?></td>
                        <td><?php echo $percentUserClass['user']; ?></td>
                    </tr>
                    <tr>
                        <td>Supervisors</td>
                        <td><?php echo number_format($totalSupervisors); ?></td>
                        <td><?php echo $percentUserClass['supervisors']; ?></td>
                    </tr>
                    <tr>
                        <td>Training Managers</td>
                        <td><?php echo number_format($totalTrainingManagers); ?></td>
                        <td><?php echo $percentUserClass['trainingManagers']; ?></td>
                    </tr>
                    <tr>
                        <td>Administrators</td>
                        <td><?php echo number_format($totalAdministrators); ?></td>
                        <td><?php echo $percentUserClass['administrators']; ?></td>
                    </tr>
                    <tr>
                        <td>Super Administrators</td>
                        <td><?php echo number_format($totalSuperAdministrators); ?></td>
                        <td><?php echo $percentUserClass['superAdministrators']; ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Question Editors</td>
                        <td><?php echo number_format($totalQuestionEditors); ?></td>
                        <td><?php echo $percentUserClass['questionEditors']; ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Office Symbols</strong></td>
                        <td colspan="2"><?php echo number_format($statisticsObj->getTotalOfficeSymbols()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/USER_DELETE">Users Deleted</a></span></td>
                        <td colspan="2"><?php echo number_format($statisticsObj->getLogCountByAction("USER_DELETE")); ?></td>
                    </tr>
                </table>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>System Statistics</h2>
                </header>
                <table>
                    <tr>
                        <td><strong>Database Size</strong></td>
                        <td><?php echo round($statisticsObj->getDatabaseSize(),2); ?> GB</td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/MYSQL_ERROR">MySQL Errors</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("MYSQL_ERROR")); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Log Entries</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLogEntries()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Log Details</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLogDetails()); ?></td>
                    </tr>
                    <tr>
                        <td>Login Errors</td>
                        <td><?php echo number_format($statisticsObj->getTotalLoginErrors()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/ERROR_LOGIN_RATE_LIMIT_REACHED">Login Rate Limit Reached</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("ERROR_LOGIN_RATE_LIMIT_REACHED")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/ROUTING_ERROR">Route Errors</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("ROUTING_ERROR")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/AJAX_DIRECT_ACCESS">AJAX Direct Access</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("AJAX_DIRECT_ACCESS")); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>All Errors</td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("%ERROR%")); ?></td>
                    </tr>
                    <tr>
                        <td>Migrated Passwords</td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("MIGRATED_PASSWORD")); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><span class="text-success"><a href="/admin/log/0/25/timestamp/DESC/action/USER_PASSWORD_RESET_COMPLETE">Password Resets</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("USER_PASSWORD_RESET_COMPLETE")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-success"><a href="/admin/log/0/25/timestamp/DESC/action/EMAIL_SEND">E-mail Messages Sent</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("EMAIL_SEND")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/ERROR_EMAIL">E-mail Errors</a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("ERROR_EMAIL%")); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>CDC Data Statistics</h2>
                </header>
                <table>
                    <tr>
                        <td><strong>AFSC Categories</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalAFSCCategories()); ?></td>
                    </tr>
                    <tr>
                        <td>AFSC Associations</td>
                        <td><?php echo number_format($statisticsObj->getTotalAFSCAssociations()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Associations Per User</td>
                        <td><?php echo number_format($statisticsObj->getTotalAFSCAssociations()/$statisticsObj->getTotalUsers(),2); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>FOUO AFSC Categories</td>
                        <td><?php echo number_format($statisticsObj->getTotalFOUOAFSCCategories()); ?></td>
                    </tr>
                    <tr>
                        <td>Archived Questions</td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsArchived()); ?></td>
                    </tr>
                    <tr>
                        <td>FOUO Questions</td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsFOUO()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Questions</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestions()); ?></td>
                    </tr>
                    <tr>
                        <td>Archived Answers</td>
                        <td><?php echo number_format($statisticsObj->getTotalAnswersArchived()); ?></td>
                    </tr>
                    <tr>
                        <td>FOUO Answers</td>
                        <td><?php echo number_format($statisticsObj->getTotalAnswersFOUO()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Answers</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalAnswers()); ?></td>
                    </tr>
                </table>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>User Activity</h2>
                </header>
                <table>
                    <tr>
                        <td><strong>Active Today</strong></td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveToday()); ?></td>
                    </tr>
                    <tr>
                        <td>Active This Week</td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveThisWeek()); ?></td>
                    </tr>
                    <tr>
                        <td>Active This Month</td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveThisMonth()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Active This Year</td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveThisYear()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><span class="text-success"><a href="/admin/log/0/25/timestamp/DESC/action/USER_REGISTER"><strong>Registrations</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("USER_REGISTER")); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>Users Active Last 15 Minutes</strong></td>
                    </tr>
                    <?php
                    $usersRecentlyActive = $statisticsObj->getUsersActiveFifteenMinutes();

                    if(count($usersRecentlyActive) > 1): ?>
                        <?php foreach($usersRecentlyActive as $recentUser): ?>
                            <?php
                            $userObj->loadUser($recentUser);
                            ?>
                            <tr>
                                <td colspan="2"><a href="/admin/users/<?php echo $recentUser; ?>"><?php echo $userObj->getFullName(); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">No users active in the last fifteen minutes.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>Test Activity</h2>
                </header>
                <?php
                $todayStart = new DateTime("today 00:00:00");
                $todayEnd = new DateTime("today 23:59:59");

                $weekStart = new DateTime("this week 00:00:00");
                $weekEnd = new DateTime("this week 23:59:59 +6 days");

                /*
                 * Make week start on Sunday
                 */
                $weekStart->modify("-1 day");
                $weekEnd->modify("-1 day");

                $monthStart = new DateTime("first day of this month 00:00:00");
                $monthEnd = new DateTime("last day of this month 23:59:59");

                $yearStart = new DateTime("January 1st 00:00:00");
                $yearEnd = new DateTime("December 31st 23:59:59");

                $testsToday = $statisticsObj->getTestCountByTimespan($todayStart,$todayEnd);
                $testsThisWeek = $statisticsObj->getTestCountByTimespan($weekStart,$weekEnd);
                $testsThisMonth = $statisticsObj->getTestCountByTimespan($monthStart,$monthEnd);
                $testsThisYear = $statisticsObj->getTestCountByTimespan($yearStart,$yearEnd);

                $averageToday = $statisticsObj->getTestAverageByTimespan($todayStart,$todayEnd);
                $averageThisWeek = $statisticsObj->getTestAverageByTimespan($weekStart,$weekEnd);
                $averageThisMonth = $statisticsObj->getTestAverageByTimespan($monthStart,$monthEnd);
                $averageThisYear = $statisticsObj->getTestAverageByTimespan($yearStart,$yearEnd);

                $yesterdayStart = $todayStart->modify("-1 day");
                $yesterdayEnd = $todayEnd->modify("-1 day");

                $lastWeekStart = $weekStart->modify("-1 week");
                $lastWeekEnd = $weekEnd->modify("-1 week");

                $lastMonthStart = $monthStart->modify("-1 month");
                $lastMonthEnd = $monthEnd->modify("-1 month");

                $lastYearStart = $yearStart->modify("-1 year");
                $lastYearEnd = $yearEnd->modify("-1 year");

                $testsYesterday = $statisticsObj->getTestCountByTimespan($yesterdayStart,$yesterdayEnd);
                $testsLastWeek = $statisticsObj->getTestCountByTimespan($lastWeekStart,$lastWeekEnd);
                $testsLastMonth = $statisticsObj->getTestCountByTimespan($lastMonthStart,$lastMonthEnd);
                $testsLastYear = $statisticsObj->getTestCountByTimespan($lastYearStart,$lastYearEnd);

                $averageYesterday = $statisticsObj->getTestAverageByTimespan($yesterdayStart,$yesterdayEnd);
                $averageLastWeek = $statisticsObj->getTestAverageByTimespan($lastWeekStart,$lastWeekEnd);
                $averageLastMonth = $statisticsObj->getTestAverageByTimespan($lastMonthStart,$lastMonthEnd);
                $averageLastYear = $statisticsObj->getTestAverageByTimespan($lastYearStart,$lastYearEnd);

                $percentIncreaseTests['today'] = number_format(((($testsToday - $testsYesterday) / $testsYesterday) * 100),2) . "%";
                $percentIncreaseTests['week'] = number_format(((($testsThisWeek - $testsLastWeek) / $testsLastWeek) * 100),2) . "%";
                $percentIncreaseTests['month'] = number_format(((($testsThisMonth - $testsLastMonth) / $testsLastMonth) * 100),2) . "%";
                $percentIncreaseTests['year'] = number_format(((($testsThisYear - $testsLastYear) / $testsLastYear) * 100),2) . "%";

                $percentIncreaseAverage['today'] = number_format(((($averageToday - $averageYesterday) / $averageYesterday) * 100),2) . "%";
                $percentIncreaseAverage['week'] = number_format(((($averageThisWeek - $averageLastWeek) / $averageLastWeek) * 100),2) . "%";
                $percentIncreaseAverage['month'] = number_format(((($averageThisMonth - $averageLastMonth) / $averageLastMonth) * 100),2) . "%";
                $percentIncreaseAverage['year'] = number_format(((($averageThisYear - $averageLastYear) / $averageLastYear) * 100),2) . "%";
                ?>
                <table>
                    <tr>
                        <td><strong>Tests Today</strong></td>
                        <td><?php echo number_format($testsToday); ?></td>
                        <td class="<?php if($percentIncreaseTests['today'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['today']; ?></td>
                    </tr>
                    <tr>
                        <td>Tests This Week</td>
                        <td><?php echo number_format($testsThisWeek); ?></td>
                        <td class="<?php if($percentIncreaseTests['week'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['week']; ?></td>
                    </tr>
                    <tr>
                        <td>Tests This Month</td>
                        <td><?php echo number_format($testsThisMonth); ?></td>
                        <td class="<?php if($percentIncreaseTests['month'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['month']; ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Tests This Year</td>
                        <td><?php echo number_format($testsThisYear); ?></td>
                        <td class="<?php if($percentIncreaseTests['year'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['year']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tests Yesterday</strong></td>
                        <td colspan="2"><?php echo number_format($testsYesterday); ?></td>
                    </tr>
                    <tr>
                        <td>Tests Last Week</td>
                        <td colspan="2"><?php echo number_format($testsLastWeek); ?></td>
                    </tr>
                    <tr>
                        <td>Tests Last Month</td>
                        <td colspan="2"><?php echo number_format($testsLastMonth); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Tests Last Year</td>
                        <td colspan="2"><?php echo number_format($testsLastYear); ?></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td><strong>Average Score Today</strong></td>
                        <td><?php echo number_format($averageToday,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['today'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['today']; ?></td>
                    </tr>
                    <tr>
                        <td>Average Score This Week</td>
                        <td><?php echo number_format($averageThisWeek,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['week'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['week']; ?></td>
                    </tr>
                    <tr>
                        <td>Average Score This Month</td>
                        <td><?php echo number_format($averageThisMonth,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['month'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['month']; ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td>Average Score This Year</td>
                        <td><?php echo number_format($averageThisYear,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['year'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['year']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Average Score Yesterday</strong></td>
                        <td colspan="2"><?php echo number_format($averageYesterday,2); ?>%</td>
                    </tr>
                    <tr>
                        <td>Average Score Last Week</td>
                        <td colspan="2"><?php echo number_format($averageLastWeek,2); ?>%</td>
                    </tr>
                    <tr>
                        <td>Average Score Last Month</td>
                        <td colspan="2"><?php echo number_format($averageLastMonth,2); ?>%</td>
                    </tr>
                    <tr>
                        <td>Average Score Last Year</td>
                        <td colspan="2"><?php echo number_format($averageLastYear,2); ?>%</td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
    <?php
    $topTenUserTestsToday = $statisticsObj->getUsersTopTenTestsDay();
    $topTenUserTestsMonth = $statisticsObj->getUsersTopTenTestsMonth();
    $topTenUserTestsYear = $statisticsObj->getUsersTopTenTestsYear();

    $topTenUserAverageToday = $statisticsObj->getUsersTopTenAverageDay();
    $topTenUserAverageMonth = $statisticsObj->getUsersTopTenAverageMonth();
    $topTenUserAverageYear = $statisticsObj->getUsersTopTenAverageYear();
    ?>
    <div class="clearfix">&nbsp;</div>
    <header>
        <h2>Top 10 Users</h2>
    </header>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Tests Taken Today</h2>
                </header>
                <?php if($topTenUserTestsToday): ?>
                <table id="topTenUserTestsToday">
                    <thead>
                        <tr>
                            <td><strong>#</strong></td>
                            <td><strong>User</strong></td>
                            <td><strong>Tests</strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($topTenUserTestsToday as $userPlace => $rowData): ?>
                        <?php $userObj->loadUser($rowData['userUUID']); ?>
                        <tr>
                            <td><?php echo $userPlace; ?></td>
                            <td><a href="/admin/profile/<?php echo $userObj->getUUID(); ?>" title="View Profile"><?php echo $userObj->getFullName(); ?></a></td>
                            <td><?php echo number_format($rowData['testCount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No users have tested today.</p>
                <?php endif; ?>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>Tests Taken This Month</h2>
                </header>
                <?php if($topTenUserTestsMonth): ?>
                    <table id="topTenUserTestsMonth">
                        <thead>
                        <tr>
                            <td><strong>#</strong></td>
                            <td><strong>User</strong></td>
                            <td><strong>Tests</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($topTenUserTestsMonth as $userPlace => $rowData): ?>
                            <?php $userObj->loadUser($rowData['userUUID']); ?>
                            <tr>
                                <td><?php echo $userPlace; ?></td>
                                <td><a href="/admin/profile/<?php echo $userObj->getUUID(); ?>" title="View Profile"><?php echo $userObj->getFullName(); ?></a></td>
                                <td><?php echo number_format($rowData['testCount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users have tested this month.</p>
                <?php endif; ?>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>Tests Taken This Year</h2>
                </header>
                <?php if($topTenUserTestsYear): ?>
                    <table id="topTenUserTestsYear">
                        <thead>
                        <tr>
                            <td><strong>#</strong></td>
                            <td><strong>User</strong></td>
                            <td><strong>Tests</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($topTenUserTestsYear as $userPlace => $rowData): ?>
                            <?php $userObj->loadUser($rowData['userUUID']); ?>
                            <tr>
                                <td><?php echo $userPlace; ?></td>
                                <td><a href="/admin/profile/<?php echo $userObj->getUUID(); ?>" title="View Profile"><?php echo $userObj->getFullName(); ?></a></td>
                                <td><?php echo number_format($rowData['testCount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users have tested this year.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Average Score Today</h2>
                </header>
                <?php if($topTenUserAverageToday): ?>
                    <table id="topTenUserAverageToday">
                        <thead>
                        <tr>
                            <td><strong>#</strong></td>
                            <td><strong>User</strong></td>
                            <td><strong>Average</strong></td>
                            <td><strong>Tests</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($topTenUserAverageToday as $userPlace => $rowData): ?>
                            <?php $userObj->loadUser($rowData['userUUID']); ?>
                            <tr>
                                <td><?php echo $userPlace; ?></td>
                                <td><a href="/admin/profile/<?php echo $userObj->getUUID(); ?>" title="View Profile"><?php echo $userObj->getFullName(); ?></a></td>
                                <td><?php echo number_format($rowData['averageScore'],2); ?></td>
                                <td><?php echo number_format($rowData['testCount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users have tested today.</p>
                <?php endif; ?>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>Average Score This Month</h2>
                </header>
                <?php if($topTenUserAverageMonth): ?>
                    <table id="topTenUserAverageMonth">
                        <thead>
                        <tr>
                            <td><strong>#</strong></td>
                            <td><strong>User</strong></td>
                            <td><strong>Average</strong></td>
                            <td><strong>Tests</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($topTenUserAverageMonth as $userPlace => $rowData): ?>
                            <?php $userObj->loadUser($rowData['userUUID']); ?>
                            <tr>
                                <td><?php echo $userPlace; ?></td>
                                <td><a href="/admin/profile/<?php echo $userObj->getUUID(); ?>" title="View Profile"><?php echo $userObj->getFullName(); ?></a></td>
                                <td><?php echo number_format($rowData['averageScore'],2); ?></td>
                                <td><?php echo number_format($rowData['testCount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users have tested this month.</p>
                <?php endif; ?>
            </section>
        </div>
        <div class="4u">
            <section>
                <header>
                    <h2>Average Score This Year</h2>
                </header>
                <?php if($topTenUserAverageYear): ?>
                    <table id="topTenUserAverageYear">
                        <thead>
                        <tr>
                            <td><strong>#</strong></td>
                            <td><strong>User</strong></td>
                            <td><strong>Average</strong></td>
                            <td><strong>Tests</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($topTenUserAverageYear as $userPlace => $rowData): ?>
                            <?php $userObj->loadUser($rowData['userUUID']); ?>
                            <tr>
                                <td><?php echo $userPlace; ?></td>
                                <td><a href="/admin/profile/<?php echo $userObj->getUUID(); ?>" title="View Profile"><?php echo $userObj->getFullName(); ?></a></td>
                                <td><?php echo number_format($rowData['averageScore'],2); ?></td>
                                <td><?php echo number_format($rowData['testCount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users have tested this year.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <?php
    $groupedLogActionCountArray = $statisticsObj->getGroupedLogActionCount();

    if($groupedLogActionCountArray):
    ?>
    <div class="row">
        <div class="6u">
            <section style="height:30em;overflow-y:scroll;overflow-x:hidden;">
                <header>
                    <h2>Log Action Totals</h2>
                </header>
                <table id="logActionCountTable">
                    <thead>
                        <tr>
                            <td><strong>Action</strong></td>
                            <td><strong>Count</strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($groupedLogActionCountArray as $groupedLogActionCountKey => $groupedLogActionCountValue): ?>
                        <tr>
                            <td title="<?php echo $groupedLogActionCountKey; ?>"><span class="<?php echo $log->getRowStyle($groupedLogActionCountKey); ?>"><?php echo $groupedLogActionCountKey; ?></span></td>
                            <td><?php echo number_format($groupedLogActionCountValue); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
    <?php endif; ?>
</div>