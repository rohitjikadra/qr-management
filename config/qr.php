<?php

return [

    /*
     * Salt used to hash scanner IP addresses. Raw IPs are never stored.
     */
    'scan_salt' => env('QR_SCAN_SALT', 'change-me-in-production'),

    /*
     * Path to the MaxMind GeoLite2 City database. Geo lookups are
     * skipped gracefully when the file does not exist.
     */
    'geoip_database' => env('GEOIP_DATABASE', storage_path('app/geoip/GeoLite2-City.mmdb')),

];
