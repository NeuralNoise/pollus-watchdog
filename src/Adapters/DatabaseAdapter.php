<?php declare(strict_types=1);

/**
 * Pollus Watchdog
 * @license https://opensource.org/licenses/MIT MIT
 * @author Renan Cavalieri <renan@tecdicas.com>
 */

namespace Pollus\Watchdog\Adapters;

use \PDO;
use Pollus\Watchdog\Exceptions\WatchdogException;

/**
 * Database SQL Adapter
 */
class DatabaseAdapter implements AdapterInterface
{
    /**
     * @var PDO
     */
    protected $pdo;
    
    /**
     * @var string
     */
    protected $table;
    
    /**
     * @param PDO $pdo
     * @param string $table
     */
    public function __construct(PDO $pdo, string $table = "watchdog_dogs")
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }
    
    /**
     * {@inheritDoc}
     */
    public function log(string $action, string $type, ?string $ipaddress, ?string $sessionid): void
    {
        if ($ipaddress === null && $sessionid === null)
            throw new WatchdogException("No lookup method was specified");
        
        $sql = "INSERT INTO {$this->table} (action, type, ip_address, session_id, timestamp) "
        . "VALUES (:action, :type, :ip_address, :session_id, :timestamp)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue("action", $action);
        $stmt->bindValue("type", $type);
        $stmt->bindValue("ip_address", $ipaddress);
        $stmt->bindValue("session_id", $sessionid);
        $stmt->bindValue("timestamp", date('Y-m-d H:i:s'));
        $stmt->execute();
    }
    
    /**
     * {@inheritDoc}
     */
    public function query(string $action, string $type, ?string $ipaddress, ?string $sessionid, int $findtime) : int
    {
        $sql = "SELECT COUNT(id) FROM {$this->table} "
        . "WHERE timestamp >= :timestamp "
        . "AND action = :action "
        . "AND type = :type "
        . "AND (%cond%)";
        
        $cond_sql = "";
        
        if ($ipaddress === null && $sessionid === null)
            throw new WatchdogException("No lookup method was specified");
        
        if ($ipaddress !== null)
            $cond_sql .= "ip_address = :ipaddress";
        
        if ($ipaddress !== null && $sessionid !== null)
            $cond_sql .= " OR ";
        
        if ($sessionid !== null)
            $cond_sql .= "session_id = :sessionid";
        
        $sql = str_replace("%cond%", $cond_sql, $sql);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue("action", $action);
        $stmt->bindValue("timestamp", $this->dateSubMinutes($findtime));
        $stmt->bindValue("type", $type);
        
        if ($ipaddress !== null)
            $stmt->bindValue("ipaddress", $ipaddress);
        
        if ($sessionid !== null)
            $stmt->bindValue("sessionid", $sessionid);
        
        
        $stmt->execute();
        return (int) $stmt->fetchColumn(0);
    }
    
    /**
     * {@inheritDoc}
     */
    public function clearLog(string $action)
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE action = :action");
        $stmt->bindValue("action", $action);
        $stmt->execute();
    }
    
    /**
     * Subtract minutes from a given date
     * 
     * @param int $minutes
     * @return string
     */
    protected function dateSubMinutes(int $minutes) : string
    {
        return date("Y-m-d H:i:s", strtotime(date('Y-m-d H:i:s')) - (60 * $minutes));
    }

    

}
