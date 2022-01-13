#
# Build the image
#   docker build -t ottwatch-prod -f Dockerfile.prod .
#

. ~/src/infra/ottwatch.sh

sudo docker run \
	-e RAILS_ENV=production \
	-e DB_HOST=$DB_HOST \
	-e DB_NAME=$DB_NAME \
	-e DB_USER=$DB_USER \
	-e DB_PASS=$DB_PASS \
	-e RAILS_MASTER_KEY=$RAILS_MASTER_KEY \
	-p 20022:22 \
	-p 80:3000 \
	--name ottwatch-prod \
	-i -t \
	ottwatch-prod

# docker start -i ottwatch-prod
