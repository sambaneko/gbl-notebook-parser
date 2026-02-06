# GBL Notebook Parser

This is a companion project to [GBL Notebook](https://github.com/sambaneko/gbl-notebook); it retrieves, parses and formats Pokemon Go game data files from the PokeMiners' repos.

The formatted files will be updated in the GBL Notebook repo, so you don't need to run this to keep up to date.  If you do want to run it, simply clone and run `parse.php`.  The resulting output files will be populated in the `game-data` directory, which should then be copied into the `game-data` directory of your GBL Notebook instance.

# Development Notes

Niantic doesn't provide a public API, so developing from their data files is, in part, an act of divination.  For data oddities that I needed to conditionally accomodate, but don't understand, I have commented them as "DKW" (don't know why).

# Regarding the Championship Series Cups

In 2025, a new Championship Series Cup debuted, with some pretty unique eligibility rules.  In 2026, the Cup returned with a very different set of rules.  I'd like to retain each year's configuration separately, rather than overwriting the same Cup each year (which seems to be how Niantic is choosing to handle it), so I'm defining custom, yearly Championship Cups templateId values.