<?php
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 9/23/2015
 * Time: 8:01 PM
 */
$statisticsObj = new statistics($db,$log,$emailQueue);
?>
<div class="container">
    <div class="row">
        <div class="4u">
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
    </div>
    <div class="row">
        <div class="4u">
            <section>
                <header>
                    <h2>Testing Statistics</h2>
                </header>
                <table>
                    <tr>
                        <td><strong>Completed Tests</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalCompletedTests()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Incomplete Tests</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalIncompleteTests()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Tests</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalTests()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Questions Answered</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsAnswered()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-success"><a href="/admin/log/0/25/timestamp/DESC/action/TEST_START"><strong>Tests Started</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("TEST_START")); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><span class="text-caution"><a href="/admin/log/0/25/timestamp/DESC/action/INCOMPLETE_TEST_DELETE"><strong>Tests Deleted</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("TEST_DELETE") + $statisticsObj->getLogCountByAction("INCOMPLETE_TEST_DELETE")); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Flash Card Sessions</strong></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("NEW_FLASH_CARD_SESSION")); ?></td>
                    </tr>
                    <tr>
                        <td><strong>AFSC Flash Card Categories</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalAFSCCategories()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Global Flash Card Categories</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalGlobalFlashCardCategories()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Private Flash Card Categories</strong></td>
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
                <table>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Users</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalUsers()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Normal Users</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleUser()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Supervisors</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleSupervisor()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Training Managers</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleTrainingManager()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Administrators</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleAdministrator()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Super Administrators</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleSuperAdministrator()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Question Editors</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleEditor()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Office Symbols</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalOfficeSymbols()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/USER_DELETE"><strong>Users Deleted</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("USER_DELETE")); ?></td>
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
                        <td><strong>Log Entries</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLogEntries()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Log Details</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLogDetails()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Login Errors</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLoginErrors()); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/ERROR_LOGIN_RATE_LIMIT_REACHED"><strong>Login Rate Limit Reached</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("ERROR_LOGIN_RATE_LIMIT_REACHED")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/ROUTING_ERROR"><strong>Route Errors</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("ROUTING_ERROR")); ?></td>
                    </tr>
                    <tr>
                        <td><span class="text-warning"><a href="/admin/log/0/25/timestamp/DESC/action/AJAX_DIRECT_ACCESS"><strong>AJAX Direct Access</strong></a></span></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("AJAX_DIRECT_ACCESS")); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Errors</strong></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("%ERROR%")); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Migrated Passwords</strong></td>
                        <td><?php echo number_format($statisticsObj->getLogCountByAction("MIGRATED_PASSWORD")); ?></td>
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
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>FOUO AFSC Categories</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalFOUOAFSCCategories()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Archived Questions</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsArchived()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>FOUO Questions</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsFOUO()); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Total Questions</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestions()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Archived Answers</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalAnswersArchived()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>FOUO Answers</strong></td>
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
                        <td><strong>Active This Week</strong></td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveThisWeek()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Active This Month</strong></td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveThisMonth()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Active This Year</strong></td>
                        <td><?php echo number_format($statisticsObj->getUsersActiveThisYear()); ?></td>
                    </tr>
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

                $percentIncreaseTests['today'] = round(((($testsToday - $testsYesterday) / $testsYesterday) * 100),2) . "%";
                $percentIncreaseTests['week'] = round(((($testsThisWeek - $testsLastWeek) / $testsLastWeek) * 100),2) . "%";
                $percentIncreaseTests['month'] = round(((($testsThisMonth - $testsLastMonth) / $testsLastMonth) * 100),2) . "%";
                $percentIncreaseTests['year'] = round(((($testsThisYear - $testsLastYear) / $testsLastYear) * 100),2) . "%";

                $percentIncreaseAverage['today'] = round(((($averageToday - $averageYesterday) / $averageYesterday) * 100),2) . "%";
                $percentIncreaseAverage['week'] = round(((($averageThisWeek - $averageLastWeek) / $averageLastWeek) * 100),2) . "%";
                $percentIncreaseAverage['month'] = round(((($averageThisMonth - $averageLastMonth) / $averageLastMonth) * 100),2) . "%";
                $percentIncreaseAverage['year'] = round(((($averageThisYear - $averageLastYear) / $averageLastYear) * 100),2) . "%";
                ?>
                <table>
                    <tr>
                        <td><strong>Tests Today</strong></td>
                        <td><?php echo number_format($testsToday); ?></td>
                        <td class="<?php if($percentIncreaseTests['today'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['today']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tests This Week</strong></td>
                        <td><?php echo number_format($testsThisWeek); ?></td>
                        <td class="<?php if($percentIncreaseTests['week'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['week']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tests This Month</strong></td>
                        <td><?php echo number_format($testsThisMonth); ?></td>
                        <td class="<?php if($percentIncreaseTests['month'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['month']; ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Tests This Year</strong></td>
                        <td><?php echo number_format($testsThisYear); ?></td>
                        <td class="<?php if($percentIncreaseTests['year'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseTests['year']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tests Yesterday</strong></td>
                        <td colspan="2"><?php echo number_format($testsYesterday); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tests Last Week</strong></td>
                        <td colspan="2"><?php echo number_format($testsLastWeek); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tests Last Month</strong></td>
                        <td colspan="2"><?php echo number_format($testsLastMonth); ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Tests Last Year</strong></td>
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
                        <td><strong>Average Score This Week</strong></td>
                        <td><?php echo number_format($averageThisWeek,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['week'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['week']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Average Score This Month</strong></td>
                        <td><?php echo number_format($averageThisMonth,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['month'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['month']; ?></td>
                    </tr>
                    <tr style="border-bottom: 2px solid #999">
                        <td><strong>Average Score This Year</strong></td>
                        <td><?php echo number_format($averageThisYear,2); ?>%</td>
                        <td class="<?php if($percentIncreaseAverage['year'] < 0): echo "text-warning"; else: echo "text-success"; endif; ?>"><?php echo $percentIncreaseAverage['year']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Average Score Yesterday</strong></td>
                        <td colspan="2"><?php echo number_format($averageYesterday,2); ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>Average Score Last Week</strong></td>
                        <td colspan="2"><?php echo number_format($averageLastWeek,2); ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>Average Score Last Month</strong></td>
                        <td colspan="2"><?php echo number_format($averageLastMonth,2); ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>Average Score Last Year</strong></td>
                        <td colspan="2"><?php echo number_format($averageLastYear,2); ?>%</td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>