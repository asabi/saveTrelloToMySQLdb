<?php
require __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('America/Vancouver');

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

      saveAllCards($trello, $conn, $board, $cardOptions);

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

/*
   Boards are limited to a 1000 max per request. We need to grab them
   using pagngination.

   Returns a string with comma delimited card id's to keep (we remove any card
   that was deleted from Trello - not simply archived but completely deleted)

   @return string
*/
function saveAllCards($trello, $conn, $board, $cardOptions) {

      $cards = $trello->get('boards/'.$board->id.'/cards', $cardOptions);


      if ($board->id === '56458350c739ebdd30e5f058') {
        //echo 'Processing:'.sizeof($cards)." cards in {$board->name}\n";
        echo ' Last: '.$cards[$cardOptions['limit'] - 1]->id.' - First: '.$cards[0]->id."\n";

        //print_r($cards);
      }

      // We will continue to pull cards as long as there are more than the limit
      // The first pass has to happen though.
      $continue = true;

      while ($continue) {
        $cardsToKeepFromSave = saveFoundCards($cards, $conn);

        if ($cardsToKeepFromSave!= '') {
          $cardsToKeep.= empty($cardsToKeep)? $cardsToKeepFromSave:','.$cardsToKeepFromSave;
        }

        // If the number of cards found is less than the limit, then we do can stop.
        if (sizeof($cards) < $cardOptions['limit']) {
            $continue = false;
        } else {
            $lastCardIdInFoundSet = $cards[0]->id;

            $cardOptions['before'] = $lastCardIdInFoundSet;
            $cards = $trello->get('boards/'.$board->id.'/cards', $cardOptions);

            if ($board->id === '56458350c739ebdd30e5f058') {
              echo "-----------------------------------------------------\n";
              //print_r($cards);
              //echo 'Processing:'.sizeof($cards)." cards in {$board->name}\n";
              echo 'Last: '.$cards[$cardOptions['limit'] - 1]->id.' - First: '.$cards[0]->id."\n";

              //print_r($cards);
            }

        }
      }

      return $cardsToKeep;

}

function saveFoundCards($cards, $conn) {
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
