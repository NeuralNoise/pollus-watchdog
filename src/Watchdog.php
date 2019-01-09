<?php declare(strict_types=1);

/**
 * Pollus Watchdog
 * @license https://opensource.org/licenses/MIT MIT
 * @author Renan Cavalieri <renan@tecdicas.com>
 */

namespace Pollus\Watchdog;

use Pollus\HttpClientFingerprint\HttpClientFingerprintInterface;
use Pollus\Watchdog\Adapters\AdapterInterface;
use Pollus\Watchdog\Exceptions\WatchdogException;

/**
 * Simple bruteforce and feature abuse detection based on IP Address and/or Session
 * ID.  
 */
class Watchdog
{
    /**
     * @var CaptchaInterface
     */
    protected $captcha;
    
    /**
     * @var array
     */
    protected $settings;
    
    /**
     * @var HttpClientFingerprintInterface
     */
    protected $fingerprint;
    
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * @var string
     */
    protected $action;
    
    /**
     * @param string $action - Name of the action that this Watchdog will look up and log
     * @param AdapterInterface $adapter - Adapter to database
     * @param HttpClientFingerprintInterface $fingerprint - Gets the IPAddress and Session ID
     * @param array $settings - Custom settings
     * @throws WatchdogException
     */
    public function __construct(string $action, AdapterInterface $adapter, HttpClientFingerprintInterface $fingerprint, array $settings = [])
    {
        $this->fingerprint = $fingerprint;
        $this->adapter = $adapter;
        $this->action = $action;
       
        $defaults = 
        [
            // Sets the number of actions that will be tolerated within the 
            // analysis window before flagging as "suspect"
            "suspect_counter" => 5,

            // Enables ban
            "ban_enabled" => true,
            
            // sets the number of actions that will be tolerated within the 
            // analysis window before a ban is instituted.
            "ban_counter" => 20,

            // Ban duration (in minutes)
            "ban_time" => 10,

            // Analysis window (in minutes)
            "find_time" => 15,

            // Enables the IP lookup
            //
            // Useful to detect bruteforce on a login form in web applications
            // that are visible on the internet.
            "ip_lookup" => true,

            // Enables the Session ID lookup
            // 
            // Useful only when you want to prevent a authenticated user from 
            // abuse some functionality. It's pointless use session ID lookup to 
            // prevent bruteforce on a login form.
            // login form.
            "session_lookup" => true,
        ];
       
        $this->settings = array_merge($defaults, $settings);
       
        if ($this->settings["ip_lookup"] === false && $this->settings["session_lookup"] === false)
        {
            throw new WatchdogException("No lookup method was specified");
        }
    }
    
    /**
     * Checks if a ban was given to the current IP Address and/or Session ID
     * 
     * If TRUE, is strongly recommended prevent the requested action from being 
     * completed.
     * 
     * @return bool
     */
    public function isBanned() : bool
    {
        return ($this->settings["ban_enabled"] && $this->query("ban") >= 1);
    }
    
    /**
     * Checks if the current IP Address and/or Session ID have exceeded the 
     * value specified in "suspect_counter".
     * 
     * If TRUE, is highly recommended to ask the user to solve a captcha.
     * 
     * @return bool
     */
    public function isSuspect() : bool
    {
        return ($this->query("log") >= $this->settings["suspect_counter"]);
    }
    
    /**
     * Logs to database
     * 
     * This method should be called every time a feature protected from being 
     * abused is used or when something goes wrong, like a invalid password on 
     * a login form.
     * 
     * If a ban was given, calling this method will reset the ban time.
     * 
     * @throws WatchdogException
     */
    public function log()
    {
        $ipaddress = null;
        $sessionid = null;
        try
        {
            if ($this->settings["ip_lookup"] === true)
                $ipaddress = $this->fingerprint->getIpAddress()->toString();

            if (session_status() === PHP_SESSION_ACTIVE && $this->settings["session_lookup"] === true)
                $session_id = $this->fingerprint->getSessionId();
        } 
        catch (\Exception $ex) 
        {
            throw new WatchdogException($ex->getMessage());
        }
        $this->adapter->log($this->action, "log", $ipaddress, $sessionid);
        if ($this->query("log") >= $this->settings["ban_counter"] && $this->settings["ban_enabled"] === true)
        {
            $this->adapter->log($this->action, "ban", $ipaddress, $sessionid);
        }
    }
    
    /**
     * Erases the log for the current action
     */
    public function clearLog()
    {
        $this->adapter->clearLog($this->action);
    }

    /**
     * Executes the query
     * 
     * @param string $type
     * @return int
     * @throws WatchdogException
     */
    protected function query(string $type) : int
    {
        $ipaddress = null;
        $sessionid = null;
        
        try
        {
            if ($this->settings["ip_lookup"] === true)
                $ipaddress = $this->fingerprint->getIpAddress()->toString();

            if (session_status() === PHP_SESSION_ACTIVE && $this->settings["session_lookup"] === true)
                $sessionid = $this->fingerprint->getSessionId();
            
            $time = ($this->action === "ban") ? 
                    $this->settings["ban_time"] : $this->settings["find_time"];
        } 
        catch (\Exception $ex) 
        {
            throw new WatchdogException($ex->getMessage());
        }

        return $this->adapter->query($this->action, $type, $ipaddress, $sessionid, $time);
    }
}
