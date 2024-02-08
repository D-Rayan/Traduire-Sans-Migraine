<?php

namespace TraduireSansMigraine;

use stdClass;

class Updater {
    public function __construct() {
        add_filter( 'plugins_api', [$this, "getInfoUpdate"], 20, 3);
        add_filter( 'site_transient_update_plugins', [$this, "update"] );
    }

    public function getInfoUpdate($res, $action, $args) {
        if( 'plugin_information' !== $action ) {
            return $res;
        }

        if( plugin_basename( __DIR__ ) !== $args->slug ) {
            return $res;
        }

        $remote = wp_remote_get(
            TSM__URL_DOMAIN . '/wp-content/uploads/products/traduire-sans-migraine/info.php',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json'
                )
            )
        );

        if(
            is_wp_error( $remote )
            || 200 !== wp_remote_retrieve_response_code( $remote )
            || empty( wp_remote_retrieve_body( $remote ) )
            ) {
            return $res;
        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );

        $res = new stdClass();
        $res->name = $remote->name;
        $res->slug = $remote->slug;
        $res->author = $remote->author;
        $res->author_profile = $remote->author_profile;
        $res->version = $remote->version;
        $res->tested = $remote->tested;
        $res->requires = $remote->requires;
        $res->requires_php = $remote->requires_php;
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->last_updated = $remote->last_updated;
        $res->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog
            // you can add your custom sections (tabs) here
        );
        // in case you want the screenshots tab, use the following HTML format for its content:
        // <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
        if( ! empty( $remote->sections->screenshots ) ) {
            $res->sections[ 'screenshots' ] = $remote->sections->screenshots;
        }

        $res->banners = array(
            'low' => $remote->banners->low,
            'high' => $remote->banners->high
        );

        return $res;
    }

    public function update($transient) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = wp_remote_get(
            TSM__URL_DOMAIN . '/wp-content/uploads/products/traduire-sans-migraine/info.php',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json'
                )
            )
        );

        if(
            is_wp_error( $remote )
            || 200 !== wp_remote_retrieve_response_code( $remote )
            || empty( wp_remote_retrieve_body( $remote ))
            ) {
            return $transient;
        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );

        if(
            $remote
            && version_compare( TSM__VERSION, $remote->version, '<' )
            && version_compare( TSM__WORDPRESS_REQUIREMENT, get_bloginfo( 'version' ), '<' )
            && version_compare( TSM__PHP_REQUIREMENT, PHP_VERSION, '<' )
        ) {

            $res = new stdClass();
            $res->slug = $remote->slug;
            $res->plugin = plugin_basename( __FILE__ );
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;
            $transient->response[ $res->plugin ] = $res;

            //$transient->checked[$res->plugin] = $remote->version;
        }

        return $transient;
    }
}