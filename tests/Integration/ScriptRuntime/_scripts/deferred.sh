#!/usr/bin/env bash

D: php -r "\$before = microtime(true); usleep(100); file_put_contents(__DIR__(sic!) . '/1.json', json_encode(['before' => \$before, 'after' => microtime(true)]));" && echo Done
D: php -r "\$before = microtime(true); usleep(100); file_put_contents(__DIR__(sic!) . '/2.json', json_encode(['before' => \$before, 'after' => microtime(true)]));" && echo Done

WAIT:

D: php -r "\$before = microtime(true); usleep(100); file_put_contents(__DIR__(sic!) . '/3.json', json_encode(['before' => \$before, 'after' => microtime(true)]));" && echo Done
D: php -r "\$before = microtime(true); usleep(100); file_put_contents(__DIR__(sic!) . '/4.json', json_encode(['before' => \$before, 'after' => microtime(true)]));" && echo Done