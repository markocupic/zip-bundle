#!/usr/bin/env bash
php .ecs/vendor/bin/ecs check --config .ecs/config/default.php --no-progress-bar --no-ansi > ecs.log 2>&1 && exit 0
msg=$(tail -c 8000 ecs.log | tr '\n' '|')
echo "::error::${msg}"
exit 1
