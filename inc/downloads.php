<?php

define('MM_DOWNLOAD_QUERY_VAR', 'mm_download');

class Message_Manager_Downloads
{

    /**
     * @var singleton instance of the message manager downloads
     */
    private static $instance;

    /**
     * @return Message_Manager_Downloads instance of the message manager
     */
    public static function get_instance()
    {
        if (empty(static::$instance)) {
            static::$instance = new Message_Manager_Downloads();
        }
        return static::$instance;
    }

    private function __construct()
    {
        add_filter('rewrite_rules_array', array($this, 'filter_rewrite_rules_array'), 20);
        add_filter('query_vars', array($this, 'filter_query_vars'));
        add_action('parse_request', array($this, 'download_action'));
    }

    /**
     * Adds file download rewrite rules used by Message Manager (filter: rewrite_rules_array)
     * @param $rules Rules to filter
     * @return array Filtered rules
     */
    public function filter_rewrite_rules_array($rules)
    {
        $base = Message_Manager::get_instance()->get_base_slug();

        $download_rules = array(
            $base . '/download/([^/]+)/?$' => 'index.php?' . MM_DOWNLOAD_QUERY_VAR . '=$matches[1]',
        );

        return $download_rules + $rules;
    }

    /**
     * Add the download query variable (filter: query_vars)
     * @param $vars The unfiltered query vars
     * @return array The filtered query vars
     */
    public function filter_query_vars($vars)
    {
        $vars[] = MM_DOWNLOAD_QUERY_VAR;
        return $vars;
    }

    /**
     * @param $request The request object (action: parse_request)
     * @return mixed The modified request
     */
    function download_action($request)
    {
        if (!empty($request->query_vars[MM_DOWNLOAD_QUERY_VAR])) {
            $download = $this->base64url_decode($request->query_vars[MM_DOWNLOAD_QUERY_VAR]);

            if (is_numeric($download)) {
                $attachment_id = $download;
            } else {
                $attachment_id = $this->get_attachment_id_from_src($download);
            }

            if (empty($attachment_id)) {
                wp_redirect($download, 302);
                exit;
            }

            update_post_meta($attachment_id, MM_META_PREFIX . 'downloads', get_post_meta($attachment_id, MM_META_PREFIX . 'downloads', true) + 1);

            $file = get_attached_file($attachment_id, true);

            if (empty($file) || !file_exists($file)) {
                wp_redirect($download, 302);
                exit;
            }

            if (headers_sent()) die('Headers Sent');
            if (ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            if (@readfile($file) === false) {
                header_remove();
                die(__("An error occurred while downloading file.", 'message-manager'));
            }
            exit;
        }
        return $request;
    }

    /**
     * Returns a download url for an attachment id or any url
     * @param $attachment The attachment id or any url
     * @return string The download url
     */
    public function get_download_url($attachment)
    {
        if (empty($attachment)) return;
        if (!get_option('permalink_structure')) {
            return get_site_url() . '?' . MM_DOWNLOAD_QUERY_VAR . '=' . $this->base64url_encode($attachment);
        } else {
            return get_site_url() . '/' . trailingslashit(Message_Manager::get_instance()->get_base_slug()) . 'download/' . $this->base64url_encode($attachment);
        }
    }

    /**
     * Returns the attachment id from the guid
     * @param $attachment_src The attachment url
     * @return mixed The id or empty
     */
    private function get_attachment_id_from_src($attachment_src)
    {
        global $wpdb;
        $query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$attachment_src'";
        $id = $wpdb->get_var($query);
        return $id;
    }

    /**
     * URL safe base64 encode
     * @param $data The data to encode
     * @return string The encoded data
     */
    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * URL safe base64 decode
     * @param $data The encoded data
     * @return string The decoded data
     */
    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Returns the path to the local file if it exists
     * @param $attachment
     * @return boolean|string
     */
    public function get_local_file($attachment)
    {
        if (is_numeric($attachment)) {
            $id = $attachment;
        } else {
            $id = $this->get_attachment_id_from_src($attachment);
        }

        if (!empty($id)) {
            $uri = get_attached_file($id);
            if (file_exists($uri)) {
                return $uri;
            }
        }
        return false;
    }

    /**
     * Returns the file size of a url (cached)
     * @param $attachment The attachment url or id
     * @return int|mixed|string The file size of the url
     */
    public function get_file_size($attachment)
    {
        $transient_id = MM_META_PREFIX . 'filesize_' . sha1($attachment);

        $filesize = get_transient($transient_id);
        if ($filesize) return $filesize;

        $filesize = 0;

        $file = $this->get_local_file($attachment);
        if ($file) {
            $filesize = filesize($file);
        }

        // try curl
        if ($filesize == 0) {
            $uh = curl_init();
            curl_setopt($uh, CURLOPT_URL, $attachment);
            curl_setopt($uh, CURLOPT_NOBODY, 1);
            curl_setopt($uh, CURLOPT_HEADER, 0);
            curl_exec($uh);
            $filesize = curl_getinfo($uh, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($uh);
        }

        if (!is_numeric($filesize)) {
            $filesize = 0;
        } else {
            set_transient($transient_id, $filesize, 60 * 60 * 24 * 7);
        }

        return $filesize;
    }


    public function get_id3_info($attachment)
    {
        $transient_id = 'id3_' . sha1($attachment);

        // valid cache
        $info = get_transient($transient_id);
        if ($info) return $info;

        // no cache
        require_once MM_VENDOR_PATH . 'getid3/getid3.php';
        $getID3 = new getID3;

        $file = $this->get_local_file($attachment);
        if ($file) {
            $info = $getID3->analyze($file);
        }

        if (empty($info)) {
            $dir = get_temp_dir();
            $filename = basename($attachment);
            $filename = time($filename);
            $filename = preg_replace('|\..*$|', '.tmp', $filename);
            $filename = $dir . wp_unique_filename($dir, $filename);
            touch($filename);

            if (file_put_contents($filename, file_get_contents($attachment, false, null, 0))) {
                $info = $getID3->analyze($filename);
                unlink($filename);
            }
        }

        $new_info = array();
        if (!empty($info['playtime_seconds'])) {
            $new_info['playtime_seconds'] = $info['playtime_seconds'];
        }
        if (!empty($info['filesize'])) {
            $new_info['filesize'] = $info['filesize'];
        }

        if (!empty($info['mime_type'])) {
            $new_info['mime_type'] = $info['mime_type'];
        }

        set_transient($transient_id, $new_info);
        return $new_info;
    }
}

// create an instance of the message_manager_downloads
$message_manager_downloads = Message_Manager_Downloads::get_instance();