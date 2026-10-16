<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Part of the local_railvieweradmin plugin.
 *
 * @package    local_railvieweradmin
 * @copyright  dpr-rail
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_railvieweradmin;

class vimeo_api {
    private $access_token;
    private $client_id;
    
    public function __construct() {
        $this->access_token = get_config('local_railvieweradmin', 'vimeo_access_token');
        $this->client_id = get_config('local_railvieweradmin', 'vimeo_client_id');
    }
    
    /**
     * Holt alle Videos des Benutzers von Vimeo
     */
    public function get_user_videos($page = 1, $per_page = 25) {
        $url = "https://api.vimeo.com/me/videos?page={$page}&per_page={$per_page}";
        
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Accept: application/vnd.vimeo.*+json;version=3.4'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Sucht nach Videos mit bestimmtem Begriff
     */
    public function search_videos($query, $page = 1, $per_page = 25) {
        $url = "https://api.vimeo.com/me/videos?query=" . urlencode($query) . "&page={$page}&per_page={$per_page}";
        
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Accept: application/vnd.vimeo.*+json;version=3.4'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }

        return false;
    }

    /**
     * Legt ein neues Video-Objekt bei Vimeo an und liefert nur den TUS-Upload-Link zurück (siehe
     * developer.vimeo.com/api/upload/videos) - die eigentlichen Datei-Bytes werden NICHT mehr von
     * PHP hochgeladen, sondern direkt vom Browser per TUS-PATCH an diesen Link geschickt (siehe
     * uploadVideo()/tusUpload() in section_form.php). Grund: ein serverseitiger Umweg über PHP
     * (Datei erst komplett per POST an unseren Server, dann per PATCH weiter an Vimeo) ist durch
     * upload_max_filesize/post_max_size, PHP-Ausführungszeit und -Speicher sowie fehlende
     * Wiederaufnehmbarkeit bei einem Verbindungsabbruch für große Dateien (mehrere GB) praktisch
     * nicht nutzbar. Der direkte Browser-Upload umgeht diese Limits komplett.
     *
     * @param string $title
     * @param string $description
     * @param int $filesize Dateigröße in Byte (vom Browser gemeldet, für "upload.size" benötigt)
     * @param string $privacy 'anybody' (öffentlich), 'unlisted' oder 'nobody' (privat)
     * @return array ['success' => bool, 'id' => string|null, 'upload_link' => string|null, 'message' => string]
     */
    public function create_upload($title, $description, $filesize, $privacy = 'anybody') {
        if (empty($this->access_token)) {
            return ['success' => false, 'message' => 'No access token configured'];
        }

        $createbody = json_encode([
            'name' => $title,
            'description' => $description,
            'privacy' => ['view' => $privacy],
            'upload' => ['approach' => 'tus', 'size' => (string) $filesize],
        ]);

        $ch = curl_init('https://api.vimeo.com/me/videos');
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $createbody,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->access_token,
                'Accept: application/vnd.vimeo.*+json;version=3.4',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
        ]);
        $createresponse = curl_exec($ch);
        $createcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($createcode !== 200 && $createcode !== 201) {
            return ['success' => false, 'message' => "Video creation failed (HTTP $createcode): " . $createresponse];
        }

        $createdata = json_decode($createresponse, true);
        $uploadlink = $createdata['upload']['upload_link'] ?? null;
        $uri = $createdata['uri'] ?? null;
        if (empty($uploadlink) || empty($uri)) {
            return ['success' => false, 'message' => 'Vimeo response missing upload_link/uri'];
        }

        $videoid = ltrim(strrchr($uri, '/'), '/');
        return ['success' => true, 'id' => $videoid, 'upload_link' => $uploadlink, 'name' => $title];
    }
}
