<?php declare(strict_types=1);

use Pollus\Watchdog\Watchdog;
use Pollus\Watchdog\Adapters\DatabaseAdapter;
use Pollus\HttpClientFingerprint\HttpClientFingerprint;

class WatchdogMultipleTest extends \PHPUnit\Framework\TestCase
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
    protected $login_watchdog;
    
    /**
     * @var Watchdog
     */
    protected $form_watchdog;
    
    protected function setUp()
    {
        require_once __DIR__."/Helpers/Connection.php";
        $this->pdo = Connection::get();
        $this->adapter = new DatabaseAdapter($this->pdo, "watchdog_logs");
        
        $this->login_watchdog = new Watchdog("login", $this->adapter, new HttpClientFingerprint(), [
            "suspect_counter" => 3,
            "ban_enabled" => true,
            "ban_counter" => 5,
            "ban_time" => 10,
            "find_time" => 15,
            "ip_lookup" => true,
            "session_lookup" => true,
        ]);
        
        $this->form_watchdog = new Watchdog("form", $this->adapter, new HttpClientFingerprint(), [
            "suspect_counter" => 5,
            "ban_enabled" => false,
            "ban_counter" => 7,
            "ban_time" => 10,
            "find_time" => 15,
            "ip_lookup" => true,
            "session_lookup" => true,
        ]);
    }
    
    public function testWatchdogLog()
    {
        $this->login_watchdog->clearLog();
        $this->form_watchdog->clearLog();
        $this->login_watchdog->log();
        $this->form_watchdog->log();
        $this->assertSame(1, $this->adapter->query("login", "log", "127.0.0.1", null, 10));
        $this->assertSame(false, $this->login_watchdog->isSuspect());
        $this->assertSame(false, $this->login_watchdog->isBanned());
        $this->assertSame(1, $this->adapter->query("form", "log", "127.0.0.1", null, 10));
        $this->assertSame(false, $this->form_watchdog->isSuspect());
        $this->assertSame(false, $this->form_watchdog->isBanned());
        
        $this->login_watchdog->log();
        $this->form_watchdog->log();
        $this->login_watchdog->log();
        $this->form_watchdog->log();
        $this->assertSame(3, $this->adapter->query("login", "log", "127.0.0.1", null, 10));
        $this->assertSame(true, $this->login_watchdog->isSuspect());
        $this->assertSame(false, $this->login_watchdog->isBanned());
        $this->assertSame(3, $this->adapter->query("form", "log", "127.0.0.1", null, 10));
        $this->assertSame(false, $this->form_watchdog->isSuspect());
        $this->assertSame(false, $this->form_watchdog->isBanned());
        
        $this->login_watchdog->log();
        $this->form_watchdog->log();
        $this->login_watchdog->log();
        $this->form_watchdog->log();
        $this->assertSame(5, $this->adapter->query("login", "log", "127.0.0.1", null, 10));
        $this->assertSame(true, $this->login_watchdog->isSuspect());
        $this->assertSame(true, $this->login_watchdog->isBanned());
        $this->assertSame(5, $this->adapter->query("form", "log", "127.0.0.1", null, 10));
        $this->assertSame(true, $this->form_watchdog->isSuspect());
        $this->assertSame(false, $this->form_watchdog->isBanned());
        
        $this->form_watchdog->log();
        $this->form_watchdog->log();
        $this->form_watchdog->log();
        $this->assertSame(8, $this->adapter->query("form", "log", "127.0.0.1", null, 10));
        $this->assertSame(true, $this->form_watchdog->isSuspect());
        $this->assertSame(false, $this->form_watchdog->isBanned()); 
    }
}
