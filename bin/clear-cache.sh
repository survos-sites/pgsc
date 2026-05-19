ENV=${1:-dev} # dev, prod, etc.
# bin/console cache:clear
bin/console cache:pool:clear --all
redis-cli FLUSHALL
#  rm -Rf var/cache/$ENV && bin/console cache:clear --no-warmup --env=$ENV && bin/console cache:warmup --env=$ENV
