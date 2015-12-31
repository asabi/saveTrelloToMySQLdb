# Store Trello card information in a MySQL database

The purpose of this project is to allow running different statistical queries on Trello cards (read only access to trello cards).

##Requirements:

- A running mySQL server
- Composer installed (https://getcomposer.org)
- PHP 5.5.x
- A Trello account (DAH!)

##Installation:

- Download the code
- Run "composer install"
- Go to Trello.com API page, and get a key and a token for your trello account (https://developers.trello.com/get-started)
- Create a new MySQL database (I named mine "trello"), and load the trello.sql file into it.
- cp config.php.template config.php
- Update config.php with the information to your database and the information to your trello account.
- run it once to test: php trelloToMySQL.php (your mysql database should be populated with your trello cards information)
- Create yourself a cron job to run it every X minutes / hours ... whatever works for you.
   -- Example: 0 * * * * /usr/bin/php /path/to/trelloToMySQL.php will run it every hour

#Some useful queries:

## Cards by list (excluding archived):
<code>
SELECT COUNT(card.id)as cards, list.name AS listName FROM `card`
INNER join `board` on `board`.id = `card`.idBoard
INNER join list on list.idBoard = board.id And card.idList = list.id
Where card.`closed`=0 and board.`name`='Life'
GROUP BY list.id
Order by FIELD(list.name,'Project','Later','waiting on','next week','this week','today','done')
</code>

## Summary information:

- Note: Some of those assume that completed cards are moved to a "Done" list before they get archived.

<code>
SELECT 'Avg Days To Complete:' as Description, AVG(DATEDIFF(card.`dateLastActivity`,card.timeCreated)) AS Stat FROM card WHERE card.`closed` = 1
UNION
SELECT 'Avg Days Opened:', AVG(DATEDIFF(card.`dateLastActivity`,card.timeCreated)) AS avgDaysToClose FROM card WHERE card.`closed` = 0
UNION
Select 'Completed Cards:',count(card.id) as openedCards from card INNER JOIN list ON list.id = card.idList WHERE list.name = 'Done'
UNION
Select 'Available Cards:',count(card.id) as openedCards from card WHERE card.`closed` = 0
UNION
SELECT 'AVG Completed/Weekday:',AVG(completedCards) FROM (
SELECT DATE_FORMAT(`dateLastActivity`,'%Y-%m-%d'), count(card.id) as completedCards from card
INNER JOIN list ON list.id = card.idList WHERE list.name = 'Done' AND WEEKDAY(`dateLastActivity`) NOT IN (5,6)
GROUP BY DATE_FORMAT(`dateLastActivity`,'%Y-%m-%d')) AS dailyAvgWeekday
UNION
SELECT 'AVG Completed/day:',AVG(completedCards) FROM (
Select DATE_FORMAT(`dateLastActivity`,'%Y-%m-%d'), count(card.id) as completedCards from card
INNER JOIN list ON list.id = card.idList WHERE list.name = 'Done'
GROUP BY DATE_FORMAT(`dateLastActivity`,'%Y-%m-%d')) AS dailyAvg
UNION
SELECT 'AVG Added/Day:',AVG(addedCards) FROM (
Select DATE_FORMAT(`timeCreated`,'%Y-%m-%d'), count(card.id) as addedCards from card
GROUP BY DATE_FORMAT(`timeCreated`,'%Y-%m-%d')
) AS dailyAddedAvg
UNION
SELECT 'Deferred Tickets:', count(card.id) FROM card INNER JOIN list ON list.id = card.idList WHERE card.close =1 AND list.name != 'Done'
</code>

##AVG Cards added by day of the week:
<code>
SELECT DATE_FORMAT(weekDay,'%W'), ROUND(AVG(addedCards),0) AS avgCompletedCards FROM (
SELECT DATE_FORMAT(card.`timeCreated`,'%Y-%m-%d') AS weekDay , count(card.id) as addedCards from card
GROUP BY DATE_FORMAT(`timeCreated`,'%Y-%m-%d')
) AS cards
GROUP BY DATE_FORMAT(weekDay,'%W')
ORDER BY FIELD(DATE_FORMAT(weekDay,'%W'),'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday')
</code>
##AVG cards completed / day of the week (most efficient / less efficient):
- Note: Assumes that completed cards are moved to a "Done" list before they get archived.
<code>
SELECT DATE_FORMAT(weekDay,'%W'), ROUND(AVG(completedCards),0) AS avgCompletedCards FROM (
Select DATE_FORMAT(`dateLastActivity`,'%Y-%m-%d') AS weekDay , count(card.id) as completedCards from card
INNER JOIN list ON list.id = card.idList WHERE list.name = 'Done'
GROUP BY DATE_FORMAT(`dateLastActivity`,'%Y-%m-%d')
) AS cards
GROUP BY DATE_FORMAT(weekDay,'%W')
ORDER BY FIELD(DATE_FORMAT(weekDay,'%W'),'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday')
</code>

##AVG days to close cards by year/month:
<code>
SELECT DATE_FORMAT(card.timeCreated,'%Y %M') AS monthYear, AVG(DATEDIFF(card.`dateLastActivity`,card.timeCreated)) AS avgDaysToClose FROM card
INNER JOIN list ON list.id = card.`idList`
WHERE list.name = 'Done'
GROUP BY DATE_FORMAT(card.timeCreated,'%Y %M')
<code>

##AVG days to close cards:
<code>
SELECT
    AVG(DATEDIFF(card.`dateLastActivity`,card.timeCreated)) AS avgDaysToClose
FROM card
INNER JOIN list ON list.id = card.`idList`
WHERE list.name = 'Done'
<code>

#Assumptions for queries that involve projects:

- Every project has a label that starts with P- or CP-
-- P- Stands for project
-- CP- Stands for Completed project


## List of cards per project

<code>
SELECT card.name,label.`name` FROM `card`
INNER JOIN label ON card.labels LIKE CONCAT('%',label.name,'%')
WHERE label.name LIKE 'P-%' OR label.`name` LIKE 'CP-%'
ORDER BY label.name;
</code>

##List of projects by last activity compared to when they were created:
<code>
SELECT DATEDIFF(max(card.`dateLastActivity`), min(card.timeCreated)) AS lastDay, card.name, label.`name` FROM `card`
INNER JOIN label ON card.labels LIKE CONCAT('%',label.name,'%')
WHERE label.name LIKE 'P-%' OR label.`name` LIKE 'CP-%'
GROUP BY label.`name`
ORDER BY DATEDIFF(max(card.`dateLastActivity`), min(card.timeCreated)) DESC;
</code>

##Projects by inactive days and days opened and number of open tickets:
<code>
SELECT
    count(card.id) - 1 AS tickets,
    label.`name`,
    DATEDIFF(NOW(),max(card.`dateLastActivity`)) AS inactiveDays,
    DATEDIFF(NOW(),min(card.`timeCreated`)) AS daysOpened
FROM card
INNER JOIN `cardLabel` ON `cardLabel`.`idCard` = card.`id`
INNER JOIN label ON label.id = `cardLabel`.idLabel
WHERE label.`name` LIKE 'CP-%' OR label.`name` LIKE 'P-%'
GROUP BY `cardLabel`.idLabel
ORDER BY DATEDIFF(NOW(),max(card.`dateLastActivity`)) DESC
</code>
