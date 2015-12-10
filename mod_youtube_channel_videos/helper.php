<?php
/**
 * @copyright      Copyright (C) 2015 Nikita «Arttse» Bystrov. All rights reserved.
 * @license        License GNU General Public License version 3
 * @author         Nikita «Arttse» Bystrov
 */

defined ( '_JEXEC' ) or die;

class modYoutubeChannelVideosHelper {

    /**
     * Version of YouTube API
     */
    const API_VER = '3';

    /**
     * Application API Key
     */
    const API_KEY = 'AIzaSyCOAFsd2gkPaZDo_ib3-v0sOC6yFSZGPNk';

    /**
     * All params of module
     *
     * @var object
     */
    public $params;

    /**
     * Data of module
     *
     * @var object
     */
    public $module;

    /**
     * Channel URL
     *
     * @var string
     */
    public $channel_url;

    /**
     * Maximum number of items that should be returned per request
     *
     * @var int
     */
    public $video_count;

    /**
     * Show Module Errors
     *
     * @var bool
     */
    public $module_errors;

    /**
     * Channel ID
     *
     * @var string
     */
    public $channel_id;

    /**
     * User name of channel
     *
     * @var string
     */
    public $channel_username;


    /**
     * Initialization.
     *
     * @param $module - data module
     * @param $params - module params
     */
    function __construct ( $module, $params )
    {
        $this->module = $module;
        $this->params = $params;

        $this->channel_url = trim ( (string)$params->get ( 'channel_url' ) );
        $this->video_count = (int)$params->get ( 'video_count', 3 );

        $this->module_errors = (bool)$params->get ( 'module_errors', true );

        $this->parseChannelUrl ( $this->channel_url );
        $this->addChannelID ();

    }


    /**
     * Get YouTube Videos from Channel
     *
     * @return null|array - items
     */
    function getItems ()
    {
        if ( empty( $this->channel_id ) )
        {
            return null;
        }

        $channelList = $this->channelsList ( [ 'id' => $this->channel_id ] );
        $uploads = isset( $channelList->items[0]->contentDetails->relatedPlaylists->uploads ) ? $channelList->items[0]->contentDetails->relatedPlaylists->uploads : null;

        if ( is_object ( $channelList ) AND $this->isResponseError ( $channelList ) AND empty( $uploads ) )
        {
            return null;
        }

        $playlistItems = $this->playlistItemsList (
            [
                'playlistId' => $uploads,
                'maxResults' => $this->video_count
            ]
        );
        $items = $playlistItems->items;

        if ( is_object ( $playlistItems ) AND $this->isResponseError ( $playlistItems ) AND empty( $items ) )
        {
            return null;
        }

        return $items;
    }


    /***********************************************************************************************************
     * Requests
     */

    /**
     * Sends request and receive response
     *
     * @param string    $method        - method name of API request
     * @param array     $method_params - params request
     * @param bool|true $json_decode   - producing json decoding data
     *
     * @return null|string - response
     */
    public function request ( $method, $method_params, $json_decode = true )
    {
        if ( !$this->_isCurlExists () )
        {
            return null;
        }

        $url_request = self::apiUrl ( $method, $method_params );

        return $json_decode ? json_decode ( $this->_сurl ( $url_request ) ) : $this->_сurl ( $url_request );
    }

    /**
     * Use method «youtube.channels.list»
     * https://developers.google.com/youtube/v3/docs/channels/list
     *
     * @param array $method_params_your - your method params
     *
     * @return object - response
     */
    public function channelsList ( $method_params_your = [ ] )
    {
        $method = 'channels';
        $method_params_default = [
            'part' => 'contentDetails',
            'key'  => self::API_KEY
        ];

        $method_params = array_merge ( $method_params_default, $method_params_your );

        return $this->request ( $method, $method_params );
    }

    /**
     * Use method «youtube.playlistItems.list»
     * https://developers.google.com/youtube/v3/docs/playlistItems/list
     *
     * @param array $method_params_your - your method params
     *
     * @return object - response
     */
    public function playlistItemsList ( $method_params_your = [ ] )
    {
        $method = 'playlistItems';
        $method_params_default = [
            'part' => 'snippet',
            'key'  => self::API_KEY
        ];

        $method_params = array_merge ( $method_params_default, $method_params_your );

        return $this->request ( $method, $method_params );
    }

    /**
     * END Requests
     ***********************************************************************************************************/


    /***********************************************************************************************************
     * Utils
     */

    /**
     * Parse Channel URL
     * and puts it in variables «channel_id» and/not «channel_username»
     *
     * @param string $url - channel URL
     *
     * return void
     */
    public function parseChannelUrl ( $url )
    {
        if ( preg_match ( '#/(user|channel)/(.+[^\/])#i', $url, $m ) )
        {
            $this->channel_id = null;
            $this->channel_username = null;

            if ( $m[1] == 'channel' )
            {
                $this->channel_id = $m[2];
            }
            elseif ( $m[1] == 'user' )
            {
                $this->channel_username = $m[2];
            }
        }
        else
        {
            $this->_addError ( JText::_ ( 'MOD_YOUTUBE_CHANNEL_VIDEOS_ERROR_URL_CHANNEL_INCORRECT' ) );
        }
    }

    /**
     * Adds channel ID from Username
     *
     * @return void
     */
    public function addChannelID ()
    {
        if ( empty( $this->channel_id ) AND $this->channel_username )
        {
            $req = $this->channelsList (
                [
                    'part'        => 'id',
                    'forUsername' => $this->channel_username
                ]
            );

            if ( is_object ( $req ) AND !$this->isResponseError ( $req ) )
            {
                $this->channel_id = $req->items[0]->id;
            }
        }
    }

    /**
     * Return URL for request
     *
     * @param string       $method - method name of API request
     * @param array|object $params - params of request
     *
     * @return string - url for request
     */
    public static function apiUrl ( $method, $params )
    {
        return 'https://www.googleapis.com/youtube/v' . self::API_VER . '/' . $method . '?' . http_build_query ( $params );
    }

    /**
     * Check if YouTube returns error
     *
     * @param object $response - response object
     *
     * @return bool - true if returns error
     */
    public function isResponseError ( $response )
    {
        if ( isset( $response->error ) )
        {
            $this->_addError ( JText::sprintf ( 'MOD_YOUTUBE_CHANNEL_VIDEOS_ERROR_RESPONSE', $response->error->code, $response->error->message ) );

            return true;
        }
        elseif ( $response->kind == 'youtube#channelListResponse' AND $response->pageInfo->totalResults == 0 )
        {
            $this->_addError ( JText::_ ( 'MOD_YOUTUBE_CHANNEL_VIDEOS_ERROR_URL_CHANNEL_INCORRECT' ) );

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Receive data using cURL
     *
     * @param string $url - url to get the data
     *
     * @return string - received data
     */
    private function _сurl ( $url )
    {
        /**
         * Initialize a cURL session
         */
        $ch = curl_init ( $url );

        /**
         * Set an option for a cURL transfer
         * http://php.net/manual/en/function.curl-setopt.php
         */
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 300 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );

        if ( curl_exec ( $ch ) === false )
        {
            $this->_addError ( JText::sprintf ( 'MOD_YOUTUBE_CHANNEL_VIDEOS_ERROR_CURL', curl_error ( $ch ) ) );
        }

        /**
         * Perform a cURL session.
         * It stores the received data
         */
        $data = curl_exec ( $ch );

        /**
         * Close a cURL session
         */
        curl_close ( $ch );

        return $data;
    }

    /**
     * Check library installed
     *
     * @param $name_ext - name of library
     *
     * @return bool
     */
    private function _isExtInstalled ( $name_ext )
    {
        if ( in_array ( $name_ext, get_loaded_extensions () ) )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Check cURL library installed
     *
     * @return bool
     */
    private function _isCurlExists ()
    {
        if ( $this->_isExtInstalled ( 'curl' ) )
        {
            return true;
        }
        else
        {
            $this->_addError ( JText::sprintf ( 'MOD_YOUTUBE_CHANNEL_VIDEOS_ERROR_CURL_NOT_INSTALLED', $this->module->id ) );

            return false;
        }
    }

    /**
     * Show Error in client side
     *
     * @param $error_text - text with error
     */
    private function _addError ( $error_text )
    {
        if ( $this->module_errors )
        {
            JLog::add ( $error_text, JLog::ERROR );
        }
    }

    /**
     * END Utils
     ***********************************************************************************************************/
}