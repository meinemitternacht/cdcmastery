<?php
$afscList = $afsc->listAFSC();
?>
<div class="container">
    <div class="row">
        <div class="4u">
            <div class="sub-menu">
                <div class="menu-heading">
                    About CDCMastery.com
                </div>
                <ul>
                    <li><a href="#about-afsc-listing">Materials Available for Testing</a></li>
                    <li><a href="#about-fouo-materials">FOUO Information</a></li>
                    <li><a href="#about-version-history">Version History</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2 id="about-afsc-listing">Materials Available for Testing</h2>
                </header>
                <p>
                    We have the following AFSC's loaded in our database for testing purposes.  Please note that For Official Use Only (FOUO) CDC material
                    requires authorization to view. Contact your Training Manager or <a href="http://helpdesk.cdcmastery.com/">open a ticket</a> for
                    assistance. If you are interested in adding questions from your career field, contact us at
                    <a href="mailto:info@cdcmastery.com">info@cdcmastery.com</a>.
                </p>
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
                            padding-left: 25%;
                        }

                        td:before {
                            position: absolute;
                            top: 6px;
                            left: 6px;
                            width: 20%;
                            padding-right: 10px;
                            white-space: nowrap;
                        }

                        table#afsc-list-table-1 td:nth-of-type(1):before { content: "AFSC Name"; }
                        table#afsc-list-table-1 td:nth-of-type(2):before { content: "Version"; }
                        table#afsc-list-table-1 td:nth-of-type(3):before { content: "Questions"; }
                        table#afsc-list-table-1 td:nth-of-type(4):before { content: "FOUO"; }
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
                <table id="afsc-list-table-1">
                    <tr>
                        <th>AFSC Name</th>
                        <th>Version</th>
                        <th>Questions</th>
                        <th>FOUO</th>
                    </tr>
                    <?php foreach($afscList as $dataRow): ?>
                    <tr>
                        <td><?php echo $dataRow['afscName']; ?></td>
                        <td><?php echo $cdcMastery->formatOutputString($dataRow['afscVersion']); ?></td>
                        <td><?php echo $cdcMastery->formatOutputString($dataRow['totalQuestions']); ?></td>
                        <td><?php echo $dataRow['afscFOUO'] ? "Yes" : "No"; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2 id="about-fouo-materials">For Official Use Only CDC Materials</h2>
                </header>
                <p>
                    CDCMastery includes the ability for users to take tests relating to FOUO CDC material. This feature comes with additional security to protect the information stored in the database. This includes but is not limited to:
                    <br>
                    <ul>
                        <li>User activation using Air Force global e-mail addresses</li>
                        <li>Database encryption for FOUO questions and answers</li>
                        <li>Ability to take FOUO tests controlled by Unit Training Managers</li>
                    </ul>
                    <br>
                    If you have any questions concerning the implementation of FOUO testing material, please contact the site administrator: <a href="mailto:info@cdcmastery.com">info@cdcmastery.com</a>.
                </p>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="12u">
            <section>
                <header>
                    <h2 id="about-version-history">Version History</h2>
                </header>
                <p>
                    Listed below are all of the recorded code changes made to the site.
                </p>
                <pre>
<strong>3.00-Alpha
    [19 January 2015]</strong>
    Released Alpha testing version.

<strong>2.40-Production
    [14 January 2015]</strong>
    Add FOUO AFSC authorization queue.

<strong>2.39-Production
    [21 April 2014]</strong>
    Fix various bugs and permission issues.

<strong>2.38-Production
    [20 April 2014]</strong>
    Updated search page to include the ability to search AFSC associations.

<strong>2.37-Production
    [13 April 2014]</strong>
    Added a search functionality for Administrators, Training Managers and Supervisors.

<strong>2.36-Production
    [11 April 2014]</strong>
    Added a contact form to the "contact us" page for registered users.

<strong>2.35-Production
    [11 April 2014]</strong>
    Added office symbols for organizations.

<strong>2.34-Production
    [10 April 2014]</strong>
    Fixed a bug that would not allow UTM's and Supervisors to view FOUO test details without previously authorizing
    themselves for FOUO content.
    Added the ability to audit test questions after input (Administration Panel > Questions > Audit Questions)

<strong>2.33-Production
    [02 April 2014]</strong>
    Added a feature to consolidate users who have duplicate accounts
    Added server-side cron jobs to maintain database cleanliness

<strong>2.32-Production
    [31 January 2014]</strong>
    The ability to generate printable tests was added

<strong>2.31-Production
    [13 August 2013]</strong>
    A parent/child relationship view was added to the profile viewer in the administration panel

<strong>2.30-Production
    [22 April 2013]</strong>
    The ability to take a test using questions from multiple AFSC's was added

<strong>2.21-Production
    [14 January 2013]</strong>
    Added the ability for administrators to migrate tests between users

<strong>2.20-Production
    [2 December 2012]</strong>
    Redesigned User Options section, combining many areas into a new "Edit Profile" page
    Added a rate limiter to bad login attempts
    Added the ability for a user to view log entries they have made
    Added the ability to print some user information such as Missed and Unseen questions
    Refactored many parts of the codebase to allow for ease of development
    Reintroduced a bug tracker (administrators only)

<strong>2.14-Production
    [1 December 2012]</strong>
    Added 2AX7X CDC Data

<strong>2.13-Production
    [20 October 2012]</strong>
    Redesigned the site theme and navigation

<strong>2.12-Production
    [19 October 2012]</strong>
    Graphs using the Google Visualization API were added to the statistics
    Fixed a few broken links in the Administration section
    Fixed a date that wasn't being affected by the user's time zone in the test detail page

<strong>2.11-Production
    [6 October 2012]</strong>
    Public statistics were added

<strong>2.10-Production
    [23 September 2012]</strong>
    Completely reworked the permission structure
    Added Lightbox to graph images
    Removed beta status

<strong>2.08b
    [27 August 2012]</strong>
    The test engine has been updated.  You can now click the answer "block" to submit the chosen answer
    A bug has been fixed that allowed a user to continue a test that had been deleted on another computer

<strong>2.07b
    [26 August 2012]</strong>
    The loading animation between questions was changed
    User interface styling was updated
    Users can now be associated with multiple AFSC's
    Various Administration Panel sections were redesigned

<strong>2.06b
    [14 August 2012]</strong>
    The ability to take tests containing FOUO material was added.
    2W151B test material was added to the database

<strong>2.05b
    [24 July 2012]</strong>
    2W171 AFSC was added to the database
    Questions and answers updated to use a UUID for each field

<strong>2.02b
    [16 July 2012]</strong>
    Changed UI Styling
    Privacy Policy was added

<strong>1.96b
    [12 July 2012]</strong>
    Added AJAX to the testing engine
    Added Time Zones
    Added Forums
    Fixed a bug preventing supervisors from viewing tests

<strong>1.95b
    [10 July 2012]</strong>
    Unit Training Manager and Supervisor permission levels added
    Added a user filter to the log
    Added a condensed view to the test history overview

<strong>1.94b
    [6 July 2012]</strong>
    Various Admin Functionality added
    Updated User Interface styling

<strong>1.93b
    [5 July 2012]</strong>
    Various Admin Functionality added

<strong>1.91b
    [18 June 2012]</strong>
    Added e-mail verification and activation
    The resume test functionality has been restored

<strong>1.8b
    [11 May 2012]</strong>
    The resume test functionality has been temporarily removed

<strong>1.7b
    [7 April 2012]</strong>
    Added a password reset
    Fixed a bug that allowed a user to score their test before it was complete

<strong>1.6b
    [27 March 2012]</strong>
    Added the resume test functionality
    Added a graph of a user's tests to User Options
    User Administration functions are now broken down by base
    Added graphs to some statistics

<strong>1.1b - 1.5b
    [15 February 2012 - 26 March 2012]</strong>
    Various user and administrative functions were created
    User interface styling was refined

<strong>1.0b
    [14 February 2012]</strong>
    CDCMastery.com was launched
</pre>
            </section>
        </div>
    </div>
</div>