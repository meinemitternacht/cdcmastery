create table afscList
(
    uuid        binary(36)       not null
        primary key,
    name        varchar(32)      not null,
    description mediumtext       null,
    version     varchar(191)     null,
    editCode    varchar(191)     null,
    fouo        int(1) default 0 not null,
    hidden      int(1) default 0 not null,
    obsolete    int(1) default 0 not null,
    constraint afscName
        unique (name, editCode)
)
    charset = utf8;

create table answerData
(
    uuid          binary(36)       not null
        primary key,
    answerText    blob             not null,
    answerCorrect int(1) default 0 not null,
    questionUUID  binary(36)       not null
)
    charset = utf8;

create index answerCorrect
    on answerData (answerCorrect);

create index questionUUID
    on answerData (questionUUID);

create table baseList
(
    uuid     binary(36)   not null
        primary key,
    baseName varchar(255) not null
)
    charset = utf8;

create index baseName
    on baseList (baseName);

create table emailQueue
(
    uuid           binary(36)                          not null
        primary key,
    queueTime      timestamp default CURRENT_TIMESTAMP not null,
    emailSender    varchar(255)                        not null,
    emailRecipient varchar(255)                        not null,
    emailSubject   varchar(255)                        not null,
    emailBodyHTML  longtext                            not null,
    emailBodyText  longtext                            not null,
    queueUser      binary(36)                          not null
)
    charset = utf8;

create index emailRecipient
    on emailQueue (emailRecipient);

create index emailSender
    on emailQueue (emailSender);

create index queueTime
    on emailQueue (queueTime);

create index queueUser
    on emailQueue (queueUser);

create table officeSymbolList
(
    uuid         binary(36)  not null
        primary key,
    officeSymbol varchar(16) not null,
    constraint officeSymbol
        unique (officeSymbol)
)
    charset = utf8;

create table questionData
(
    uuid         binary(36)           not null
        primary key,
    afscUUID     binary(36)           not null,
    questionText blob                 not null,
    disabled     tinyint(1) default 0 not null
)
    charset = utf8;

create index afscUUID
    on questionData (afscUUID);

create index questionData_disabled_index
    on questionData (disabled);

create table roleList
(
    uuid            binary(36)  not null
        primary key,
    roleType        varchar(64) null,
    roleName        varchar(64) not null,
    roleDescription text        null,
    constraint roleName
        unique (roleName),
    constraint roleType
        unique (roleType)
)
    charset = utf8;

create table userData
(
    uuid               binary(36)       not null
        primary key,
    userFirstName      varchar(64)      not null,
    userLastName       varchar(64)      not null,
    userHandle         varchar(64)      not null,
    userPassword       varchar(255)     null,
    userLegacyPassword varchar(255)     null,
    userEmail          varchar(255)     not null,
    userRank           varchar(11)      not null,
    userDateRegistered datetime         not null,
    userLastLogin      datetime         null,
    userLastActive     timestamp        null,
    userTimeZone       varchar(255)     not null,
    userRole           binary(36)       not null,
    userOfficeSymbol   binary(36)       null,
    userBase           binary(36)       not null,
    userDisabled       int(1) default 0 not null,
    reminderSent       tinyint(1)       null,
    constraint userHandle
        unique (userHandle),
    constraint userData_ibfk_1
        foreign key (userBase) references baseList (uuid)
            on update cascade,
    constraint userData_ibfk_2
        foreign key (userRole) references roleList (uuid)
            on update cascade,
    constraint userData_ibfk_4
        foreign key (userOfficeSymbol) references officeSymbolList (uuid)
            on update cascade on delete set null
)
    charset = utf8;

create table flashCardCategories
(
    uuid              binary(36)           not null
        primary key,
    categoryName      varchar(255)         not null,
    categoryEncrypted tinyint(1) default 0 not null,
    categoryType      varchar(32)          not null,
    categoryBinding   binary(36)           null,
    categoryCreatedBy binary(36)           null,
    categoryComments  text                 null,
    constraint flashCardCategories_ibfk_1
        foreign key (categoryCreatedBy) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index categoryBinding
    on flashCardCategories (categoryBinding);

create index categoryCreatedBy
    on flashCardCategories (categoryCreatedBy);

create index categoryEncrypted
    on flashCardCategories (categoryEncrypted);

create index categoryName
    on flashCardCategories (categoryName);

create index categoryType
    on flashCardCategories (categoryType);

create table flashCardData
(
    uuid         binary(36) not null
        primary key,
    frontText    blob       not null,
    backText     blob       not null,
    cardCategory binary(36) not null,
    constraint flashCardData_ibfk_1
        foreign key (cardCategory) references flashCardCategories (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index cardCategory
    on flashCardData (cardCategory);

create table queueRoleAuthorization
(
    userUUID      binary(36) not null
        primary key,
    roleUUID      binary(36) not null,
    dateRequested datetime   not null,
    constraint queueRoleAuthorization_ibfk_1
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade,
    constraint queueRoleAuthorization_ibfk_2
        foreign key (roleUUID) references roleList (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index roleUUID
    on queueRoleAuthorization (roleUUID);

create index userUUID
    on queueRoleAuthorization (userUUID, roleUUID, dateRequested);

create table queueUnactivatedUsers
(
    activationCode binary(36) not null
        primary key,
    userUUID       binary(36) not null,
    timeExpires    datetime   not null,
    constraint userUUID
        unique (userUUID),
    constraint queueUnactivatedUsers_ibfk_1
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index timeExpires
    on queueUnactivatedUsers (timeExpires);

create table testCollection
(
    uuid          binary(36)                 not null
        primary key,
    userUuid      binary(36)                 not null,
    afscList      mediumtext                 null,
    timeStarted   datetime                   not null,
    timeCompleted datetime                   null,
    questionList  mediumtext                 null,
    curQuestion   int(4)        default 0    not null,
    numAnswered   int(4)        default 0    not null,
    numMissed     int(4)        default 0    not null,
    score         decimal(5, 2) default 0.00 not null,
    combined      int(1)        default 0    not null,
    archived      int(1)        default 0    not null,
    constraint testCollection_userData_uuid_fk
        foreign key (userUuid) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create table testData
(
    testUUID     binary(36) not null,
    questionUUID binary(36) not null,
    answerUUID   binary(36) not null,
    constraint testQuestion
        unique (testUUID, questionUUID),
    constraint testData_answerData_uuid_fk
        foreign key (answerUUID) references answerData (uuid)
            on update cascade on delete cascade,
    constraint testData_questionData_uuid_fk
        foreign key (questionUUID) references questionData (uuid)
            on update cascade on delete cascade,
    constraint testData_testCollection_uuid_fk
        foreign key (testUUID) references testCollection (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index answerUUID
    on testData (answerUUID);

create index questionUUID
    on testData (questionUUID);

create index testUUID
    on testData (testUUID);

create table testGeneratorData
(
    uuid           binary(36)                          not null
        primary key,
    afscUUID       binary(36)                          not null,
    questionList   mediumtext                          not null,
    totalQuestions int(5)                              not null,
    userUUID       binary(36)                          not null,
    dateCreated    timestamp default CURRENT_TIMESTAMP not null,
    constraint testGeneratorData_ibfk_1
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade,
    constraint testGeneratorData_ibfk_2
        foreign key (afscUUID) references afscList (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index afscUUID
    on testGeneratorData (afscUUID, totalQuestions, userUUID, dateCreated);

create index userUUID
    on testGeneratorData (userUUID);

create table userAFSCAssociations
(
    userUUID       binary(36)           not null,
    afscUUID       binary(36)           not null,
    userAuthorized tinyint(1) default 0 not null,
    primary key (userUUID, afscUUID),
    constraint userAFSCAssociations_ibfk_1
        foreign key (afscUUID) references afscList (uuid)
            on update cascade on delete cascade,
    constraint userAFSCAssociations_ibfk_2
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index afscUUID
    on userAFSCAssociations (afscUUID);

create index userAuthorized
    on userAFSCAssociations (userAuthorized);

create index reminderSent
    on userData (reminderSent);

create index userBase
    on userData (userBase);

create index userDateRegistered
    on userData (userDateRegistered);

create index userDisabled
    on userData (userDisabled);

create index userEmail
    on userData (userEmail);

create index userFirstName
    on userData (userFirstName);

create index userLastActive
    on userData (userLastActive);

create index userLastLogin
    on userData (userLastLogin);

create index userLastName
    on userData (userLastName);

create index userLegacyPassword
    on userData (userLegacyPassword);

create index userOfficeSymbol
    on userData (userOfficeSymbol);

create index userPassword
    on userData (userPassword);

create index userRank
    on userData (userRank);

create index userRole
    on userData (userRole);

create index userTimeZone
    on userData (userTimeZone);

create table userPasswordResets
(
    uuid          binary(36)                          not null
        primary key,
    userUUID      binary(36)                          not null,
    timeRequested timestamp default CURRENT_TIMESTAMP not null,
    timeExpires   datetime                            not null,
    constraint userUUID
        unique (userUUID),
    constraint userPasswordResets_ibfk_1
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index timeRequested
    on userPasswordResets (timeRequested, timeExpires);

create table userSupervisorAssociations
(
    supervisorUUID binary(36) not null,
    userUUID       binary(36) not null,
    primary key (supervisorUUID, userUUID),
    constraint userSupervisorAssociations_userData_uuid_fk
        foreign key (supervisorUUID) references userData (uuid)
            on update cascade on delete cascade,
    constraint userSupervisorAssociations_userData_uuid_fk_2
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index supervisorUUID
    on userSupervisorAssociations (supervisorUUID);

create index userUUID
    on userSupervisorAssociations (userUUID);

create table userTrainingManagerAssociations
(
    trainingManagerUUID binary(36) not null,
    userUUID            binary(36) not null,
    primary key (trainingManagerUUID, userUUID),
    constraint userTrainingManagerAssociations_ibfk_1
        foreign key (userUUID) references userData (uuid)
            on update cascade on delete cascade,
    constraint userTrainingManagerAssociations_ibfk_2
        foreign key (trainingManagerUUID) references userData (uuid)
            on update cascade on delete cascade
)
    charset = utf8;

create index trainingManagerUUID
    on userTrainingManagerAssociations (trainingManagerUUID);

create index userUUID
    on userTrainingManagerAssociations (userUUID);
