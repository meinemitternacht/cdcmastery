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
                    <tr>
                        <td><strong>Total Tests</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalTests()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Questions Answered</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsAnswered()); ?></td>
                    </tr>
                </table>
            </section>
            <div class="clearfix">&nbsp;</div>
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
                    <tr>
                        <td><strong>Total Questions</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalQuestionsArchived()); ?></td>
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
                    <h2>User Statistics</h2>
                </header>
                <table>
                    <tr>
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
                    <tr>
                        <td><strong>Question Editors</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalRoleEditor()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Office Symbols</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalOfficeSymbols()); ?></td>
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
                    <tr>
                        <td><strong>Log Details</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLogDetails()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Login Errors</strong></td>
                        <td><?php echo number_format($statisticsObj->getTotalLoginErrors()); ?></td>
                    </tr>
                </table>
            </section>
        </div>
    </div>
</div>