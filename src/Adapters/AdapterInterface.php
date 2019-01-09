<?php declare(strict_types=1);

/**
 * Pollus Watchdog
 * @license https://opensource.org/licenses/MIT MIT
 * @author Renan Cavalieri <renan@tecdicas.com>
 */

namespace Pollus\Watchdog\Adapters;

/**
 * Adapter Interface
 */
interface AdapterInterface
{
    /**
     * Query the database and returns the number of matches
     * 
     * @param string $action
     * @param string $type
     * @param string|null $ipaddress
     * @param string|null $sessionid
     * @param int $findtime
     * @return int
     */
    public function query(string $action, string $type, ?string $ipaddress, ?string $sessionid, int $findtime) : int;
    
    /**
     * Logs the action to database
     * 
     * @param string $action
     * @param string $type
     * @param string|null $ipaddress
     * @param string|null $sessionid
     * @return void
     */
    public function log(string $action, string $type, ?string $ipaddress, ?string $sessionid): void;
    
    /**
     * Erases the log
     * 
     * @param string $action
     */
    public function clearLog(string $action);
}
