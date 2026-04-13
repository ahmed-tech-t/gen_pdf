#!/bin/bash

php \
  -d post_max_size=1G \
  -d upload_max_filesize=1G \
  -d memory_limit=1G \
  -d max_execution_time=300 \
  -d max_input_time=300 \
  -S localhost:8000 \
  -t public \
  router.php
