#!/bin/bash

. ~/src/infra/ottwatch.sh

sudo docker run \
  --rm \
	--network prodweb \
	-e RAILS_ENV=production \
	-e DB_HOST=$DB_HOST \
	-e DB_NAME=$DB_NAME \
	-e DB_NAME_V1=$DB_NAME_V1 \
	-e DB_USER=$DB_USER \
	-e DB_PASS=$DB_PASS \
	-e RAILS_MASTER_KEY=$RAILS_MASTER_KEY \
	-e TWITTER_POST_ACCESS_TOKEN=$TWITTER_POST_ACCESS_TOKEN \
	-e TWITTER_POST_CONSUMER_KEY=$TWITTER_POST_CONSUMER_KEY \
	-e TWITTER_POST_CONSUMER_SECRET=$TWITTER_POST_CONSUMER_SECRET \
	-e TWITTER_POST_TOKEN_SECRET=$TWITTER_POST_TOKEN_SECRET \
  -e REDIS_URL=$REDIS_URL \
  -e SENDGRID_API_KEY=$SENDGRID_PRODWEB_FULL \
  -i -t \
	ottwatch-prod $*

