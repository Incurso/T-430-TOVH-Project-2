# T-430-TOVH-Project-2
Music Search Module

# To start project run
```
composer install
ddev import-db --src=database/database.sql.gz
ddev start
```

# To export database run
ddev export-db > database/database.sql.gz

# To use the Module.
1. add the music_search module to web/modules/custom/ folder
2. run the command ddev drush en music_search
3. Create the content type Artist and the custom fields
   - field_desc "description"
     - type Text (formatted, long, with summary)
   - field_discogs_id "discogs id"
     - type Text (plain)
   - field_spotify_id "spotify id"
     - type Text (plain)
   - field_photos "photos"
     - Entity reference to media type image
   - field_website
     - Link
4. Create the content type Song and the custom fields
   - field_position "position"
     - type Text (plain)
   - field_discogs_id "discogs id"
     - type Text (plain)
   - field_spotify_id "spotify id"
     - type Text (plain)
   - field_duration "duration"
     - type Text (plain)
5. Create the content type Album and the custom fields
   - field_desc "description"
     - type Text (formatted, long, with summary)
   - field_discogs_id "discogs id"
     - type Text (plain)
   - field_spotify_id "spotify id"
     - type Text (plain)
   - field_cover "album cover"
     - Entity reference to media type image
   - field_songs "songs"
     - 	Entity reference to content type song

# To use the module
Module should appear in the main navigation as "Music Search"
1. Click on "Music Search"
2. Click on "Configuration"
   1. add the required key's and secrets from the spotify and discogs api's
   2. press save
3. Go back to music search
   1. Select either album or artist and make your search
   2. Select an item from the Spotify selection or the Discogs selection or both.
   3. Press the add button located at the bottom of the page
4. You will be redirected to a relevant page containing the id's of your selections and information loaded from them.
   1. Select the items you want to add
   2. press save
5. You will then be redirected back to the new artist or album.
