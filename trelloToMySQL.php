<?php
require __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('America/Vancouver');
error_reporting(0);

use \Trello\Trello;

require_once(__DIR__.'/config.php');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$cardsToKeep = '';

$trello = new Trello($key, null, $token);

$boardsOptions = array();
$boardsOptions['boards']='all';

$cardOptions = array();
$cardOptions['filter'] = 'all';
$cardOptions['fields'] = 'id,name,shortUrl,due,dateLastActivity,closed,desc,idBoard,idList,labels,pos';
$cardOptions['limit'] = $cardsLimit;


$labelOptions = array();
$labelOptions['limit']=1000;
$labelOptions['fields']='color,idBoard,name,uses';

$listOptions = array();
$listOptions['filter']=  'all';
$listOptions['fields'] = 'closed,idBoard,name,pos';

$me = $trello->members->get('me',$boardsOptions);


foreach ($me->idBoards as $boardId) {
      $board = $trello->boards->get($boardId);
      saveBoard($board, $conn);

      $labels = $trello->get('boards/'.$boardId.'/labels',$labelOptions);
      saveLabels($labels, $conn);
      $lists = $trello->get('boards/'.$boardId.'/lists',$listOptions);
      saveLists($lists, $conn);

      $cards = $trello->get('boards/'.$boardId.'/cards', $cardOptions);

      $continue = true;

      echo 'Processing:'.sizeof($cards)." cards in {$board->name}\n";
      //saveCards($cards, $conn);
      while ($continue) {

        $cardsToKeepFromSave = saveCards($cards, $conn);

        if ($cardsToKeepFromSave!= '') {
          $cardsToKeep.= empty($cardsToKeep)? $cardsToKeepFromSave:','.$cardsToKeepFromSave;
        }

        if (sizeof($cards) < $cardsLimit) {
            $continue = false;
        } else {
            $lastCardCreationDate = date('c', hexdec( substr( $cards[$cardsLimit - 1]->id  , 0, 8 ) ) );
            $dateObj = new DateTime($lastCardCreationDate);

            $lastCard = str_replace('--','T',$dateObj->format('Y-m-d--H:i:s'));

          //  echo $lastCard->id. ' -- '.$lastCard."\n";
            $cardOptions['since'] = $lastCard;
            $cards = $trello->get('boards/'.$boardId.'/cards', $cardOptions);
        }
      }


}

// A simple check, if $cardsToKeep is empty, something probably went wrong
if ($cardsToKeep != '')  {
// Removing all cards no longer in Trello
$strSQL = "DELETE FROM card WHERE id NOT IN ($cardsToKeep)";

$conn->query($strSQL);
}

$conn->close();

function saveBoard($board, $conn) {
  // prepare and bind
  $board = addTimeCreated($board);
  $stmt = $conn->prepare("REPLACE INTO board (id, closed, idOrganization, name, pinned, timeCreated) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $id, $closed, $idOrganization, $name,$pinned, $timeCreated);

  $id = $board->id;
  $closed = $board->closed;
  $idOrganization = $board->idOrganization;
  $name = $board->name;
  $pinned = $board->pinned;
  $timeCreated = $board->timeCreated;
  $stmt->execute();
  $stmt->close();
}

function saveLabels($labels, $conn) {
  //color,idBoard,name,uses
  $stmt = $conn->prepare("REPLACE INTO label (id, color, idBoard, name, uses, timeCreated) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $id, $color, $idBoard, $name,$uses, $timeCreated);

  foreach ($labels as $object) {
    $object = addTimeCreated($object);

    $id = $object->id;
    $color = $object->color;
    $idBoard = $object->idBoard;
    $name = $object->name;
    $uses = $object->uses;
    $timeCreated = $object->timeCreated;
    $stmt->execute();
  }
  $stmt->close();
}

function saveLists($lists, $conn) {
  //closed,idBoard,name,pos
  $stmt = $conn->prepare("REPLACE INTO list (id, closed, idBoard, name, pos, timeCreated) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $id, $closed, $idBoard, $name,$pos, $timeCreated);

  foreach ($lists as $object) {
    $object = addTimeCreated($object);

    $id = $object->id;
    $closed = $object->closed;
    $idBoard = $object->idBoard;
    $name = $object->name;
    $pos = $object->pos;
    $timeCreated = $object->timeCreated;
    $stmt->execute();
  }
  $stmt->close();
}

function saveCards($cards, $conn) {
  //id,closed, idBoard, name,pos, shortUrl,due,dateLastActivity,desc,idList,labels
  $cardsToKeep = '';
  $stmt = $conn->prepare("REPLACE INTO card (id, closed, idBoard, name, pos,shortUrl,due,dateLastActivity,`desc`, idList,labels, timeCreated) VALUES (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?)");

  if ( !$stmt ) {
      echo 'Error:'.$mysqli->error;
      die;
  }

  $stmt->bind_param("ssssssssssss", $id, $closed, $idBoard, $name,$pos, $shortUrl, $due, $dateLastActivity,$desc,$idList,$labels,$timeCreated);

  foreach ($cards as $object) {
    $object = addTimeCreated($object);
    $id = $object->id;
    $closed = $object->closed;
    $idBoard = $object->idBoard;
    $name = $object->name;
    $pos = $object->pos;
    $shortUrl = $object->shortUrl;
    $due = $object->due;
    $dateLastActivity = $object->dateLastActivity;
    $desc = $object->desc;
    $idList = $object->idList;

    $labels = '';
    foreach ($object->labels as $labelObj) {
      $labels.= empty($labels)? $labelObj->name:','.$labelObj->name;
    }

    $timeCreated = $object->timeCreated;
    $stmt->execute();

    saveDetailedLabels($object->id, $object->labels, $conn);
    $cardsToKeep.=empty($cardsToKeep)? "'{$object->id}'":",'{$object->id}'";
    //echo $object->id.','.$object->timeCreated."\n";
  }
  $stmt->close();

  return $cardsToKeep;
}

function saveDetailedLabels($idCard, $labels, $conn) {

  $sql = "DELETE FROM cardLabel WHERE idCard = '$idCard'";
  $conn->query($sql);

  $stmt = $conn->prepare("INSERT INTO cardLabel (idCard, idLabel ) VALUES (?, ?)");
  $stmt->bind_param("ss", $idCard, $idLabel);

  foreach($labels as $label) {
    $idLabel = $label->id;
    $stmt->execute();
  }

  $stmt->close();
}

function addTimeCreated($object) {
  $id = $object->id;
  $createdDate = date('c', hexdec( substr( $id  , 0, 8 ) ) );


  $dateObj = new DateTime($createdDate);

  $object->timeCreated = $dateObj->format('Y-m-d H:i:s');

  return $object;
}
