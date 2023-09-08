# Backup
docker exec ts-service-db-1 /usr/bin/mysqldump -u root --password=secret touchdownstars > backup.sql

# Restore
cat backup.sql | docker exec -i ts-service-db-1 /usr/bin/mysql -u root --password=secret touchdownstars