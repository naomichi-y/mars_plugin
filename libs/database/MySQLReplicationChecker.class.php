<?php
/**
 * MySQLReplicationChecker.
 *
 * @package database
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 *
 * 04/27/07 yamakita ReplicationStatusAction のロジックをユーティリティ化
 * 09/05/07 yamakita スレーブ遅延を検知するよう変更
 */
class MySQLReplicationChecker
{
  const REPLICATION_STATUS_ENABLE = 1;
  const REPLICATION_STATUS_DISABLE = 0;
  const DELAY_ALLOWANCE_TIME = 300;

  private $_masterStatus;
  private $_slaveStatus;

  private $_databaseLogDir;
  private $_masterStatusFile;
  private $_slaveStatusFile;
  private $_replicationStatusFile;

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct()
  {
    $this->checkDatabaseLogDirectory();
    $this->_databaseLogDir = APP_ROOT_DIR . '/logs/database';
    $this->setStatusFiles();

  }

  private function checkDatabaseLogDirectory()
  {
    if (!is_dir($this->_databaseLogDir)) {
      create_dir($this->_databaseLogDir);
    }
  }

  private function setStatusFiles()
  {
    $this->_masterStatusFile = $this->_databaseLogDir . '/master_status.txt';
    $this->_slaveStatusFile = $this->_databaseLogDir . '/slave_status.txt';
    $this->_replicationStatusFile = $this->_databaseLogDir . '/replication_status.txt';
  }

  /**
   * マスタ DB にて "SHOW MASTER STATUS" を実行し、結果を "logs/database/master_status.txt" に保存します。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function storeMasterStatus()
  {
    $buffer = 'last update,' . date('Y/m/d H:i:s') . "\n";

    $stmt = Mars_PDOManager::getConnection()->query('SHOW MASTER STATUS');

    $rs = array();
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);

    $masterStatus = array();

    foreach ($rs as $key => $value) {
      $masterStatus[$key] = $value;
      $buffer .= $key . ',' . $value . "\n";
    }

    file_put_contents($this->_masterStatusFile, $buffer);

    return $masterStatus;
  }

  /**
   * マスタ DB にて "SHOW SLAVE STATUS" を実行し、結果を "logs/database/slave_status.txt" に保存します。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  private function storeSlaveStatus()
  {
    $buffer = 'last update,' . date('Y/m/d H:i:s') . "\n";
    $stmt = Mars_PDOManager::getConnection('slave')->query('SHOW SLAVE STATUS');

    $rs = array();
    $rs = $stmt->fetch(PDO::FETCH_ASSOC);

    if (is_array($rs)) {
      $slaveStatus = array();

      foreach ($rs as $key => $value) {
        $slaveStatus[$key] = $value;
        $buffer .= $key . ',' . $value . "\n";
      }

      file_put_contents($this->_slaveStatusFile, $buffer);

      return $slaveStatus;

     } else {
       throw new Mars_ConnectException('Failed connect to slave');
     }
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getReplicationStatus()
  {
    $replication = Mars_Config::loadProperties('database.replication');

    return $replication;
  }

  /**
   * スレーブの状態をチェックし、遅延が発生しているようであれば管理者宛にメール通知を行います。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function checkConsistency($getFile = FALSE)
  {
    if ($getFile) {
      $this->_masterStatus = $masterStatus = $this->storeMasterStatus();
      $this->_slaveStatus = $slaveStatus = $this->storeSlaveStatus();

      $masterFile = $masterStatus['File'];
      $masterPosition = $masterStatus['Position'];

      $slaveFile = $slaveStatus['Master_Log_File'];
      $slavePosition = $slaveStatus['Exec_Master_Log_Pos'];
      $slaveIoRunning = $slaveStatus['Slave_IO_Running'];
      $slaveSqlRunning = $slaveStatus['Slave_SQL_Running'];

    } else {
      $filePath = $this->_masterStatusFile;

      if (is_file($filePath)) {
        $fp = fopen($filePath, 'r');
        $masterStatus = array();

        while (($values = fgetcsv($fp, 512, ',')) !== FALSE) {
          $parameter = explode(':', $values[0]);
          $value = trim($values[1]);

          $masterStatus[$parameter[0]] = $value;

          if ($parameter[0] == 'File') {
            $masterFile = $value;
          } else if  ($parameter[0] == 'Position') {
            $masterPosition = $value;
          }
        }

        $this->_masterStatus = $masterStatus;

      } else {
        throw new Mars_ParseException('master_status.txt does not exist');
      }

      $filePath = $this->_slaveStatusFile;

      if (is_file($filePath)) {
        $fp = fopen($filePath, 'r');
        $slaveStatus = array();

        while (($values = fgetcsv($fp, 512, ',')) !== FALSE) {
          $parameter = explode(':', $values[0]);
          $value = trim($values[1]);

          $slaveStatus[$parameter[0]] = $value;

          if ($parameter[0] == 'Master_Log_File') {
            $slaveFile = $value;
          } else if  ($parameter[0] == 'Exec_Master_Log_Pos') {
            $slavePosition = $value;
          } else if  ($parameter[0] == 'Slave_IO_Running') {
            $slaveIoRunning = $value;
          } else if  ($parameter[0] == 'Slave_SQL_Running') {
            $slaveSqlRunning = $value;
          }
        }

        $this->_slaveStatus = $slaveStatus;

      } else {
        throw new Mars_ParseException('slave_status.txt does not exist');
      }
    }

    if ($slaveIoRunning == 'No' or $slaveSqlRunning == 'No') {
      throw new PDOException('Slave is fatal error');

    } else {
      $stmt = ExtendPDOManager::getConnection(ExtendPDOManager::DB_SLAVE, TRUE)->query('SHOW SLAVE STATUS');
      $slaveStatus = $stmt->fetch(PDO::FETCH_ASSOC);

      $delayTime = (int) $slaveStatus['Seconds_Behind_Master'];

      if (self::DELAY_ALLOWANCE_TIME < $delayTime) {
        throw new Mars_Exception('Slave is delayed');
      }
    }

    return TRUE;
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getMasterStatus()
  {
    return $this->_masterStatus;
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function getSlaveStatus()
  {
    return $this->_slaveStatus;
  }
}
