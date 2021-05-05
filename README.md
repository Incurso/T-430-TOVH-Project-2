# T-430-TOVH-Project-1

# To start project run
composer install
ddev import-db --src=database/database.sql.gz
ddev start

# To export database run
ddev export-db > database/database.sql.gz