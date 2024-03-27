#!/bin/bash

docker compose up -d
docker compose exec php composer install