---
title: "advanced_search"
metaTitle: "advanced_search"
metaDescription: "API documentation"
---

Advanced search is the API method to access the search rules used in the WEB UI.

It can be confusing to process how the rules are generated so this has been split into it's own page.

### Search Types

You can search for multiple object types in advanced_search.

This is passed as a type argument and will only return this object in results

* song
* album
* artist
* video
* playlist
* label
* user
* tag
* genre (*Alias of tag)

## Using advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.

You can pass multiple rules as well as joins to create in depth search results

Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.

Use operator ('and', 'or') to choose whether to join or separate each rule when searching.

* Rule arrays must contain the following:
  * rule name (e.g. rule_1['title'], rule_2['album'])
  * rule operator (e.g. rule_1_operator[0], rule_2_operator[3])
  * rule input (e.g. rule_1_input['Prodigy'], rule_2_input['Land'])

### Available search rules

Select the type of search based on the type of data you are searching for. (songs, playlists, etc)

| rule_1             | Title                   | Operator Type     |           Valid Types            |
|--------------------|-------------------------|-------------------|:--------------------------------:|
| anywhere           | Any searchable text     | text              |               song               |
| title              | Title / Name            | text              | song,album,artist,playlist,label |
| name               | (*Alias of title)       |                   |                                  |
| album              | Album Title             | text              |            song,artist           |
| artist             | Artist                  | text              |         song,album,artist        |
| album_artist       | Album Artist            | text              |               song               |
| composer           | Composer                | text              |               song               |
| year               | Year                    | numeric           |            song,album            |
| original_year      | Original Year           | numeric           |              album               |
| yearformed         | Year Formed             | numeric           |              artist              |
| placeformed        | Place Formed            | text              |              artist              |
| release_type       | Release Type            | text              |              album               |
| release_status     | Release Status          | text              |              album               |
| myrating           | My Rating               | numeric           |        song,album,artist         |
| rating             | Rating (Average)        | numeric           |        song,album,artist         |
| albumrating        | My Rating (Album)       | numeric           |               song               |
| artistrating       | My Rating (Artist)      | numeric           |            song,album            |
| favorite           | Favorites               | text              |        song,album,artist         |
| favorite_album     | Favorites (Album)       | text              |               song               |
| favorite_artist    | Favorites (Artist)      | text              |               song               |
| played_times       | # Played                | numeric           |        song,album,artist         |
| skipped_times      | # Skipped               | numeric           |               song               |
| play_skip_ratio    | Played/Skipped ratio    | numeric           |               song               |
| last_play          | My Last Play            | days              |        song,album,artist         |
| played             | Played                  | boolean           |               song               |
| myplayed           | Played by Me            | boolean           |        song,album,artist         |
| myplayedalbum      | Played by Me (Album)    | boolean           |               song               |
| myplayedartist     | Played by Me (Artist)   | boolean           |               song               |
| time               | Length (in minutes)     | numeric           |        song,album,artist         |
| tag                | Genre                   | tags              |        song,album,artist         |
| genre              | (*Alias of tag)         |                   |                                  |
| album_tag          | Album Genre             | tags              |               song               |
| album_genre        | (*Alias of album_tag)   |                   |                                  |
| artist_tag         | Artist Genre            | tags              |               song               |
| artist_genre       | (*Alias of artist_tag)  |                   |                                  |
| no_tag             | No Genre                | is_true           |        song,album,artist         |
| no_genre           | (*Alias of no_tag)      |                   |                                  |
| other_user         | Another User            | user_numeric      |        song,album,artist         |
| other_user_album   | Another User (Album)    | user_numeric      |               song               |
| other_user_artist  | Another User (Artist)   | user_numeric      |               song               |
| label              | Label                   | text              |               song               |
| license            | Music License           | boolean_numeric   |               song               |
| playlist           | Playlist                | boolean_numeric   |            song,album            |
| smartplaylist      | Smart Playlist          | boolean_subsearch |               song               |
| playlist_name      | Playlist Name           | text              |            song,album            |
| comment            | Comment                 | text              |               song               |
| lyrics             | Lyrics                  | text              |               song               |
| file               | Filename                | text              |            song,video            |
| bitrate            | Bitrate                 | numeric           |               song               |
| added              | Added                   | date              |               song               |
| updated            | Updated                 | date              |               song               |
| recent_played      | Recently Played         | numeric_limit     |        song,album,artist         |
| recent_added       | Recently Added          | numeric_limit     |               song               |
| recent_updated     | Recently Updated        | numeric_limit     |               song               |
| catalog            | Catalog                 | boolean_numeric   |        song,album,artist         |
| mbid               | MusicBrainz ID          | text              |        song,album,artist         |
| mbid_album         | MusicBrainz ID (Album)  | text              |               song               |
| mbid_artist        | MusicBrainz ID (Artist) | text              |               song               |
| metadata           | Metadata                | metadata (mixed)  |               song               |
| has_image          | Local Image             | boolean           |           album,artist           |
| image_height       | Image Height            | numeric           |           album,artist           |
| image_width        | Image Width             | numeric           |           album,artist           |
| possible_duplicate | Possible Duplicate      | is_true           |        song,album,artist         |
| username           | Username                | text              |               user               |
| category           | Category                | text              |              label               |

### Available operator values

Select your operator (integer only!) based on the type or your selected search

**NOTE** with the numeric_limit and is_true operators the operator is ignored, but still required

| rule_1_operator | Text / Tags / Metadata            | Numeric / user_numeric                       | Date   | Boolean, Numeric, Subsearch / Days |
|:---------------:|-----------------------------------|----------------------------------------------|--------|------------------------------------|
|        0        | contains                          | is greater than or equal to / has loved      | before | is true / before (x) days ago      |
|        1        | does not contain                  | is less than or equal to / has rated 5 stars | after  | is false / after (x) days ago      |
|        2        | starts with                       | equals / has rated 4 stars                   |        |                                    |
|        3        | ends with                         | does not equal / has rated 3 stars           |        |                                    |
|        4        | is                                | is greater than / has rated 2 stars          |        |                                    |
|        5        | is not                            | is less than / has rated 1 stars             |        |                                    |
|  6 (Text Only)  | sounds like                       |                                              |        |                                    |
|  7 (Text Only)  | does not sound like               |                                              |        |                                    |
|  8 (Text Only)  | matches regular expression        |                                              |        |                                    |
|  9 (Text Only)  | does not match regular expression |                                              |        |                                    |

Send the correct input based on the type of search.

| rule_1_input |
|--------------|
| text         |
| integer      |
| boolean      |

**NOTE** To search metadata you need to add a 4th rule "rule_*_subtype"

Operators for metadata are using the text/tag types **AND** numeric types in a single list as they can be ints/strings/dates.

Currently there is not a simple way to identify what metadata types you have saved. New methods will be created for this.

### Metadata operator table

| rule_1_operator | Metadata                          |
|:---------------:|-----------------------------------|
|        0        | contains                          |
|        1        | does not contain                  |
|        2        | starts with                       |
|        3        | ends with                         |
|        4        | is                                |
|        5        | is not                            |
|  6 (Text Only)  | sounds like                       |
|  7 (Text Only)  | does not sound like               |
|  8 (Text Only)  | matches regular expression        |
|  9 (Text Only)  | does not match regular expression |
|       10        | is greater than or equal to       |
|       11        | is less than or equal to          |
|       12        | is                                |
|       13        | is not                            |
|       14        | is greater than                   |
|       15        | is less than                      |

To search a mixed type like metadata you must search using 4 rules.

* Search rule 1 for band containing 'Prodigy', Search Rule 2 for bbm > 120
  * rule name (e.g. rule_1['metadata'], rule_2['metadata'])
  * rule operator (e.g. rule_1_operator[0], rule_2_operator[12])
  * rule input (e.g. rule_1_input['Prodigy'], rule_2_input['120'])
  * rule subtype (e.g. rule_1_subtype['4'], rule_2_subtype['9'])

### advanced_search parameters

@param array $input

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| operator | string  | 'and','or' (whether to match one rule or all) |       NO |
| rule_*   | array   | [rule_1,rule_1_operator,rule_1_input],        |       NO |
| rule_*   | array   | [rule_2,rule_2_operator,rule_2_input], [etc]  |      YES |
| type     | string  | 'song', 'album', 'artist', 'playlist',        |       NO |
|          |         | 'label', 'user', 'video'                      |          |
| random   | boolean | 0, 1 (random order of results; default to 0)  |      YES |
| offset   | integer |                                               |      YES |
| limit'   | integer |                                               |      YES |

### Example URLs

Here are some example calls that might help you get an idea of the URL required to create an advanced search.

Artist `https://music.com.au/server/xml.server.php?action=advanced_search&auth=eeb9f1b6056246a7d563f479f518bb34&operator=or&type=artist&offset=0&limit=4&random=0&rule_1=favorite&rule_1_operator=0&rule_1_input=%25&rule_2=artist&rule_2_operator=2&rule_2_input=Car`

Album `https://music.com.au/server/xml.server.php?action=advanced_search&auth=eeb9f1b6056246a7d563f479f518bb34&operator=or&type=album&offset=0&limit=4&random=0&rule_1=favorite&rule_1_operator=0&rule_1_input=%25&rule_2=artist&rule_2_operator=0&rule_2_input=Men`

Song `https://music.com.au/server/xml.server.php?action=advanced_search&auth=eeb9f1b6056246a7d563f479f518bb34&operator=or&type=song&offset=0&limit=4&random=0&rule_1=favorite&rule_1_operator=0&rule_1_input=%25&rule_2=title&rule_2_operator=2&rule_2_input=Dance`

