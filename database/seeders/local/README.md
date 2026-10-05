# Local Halinh Travel snapshot

`halinh_travel.sql` contains personal and financial information, so Git ignores
every file in this directory except this guide.

To restore the approved snapshot on another machine:

1. Copy the supplied `halinh_travel.sql` here through an approved secure channel.
2. Configure the target database and run migrations only after explicit approval.
3. Run `php artisan db:seed` on an empty database. When this file exists,
   `DatabaseSeeder` imports the snapshot instead of the demo master data.

The import creates ten accounts with the password `password`, hashed by Laravel.
Change those passwords immediately outside local development. To keep the SQL
file elsewhere, set `HALINH_TRAVEL_IMPORT_SQL_PATH` to its absolute path.
