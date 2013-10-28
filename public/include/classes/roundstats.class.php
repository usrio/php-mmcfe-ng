<?php

// Make sure we are called from index.php
if (!defined('SECURITY'))
  die('Hacking attempt');

class RoundStats {
  private $sError = '';
  private $tableTrans = 'transactions';
  private $tableStats = 'statistics_shares';
  private $tableBlocks = 'blocks';
  private $tableUsers = 'accounts';

  public function __construct($debug, $mysqli, $config) {
    $this->debug = $debug;
    $this->mysqli = $mysqli;
    $this->config = $config;
    $this->debug->append("Instantiated RoundStats class", 2);
  }

  // get and set methods
  private function setErrorMessage($msg) {
    $this->sError = $msg;
  }
  public function getError() {
    return $this->sError;
  }

  /**
   * Get next block for round stats
   **/
  public function getNextBlock($iHeight=0) {
    $stmt = $this->mysqli->prepare("
      SELECT height
      FROM $this->tableBlocks
      WHERE height > ?
      ORDER BY height ASC
      LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->height;
    return false;
  }

  /**
   * Get prev block for round stats
   **/
  public function getPreviousBlock($iHeight=0) {
    $stmt = $this->mysqli->prepare("
      SELECT height
      FROM $this->tableBlocks
      WHERE height < ?
      ORDER BY height DESC
      LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->height;
    return false;
  }

  /**
   * search for block height
   **/
  public function searchForBlockHeight($iHeight=0) {
    $stmt = $this->mysqli->prepare("
       SELECT height 
       FROM $this->tableBlocks
       WHERE height >= ?
       ORDER BY height ASC 
       LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->height;
    return false;
  }

  /**
   * get next block for stats paging
   **/
  public function getNextBlockForStats($iHeight=0, $limit=10) {
    $stmt = $this->mysqli->prepare("
      SELECT MAX(x.height) AS height 
      FROM (SELECT height FROM $this->tableBlocks 
      WHERE height >= ?
      ORDER BY height ASC LIMIT ?) AS x");
    if ($this->checkStmt($stmt) && $stmt->bind_param("ii", $iHeight, $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->height;
    return false;
  }

  /**
   * Get details for block height
   * @param height int Block Height
   * @return data array Block information from DB
   **/
  public function getDetailsForBlockHeight($iHeight=0) {
    $stmt = $this->mysqli->prepare("
      SELECT 
      b.id, height, blockhash, amount, confirmations, difficulty, FROM_UNIXTIME(time) as time, shares,
      IF(a.is_anonymous, 'anonymous', a.username) AS finder,
      ROUND((difficulty * 65535) / POW(2, (" . $this->config['difficulty'] . " -16)), 0) AS estshares, 
      (time - (SELECT time FROM $this->tableBlocks WHERE height < ? ORDER BY height DESC LIMIT 1)) AS round_time
        FROM $this->tableBlocks as b
        LEFT JOIN $this->tableUsers AS a ON b.account_id = a.id
        WHERE b.height = ? LIMIT 1");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $iHeight, $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_assoc();
    return false;
  }

  /**
   * Get shares statistics for round block height
   * @param height int Block Height
   * @return data array Block information from DB
   **/
  public function getRoundStatsForAccounts($iHeight=0) {
    $stmt = $this->mysqli->prepare("
      SELECT
        a.id,
        a.username,
        a.is_anonymous,
        s.valid,
        s.invalid
        FROM $this->tableStats AS s
        LEFT JOIN $this->tableBlocks AS b ON s.block_id = b.id
        LEFT JOIN $this->tableUsers AS a ON a.id = s.account_id
        WHERE b.height = ?
        GROUP BY username ASC
        ORDER BY valid DESC
        ");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result()) {
      while ($row = $result->fetch_assoc()) {
        $aData[$row['id']] = $row;
      }
      return $aData;
    }
    return false;
  }

  /**
   * Get pplns statistics for round block height
   * @param height int Block Height
   * @return data array Block information from DB
   **/
  public function getPPLNSRoundStatsForAccounts($iHeight=0) {
    $stmt = $this->mysqli->prepare("
      SELECT
        a.username,
        a.is_anonymous,
        s.pplns_valid,
        s.pplns_invalid
        FROM $this->tableStats AS s
        LEFT JOIN $this->tableBlocks AS b ON s.block_id = b.id
        LEFT JOIN $this->tableUsers AS a ON a.id = s.account_id
        WHERE b.height = ?
        GROUP BY username ASC
        ORDER BY pplns_valid DESC
        ");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_all(MYSQLI_ASSOC);
    return false;
  }

  /**
   * Get total valid pplns shares for block height
   **/
  public function getPPLNSRoundShares($iHeight=0) {
    $stmt = $this->mysqli->prepare("
      SELECT
        SUM(s.pplns_valid) AS pplns_valid
        FROM $this->tableStats AS s
        LEFT JOIN $this->tableBlocks AS b ON s.block_id = b.id
        WHERE b.height = ?
        ");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->pplns_valid;
    return false;
  }

  /**
   * Get all transactions for round block height for admin
   * @param height int Block Height
   * @return data array Block round transactions
   **/
  public function getAllRoundTransactions($iHeight=0) {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("
      SELECT
      t.id AS id,
      a.id AS uid,
      a.username AS username,
      a.is_anonymous,
      t.type AS type,
      t.amount AS amount
      FROM $this->tableTrans AS t
      LEFT JOIN $this->tableBlocks AS b ON t.block_id = b.id
      LEFT JOIN $this->tableUsers AS a ON t.account_id = a.id
      WHERE b.height = ? AND t.type = 'Credit'
      ORDER BY amount DESC");
    if ($this->checkStmt($stmt) && $stmt->bind_param('i', $iHeight) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_all(MYSQLI_ASSOC);
    $this->debug->append('Unable to fetch transactions');
    return false;
  }

  /**
   * Get transactions for round block height user id
   * @param height int Block Height
   * @param id int user id
   * @return data array Block round transactions for user id
   **/
  public function getUserRoundTransactions($iHeight=0, $id=0) {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("
      SELECT
      t.id AS id,
      a.username AS username,
      t.type AS type,
      t.amount AS amount
      FROM $this->tableTrans AS t
      LEFT JOIN $this->tableBlocks AS b ON t.block_id = b.id
      LEFT JOIN $this->tableUsers AS a ON t.account_id = a.id
      WHERE b.height = ? AND a.id = ?
      ORDER BY id ASC");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $iHeight, $id) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_all(MYSQLI_ASSOC);
    $this->debug->append('Unable to fetch transactions');
    return false;
  }

  /**
   * Get ALL last blocks from height for admin panel
   **/
  public function getAllReportBlocksFoundHeight($iHeight=0, $limit=10) {
    $stmt = $this->mysqli->prepare("
      SELECT
        height, shares
      FROM $this->tableBlocks 
      WHERE height <= ?
      ORDER BY height DESC LIMIT ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param("ii", $iHeight, $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_all(MYSQLI_ASSOC);
    return false;
  }

  /**
   * Get USER last blocks from height for admin panel
   **/
  public function getUserReportBlocksFoundHeight($iHeight=0, $limit=10, $iUser) {
    $stmt = $this->mysqli->prepare("
      SELECT
        b.height, b.shares
        FROM $this->tableBlocks AS b
        LEFT JOIN $this->tableStats AS s ON s.block_id = b.id
        LEFT JOIN $this->tableUsers AS a ON a.id = s.account_id 
      WHERE b.height <= ? AND a.id = ?
      ORDER BY height DESC LIMIT ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('iii', $iHeight, $iUser, $limit) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_all(MYSQLI_ASSOC);
    return false;
  }

  /**
   * Get shares for block height for user admin panel
   **/
  public function getRoundStatsForUser($iHeight=0, $iUser) {
    $stmt = $this->mysqli->prepare("
      SELECT
        s.valid,
        s.invalid,
        s.pplns_valid,
        s.pplns_invalid
        FROM $this->tableStats AS s
        LEFT JOIN $this->tableBlocks AS b ON s.block_id = b.id
        LEFT JOIN $this->tableUsers AS a ON a.id = s.account_id
        WHERE b.height = ? AND a.id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $iHeight, $iUser) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_assoc();
    return false;
  }

  /**
   * Get credit transactions for round block height for admin panel
   **/
  public function getUserRoundTransHeight($iHeight=0, $iUser) {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("
      SELECT
      IFNULL(t.amount, 0) AS amount
      FROM $this->tableTrans AS t
      LEFT JOIN $this->tableBlocks AS b ON t.block_id = b.id
      LEFT JOIN $this->tableUsers AS a ON t.account_id = a.id
      WHERE b.height = ? AND t.type = 'Credit' AND t.account_id = ?");
    if ($this->checkStmt($stmt) && $stmt->bind_param('ii', $iHeight, $iUser) && $stmt->execute() && $result = $stmt->get_result())
      return $result->fetch_object()->amount;
    $this->debug->append('Unable to fetch transactions');
    return false;
  }

  /**
   * Get all users for admin panel
   **/
  public function getAllUsers($filter='%') {
    $this->debug->append("STA " . __METHOD__, 4);
    $stmt = $this->mysqli->prepare("
      SELECT
        a.id AS id,
        a.username AS username
      FROM $this->tableUsers AS a
      WHERE a.username LIKE ?
      GROUP BY username
      ORDER BY username");
    if ($this->checkStmt($stmt) && $stmt->bind_param('s', $filter) && $stmt->execute() && $result = $stmt->get_result()) {
      while ($row = $result->fetch_assoc()) {
        $aData[$row['id']] = $row['username'];
      }
      return $aData;
    }
    return false;
  }

  private function checkStmt($bState) {
    if ($bState ===! true) {
      $this->debug->append("Failed to prepare statement: " . $this->mysqli->error);
      $this->setErrorMessage('Internal application Error');
      return false;
    }
    return true;
  }

}

$roundstats = new RoundStats($debug, $mysqli, $config);
