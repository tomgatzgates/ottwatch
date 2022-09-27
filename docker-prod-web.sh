#!/bin/bash

. ~/src/infra/ottwatch.sh

sudo docker container rm ottwatch-web

sudo docker run \
  --restart always \
  -d \
  --network prodweb \
  -e RAILS_ENV=production \
  -e DB_HOST=$DB_HOST \
  -e DB_NAME_V1=$DB_NAME_V1 \
  -e DB_NAME=$DB_NAME \
  -e DB_USER=$DB_USER \
  -e DB_PASS=$DB_PASS \
  -e RAILS_MASTER_KEY=$RAILS_MASTER_KEY \
  -e REDIS_URL=$REDIS_URL \
  -e GCS_KEYFILE=/infra/gcs-prodweb-service-account.json \
  -e RAILS_SERVE_STATIC_FILES=1 \
  -e GOOGLE_MAPS_API_KEY=$GOOGLE_MAPS_API_KEY \
  -e SENDGRID_API_KEY=$SENDGRID_PRODWEB_FULL \
  -e TWITTER_OAUTH_CLIENT_ID=$TWITTER_OAUTH_CLIENT_ID \
  -e TWITTER_OAUTH_CLIENT_SECRET=$TWITTER_OAUTH_CLIENT_SECRET \
  -v $INFRA_FOLDER:/infra \
  -p 3000:3000 \
  --name ottwatch-web \
  ottwatch-prod bin/rails server

