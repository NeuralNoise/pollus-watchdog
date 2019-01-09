<?php declare(strict_types=1);

use Pollus\Watchdog\Watchdog;
use Pollus\Watchdog\Adapters\DatabaseAdapter;
use Pollus\HttpClientFingerprint\HttpClientFingerprint;

class WatchdogSingleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;
    
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * @var Watchdog
     */
    protected $watchdog;
    
    protected function setUp()
    {
        require_once __DIR__."/Helpers/Connection.php";
        $this->pdo = Connection::get();
        $this->adapter = new DatabaseAdapter($this->pdo, "watchdog_logs");
        $this->watchdog = new Watchdog("default", $this->adapter, new HttpClientFingerprint(), [
            "suspect_counter" => 5,
            "ban_enabled" => true,
            "ban_counter" => 10,
            "ban_time" => 10,
            "find_time" => 15,
            "ip_lookup" => true,
            "session_lookup" => true,
        ]);
    }
    
    public function testWatchdogLog()
    {
        $this->watchdog->clearLog();
        $this->watchdog->log();
        $this->watchdog->log();
        $this->watchdog->log();
        $this->assertSame(3, $this->adapter->query("default", "log", "127.0.0.1", null, 10));
        $this->assertSame(false, $this->watchdog->isSuspect());
        $this->assertSame(false, $this->watchdog->isBanned());
        
        $this->watchdog->log();
        $this->watchdog->log();
        $this->assertSame(5, $this->adapter->query("default", "log", "127.0.0.1", null, 10));
        $this->assertSame(true, $this->watchdog->isSuspect());
        $this->assertSame(false, $this->watchdog->isBanned());
        
        $this->watchdog->log();
        $this->watchdog->log();
        $this->watchdog->log();
        $this->watchdog->log();
        $this->watchdog->log();
        $this->assertSame(10, $this->adapter->query("default", "log", "127.0.0.1", null, 10));
        $this->assertSame(true, $this->watchdog->isSuspect());
        $this->assertSame(true, $this->watchdog->isBanned());
        
    }
}
