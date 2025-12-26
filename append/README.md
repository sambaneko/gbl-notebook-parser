# Append Files

Append files can be used to manually add data when parsing the latest game master and language files.  This is useful if the repo files are missing data, such as new moves that have been announced for a season, but are not yet present in the game master.  Parse the files first, and then refer to their formatting to create your appends.

# pokemon.json

Use this to add new moves to Pokemon; for example, to add the fast move Rollout for Blastoise:

```
{
	"V0009_POKEMON_BLASTOISE_NORMAL": {
		"fastMoves": [
			"ROLLOUT_FAST"
		]
	}
}
```

Use the Pokemon's templateId as a key, containing the data that should be appended.  Seasonal new move additions can be found in Niantic's season introductory post.

# moves.json

Use this to add new moves, or update the stats on existing moves:

```
{
    "SHADOW_BALL": {
        "energyDelta": -50
    }
}
```

GBL Notebook is currently only concerned with the energyDelta stat of moves; changes to these values can usually be sourced from posts in /r/TheSilphRoad at the start of a season.

# languages/English.txt

If after parsing, you notice moves that are labeled by their key values (ex. "move_name_0471"), then you will want to add them to this file.  The format expects a pair of lines, the first identifying the key (RESOURCE ID) and the second with its value (TEXT):

```
RESOURCE ID: move_name_0471
TEXT: Upper Hand
```

You may also need to add instances for new GBL Cups that haven't been named in the language files.