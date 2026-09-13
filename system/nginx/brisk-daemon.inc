# Directives common to every url served by the brisk-spush daemon.
# To be installed as /etc/nginx/brisk-daemon.inc and included inside each
# "location =" of the list (see brisk.conf).
#
# None of these lines is decorative: brisk is a comet application, and the
# response of index_rd.php is a stream that stays open for hours.

proxy_pass              http://brisk_daemon;

# 1.1 is needed both for the websocket upgrade and for chunked
proxy_http_version      1.1;

# without this nginx buffers the response and the comet stream never arrives
proxy_buffering         off;
# the daemon reads the POST body by itself, with no rebuilt Content-Length
proxy_request_buffering off;

# a read channel stays open as long as the player is connected: the default
# timeout (60s) would keep closing it
proxy_read_timeout      3600s;
proxy_send_timeout      3600s;

# the daemon already compresses by itself when the client says so
gzip                    off;

proxy_set_header        Host              $host;
# the daemon uses X-Real-Ip for the player address (see pproxy_realip)
proxy_set_header        X-Real-Ip         $remote_addr;
proxy_set_header        X-Forwarded-Proto $scheme;
proxy_set_header        Upgrade           $http_upgrade;
proxy_set_header        Connection        $connection_upgrade;
