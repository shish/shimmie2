<?php

$stdIn = STDIN;
$stdOut = STDOUT;

fwrite($stdOut, "READY\n");

while (true) {
    $line = fgets($stdIn);

    if ($line === false || preg_match('/eventname:PROCESS_STATE_(EXITED|STOPPED|FATAL) /', $line)) {
        exec('kill -15 '.file_get_contents('/var/run/supervisord.pid'));
    }

    fwrite($stdOut, "RESULT 2\nOK");
    sleep(1);
    fwrite($stdOut, "READY\n");
}
