<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pollus\Watchdog\Adapters\DatabaseAdapter;
use Pollus\Watchdog\Exceptions\WatchdogException;

final class AdapterTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;
    
    /**
     * @var DatabaseAdapter
     */
    protected $adapter;
    
    
    protected function setUp()
    {
        require_once __DIR__."/Helpers/Connection.php";
        $this->pdo = Connection::get();
        $this->adapter = new DatabaseAdapter($this->pdo, "watchdog_logs");
    }

    public function testAdapterIpAddressLog()
    {
        $this->adapter->log("default", "log", "127.0.0.1", null);
        $stmt = $this->pdo->prepare("SELECT * FROM watchdog_logs WHERE ip_address = :ip");
        $stmt->bindValue("ip", "127.0.0.1");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame(count($result), 1);
        $this->assertSame($result[0]["ip_address"], "127.0.0.1");
        $this->adapter->clearLog("default");
    }
    
    /**
     * @depends testAdapterIpAddressLog
     */
    public function testAdapterSessionLog()
    {
        $this->adapter->log("default", "log", null, "1827465968329385069283059683");
        $stmt = $this->pdo->prepare("SELECT * FROM watchdog_logs WHERE session_id = :id");
        $stmt->bindValue("id", "1827465968329385069283059683");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertSame(count($result), 1);
        $this->assertSame($result[0]["session_id"], "1827465968329385069283059683");
        $this->adapter->clearLog("default");
    }
    
    /**
     * @depends testAdapterSessionLog
     */
    public function testAdapterLogException()
    {
        $this->expectException(WatchdogException::class);
        $this->adapter->log("default", "log", null, null);
        $this->adapter->clearLog("default");
    }
    
    /**
     * @depends testAdapterLogException
     */
    public function testAdapterQueryIpAddress()
    {
        $this->adapter->log('default', 'log', '127.0.0.1', null);
        $this->adapter->log('default', 'log', '127.0.0.1', null);
        $this->adapter->log('default', 'log', '127.0.0.1', null);
        $this->adapter->log('default', 'log', '127.0.0.1', null);
        $this->adapter->log('default', 'log', '127.0.0.1', null);
        $this->adapter->log('default', 'log', '255.255.255.255', null);
        $result = $this->adapter->query('default', 'log', '127.0.0.1', null, 10);
        $this->assertSame(5, $result);
        
        $this->adapter->log('default', 'ban', '127.0.0.1', null);
        $this->adapter->log('default', 'ban', '127.0.0.1', null);
        $this->adapter->log('default', 'ban', '127.0.0.1', null);
        $this->adapter->log('default', 'ban', '127.0.0.1', null);
        $result = $this->adapter->query('default', 'ban', '127.0.0.1', null, 10);
        $this->assertSame(4, $result);
        
        $this->adapter->log('login', 'ban', '127.0.0.1', null);
        $this->adapter->log('login', 'ban', '127.0.0.1', null);
        $this->adapter->log('login', 'ban', '127.0.0.1', null);
        $result = $this->adapter->query('login', 'ban', '127.0.0.1', null, 10);
        $this->assertSame(3, $result);
    }
    
    /**
     * @depends testAdapterQueryIpAddress
     */
    public function testAdapterQuerySession()
    {
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "010101010101");
        $result = $this->adapter->query('default', 'log', null, "192830192380129381920", 10);
        $this->assertSame(5, $result);
        
        $this->adapter->log('default', 'ban', null, "192830192380129381920");
        $this->adapter->log('default', 'ban', null, "192830192380129381920");
        $this->adapter->log('default', 'ban', null, "192830192380129381920");
        $this->adapter->log('default', 'ban', null, "192830192380129381920");
        $result = $this->adapter->query('default', 'ban', null, "192830192380129381920", 10);
        $this->assertSame(4, $result);
        
        $this->adapter->log('login', 'ban', null, "192830192380129381920");
        $this->adapter->log('login', 'ban', null, "192830192380129381920");
        $this->adapter->log('login', 'ban', null, "192830192380129381920");
        $result = $this->adapter->query('login', 'ban', null, "192830192380129381920", 10);
        $this->assertSame(3, $result);
    }
    
    /**
     * @depends testAdapterQuerySession
     */
    public function testAdapterTime()
    {
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "192830192380129381920");
        $this->adapter->log('default', 'log', null, "010101010101");
        sleep(3);
        $result = $this->adapter->query('default', 'log', null, "192830192380129381920", 0);
        $this->assertSame(0, $result);
    }
}