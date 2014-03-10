<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Codeigniter Message:: A Library for passing messages between pages
 * @author Adebola Olowofela
 * @copyright 2012
 * @version 1.0
 */
class Message
{
    /**
     * Codeigniter Instance
     * @var
     */
    var $CI;

    /**
     * This variable stores the messages to be displayed
     * @var array
     */
    var $aMessages = array();

    /**
     * Array of wrapper tags for the message display
     * @var array
     */
    var $aMessageWrapper        = array('<div class=\'wrapper\'>', '</div>');

    /**
     * Array of wrapper tags for the display of each item
     * @var array
     */
    var $aMessageItemWrapper    = array('<p>','</p>');

    /**
     * This constant determines the key for which the sessions are stored in the session object
     * const SESS_FLASH_MESSAGE_KEY
     */
    const SESS_FLASH_MESSAGE_KEY = "_CI_Message";

    /**
     * This constant specifies the config key item for the wrapper
     * @const CONFIG_WRAPPER_ITEM
     */
    const CONFIG_WRAPPER_ITEM     = "wrapper";

    /**
     * This constant specifies the default operation for setting message as a flash message
     * @const int
     */
    const  DEFAULT_MESSAGE_FLASH  = FALSE;

    /**
     * This represents the wrapper for the displayed message
     * @const int WRAPPER_TYPE_CONTAINER
     */
    const WRAPPER_TYPE_CONTAINER  = 0;

    /**
     * This represents the wrapper for the message item in the wrapper
     * @const int WRAPPER_TYPE_ITEM
     */
    const WRAPPER_TYPE_ITEM       = 1;

    /**
     * constructor
     */
    function __construct()
    {
        // load the CI object
        $this->CI =& get_instance();
        // load the session class
        $this->_loadSession();
        // load message configuration
        $this->_loadConfig();
    }

    /**
     * This method loads the session class if it has not been loaded
     * @access private
     * @return void
     * @author Adebola
     */
    private function _loadSession()
    {
        // load session if not loaded
        if(!class_exists('CI_Session'))
        {
            $this->CI->load->library("Session");
        }
    }

    /**
     * This method loads the configuration details stored in the config file
     * @access private
     * @return void
     * @author Adebola
     */
    private function _loadConfig()
    {
        // load the config file
        // $this->CI->load->config("messages");

        // store messages if exists
        if($this->CI->session->flashdata(self::SESS_FLASH_MESSAGE_KEY))
            $this->aMessages = $this->CI->session->flashdata(self::SESS_FLASH_MESSAGE_KEY);

        // load the wrapper from the config
        if($this->CI->config->item(self::CONFIG_WRAPPER_ITEM))
            $this->aMessageWrapper = $this->CI->config->item(self::CONFIG_WRAPPER_ITEM);
    }

    /**
     * This method would be used to set a message
     * @param array $aMessage
     * @param string $sType
     * @param bool $bFlash
     * @param bool $bGroup
     * @return Message
     */
    public function set($aMessage = array(), $sType = "", $bFlash = self::DEFAULT_MESSAGE_FLASH, $bGroup = FALSE)
    {
        if(!is_array($aMessage) && is_string($aMessage))
            $aMessage = array($aMessage);

        // load the message
        $this->_loadMessages($aMessage, $sType, $bFlash, $bGroup);
        
        return $this;
    }


    /**
     * This method loads the array of messages into the class object
     * @param array $aMessages
     * @param string $sType
     * @param bool $bFlash
     * @param bool $bGroup
     * @author Adebola
     * @return void
     */
    private function _loadMessages($aMessages = array(), $sType= "", $bFlash = self::DEFAULT_MESSAGE_FLASH, $bGroup = FALSE)
    {
        if(!empty($aMessages) && is_array($aMessages))
        {
            foreach($aMessages as $iKey=>$sMsg)
            {
                // create new message object
                $oMessage = new stdClass();
                $oMessage->sMessage     = $sMsg;
                $oMessage->sType        = $sType;
                $oMessage->sFlash       = $bFlash;
                $oMessage->sGroup       = $bGroup;

                $this->aMessages[] = $oMessage;

                unset($oMessage);
            }
        }
    }


    /**
     * This method sorts the messages from the message pool and it is returned to be displayed
     * @param bool $bType, the type of the message to fetch
     * @return array
     */
    private function _fetchMessage($bType = FALSE)
    {
        $aReturn = array();
        if(!empty($this->aMessages) && (!$bType))
        {
            $aReturn = $this->aMessages;
        }
        elseif($bType === TRUE)
        {
            foreach($this->aMessages as $iKey=>$oMsg)
            {
                if(!empty($oMsg->sType))
                {
                    if(!isset($aReturn[$oMsg->sType]))
                        $aReturn[$oMsg->sType] = array();

                    $aReturn[$oMsg->sType][] = $oMsg;
                }
            }
        }
        return $aReturn;
    }

    /**
     * This method gets the start or ending part of a wrapper for the container or message item
     * @param int $iType
     * @param bool $bStart
     * @return string
     */
    private function _fetchWrapper($iType = self::WRAPPER_TYPE_CONTAINER, $bStart = TRUE)
    {
        $sTag = "";
        $aWrapper = array();
        switch ($iType)
        {
            case self::WRAPPER_TYPE_CONTAINER:
                $aWrapper = $this->aMessageWrapper;
            break;

            case self::WRAPPER_TYPE_ITEM:
                $aWrapper = $this->aMessageItemWrapper;
            break;

            default:
                $aWrapper = array();
            break;
        }
        if(is_array($aWrapper) && !empty($aWrapper))
        {
            if($bStart && isset($aWrapper[0]))
                $sTag = $aWrapper[0];
            elseif (!$bStart && isset($aWrapper[1]))
                $sTag = $aWrapper[1];
            else
                $sTag = "";
        }

        return $sTag . PHP_EOL;
    }


    /**
     * This method displays the messages that has been previously set
     * @param bool $bGroup
     * @param bool $bWrapMessage
     */
    public function display($bGroup = FALSE, $bWrapMessage = TRUE)
    {
        // get the message
        if(!empty($this->aMessages))
        {
            if($bWrapMessage) echo $this->_fetchWrapper(self::WRAPPER_TYPE_CONTAINER, TRUE);
            $aDisplayMessages = $this->_fetchMessage($bGroup);

            // display the messages in the pattern inserted
            if(!$bGroup)
                $this->_displayMessage($aDisplayMessages);
            else
                // display the messages while grouped together
                foreach($aDisplayMessages as $sKey=>$aMessages)
                {
                    $this->_displayMessage($aMessages);
                }

            if($bWrapMessage) echo $this->_fetchWrapper(self::WRAPPER_TYPE_CONTAINER, FALSE);

        }
    }


    /**
     * This method displays the message grouped in an array
     * @param array $aDisplayMessages
     */
    protected function _displayMessage($aDisplayMessages = array())
    {
        foreach($aDisplayMessages as $iKey=>$oMessage)
        {
            echo $this->_fetchWrapper(self::WRAPPER_TYPE_ITEM, TRUE);
            echo $oMessage->sMessage;
            echo $this->_fetchWrapper(self::WRAPPER_TYPE_ITEM, FALSE);
        }
    }



}
