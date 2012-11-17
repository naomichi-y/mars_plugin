<?php
/**
 * MySQLController.
 *
 * @package database
 * @author Naomichi Yamakita <yamakita@dtx.co.jp>
 */
class MySQLController
{
  private $_type;
  private $_tables;
  private $_conn;

  const MYSQL_HOME_DIR = '/home/mysql';
  const MYSQL_BACKUP_DIR = '/home/mysql/dbbackup';
  const MYSQL_ROOT_DIR = '/usr/local/mysql';
  const MYSQL_DATA_DIR = '/usr/local/mysql/data';

  /**
   * @param string $type ExtendDAO::DB_* 定数の指定。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function __construct($type = ExtendDAO::DB_MASTER)
  {
    $this->_type = $type;

    $dao = new ExtendDAO();
    $this->_conn = $dao->getConnection();
    $rec = $this->_conn->query('SHOW TABLE STATUS');

    $this->_tables = $rec->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function isAlive()
  {
    try {
      $this->_conn->query('SELECT NOW() FROM DUAL');

      return TRUE;

    } catch (PDOException $e) {
      return FALSE;
    }
  }

  /**
   * MySQL を停止させます。
   * 停止に失敗した場合は 10 秒間隔で停止を試みますが、300 秒以上経過しても停止しない場合は FALSE を返します。
   *
   * @return bool MySQL の停止に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function stop()
  {
    exec('/etc/rc.d/init.d/mysql stop');

    if ($this->_type == ExtendDAO::DB_SLAVE) {
      sleep(10);
      $response = shell_exec('ps ax|grep mysql');

      $failover = 0;

      while (stripos($response, 'mysqld') !== FALSE) {
        sleep(10);
        $failover++;

        if ($failover == 30) {
          // stop slave しないと killall は危険 (stop slave するには root 権限が必要)
          //exec('killall mysqld');
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * MySQL を起動します。
   * 起動に失敗した場合は 20 秒間隔で再試行を試みますが、600 秒以上経過しても起動しない場合は FALSE を返します。
   *
   * @return bool MySQL の起動に成功した場合は TRUE、失敗した場合は FALSE を返します。
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function start()
  {
    exec('/etc/rc.d/init.d/mysql start');

    if ($this->_type == ExtendDAO::DB_SLAVE) {
      $failover = 0;

      do {
        if ($failover == 30) {
          return FALSE;
        }

        sleep(10);
        exec('/etc/rc.d/init.d/mysql start');
        sleep(10);

        $response = shell_exec('ps ax|grep mysql');

      } while (stripos($response, 'mysqld') === FALSE);
    }

    return TRUE;
  }

  /**
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   */
  public function backup()
  {
    $backupPath = self::MYSQL_BACKUP_DIR;

    if (date('z') % 2 != 0) {
      $backupPath .= '/dbbackup';
    } else {
      $backupPath .= '/dbbackup.2';
    }

    $command = sprintf('rm -rf %s', $backupPath);
    pcntl_setpriority(20);
    exec($command);

    pcntl_setpriority(-20);

    $command = sprintf('cp -Rfp %s %s', self::MYSQL_DATA_DIR, $backupPath);
    exec($command);
  }

  /**
   * テーブルのエラーをチェックし、問題があれば修正します。
   * MySQLController::myisamCheck() を実行するには、MySQL のプロセスがあらかじめ終了している必要があります。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   * @see http://dev.mysql.com/doc/refman/5.1/ja/myisamchk.html
   */
  public function myisamCheck()
  {
    // myisamchk 対象外のテーブル
    $excludeTables = Mars_Config::loadProperties('database.checkExcludeTables');

    $tables = $this->_tables;

    foreach ($tables as $table) {
      if ($table['Engine'] === 'MyISAM' && !in_array($table['Name'], $excludeTables)) {
        $optimizeTables[] = $table['Name'];
      }
    }

    $response = '';
    pcntl_setpriority(20);

    foreach ($optimizeTables as $table) {
      $command = sprintf('%s/bin/myisamchk '
                        .'--fast '
                        .'--check-only-changed '
                        .'--update-state '
                        .'--force '
                        .'--analyze '
                        .'%s/%s/%s.MYI', self::MYSQL_ROOT_DIR, self::MYSQL_DATA_DIR, $dbName, $table);

      $response .= shell_exec($command);
    }

    return $response;
  }

  /**
   * テーブルの未使用領域を解放し、データファイルを最適化します。
   * MySQLController::optimizeTable() を実行するには、MySQL のプロセスがあらかじめ終了している必要があります。
   *
   * @author Naomichi Yamakita <yamakita@dtx.co.jp>
   * @see http://dev.mysql.com/doc/refman/4.1/ja/optimisation.html
   */
  public function optimizeTable()
  {
    // MySQLController::checkTable() で統計を更新するため、ここでは OPTIMIZE TABLE を使用しない
    $command = sprintf('%s/bin/myisamchk -r %s/%s/', self::MYSQL_ROOT_DIR, self::MYSQL_DATA_DIR, $dbName);

    // "Deleted blocks" がおおよそ 100000 以上となるテーブルを対象とする
    $optimizeTables = Mars_Config::loadProperties('database.optimizeTables');

    pcntl_setpriority(20);

    foreach ($optimizeTables as $table) {
      $start = microtime(TRUE);
      shell_exec(sprintf('%s%s.MYI', $command, $table));
      $second = microtime(TRUE) - $start;

      file_put_contents('/home/mysql/isambench', sprintf("%s:%s\n", $table, $second), FILE_APPEND);
    }
  }
}
